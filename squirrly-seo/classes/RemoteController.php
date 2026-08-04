<?php
defined('ABSPATH') || die('Cheatin\' uh?');

/**
 * Class SQ_Classes_RemoteController
 *
 * Handles remote communication with the Squirrly Cloud Server, including API calls,
 * authentication, and link generation. Provides utility methods for sending HTTP requests
 * and managing API responses.
 */
class SQ_Classes_RemoteController
{

    public static $apimethod = 'get';

    /**
     * HTTP status code of the last sq_wpcall(): 0 = unreachable/network error, null = no call made,
     * otherwise the real response code. Used to tell a server outage (5xx/unreachable) apart from a
     * genuine auth failure so a maintenance window never disconnects a working site.
     *
     * @var int|null
     */
    public static $lastHttpCode = null;

    /**
     * Snapshot of the last sq_wpcall(): url, method, redacted request, http code, raw body and any
     * WP_Error message. Vague failures like 'no_data' only say "the Cloud didn't answer with data" -
     * this keeps what was actually sent and received so the real cause (WAF/Cloudflare HTML page,
     * empty body, 401/403, DNS/SSL failure, PHP notice glued in front of the JSON) is visible.
     * Secrets are removed by redactSecrets() before anything is stored here.
     *
     * @var array
     */
    public static $lastCall = array();

    /**
     * Cloud auth errors that mean "this site's identity changed" (it was cloned, staged or moved to
     * a new address, or the Cloud now requires a signed handshake this install can't satisfy). These
     * never self-heal by blindly retrying the same call, so they are handled apart from the generic
     * reconnect path - otherwise checkin() loops on every page load and users see only a vague
     * "can't load data" and blame the plugin instead of reconnecting.
     *
     * @var array
     */
    protected static $cloneErrors = array(
        'signature_required',
        'clone_detected',
        'url_change_pending',
        'site_key_already_set',
        'site_key_reused',
    );

    /**
     * Guard so the one-shot signed re-handshake attempted for `signature_required` can re-run
     * checkin() at most once and never recurse into an infinite loop.
     *
     * @var bool
     */
    protected static $healingSignature = false;

    /**
     * Call the Squirrly Cloud Server
     *
     * @param  string $module
     * @param  array  $args
     * @param  array  $options
     * @return string
     */
    public static function apiCall($module, $args = array(), $options = array())
    {
        $parameters = "";

        //Reset the last HTTP status; sq_wpcall() sets it when (and only when) a request is actually made.
        self::$lastHttpCode = null;
        self::$lastCall = array();

        //Don't make API calls without the token unless it's login or register
        if (!SQ_Classes_Helpers_Tools::getOption('sq_api')) {
            if (!in_array($module, array('api/user/login', 'api/user/register', 'api/user/checkin'))) {
                return '';
            }
        }

        //predefined options
        $options = array_merge(
            array(
                'method' => self::$apimethod,
                'sslverify' => SQ_CHECK_SSL,
                'timeout' => 20,
                'headers' => array(
                    'USER-TOKEN' => SQ_Classes_Helpers_Tools::getOption('sq_api'),
                    'URL-TOKEN' => (SQ_Classes_Helpers_Tools::getOption('sq_cloud_connect') ? SQ_Classes_Helpers_Tools::getOption('sq_cloud_token') : false),
                    'USER-URL' => apply_filters('sq_homeurl', get_bloginfo( 'url' )),
                    'LANG' => apply_filters('sq_language', get_bloginfo('language')),
                    'VERSQ' => (int)str_replace('.', '', SQ_VERSION)
                )
            ),
            $options
        );

        try {
            if ($options['method'] == 'get' && !empty($args)) {
                $parameters = "?" . http_build_query($args);
            }

            //call it with http to prevent curl issues with ssls
            $url = self::cleanUrl(_SQ_APIV2_URL_ . $module . $parameters);

            if ($options['method'] == 'post') {
                $options['body'] = $args;
            }

            self::attachSignedHeaders($module, $url, $options, $args);

            $response = self::sq_wpcall($url, $options);

            if (is_string($response) && strpos($response, 'clock_skew:') !== false) {
                if (preg_match('/"clock_skew:(\d+)"/', $response, $m)) {
                    SQ_Classes_Helpers_SiteAuth::setServerTime((int) $m[1]);
                    self::attachSignedHeaders($module, $url, $options, $args);
                    $response = self::sq_wpcall($url, $options);
                }
            }

            return $response;

        } catch (Exception $e) {
            return '';
        }

    }

	/**
	 * Attaches signed headers to the provided options for authenticated requests.
	 *
	 * This method generates signed headers using the site authentication class and appends
	 * them to the existing headers within the options array. It ensures secure communication
	 * with the provided module and URL by verifying the blog ID and site key before proceeding.
	 *
	 * @param string $module The module part of the request URL, used for generating the signed headers.
	 * @param string $url The full request URL, not directly used for signing but involved in the process context.
	 * @param array $options A reference to the options array where the signed headers will be appended.
	 *                       The array is expected to include keys such as 'method' (HTTP method) and optionally 'body'.
	 * @param array $args Additional arguments for the request, not directly used in this method but available as context.
	 *
	 * @return void This method does not return a value. It modifies the $options array directly if headers are successfully signed.
	 */
	private static function attachSignedHeaders($module, $url, &$options, $args)
    {
        if (!class_exists('SQ_Classes_Helpers_SiteAuth')) {
            return;
        }

        $blogId = (int) SQ_Classes_Helpers_Tools::getOption('sq_user_blog_id');
        if (!$blogId || SQ_Classes_Helpers_Tools::getOption('sq_legacy_auth')) {
            return;
        }

        if (SQ_Classes_Helpers_SiteAuth::getSiteKey() === '') {
            return;
        }

        $method = strtoupper($options['method']);
        $path   = '/' . ltrim((string) $module, '/');
        $body   = '';
        if ($method === 'POST' && isset($options['body'])) {
            $body = is_array($options['body']) ? http_build_query($options['body']) : (string) $options['body'];
        }

        $signed = SQ_Classes_Helpers_SiteAuth::buildSignedHeaders(
            $method,
            $path,
            $body,
            apply_filters('sq_homeurl', get_bloginfo('url')),
            $blogId,
            (string) SQ_Classes_Helpers_Tools::getOption('sq_api')
        );

        if (empty($signed)) {
            return;
        }

        $options['headers'] = array_merge($options['headers'], $signed);
    }

