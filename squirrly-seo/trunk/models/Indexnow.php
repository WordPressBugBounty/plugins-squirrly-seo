<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Auto-Indexing class.
 *
 */
class SQ_Models_Indexnow {

	/**
	 * IndexNow key.
	 *
	 * @var string
	 */
	protected $_apiKey = '';

	protected $_success;

	public function submitUrl( $urls, $manual = 0 ) {

		$data = $this->getLinks( $urls );

		//Send the ULRs to Google API Indexing
		//Requires GSC Connection
		$args['urls'] = $urls;
		SQ_Classes_RemoteController::sendGSCIndex( $args );

		//Default to the api.indexnow.org hub only - it forwards to every engine, so one request avoids the extra rate-limiting of pinging each engine directly.
		$this->_apiUrls = SQ_Classes_Helpers_Tools::getOption( 'indexnow_endpoints' );

		if ( empty( $this->_apiUrls ) ) {
			$this->_apiUrls = array( 'https://api.indexnow.org/indexnow' );
		}

		//Fix the legacy bare hub URL saved by older versions (it was missing the /indexnow path).
		$this->_apiUrls = array_map( array( $this, 'normalizeEndpoint' ), (array) $this->_apiUrls );

		$headers = array(
			'Content-Type'  => 'application/json',
			'User-Agent'    => 'Squirrly/' . md5( esc_url( home_url( '/' ) ) ),
			'X-Source-Info' => 'https://squirrly.co/' . SQ_VERSION . '/' . $manual,
		);

		//Blocking so the real status is logged; auto submissions run on 'shutdown' so the save isn't delayed. api.indexnow.org forwards to every engine, so success = ANY endpoint accepting it.
		$success_code = 0;
		$error_code   = 0;
		$error_msg    = '';

		foreach ( $this->_apiUrls as $apiurl ) {
			$response = wp_remote_post( $apiurl, array(
				'blocking' => true,
				'timeout'  => 5,
				'body'     => $data,
				'headers'  => $headers,
			) );

			if ( is_wp_error( $response ) ) {
				$error_code = 0;
				$error_msg  = 'Error: ' . $response->get_error_message();
				continue;
			}

			$http_code = (int) wp_remote_retrieve_response_code( $response );

			if ( in_array( $http_code, array( 200, 202, 204 ), true ) ) {
				$success_code = $http_code;
			} else {
				$error_code = $http_code;
				//Prefer our actionable guidance for auth/not-found errors.
				if ( in_array( $http_code, array( 401, 403, 404 ), true ) ) {
					$error_msg = $this->getErrorMessage( $http_code );
				} elseif ( ! $error_msg = wp_remote_retrieve_response_message( $response ) ) {
					$error_msg = $this->getErrorMessage( $http_code );
				}
			}
		}

		if ( $success_code ) {
			$this->_success = true;
			$this->addLog( (array) $urls, $success_code, $manual, 'Success', $this->_apiUrls );

			return true;
		}

		$this->addLog( (array) $urls, $error_code, $manual, $error_msg ? $error_msg : $this->getErrorMessage( $error_code ), $this->_apiUrls );

		return false;
	}

