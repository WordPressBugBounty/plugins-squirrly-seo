<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Loads the bundled OAuth 2.1 layer so Squirrly can be added to claude.ai as a standard
 * connector, instead of only through a local MCP client holding an Application Password.
 *
 * claude.ai does not ask the user for a username and password when it adds a connector. It
 * asks the site who signs people in, by fetching /.well-known/oauth-protected-resource and
 * /.well-known/oauth-authorization-server. The MCP Adapter plugin serves neither and returns
 * a bare 401, so there is nothing to discover and the connector reports that authorization
 * failed. The bundled library publishes both documents, adds the authorize/token endpoints,
 * and recognises Claude as a known client.
 *
 * Everything here is inert unless the site opts in: the switch is off by default, and each
 * guard below fails closed.
 *
 * Class SQ_Classes_McpOauthController
 */
class SQ_Classes_McpOauthController {

	/** @var string minimum PHP version - the bundled library is PHP 7.4 source */
	const MIN_PHP = '7.4';

	/** @var string REST route of the MCP server the library registers */
	const SERVER_ROUTE = 'mcp/mcp-oauth-server';

	/** @var array the discovery documents claude.ai fetches, by label */
	public static $discovery = array(
		'protected-resource'   => '/.well-known/oauth-protected-resource',
		'authorization-server' => '/.well-known/oauth-authorization-server',
	);

	public function __construct() {

		//The library is PHP 7.4 source. Nothing below may be require()d on an older PHP, so
		//this is checked before any include, not inside one.
		if ( version_compare( PHP_VERSION, self::MIN_PHP, '<' ) ) {
			return;
		}

		//WordPress < 6.9 has no Abilities API, so there is nothing to expose over MCP
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		//Off unless the site turned it on in Settings > Connect Tools > AI Tools
		if ( ! SQ_Classes_Helpers_Tools::getOption( 'sq_mcp_oauth' ) ) {
			add_action( 'plugins_loaded', array( $this, 'cleanup' ), 20 );

			return;
		}

		add_action( 'plugins_loaded', array( $this, 'load' ), 5 );
	}

	/**
	 * Wire the OAuth layer, on plugins_loaded so every other plugin has been given the
	 * chance to declare its own copy of the library first.
	 */
	public function load() {

		//The MCP Adapter plugin provides the MCP protocol itself. Without it there is no
		//server to authenticate against, so the OAuth endpoints would lead nowhere.
		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			return;
		}

		//WP Rocket and any other consumer embed the same library. Bootstrap::instance() is a
		//singleton shared across consuming plugins, so when a copy is already present we use
		//it rather than registering a second autoloader for our own.
		if ( ! class_exists( '\WPMedia\MCP\OAuth\Bootstrap' ) ) {

			if ( ! is_readable( _SQ_MCP_DIR_ . 'autoload.php' ) ) {
				return;
			}

			require_once _SQ_MCP_DIR_ . 'typed-filters.php';
			require_once _SQ_MCP_DIR_ . 'autoload.php';
		}

		//An incomplete upload can leave Bootstrap.php in place while the sub-namespaces it
		//wires are missing - an FTP client that does not recurse, or a flattened archive,
		//produces exactly that. Booting it then throws on plugins_loaded and takes the whole
		//site down, front and admin, so one class from each sub-namespace is checked first.
		if ( ! class_exists( '\WPMedia\MCP\OAuth\Bootstrap' )
		     || ! class_exists( '\WPMedia\MCP\OAuth\Auth\AuthorizeEndpoint' )
		     || ! class_exists( '\WPMedia\MCP\OAuth\Auth\Discovery\Endpoints' )
		     || ! class_exists( '\WPMedia\MCP\OAuth\Transport\Server' ) ) {
			return;
		}

		add_filter( 'wpmedia_mcp_oauth_trusted_publishers', array( $this, 'trustedPublishers' ) );