    /**
     * Clear the url before the call
     *
     * @param  string $url
     * @return string
     */
    private static function cleanUrl($url)
    {
        return str_replace(array(' '), array('+'), $url);
    }

    public static function generatePassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $password;
    }

    /**
     * Get My Squirrly Link
     *
     * @param  $path
     * @return string
     */
    public static function getMySquirrlyLink($path)
    {
        return apply_filters('sq_cloudmenu', _SQ_DASH_URL_, $path);
    }

    /**
     * Get API Link
     *
     * @param  string  $path
     * @param  integer $version
     * @return string
     */
    public static function getApiLink($path)
    {
        return _SQ_APIV2_URL_ . $path . '?token=' . SQ_Classes_Helpers_Tools::getOption('sq_api') . '&url_token='.(SQ_Classes_Helpers_Tools::getOption('sq_cloud_connect') ? SQ_Classes_Helpers_Tools::getOption('sq_cloud_token') : false).'&url=' . apply_filters('sq_homeurl', get_bloginfo('url'));
    }

    /**
     * Use the WP remote call
     *
     * @param  $url
     * @param  $options
     * @return array|bool|string|WP_Error
     */
    public static function sq_wpcall($url, $options)
    {
        $method = $options['method'];
        //not accepted as option
        unset($options['method']);

        //Keep a redacted snapshot of this call so a later 'no_data' can be explained (see $lastCall).
        self::$lastCall = array(
            'url'      => $url,
            'method'   => strtoupper($method),
            'request'  => self::redactSecrets($options),
            'code'     => null,
            'body'     => null,
            'wp_error' => '',
        );

        switch ($method) {
        case 'get':
            $response = wp_remote_get($url, $options);
            break;
        case 'post':
            $response = wp_remote_post($url, $options);
            break;
        default:
            $response = wp_remote_request($url, $options);
            break;
        }

        if (is_wp_error($response)) {
            SQ_Classes_Error::setError($response->get_error_message());
            self::$lastHttpCode = 0; //server unreachable (DNS/timeout/SSL/network)
            self::$lastCall['code'] = 0;
            self::$lastCall['wp_error'] = $response->get_error_code() . ': ' . $response->get_error_message();
            return false;
        }

        self::$lastHttpCode = (int) wp_remote_retrieve_response_code($response);
        self::$lastCall['code'] = self::$lastHttpCode;
        self::$lastCall['body'] = (string) wp_remote_retrieve_body($response);

        $response = self::cleanResponse(wp_remote_retrieve_body($response)); //clear and get the body

        SQ_Debug::dump('wp_remote_get', $method, $url, self::redactSecrets($options), $response); //output debug
        return $response;
    }

    /**
     * Redact secrets (site_key, signature) from anything that may be dumped or logged.
     */
    private static function redactSecrets($options)
    {
        if (isset($options['headers']) && is_array($options['headers'])) {
            if (isset($options['headers']['X-SQ-Sig'])) {
                $options['headers']['X-SQ-Sig'] = '***redacted***';
            }
            if (isset($options['headers']['X-SQ-User-Token'])) {
                $options['headers']['X-SQ-User-Token'] = '***redacted***';
            }
            if (isset($options['headers']['USER-TOKEN'])) {
                $options['headers']['USER-TOKEN'] = '***redacted***';
            }
            if (isset($options['headers']['URL-TOKEN']) && $options['headers']['URL-TOKEN']) {
                $options['headers']['URL-TOKEN'] = '***redacted***';
            }
        }
        if (isset($options['body']) && is_array($options['body'])) {
            if (isset($options['body']['site_key'])) {
                $options['body']['site_key'] = '***redacted***';
            }
            //api/user/login posts the account password - never let it reach a dump, log or screen.
            if (isset($options['body']['password'])) {
                $options['body']['password'] = '***redacted*** (len ' . strlen((string) $options['body']['password']) . ')';
            }
        }
        return $options;
    }

    /**
     * Human readable dump of the last API call (see self::$lastCall), safe to show to an admin or
     * write to the error log: secrets are already redacted and the body is truncated.
     *
     * @return string
     */
    public static function getLastCallDebug()
    {
        if (empty(self::$lastCall)) {
            return 'no request was made (the call was skipped before reaching the Cloud - usually a missing sq_api token)';
        }

        $call = self::$lastCall;

        $body = (string) $call['body'];
        if (strlen($body) > 1500) {
            $body = substr($body, 0, 1500) . ' ...[truncated]';
        }
        if (trim($body) === '') {
            $body = '(empty body)';
        }

        $sent = array();
        if (isset($call['request']['headers']) && is_array($call['request']['headers'])) {
            foreach ($call['request']['headers'] as $name => $value) {
                if ($value === false || $value === '') {
                    $value = '(empty)';
                }
                $sent[] = $name . '=' . (is_scalar($value) ? $value : wp_json_encode($value));
            }
        }

        $posted = array();
        if (isset($call['request']['body']) && is_array($call['request']['body'])) {
            foreach ($call['request']['body'] as $name => $value) {
                $posted[] = $name . '=' . (is_scalar($value) ? $value : wp_json_encode($value));
            }
        }

        $lines = array(
            'request : ' . $call['method'] . ' ' . $call['url'],
            'http    : ' . ($call['code'] === null ? '(no response received)' : $call['code']),
            'headers : ' . implode(', ', $sent),
        );

        if (!empty($posted)) {
            $lines[] = 'post    : ' . implode(', ', $posted);
        }
        if ($call['wp_error'] !== '') {
            $lines[] = 'wp_error: ' . $call['wp_error'];
        }

        $lines[] = 'response: ' . $body;
        $lines[] = 'ssl     : ' . (SQ_CHECK_SSL ? 'verify on' : 'verify off') . ' | php ' . PHP_VERSION . ' | plugin ' . SQ_VERSION;

        return implode("\n", $lines);
    }

    /**
     * Record why an API call failed so the reason survives past the request that produced it.
     *
     * The Cloud answers most auth problems with a bare code ('no_data', 'badlogin', ...) which tells
     * an admin nothing. This stores the full redacted call next to that code - kept for an hour in a
     * transient so it can be rendered on the login screen / Diagnostics panel after the page reloads,
     * and mirrored to the PHP error log when WP_DEBUG is on.
     *
     * @param  string $module Endpoint that failed, e.g. 'api/user/login'
     * @param  string $reason Error code returned to the caller, e.g. 'no_data'
     * @return string The debug text (also returned so it can be attached to the WP_Error)
     */
    public static function debugFailure($module, $reason)
    {
        $debug = 'Squirrly API [' . $module . '] failed with "' . $reason . '"' . "\n" . self::getLastCallDebug();

        set_transient('sq_last_api_debug', $debug, HOUR_IN_SECONDS);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Squirrly SEO: ' . str_replace("\n", ' | ', $debug));
        }

        return $debug;
    }

    /**
     * Get the Json from response if any
     *
     * @param  string $response
     * @return string
     */
    private static function cleanResponse($response)
    {
        return trim($response, '()');
    }

    /**********************
     * 
     * USER 
     ******************************/
    /**
     * @param  array $args
     * @return array|mixed|object|WP_Error
     */
    public static function connect($args = array())
    {
        self::$apimethod = 'post'; //call method

        SQ_Classes_Helpers_SiteAuth::ensureSiteKey();

        $args = array_merge($args, array(
            'site_key'  => SQ_Classes_Helpers_SiteAuth::getSiteKeyHex(),
            'site_uuid' => SQ_Classes_Helpers_SiteAuth::getSiteUuid(),
        ));

        $json = json_decode(self::apiCall('api/user/connect', $args));

        //Never disconnect because of a server outage: only the explicit auth errors below
        //(invalid_token/disconnected/banned) should clear stored credentials.
        if (self::$lastHttpCode === 0 || (int) self::$lastHttpCode >= 500) {
            return (new WP_Error('maintenance', 'server_unavailable'));
        }

        if (isset($json->error) && $json->error <> '') {

            if ($json->error == 'invalid_token') {
                SQ_Classes_Helpers_Tools::saveOptions('sq_api', false);
                SQ_Classes_Helpers_Tools::saveOptions('sq_cloud_token', false);
            }
            if ($json->error == 'disconnected') {
                SQ_Classes_Helpers_Tools::saveOptions('sq_api', false);
                SQ_Classes_Helpers_Tools::saveOptions('sq_cloud_token', false);
            }
            if ($json->error == 'banned') {
                SQ_Classes_Helpers_Tools::saveOptions('sq_api', false);
                SQ_Classes_Helpers_Tools::saveOptions('sq_cloud_token', false);
            }
            if ($json->error == 'site_key_already_set' || $json->error == 'site_key_reused') {
                SQ_Classes_Helpers_SiteAuth::clearSiteKey();
            }
            return (new WP_Error('api_error', $json->error, self::debugFailure('api/user/connect', $json->error)));
        }

        if (isset($json->data->user_blog_id)) {
            SQ_Classes_Helpers_Tools::saveOptions('sq_user_blog_id', (int) $json->data->user_blog_id);
            SQ_Classes_Helpers_Tools::saveOptions('sq_legacy_auth', 0);
        }

        //Refresh the checkin on login
        delete_transient('sq_checkin');

        return $json;
    }

    /**
     * Login user to API
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function login($args = array())
    {
        self::$apimethod = 'post'; //call method

        $json = json_decode(self::apiCall('api/user/login', $args));

        //Server outage: unreachable (0) or any 5xx. The body of a 5xx is an error page, not JSON, so
        //without this it falls through to 'no_data' and the user is told their login is wrong when
        //the Cloud is simply down or erroring.
        if (self::$lastHttpCode === 0 || (int) self::$lastHttpCode >= 500) {
            self::debugFailure('api/user/login', 'server_unavailable');
            return (new WP_Error('maintenance', 'server_unavailable', self::getLastCallDebug()));
        }

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error, self::debugFailure('api/user/login', $json->error)));
        } elseif (!isset($json->data)) {
            //The Cloud answered with something that isn't the expected {data:...} payload. Keep the
            //raw exchange so the real cause is visible instead of a bare "no_data".
            return (new WP_Error('api_error', 'no_data', self::debugFailure('api/user/login', 'no_data')));
        }

        //Refresh the checkin on login
        delete_transient('sq_checkin');
        delete_transient('sq_last_api_debug');

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    /**
     * Register user to API
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function register($args = array())
    {
        self::$apimethod = 'post'; //call method

        $json = json_decode(self::apiCall('api/user/register', $args));

        //See login(): never report a Cloud outage/500 as a problem with what the user typed.
        if (self::$lastHttpCode === 0 || (int) self::$lastHttpCode >= 500) {
            self::debugFailure('api/user/register', 'server_unavailable');
            return (new WP_Error('maintenance', 'server_unavailable', self::getLastCallDebug()));
        }

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error, self::debugFailure('api/user/register', $json->error)));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data', self::debugFailure('api/user/register', 'no_data')));
        }

        //Refresh the checkin on login
        delete_transient('sq_checkin');

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    /**
     * Get a new token for the current URL
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function getCloudToken($args = array())
    {
        self::$apimethod = 'get'; //call method

        if(function_exists('rest_get_url_prefix')) {
            $apiUrl = trim(rest_get_url_prefix(), '/');
        }elseif(function_exists('rest_url')) {
            $apiUrl = trim(parse_url(rest_url(), PHP_URL_PATH), '/');
        }

        $args = array_merge($args, array('wp-json' => $apiUrl));

        $json = json_decode(self::apiCall('api/user/token', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error, self::debugFailure('api/user/token', $json->error)));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data', self::debugFailure('api/user/token', 'no_data')));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    /**
     * User Checkin
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function checkin($args = array())
    {
        self::$apimethod = 'get'; //call method

	    if (get_transient('sq_checkin')) {
		    return get_transient('sq_checkin');
	    }

	    //While the Cloud is known to be down (maintenance/outage), back off instead of calling the API
	    //on every admin page load - and never attempt a reconnect during that window.
	    if (get_transient('sq_checkin_down')) {
		    return (new WP_Error('maintenance', 'server_unavailable'));
	    }

	    //This site was detected as a clone/move that can't be auto-verified. Back off (the same call
	    //would just fail again) and keep returning the reconnect state until the user reconnects or
	    //the short window elapses - without re-hammering the Cloud on every page load. Re-emit the
	    //notice here too so it shows consistently on every admin load, not only on the one load every
	    //couple of minutes where the full branch runs (otherwise it flashes once and vanishes).
	    if (get_transient('sq_checkin_clone')) {
		    self::setReconnectNotice();
		    return (new WP_Error('reconnect', 'signature_required'));
	    }

        $json = json_decode(self::apiCall('api/user/checkin', $args));

        //Server outage: unreachable (0) or any 5xx (includes 503 maintenance). Treat as a transient
        //failure - do NOT reconnect or clear credentials, otherwise a maintenance window would
        //disconnect working sites. Back off a couple of minutes so we don't flood the down server.
        if (self::$lastHttpCode === 0 || (int) self::$lastHttpCode >= 500) {
            set_transient('sq_checkin_down', 1, 2 * MINUTE_IN_SECONDS);
            SQ_Classes_Error::setError(esc_html__("Squirrly Cloud is temporarily unavailable. The connection will be restored automatically once it's back.", 'squirrly-seo'));
            SQ_Classes_Error::hookNotices();
            return (new WP_Error('maintenance', 'server_unavailable'));
        }

        if (isset($json->error) && $json->error <> '') {

            //prevent throttling on API
            if ($json->error == 'too_many_requests') {
                SQ_Classes_Error::setError(esc_html__("Too many API attempts, please slow down the request.", 'squirrly-seo'));
                SQ_Classes_Error::hookNotices();
                return (new WP_Error('api_error', $json->error));
            } elseif ($json->error == 'maintenance') {
                //Maintenance reported as a normal (200) JSON payload: back off the same way and never reconnect.
                set_transient('sq_checkin_down', 1, 2 * MINUTE_IN_SECONDS);
                SQ_Classes_Error::setError(esc_html__("Squirrly Cloud is down for a bit of maintenance right now. But we'll be back in a minute.", 'squirrly-seo'));
                SQ_Classes_Error::hookNotices();
                return (new WP_Error('maintenance', $json->error));
            }

            //Site cloned/staged/moved, or the Cloud now requires a signed handshake this install
            //can't satisfy. Retrying the same call just loops and surfaces the generic "can't load
            //data" error. Handle these explicitly: self-heal when possible, otherwise stop the loop
            //and force a reconnect with a clear, specific message.
            if (in_array($json->error, self::$cloneErrors, true)) {
                return self::handleSignatureState($json->error, $args);
            }

            SQ_Classes_RemoteController::connect(); //connect the website
            return (new WP_Error('api_error', $json->error));

        } elseif (!isset($json->data)) {

	        if (get_transient('sq_checkin_fallback')) {
		        return get_transient('sq_checkin_fallback');
	        }

	        return (new WP_Error('api_error', 'no_data'));
        }

        //Connect the website if not yet connected
        if (isset($json->data->connected) && !$json->data->connected) {
            SQ_Classes_Helpers_Tools::saveOptions('sq_cloud_token', false);
            SQ_Classes_Helpers_Tools::saveOptions('sq_cloud_connect', 0);

            //Make sure the website exists on the Cloud
            SQ_Classes_RemoteController::connect();

            if ($token = SQ_Classes_RemoteController::getCloudToken()) {
                if(isset($token->token) && $token->token <> '') {
                    SQ_Classes_Helpers_Tools::saveOptions('sq_cloud_token', $token->token);
                    SQ_Classes_Helpers_Tools::saveOptions('sq_cloud_connect', 1);
                }
            }
        } elseif (SQ_Classes_Helpers_SiteAuth::getSiteKey() === '' && SQ_Classes_Helpers_Tools::getOption('sq_api')) {
            //Existing connection upgrading to signed auth - try to seed site_key, but throttle so a
            //failing handshake cannot hammer connect() on every checkin call and possibly clear
            //sq_api as a side effect. We back off 10 minutes between attempts.
            if (!get_transient('sq_handshake_attempted')) {
                set_transient('sq_handshake_attempted', 1, 10 * MINUTE_IN_SECONDS);
                $apiBefore        = SQ_Classes_Helpers_Tools::getOption('sq_api');
                $cloudTokenBefore = SQ_Classes_Helpers_Tools::getOption('sq_cloud_token');

                SQ_Classes_RemoteController::connect();

                //If the handshake destroyed the legacy tokens but checkin had just confirmed the
                //connection a moment ago, the user is still legitimately connected via the legacy
                //path - restore the tokens so the install isn't bricked.
                if ($apiBefore && !SQ_Classes_Helpers_Tools::getOption('sq_api')) {
                    SQ_Classes_Helpers_Tools::saveOptions('sq_api', $apiBefore);
                }
                if ($cloudTokenBefore && !SQ_Classes_Helpers_Tools::getOption('sq_cloud_token')) {
                    SQ_Classes_Helpers_Tools::saveOptions('sq_cloud_token', $cloudTokenBefore);
                }
            }
        }

        //Save the connections into database
        if (isset($json->data->connection_gsc) && isset($json->data->connection_ga)) {
            $connect = SQ_Classes_Helpers_Tools::getOption('connect');
            $connect['google_analytics'] = $json->data->connection_ga;
            $connect['google_search_console'] = $json->data->connection_gsc;
            SQ_Classes_Helpers_Tools::saveOptions('connect', $connect);
        }

        if(isset($json->data->subscription_devkit)) {
            SQ_Classes_Helpers_Tools::saveOptions('sq_auto_devkit', (int)$json->data->subscription_devkit);
        }

        set_transient('sq_checkin', $json->data, 60);
        set_transient('sq_checkin_fallback', $json->data);
        return $json->data;
    }

    /**
     * Handle the clone/signature family of checkin errors (see self::$cloneErrors).
     *
     * - url_change_pending: the Cloud saw this site's address change and is verifying it in the
     *   background. Transient - back off briefly, keep the connection and ask for a refresh.
     * - signature_required: can be a benign legacy->signed upgrade on the SAME site that just hasn't
     *   seeded a site key yet. Try one guarded signed handshake; if the Cloud binds our key we're
     *   healed and the next checkin returns live data.
     * - everything else (or a signature_required that didn't heal): a genuine clone/move the Cloud
     *   can no longer verify. We can't auto-recover, so flag the connection as needing a manual
     *   reconnect and surface a clear, specific message instead of the generic failure.
     *
     * @param  string $error
     * @param  array  $args
     * @return mixed|WP_Error
     */
    protected static function handleSignatureState($error, $args = array())
    {
        if ($error == 'url_change_pending') {
            set_transient('sq_checkin_down', 1, 2 * MINUTE_IN_SECONDS);
            SQ_Classes_Error::setError(esc_html__("Squirrly Cloud noticed this site's address changed and is verifying it. This takes about a minute - please refresh the page shortly.", 'squirrly-seo'));
            SQ_Classes_Error::hookNotices();
            return (new WP_Error('maintenance', $error));
        }

        if ($error == 'signature_required' && !self::$healingSignature) {
            self::$healingSignature = true;

            //Make sure we have a key to sign with, then attempt the handshake once.
            SQ_Classes_Helpers_SiteAuth::ensureSiteKey();
            $connect = SQ_Classes_RemoteController::connect();

            if (!is_wp_error($connect)) {
                //Handshake bound our key: refresh and re-run checkin so the caller gets live data.
                delete_transient('sq_checkin');
                delete_transient('sq_checkin_down');
                $healed = self::checkin($args);
                self::$healingSignature = false;
                return $healed;
            }

            self::$healingSignature = false;
            //Handshake failed - fall through using the real reason (site_key_already_set/reused).
            $error = $connect->get_error_message();
        }

        //Genuine clone/move: another live site already owns this identity, or the key is bound
        //elsewhere. Stop the retry loop, mark the connection as needing a manual reconnect (keep the
        //account token so the user isn't forced into a full re-login) and tell them exactly why.
        SQ_Classes_Helpers_Tools::saveOptions('sq_cloud_connect', 0);
        SQ_Classes_Helpers_Tools::saveOptions('sq_cloud_token', false);
        set_transient('sq_checkin_clone', 1, 2 * MINUTE_IN_SECONDS);

        self::setReconnectNotice();

        return (new WP_Error('reconnect', $error));
    }

    /**
     * Queue the persistent "this site must reconnect" admin notice. Called on every admin load while
     * the clone/reconnect state is active (including the throttled cached path) so the message is
     * consistent instead of flashing for a few seconds once every couple of minutes.
     *
     * Rendered as the 'reconnect' notice type (red, click-to-dismiss, NOT auto-removed after 5s).
     * Skips the inline echo during AJAX so it never corrupts a JSON response such as sla_checkin -
     * the AJAX payload already carries the 'reconnect' error code for the JS to act on.
     *
     * @return void
     */
    protected static function setReconnectNotice()
    {
        //Link straight to the reconnect handler (sq_account_reconnect in SQ_Controllers_Account),
        //nonce-signed so check_admin_referer() in the ActionController dispatcher accepts it. A plain
        //link to the dashboard only reloads the page - it doesn't run the handshake.
        $reconnect_url = wp_nonce_url(
            admin_url('admin.php?page=sq_dashboard&action=sq_account_reconnect'),
            'sq_account_reconnect',
            'sq_nonce'
        );

        SQ_Classes_Error::setError(sprintf(
            esc_html__("It looks like this website was cloned, staged or moved to a new address, so Squirrly Cloud can no longer verify it. Please %sreconnect this site to Squirrly Cloud%s so your SEO data keeps working.", 'squirrly-seo'),
            '<a href="' . esc_url($reconnect_url) . '">',
            '</a>'
        ), 'reconnect');

        if (!SQ_Classes_Helpers_Tools::isAjax()) {
            SQ_Classes_Error::hookNotices();
        }
    }

    /**
     * Collect a read-only snapshot of the Cloud connection / signed-auth state for the Diagnostics
     * panel on the settings page. Nothing here writes options or triggers a reconnect. When
     * $runLive is true it also fires a raw checkin so the panel can show exactly what the Cloud
     * replies right now (e.g. signature_required / clone_detected / invalid_signature).
     *
     * @param  bool $runLive
     * @return array
     */
    public static function getConnectionDebug($runLive = false)
    {
        $mask = function ($v) {
            $v = (string) $v;
            if ($v === '') {
                return '(empty)';
            }
            $len = strlen($v);
            if ($len <= 8) {
                return str_repeat('*', $len) . ' (len ' . $len . ')';
            }
            return substr($v, 0, 4) . str_repeat('*', $len - 8) . substr($v, -4) . ' (len ' . $len . ')';
        };

        $siteKey = SQ_Classes_Helpers_SiteAuth::getSiteKey();
        $blogId  = (int) SQ_Classes_Helpers_Tools::getOption('sq_user_blog_id');
        $legacy  = (int) SQ_Classes_Helpers_Tools::getOption('sq_legacy_auth');
        $constant = (defined('SQUIRRLY_SITE_KEY') && SQUIRRLY_SITE_KEY);

        $user  = function_exists('wp_get_current_user') ? wp_get_current_user() : null;

        $debug = array(
            'plugin_version'           => SQ_VERSION,
            'current_user'             => ($user ? $user->user_login . ' [' . implode(',', (array) $user->roles) . ']' : '(unknown)'),
            'user_can_manage_settings' => (SQ_Classes_Helpers_Tools::userCan('sq_manage_settings') ? 'yes' : 'NO - reconnect/disconnect buttons will silently do nothing'),
            'current_url'              => (string) apply_filters('sq_homeurl', get_bloginfo('url')),
            'stored_site_origin'       => (SQ_Classes_Helpers_SiteAuth::getSiteOrigin() ?: '(none)'),
            'url_changed_since_connect'=> (SQ_Classes_Helpers_SiteAuth::getSiteOrigin() && SQ_Classes_Helpers_SiteAuth::getSiteOrigin() !== (string) apply_filters('sq_homeurl', get_bloginfo('url')) ? 'YES (clone/stage/move)' : 'no'),
            'sq_api_token'             => $mask(SQ_Classes_Helpers_Tools::getOption('sq_api')),
            'sq_user_blog_id'          => ($blogId ?: '(none)'),
            'sq_legacy_auth'           => $legacy,
            'sq_cloud_connect'         => (int) SQ_Classes_Helpers_Tools::getOption('sq_cloud_connect'),
            'sq_cloud_token'           => $mask(SQ_Classes_Helpers_Tools::getOption('sq_cloud_token')),
            'site_key_present'         => ($siteKey !== '' ? 'yes' : 'NO'),
            'site_key_source'          => ($constant ? 'wp-config constant SQUIRRLY_SITE_KEY (cannot be cleared on reconnect!)' : ($siteKey !== '' ? 'sq_site_key option' : 'none')),
            'site_uuid'                => (SQ_Classes_Helpers_SiteAuth::getSiteUuid() ?: '(none)'),
            'time_offset_sec'          => (int) SQ_Classes_Helpers_Tools::getOption('sq_time_offset'),
            'request_will_be_signed'   => (($siteKey !== '' && $blogId && !$legacy) ? 'yes (HMAC signed)' : 'NO (sent as legacy/unsigned)'),
            'transient_sq_checkin'     => (get_transient('sq_checkin') ? 'cached (connected)' : 'none'),
            'transient_checkin_down'   => (get_transient('sq_checkin_down') ? 'set (server outage back-off)' : 'none'),
            'transient_checkin_clone'  => (get_transient('sq_checkin_clone') ? 'set (clone back-off)' : 'none'),
            'transient_handshake'      => (get_transient('sq_handshake_attempted') ? 'set' : 'none'),
            'last_api_failure'         => (get_transient('sq_last_api_debug') ? 'recorded (see below)' : 'none in the last hour'),
        );

        if ($runLive) {
            self::$apimethod = 'get';
            $raw  = self::apiCall('api/user/checkin', array());
            $json = json_decode($raw);

            $debug['live_checkin_http']     = (self::$lastHttpCode === null ? '(no request made)' : self::$lastHttpCode);
            $debug['live_checkin_error']    = (isset($json->error) && $json->error <> '' ? $json->error : (isset($json->data) ? '(none - connected)' : '(no data)'));
            $debug['live_checkin_response'] = (is_string($raw) ? $raw : wp_json_encode($raw));
            $debug['live_checkin_call']     = self::getLastCallDebug();
        }

        if ($last = get_transient('sq_last_api_debug')) {
            $debug['last_api_failure_details'] = $last;
        }

        return $debug;
    }

    /********************************
     *
     * NOTIFICATIONS
     *********************/
    /**
     * Get the Notifications from API for the current blog
     *
     * @return array|WP_Error
     */
    public static function getNotifications()
    {
        self::$apimethod = 'get'; //call method

        $notifications = array();
        if ($json = json_decode(self::apiCall('api/audits/notifications', array()))) {

            if (isset($json->error) && $json->error <> '') {
                return (new WP_Error('api_error', $json->error));
            } elseif (!isset($json->data)) {
                return (new WP_Error('api_error', 'no_data'));
            }

            $notifications = $json->data;

        }

        return $notifications;
    }

    /**
     * Get the API stats for this blog
     *
     * @return array|WP_Error|false
     */
    public static function getStats()
    {
        self::$apimethod = 'get'; //call method

        if (get_transient('sq_stats')) {
            return get_transient('sq_stats');
        }

        $args = array();
        $json = json_decode(self::apiCall('api/user/stats', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            set_transient('sq_stats', $json->data, 60);
            return $json->data;
        }

        return false;

    }

    public static function saveFeedback($args = array())
    {
        self::$apimethod = 'post'; //call method

        $json = json_decode(self::apiCall('api/user/feedback', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }


    /********************************
     * LIVE ASSISTANT
     *********************/
    public static function getSLAKeywords($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/posts/keyword', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function getSLAPreview($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/research/ib/preview', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function getSLATasks($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/posts/seo/tasks', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function getSLABriefcase($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/briefcase/optimize/get', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function addSLABriefcase($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/briefcase/optimize/add', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function saveSLABriefcase($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/briefcase/optimize/save', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function deleteSLABriefcase($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/briefcase/optimize/delete', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function getCustomCall($url, $args = array())
    {
        self::$apimethod = 'get'; //call method

        return self::apiCall($url, $args);

    }

    /********************************
     * BRIEFCASE
     *********************/
    public static function getBriefcaseStats($args = array())
    {
        self::$apimethod = 'get'; //call method

        if (get_transient('sq_briefcase_stats')) {
            return get_transient('sq_briefcase_stats');
        }

        $json = json_decode(self::apiCall('api/briefcase/stats', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            set_transient('sq_briefcase_stats', $json->data, 60);
            return $json->data;
        }

        return false;
    }

    public static function getBriefcase($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/briefcase/get', $args, ['timeout' => 60]));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }


        if (!empty($json->data)) {
	        //prepare the pagination if there is a total number for array
	        if(isset($json->data->results)){
		        add_filter('sq_total_records', function () use ($json) {return $json->data->results;});
	        }

	        return $json->data;
        }

        return false;
    }

	public static function getBriefcaseLabels($args = array())
	{
		self::$apimethod = 'get'; //call method

		$json = json_decode(self::apiCall('api/briefcase/label/get', $args));

		if (isset($json->error) && $json->error <> '') {
			return (new WP_Error('api_error', $json->error));
		} elseif (!isset($json->data)) {
			return (new WP_Error('api_error', 'no_data'));
		}

		if (!empty($json->data)) {
			//prepare the pagination if there is a total number for array
			if (isset($json->message->total)) {
				add_filter('sq_total_records', function () use ($json) {return $json->message->total;});
			}

			return $json->data;
		}

		return false;
	}


    public static function importBriefcaseKeywords($args = array())
    {
        self::$apimethod = 'post'; //call method

        //clear the briefcase stats
        delete_transient('sq_briefcase_stats');

        $json = json_decode(self::apiCall('api/briefcase/import', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function addBriefcaseKeyword($args = array())
    {
        self::$apimethod = 'post'; //call method

        //clear the briefcase stats
        delete_transient('sq_briefcase_stats');

        $json = json_decode(self::apiCall('api/briefcase/add', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function removeBriefcaseKeyword($args = array())
    {
        self::$apimethod = 'post'; //call method

        if ($json = json_decode(self::apiCall('api/briefcase/hide', $args))) {
            return $json;
        }

        return false;
    }

	public static function removeBriefcaseKeywords($args = array())
	{
		self::$apimethod = 'post'; //call method

		if ($json = json_decode(self::apiCall('api/briefcase/hide/keywords', $args))) {
			return $json;
		}

		return false;
	}

    public static function saveBriefcaseKeywordLabel($args = array())
    {
        self::$apimethod = 'post'; //call method

        //clear the briefcase stats
        delete_transient('sq_briefcase_stats');

        $json = json_decode(self::apiCall('api/briefcase/label/keyword', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

	public static function saveBriefcaseKeywordsLabel($args = array())
	{
		self::$apimethod = 'post'; //call method

		//clear the briefcase stats
		delete_transient('sq_briefcase_stats');

		$json = json_decode(self::apiCall('api/briefcase/label/keywords', $args));

		if (isset($json->error) && $json->error <> '') {
			return (new WP_Error('api_error', $json->error));
		} elseif (!isset($json->data)) {
			return (new WP_Error('api_error', 'no_data'));
		}

		if (!empty($json->data)) {
			return $json->data;
		}

		return false;
	}

    public static function addBriefcaseLabel($args = array())
    {
        self::$apimethod = 'post'; //call method

        //clear the briefcase stats
        delete_transient('sq_briefcase_stats');

        $json = json_decode(self::apiCall('api/briefcase/label/add', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function saveBriefcaseLabel($args = array())
    {
        self::$apimethod = 'post'; //call method

        //clear the briefcase stats
        delete_transient('sq_briefcase_stats');

        $json = json_decode(self::apiCall('api/briefcase/label/save', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function removeBriefcaseLabel($args = array())
    {
        self::$apimethod = 'post'; //call method

        $json = json_decode(self::apiCall('api/briefcase/label/delete', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function saveBriefcaseMainKeyword($args = array())
    {
        self::$apimethod = 'post'; //call method

        $json = json_decode(self::apiCall('api/briefcase/main', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }


    /********************************
     * 
     * Keyword RESEARCH
     ****************/

    /**
     * Get KR Countries
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function getKrCountries($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/kr/countries', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

	/**
	 * Get KR Languages
	 *
	 * @param  array $args
	 * @return bool|WP_Error
	 */
    public static function getKrLanguages($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/kr/languages', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function getKROthers($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/kr/other', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    /**
     * Set Keyword Research
     *
     * @param  array $args
     * @return array|bool|mixed|object|WP_Error
     */
    public static function setKRSuggestion($args = array())
    {
        self::$apimethod = 'post'; //call method

        //clear the briefcase stats
        delete_transient('sq_stats');
        delete_transient('sq_briefcase_stats');

        $json = json_decode(self::apiCall('api/kr/suggestion', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function getKRSuggestion($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/kr/suggestion', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    /********************************
     * 
     * KEYWORD HISTORY & FOUND  
     ****************/

    /**
     * Get Keyword Research History
     *
     * @param  array $args
     * @return array|bool|mixed|object|WP_Error
     */
    public static function getKRHistory($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/kr/history', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {

	        if (isset($json->message->total)) {
		        add_filter('sq_total_records', function () use ($json) {return $json->message->total;});
	        }

            return $json->data;
        }

        return false;
    }

    /**
     * Get the Kr Found by API
     *
     * @param  array $args
     * @return array|false|WP_Error
     */
    public static function getKrFound($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/kr/found', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {

	        if (isset($json->message->total)) {
		        add_filter('sq_total_records', function () use ($json) {return $json->message->total;});
	        }

            return $json->data;
        }

        return false;
    }

    /**
     * 
     * Remove Keyword from Suggestions
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function removeKrFound($args = array())
    {
        self::$apimethod = 'post'; //call method

        $json = json_decode(self::apiCall('api/kr/found/delete', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }
    /********************
     * 
     * WP Posts 
     ***************************/
    /**
     * Save the post status on API
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function savePost($args = array())
    {
        self::$apimethod = 'post'; //call method

        //clear the briefcase stats
        delete_transient('sq_stats');

        //fire-and-forget: the response is not used by any caller (post save, trash, etc.)
        //so send it non-blocking to avoid delaying the post save while waiting for the API
        $json = json_decode(self::apiCall('api/posts/update', $args, ['timeout' => 5, 'blocking' => false]));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    /**
     * Get the post optimization
     *
     * @param  array $args
     * @return array|mixed|object
     */
    public static function getPostOptimization($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/posts/optimizations', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    /********************
     * 
     * RANKINGS 
     ***************************/

    /**
     * Add a keyword in Rank Checker
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function addSerpKeyword($args = array())
    {
        self::$apimethod = 'post'; //call method

        $json = json_decode(self::apiCall('api/briefcase/serp', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

	/**
	 * Add bulk keywords in Rank Checker
	 *
	 * @param  array $args
	 * @return bool|WP_Error
	 */
	public static function addSerpKeywords($args = array())
	{
		self::$apimethod = 'post'; //call method

		$json = json_decode(self::apiCall('api/briefcase/serp/keywords', $args));

		if (isset($json->error) && $json->error <> '') {
			return (new WP_Error('api_error', $json->error));
		} elseif (!isset($json->data)) {
			return (new WP_Error('api_error', 'no_data'));
		}

		if (!empty($json->data)) {
			return $json->data;
		}

		return false;
	}

    /**
     * Delete a keyword from Rank Checker
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function deleteSerpKeyword($args = array())
    {
        self::$apimethod = 'post'; //call method

        $json = json_decode(self::apiCall('api/briefcase/serp-delete', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    /**
     * Get the Ranks for this blog
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function getRanksStats($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/serp/stats', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    /**
     * Get the Ranks for this blog
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function getRanks($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/serp/get-ranks', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {

	        if (isset($json->message->total)) {
		        add_filter('sq_total_records', function () use ($json) {return $json->message->total;});
	        }

            return $json->data;
        }

        return false;
    }

    /**
     * Refresh the rank for a page/post
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function checkPostRank($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/serp/refresh', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    /********************
     * 
     * FOCUS PAGES 
     ***********************/

    /**
     * Get all focus pages and add them in the SQ_Models_Domain_FocusPage object
     * Add the audit data for each focus page
     *
     * @param  array $args
     * @return SQ_Models_Domain_FocusPage|WP_Error|false
     */
    public static function getFocusPages($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/posts/focus', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    /**
     * Get the focus page audit
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function getFocusAudits($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/audits/focus', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

	/**
	 * get the found innelinks for a specific Focus Page
	 * @param $args
	 *
	 * @return false|WP_Error
	 */
	public static function getFocusPageInnerlinks($args = array())
	{
		self::$apimethod = 'get'; //call method
		$json = json_decode(self::apiCall('api/posts/innelinks', $args));

		if (isset($json->error) && $json->error <> '') {
			return (new WP_Error('api_error', $json->error));
		} elseif (!isset($json->data)) {
			return (new WP_Error('api_error', 'no_data'));
		}

		if (!empty($json->data)) {
			return $json->data;
		}

		return false;
	}

	public static function setFocusPageInnerlink($args = array())
	{
		self::$apimethod = 'post'; //post call

		$json = json_decode(self::apiCall('api/posts/set-innelink', $args));

		if (isset($json->error) && $json->error <> '') {
			return (new WP_Error('api_error', $json->error));
		} elseif (!isset($json->data)) {
			return (new WP_Error('api_error', 'no_data'));
		}

		if (!empty($json->data)) {
			return $json->data;
		}

		return false;
	}

	public static function deleteFocusPageInnerlink($args = array())
	{
		self::$apimethod = 'post'; //post call

		$json = json_decode(self::apiCall('api/posts/delete-innelink', $args));

		if (isset($json->error) && $json->error <> '') {
			return (new WP_Error('api_error', $json->error));
		} elseif (!isset($json->data)) {
			return (new WP_Error('api_error', 'no_data'));
		}

		if (!empty($json->data)) {
			return $json->data;
		}

		return false;
	}

    /**
     * Add Focus Page
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function addFocusPage($args = array())
    {
        self::$apimethod = 'post'; //post call

        $json = json_decode(self::apiCall('api/posts/set-focus', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function updateFocusPage($args = array())
    {
        self::$apimethod = 'post'; //post call

        $json = json_decode(self::apiCall('api/posts/update-focus', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    /**
     * Delete the Focus Page
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function deleteFocusPage($args = array())
    {
        self::$apimethod = 'post'; //post call

        if (isset($args['user_post_id']) && $args['user_post_id'] > 0) {
            $json = json_decode(self::apiCall('api/posts/remove-focus/' . $args['user_post_id']));

            if (isset($json->error) && $json->error <> '') {
                return (new WP_Error('api_error', $json->error));
            } elseif (!isset($json->data)) {
                return (new WP_Error('api_error', 'no_data'));
            }

	        if (!empty($json->data)) {
		        return $json->data;
	        }
        }

        return false;
    }

    /**
     * Get all focus pages and add them in the SQ_Models_Domain_FocusPage object
     * Add the audit data for each focus page
     *
     * @param  array $args
     * @return SQ_Models_Domain_FocusPage|WP_Error|false
     */
    public static function getInspectURL($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/posts/crawl', $args, ['timeout' => 60]));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }
    /****************************************
     * 
     * CONNECTIONS 
     */
    /**
     * Disconnect Google Analytics account
     *
     * @return bool|WP_Error
     */
    public static function revokeGaConnection()
    {
        self::$apimethod = 'get'; //post call

        $json = json_decode(self::apiCall('api/ga/revoke'));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        //Refresh the checkin on login
        delete_transient('sq_checkin');

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    public static function getGAToken($args = array())
    {
        self::$apimethod = 'get'; //post call

        $json = json_decode(self::apiCall('api/ga/token', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    public static function getGAProperties($args = array())
    {
        self::$apimethod = 'get'; //post call

        $json = json_decode(self::apiCall('api/ga/properties', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    public static function saveGAProperties($args = array())
    {
        self::$apimethod = 'post'; //post call

        $json = json_decode(self::apiCall('api/ga/properties', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    /**
     * Disconnect Google Search Console account
     *
     * @return bool|WP_Error
     */
    public static function revokeGscConnection()
    {
        self::$apimethod = 'get'; //post call

        $json = json_decode(self::apiCall('api/gsc/revoke'));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        //Refresh the checkin on login
        delete_transient('sq_checkin');

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    public static function syncGSC($args = array())
    {
        self::$apimethod = 'get'; //post call

        $json = json_decode(self::apiCall('api/gsc/sync/kr', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    public static function getGSCToken($args = array())
    {
        self::$apimethod = 'get'; //post call

        $json = json_decode(self::apiCall('api/gsc/token', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    public static function sendGSCIndex($args = array())
    {
        self::$apimethod = 'post'; //post call

        //fire-and-forget: the response is not used by the caller (IndexNow auto-submit on save)
        //so send it non-blocking to avoid delaying the post save while waiting for the API
        $json = json_decode(self::apiCall('api/gsc/index', $args, ['blocking' => false]));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    /********************
     * 
     * AUDITS 
     *****************************/

    public static function getAuditPages($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/posts/audits', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    /**
     * Get the audit page
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function getAudit($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/audits/audit', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    /**
     * Get the AI Visibility data (traffic coming from AI engines: ChatGPT,
     * Perplexity, Gemini, Copilot, Bing, etc.) computed by the Cloud from the
     * site's connected Google Analytics (GA4) property.
     *
     * @param  array $args (e.g. array('days_back' => 30))
     * @return bool|WP_Error|object
     */
    public static function getAiVisibility($args = array())
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/audits/ai-visibility', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    /**
     * Add Audit Page
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function addAuditPage($args = array())
    {
        self::$apimethod = 'post'; //post call

        $json = json_decode(self::apiCall('api/posts/set-audit', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }

    public static function updateAudit($args = array())
    {
        self::$apimethod = 'post'; //post call

        $json = json_decode(self::apiCall('api/posts/update-audit', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;
    }

    /**
     * Delete the Audit Page
     *
     * @param  array $args
     * @return bool|WP_Error
     */
    public static function deleteAuditPage($args = array())
    {
        self::$apimethod = 'post'; //post call

        if (isset($args['user_post_id']) && $args['user_post_id'] > 0) {

            $json = json_decode(self::apiCall('api/posts/remove-audit/' . $args['user_post_id']));

            if (isset($json->error) && $json->error <> '') {
                return (new WP_Error('api_error', $json->error));
            } elseif (!isset($json->data)) {
                return (new WP_Error('api_error', 'no_data'));
            }

            if (!empty($json->data)) {
                return $json->data;
            }

        }
        return false;
    }

    /********************
     * 
     * OTHERS 
     *****************************/
    public static function saveSettings($args)
    {
        self::$apimethod = 'post'; //call method

        self::apiCall('api/user/settings', array('settings' => wp_json_encode($args)));
    }

    /**
     * Get the Facebook APi Code
     *
     * @param  $args
     * @return bool|WP_Error
     */
    public static function getFacebookApi($args)
    {
        self::$apimethod = 'get'; //call method

        $json = json_decode(self::apiCall('api/tools/facebook', $args));

        if (isset($json->error) && $json->error <> '') {
            return (new WP_Error('api_error', $json->error));
        } elseif (!isset($json->data)) {
            return (new WP_Error('api_error', 'no_data'));
        }

        if (!empty($json->data)) {
            return $json->data;
        }

        return false;

    }



}