	/**
	 * Check whether the IndexNow key file is publicly reachable and returns the key.
	 * This is the usual cause of 401/403 errors: search engines must read
	 * https://site.com/{key}.txt to verify the submission. Result is cached.
	 *
	 * @param bool $force Re-check ignoring the cache.
	 *
	 * @return array { ok, code, url, body_ok, message }
	 */
	public function verifyKeyFile( $force = false ) {
		$cache_key = 'sq_indexnow_keycheck';

		if ( ! $force ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$key = $this->getKey();
		$url = $this->getKeyUrl();

		$result = array(
			'ok'      => false,
			'code'    => 0,
			'url'     => $url,
			'body_ok' => false,
			'message' => '',
		);

		$response = wp_remote_get( $url, array(
			'timeout'     => 5,
			'sslverify'   => false,
			'redirection' => 2,
			'headers'     => array( 'User-Agent' => 'Squirrly-IndexNow/' . SQ_VERSION ),
		) );

		if ( is_wp_error( $response ) ) {
			$result['message'] = $response->get_error_message();
		} else {
			$code              = (int) wp_remote_retrieve_response_code( $response );
			$body              = trim( wp_remote_retrieve_body( $response ) );
			$result['code']    = $code;
			$result['body_ok'] = ( $body === $key );
			$result['ok']      = ( $code === 200 && $body === $key );
		}

		set_transient( $cache_key, $result, HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * Normalize an IndexNow endpoint: the api.indexnow.org hub must include the /indexnow path.
	 */
	public function normalizeEndpoint( $url ) {
		if ( rtrim( (string) $url, '/' ) === 'https://api.indexnow.org' ) {
			return 'https://api.indexnow.org/indexnow';
		}

		return $url;
	}

	/**
	 * Get the current domain host or localhost
	 *
	 * @return array|bool|mixed|string|null
	 */
	public function getHost() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( empty( $host ) ) {
			$host = 'localhost';
		}

		return $host;
	}

	/**
	 * Get the API key.
	 *
	 * @return string
	 */
	public function getKey() {
		if ( ! empty( $this->_apiKey ) ) {
			return $this->_apiKey;
		}

		if ( ! $this->_apiKey = SQ_Classes_Helpers_Tools::getOption( 'indexnow_key' ) ) {
			$this->resetIndexnowKey();
		}

		return apply_filters( 'sq_indexnow_key', $this->_apiKey );
	}

	/**
	 * Get the API key location.
	 *
	 * @return string
	 */
	public function getKeyUrl() {
		return apply_filters( 'sq_indexnow_key_url', trailingslashit( home_url() ) . $this->getKey() . '.txt' );
	}

	/**
	 * Get the additional data to send to the API.
	 *
	 * @param array $urls URLs to submit.
	 *
	 * @return mixed
	 */
	private function getLinks( $urls ) {
		return wp_json_encode( [
				'host'        => $this->getHost(),
				'key'         => $this->getKey(),
				'keyLocation' => $this->getKeyUrl(),
				'urlList'     => (array) $urls,
			] );
	}

	/**
	 * Get the error message from list
	 *
	 * @param $http_code
	 *
	 * @return mixed|string|void
	 */
	private function getErrorMessage( $http_code ) {

		$key_url = $this->getKeyUrl();

		$message     = __( 'Unknown error.', 'squirrly-seo' );
		$message_map = [
			400 => __( 'Invalid request.', 'squirrly-seo' ),
			401 => sprintf( __( 'Unauthorized. Search engines could not read your IndexNow key file (%s). Your site is likely blocking it - check for HTTP authentication, a coming-soon/maintenance mode, a security/firewall plugin, or a CDN bot rule.', 'squirrly-seo' ), $key_url ),
			403 => sprintf( __( 'Forbidden. The IndexNow key could not be verified. Make sure %s is publicly accessible and returns the key.', 'squirrly-seo' ), $key_url ),
			404 => sprintf( __( 'The IndexNow key file was not found. Make sure %s is publicly accessible.', 'squirrly-seo' ), $key_url ),
			422 => __( 'Invalid URL or the key location does not match this host.', 'squirrly-seo' ),
			429 => __( 'Too many requests.', 'squirrly-seo' ),
			500 => __( 'Internal server error.', 'squirrly-seo' ),
			503 => __( 'Service temporarily unavailable.', 'squirrly-seo' ),
		];

		if ( isset( $message_map[ $http_code ] ) ) {
			$message = $message_map[ $http_code ];
		}

		return $message;
	}


	/**
	 * Generate and save a new API key.
	 */
	public function resetIndexnowKey() {

		$this->_apiKey = $this->generateApiKey();
		SQ_Classes_Helpers_Tools::saveOptions( 'indexnow_key', $this->_apiKey );

		//the key changed, so the previous key-file check no longer applies
		delete_transient( 'sq_indexnow_keycheck' );

	}

	/**
	 * Generate new random API key.
	 */
	private function generateApiKey() {
		$api_key = wp_generate_uuid4();
		$api_key = preg_replace( '[-]', '', $api_key );

		return $api_key;
	}

	/**
	 * Log the request.
	 *
	 * @param $urls
	 * @param $status
	 * @param $manual
	 * @param $message
	 * @param $endpoints IndexNow endpoints the URLs were submitted to (kept so the log stays accurate if settings change)
	 *
	 * @return void
	 */
	public function addLog( $urls, $status, $manual, $message = '', $endpoints = array() ) {
		$log = $this->getLog();
		$url = $this->getUrlLog( $urls );

		if ( ! $url ) {
			return;
		}

		$log[] = [
			'url'       => $url,
			'status'    => (int) $status,
			'manual'    => (int) $manual,
			'message'   => $message,
			'endpoints' => array_values( array_filter( array_map( 'strval', (array) $endpoints ) ) ),
			'time'      => time(),
		];

		// Only keep the last 100 records.
		$log = array_slice( $log, - 100 );

		$this->setLog( $log );
	}

	/**
	 * Generate the History Log
	 *
	 * @param $urls
	 *
	 * @return mixed|string
	 */
	public function getUrlLog( $urls ) {
		//Store every submitted URL (newline-separated) in the existing url field so the log can show them all - no extra field needed.
		$urls = array_values( array_filter( array_map( 'strval', (array) $urls ) ) );

		return implode( "\n", $urls );
	}

	/**
	 * Backoff state from the log tail: escalating pause (60s, 5min, 30min) after consecutive failed submissions.
	 */
	private function getBackoffState() {
		$state = array( 'limited' => false, 'until' => 0, 'code' => 0 );

		$log = $this->getLog();
		if ( ! is_array( $log ) || empty( $log ) ) {
			return $state;
		}

		//Count the trailing streak of failures (any 4xx/5xx); status 0 (network / old "Submitted") breaks it.
		$count     = 0;
		$last_time = 0;
		$code      = 0;
		for ( $i = count( $log ) - 1; $i >= 0; $i -- ) {
			$status = isset( $log[ $i ]['status'] ) ? (int) $log[ $i ]['status'] : 0;
			if ( $status < 400 ) {
				break;
			}
			$count ++;
			if ( ! $last_time ) {
				$last_time = isset( $log[ $i ]['time'] ) ? (int) $log[ $i ]['time'] : 0;
				$code      = $status;
			}
		}

		if ( ! $count ) {
			return $state;
		}

		$steps            = array( 60, 300, 1800 ); // 1min, 5min, 30min
		$interval         = $steps[ min( $count - 1, count( $steps ) - 1 ) ];
		$state['code']    = $code;
		$state['until']   = $last_time + $interval;
		$state['limited'] = ( time() < $state['until'] );

		return $state;
	}

	/**
	 * Whether submissions are currently paused because IndexNow keeps returning errors.
	 */
	public function isSubmitPaused() {
		$state = $this->getBackoffState();

		return $state['limited'];
	}

	/**
	 * Human message explaining the current pause (empty when not paused).
	 */
	public function getBackoffMessage() {
		$state = $this->getBackoffState();
		if ( ! $state['limited'] ) {
			return '';
		}

		$time = wp_date( get_option( 'time_format' ), $state['until'] );

		if ( $state['code'] === 429 ) {
			return sprintf( esc_html__( 'IndexNow is rate-limiting your site (429 Too Many Requests). New submissions are paused until %s.', 'squirrly-seo' ), $time );
		}

		return sprintf( esc_html__( 'Submissions are paused until %1$s. IndexNow returned %2$d: %3$s', 'squirrly-seo' ), $time, $state['code'], $this->getErrorMessage( $state['code'] ) );
	}

	/**
	 * Whether the URL was already submitted within $within seconds (IndexNow "once per 24h" rule).
	 */
	public function wasSubmittedRecently( $urls, $within ) {
		$url = $this->getUrlLog( $urls );

		if ( ! $url ) {
			return false;
		}

		$log = $this->getLog();
		if ( ! is_array( $log ) ) {
			return false;
		}

		foreach ( $log as $entry ) {
			if ( ! isset( $entry['url'], $entry['time'] ) ) {
				continue;
			}

			if ( $entry['url'] === $url && ( time() - (int) $entry['time'] ) < (int) $within ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the IndexNow log.
	 *
	 * @return array
	 */
	public function getLog() {
		return get_option( 'sq_indexnow_log', [] );
	}

	/**
	 * Save the log in database
	 *
	 * @param $log
	 *
	 * @return void
	 */
	public function setLog( $log ) {
		update_option( 'sq_indexnow_log', $log, false );
	}

	/**
	 * Delete the IndexNow log.
	 */
	public function deleteLog() {
		delete_option( 'sq_indexnow_log' );
	}

}
