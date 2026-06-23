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

		//get the urls from options
		$this->_apiUrls = SQ_Classes_Helpers_Tools::getOption( 'indexnow_endpoints' );

		if ( empty( $this->_apiUrls ) ) {
			$this->_apiUrls = array(
				'https://api.indexnow.org',
				'https://www.bing.com/indexnow',
			);
		}

		$headers = array(
			'Content-Type'  => 'application/json',
			'User-Agent'    => 'Squirrly/' . md5( esc_url( home_url( '/' ) ) ),
			'X-Source-Info' => 'https://squirrly.co/' . SQ_VERSION . '/' . $manual,
		);

		//On auto-indexing (triggered by save_post) fire-and-forget so the post save
		//isn't blocked waiting for the IndexNow endpoints. There's no response code
		//to read, so just log it as submitted.
		if ( ! $manual ) {
			foreach ( $this->_apiUrls as $apiurl ) {
				if ( $apiurl == 'https://indexnow.yep.com' ) {
					continue;
				}

				wp_remote_post( $apiurl, array(
					'blocking' => false,
					'timeout'  => 5,
					'body'     => $data,
					'headers'  => $headers,
				) );
			}

			$this->_success = true;
			$this->addLog( (array) $urls, 0, $manual, 'Submitted' );

			return true;
		}

		//Manual submissions are blocking so the admin log can report the real result.
		//api.indexnow.org forwards the submission to every participating engine, so we
		//treat it as a success when ANY endpoint accepts it and only report an error
		//when they all fail - otherwise a single endpoint's 401/403 would mask a real
		//success and show "failed" every time.
		$success_code = 0;
		$error_code   = 0;
		$error_msg    = '';

		foreach ( $this->_apiUrls as $apiurl ) {
			if ( $apiurl == 'https://indexnow.yep.com' ) {
				continue;
			}

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
			$this->addLog( (array) $urls, $success_code, $manual, 'Success' );

			return true;
		}

		$this->addLog( (array) $urls, $error_code, $manual, $error_msg ? $error_msg : $this->getErrorMessage( $error_code ) );

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
	 *
	 * @return void
	 */
	public function addLog( $urls, $status, $manual, $message = '' ) {
		$log = $this->getLog();
		$url = $this->getUrlLog( $urls );

		if ( ! $url ) {
			return;
		}

		$log[] = [
			'url'     => $url,
			'status'  => (int) $status,
			'manual'  => (int) $manual,
			'message' => $message,
			'time'    => time(),
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
		$urls       = array_values( (array) $urls );
		$count_urls = count( $urls );
		if ( ! $count_urls ) {
			return '';
		}

		$url = $urls[0];
		if ( $count_urls > 1 ) {
			$url .= ' [+' . ( $count_urls - 1 ) . ']';
		}

		return $url;
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