		//Belt and braces: whatever the library does, it must never be able to break the site
		try {
			\WPMedia\MCP\OAuth\Bootstrap::instance();
		} catch ( Throwable $e ) {
			return;
		}
	}

	/**
	 * Which AI clients may sign in to this site.
	 *
	 * The bundled library ships with claude.ai alone, so every other assistant is turned away
	 * at the authorize screen with "Unknown OAuth client" - the host is rejected before its
	 * metadata document is even fetched. Squirrly is built to be reached by more than one
	 * assistant, so the clients we have verified are added here.
	 *
	 * A publisher is only ever an addition. The library still requires an exact client_id
	 * match, a matching host, and a public client (token_endpoint_auth_method "none"), and the
	 * user still has to approve the connection on their own login screen.
	 *
	 * @param array $publishers Publishers the library already trusts.
	 *
	 * @return array
	 */
	public function trustedPublishers( $publishers ) {

		$publishers = (array) $publishers;

		//OpenAI Codex. Verified against https://chatgpt.com/oauth/codex/client.json - a native
		//public client with loopback redirect URIs, the same shape as Claude Code's.
		$publishers['openai'] = array(
			'client_ids' => array(
				'https://chatgpt.com/oauth/codex/client.json',
			),
			'host'       => 'chatgpt.com',
		);

		/**
		 * Filters the AI clients allowed to sign in to this site over MCP.
		 *
		 * Adding a publisher lets that client reach the approval screen; it does not grant it
		 * anything. Only add clients whose metadata document you have checked.
		 *
		 * @param array $publishers Keyed by slug, each with client_ids (exact URLs) and host.
		 */
		return (array) apply_filters( 'sq_mcp_trusted_publishers', $publishers );
	}

	/**
	 * Drop the OAuth rewrite rules once, after the switch is turned off.
	 *
	 * The library adds its rules on init and flushes them itself the first time it runs.
	 * Once it stops loading, nothing removes them again, so the /oauth/ and /.well-known/
	 * paths would stay in the stored rewrite rules for as long as the site lives.
	 */
	public function cleanup() {

		//Another plugin embeds the same library and still wants these rules
		if ( class_exists( '\WPMedia\MCP\OAuth\Bootstrap' ) ) {
			return;
		}

		//The library's own marker. Absent means there is nothing left to clean up.
		if ( get_option( 'wpmedia_mcp_oauth_rewrite_version' ) === false ) {
			return;
		}

		delete_option( 'wpmedia_mcp_oauth_rewrite_version' );

		add_action( 'init', 'flush_rewrite_rules', 99 );
	}

	/**
	 * Whether the switch is on and everything it depends on is present.
	 *
	 * @return bool
	 */
	public static function isActive() {
		return (
			version_compare( PHP_VERSION, self::MIN_PHP, '>=' )
			&& function_exists( 'wp_register_ability' )
			&& class_exists( '\WP\MCP\Core\McpAdapter' )
			&& SQ_Classes_Helpers_Tools::getOption( 'sq_mcp_oauth' )
		);
	}

	/**
	 * The address the site owner pastes into claude.ai.
	 *
	 * @return string
	 */
	public static function getServerUrl() {
		return get_rest_url( null, self::SERVER_ROUTE );
	}

	/**
	 * Why the switch cannot be used on this site, if it cannot.
	 *
	 * @return string empty when there is nothing blocking it
	 */
	public static function getBlocker() {

		if ( version_compare( PHP_VERSION, self::MIN_PHP, '<' ) ) {
			return sprintf(
			/* translators: %1$s: required PHP version, %2$s: PHP version the site runs */
				esc_html__( "This needs PHP %1\$s or newer. Your site runs PHP %2\$s.", 'squirrly-seo' ),
				self::MIN_PHP,
				PHP_VERSION
			);
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			return esc_html__( "This needs WordPress 6.9 or newer, where the Abilities API is part of WordPress itself.", 'squirrly-seo' );
		}

		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			return esc_html__( "Install the free MCP Adapter plugin, from Plugins - Add New, and activate it. Squirrly registers the SEO tools; the MCP Adapter is what carries them to an AI assistant.", 'squirrly-seo' );
		}

		if ( strpos( home_url(), 'https://' ) !== 0 ) {
			return esc_html__( "Your site address does not start with https://. An AI assistant will not connect to a site that is not served over HTTPS, so install a certificate and set the address under Settings - General first.", 'squirrly-seo' );
		}

		if ( ! get_option( 'permalink_structure' ) ) {
			return esc_html__( "Your permalinks are set to Plain. Go to Settings - Permalinks, choose any other option, and save. The sign-in addresses are ordinary WordPress URLs and Plain permalinks cannot serve them, so nothing below will work until this is changed.", 'squirrly-seo' );
		}

		return '';
	}

	/**
	 * Check that the two documents claude.ai fetches actually reach WordPress.
	 *
	 * A web server can answer them before WordPress ever runs - the usual causes are an
	 * nginx rule denying dot-folders, or a certificate rule capturing the whole of
	 * /.well-known/. Both leave the MCP address itself working, so the site looks healthy
	 * and the only symptom is claude.ai reporting that authorization failed.
	 *
	 * @return array label => array( code, ok, message )
	 */
	public static function checkDiscovery() {
		$results = array();

		foreach ( self::$discovery as $label => $path ) {

			//sslverify stays on: Claude will not connect to a site whose certificate does not
			//validate, so a certificate this site cannot verify itself is a real failure worth
			//reporting, not noise to suppress.
			$response = wp_remote_get( home_url( $path ), array(
				'timeout'     => 10,
				'redirection' => 2,
			) );

			if ( is_wp_error( $response ) ) {
				//same shape as explainCode(), so the view never has to tell them apart
				$results[ $label ] = array(
					'code'    => 0,
					'ok'      => false,
					'message' => array(
						'cause'    => sprintf(
						/* translators: %s: the error the site's own HTTP request returned */
							__( "This site could not reach its own address: %s", 'squirrly-seo' ),
							$response->get_error_message()
						),
						'footnote' => __( "A firewall or a DNS setting is stopping the site from calling itself. An AI assistant reaching the same address from outside may still fail for a different reason, so fix this first and check again.", 'squirrly-seo' ),
					),
				);
				continue;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			$results[ $label ] = array(
				'code'    => $code,
				'ok'      => ( 200 === $code && is_array( $body ) && ! empty( $body ) ),
				'message' => self::explainCode( $code ),
			);
		}

		return $results;
	}

	/**
	 * Turn the status code of a discovery document into something a site owner can act on.
	 *
	 * A single sentence of nginx configuration is unreadable in a settings screen, so this
	 * returns the parts separately: what is wrong, the rule to look for, and the rule to use
	 * instead. The view renders the two rules as code blocks.
	 *
	 * @param int $code HTTP status code.
	 *
	 * @return array Empty when there is nothing wrong.
	 */
	protected static function explainCode( $code ) {

		switch ( (int) $code ) {

			case 200:
				return array();

			case 403:
				return array(
					'cause'      => __( "Your web server is refusing every address that begins with a dot, before WordPress ever sees it.", 'squirrly-seo' ),
					'find_label' => __( "Look for a rule like this in your site's nginx configuration:", 'squirrly-seo' ),
					'find_code'  => 'location ~ /\. { deny all; }',
					'fix_label'  => __( "Add this above that rule, so only the sign-in addresses are let through:", 'squirrly-seo' ),
					'fix_code'   => "location ^~ /.well-known/ {\n    try_files \$uri \$uri/ /index.php?\$args;\n}",
					'footnote'   => __( "On Apache the equivalent is a rule in .htaccess that blocks dot-folders. Your host can make the same exception.", 'squirrly-seo' ),
				);

			case 404:
				return array(
					'cause'      => __( "Something on your server answers this address before WordPress does, so WordPress never gets the chance to reply.", 'squirrly-seo' ),
					'find_label' => __( "On nginx this is usually a certificate rule written too broadly:", 'squirrly-seo' ),
					'find_code'  => 'location ^~ /.well-known/ { root /var/www/letsencrypt; }',
					'fix_label'  => __( "Narrow it to the certificate path only, so every other address still reaches WordPress:", 'squirrly-seo' ),
					'fix_code'   => 'location ^~ /.well-known/acme-challenge/ { root /var/www/letsencrypt; }',
					'footnote'   => __( "If your permalinks are set to Plain, change that first: these are ordinary WordPress addresses and Plain permalinks cannot serve them.", 'squirrly-seo' ),
				);

			default:
				return array(
					'cause'    => sprintf(
					/* translators: %d: HTTP status code */
						__( "This address answered with status %d instead of publishing the sign-in details.", 'squirrly-seo' ),
						(int) $code
					),
					'footnote' => __( "Open the address in a browser to see what is answering it. Anything other than a short block of JSON means the request is not reaching WordPress.", 'squirrly-seo' ),
				);
		}
	}
}
