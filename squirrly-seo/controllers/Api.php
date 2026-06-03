<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class SQ_Controllers_Api extends SQ_Classes_FrontController {

	/**
	 * @var string token local URL token
	 */
	private $token;
	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private $namespace = 'squirrly';

	/**
	 * Return Squirrly API URL
	 */
	public function getApiUrl() {
		return trailingslashit( get_rest_url() ) . $this->namespace . '/';
	}

	/**
	 * Initialize the TinyMCE editor for the current use
	 *
	 * @return void
	 */
	public function hookInit() {

		add_action( 'rest_api_init', array( $this, 'sqApiInitIdentity' ) );

		if ( SQ_Classes_Helpers_Tools::getOption( 'sq_api' ) == '' ) {
			return;
		}

		if ( ! SQ_Classes_Helpers_Tools::getOption( 'sq_cloud_connect' ) ) {
			return;
		}

		$this->token = SQ_Classes_Helpers_Tools::getOption( 'sq_cloud_token' ) . SQ_Classes_Helpers_Tools::getOption( 'sq_api' ) ;

		//Change the rest api if needed
		add_action( 'rest_api_init', array( $this, 'sqApiInit' ) );
	}


	function sqApiInit() {
		if ( function_exists( 'register_rest_route' ) ) {

			register_rest_route( $this->namespace, '/save/', array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'savePost' ),
					'permission_callback' => '__return_true'
				) );

			register_rest_route( $this->namespace, '/get/', array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'getData' ),
					'permission_callback' => '__return_true'
				) );

			register_rest_route( $this->namespace, '/test/', array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'testConnection' ),
					'permission_callback' => '__return_true'
				) );


			// load deprecate API for compatibility
			$this->deprecateRest();
		}
	}

	public function sqApiInitIdentity() {
		if ( ! function_exists( 'register_rest_route' ) ) {
			return;
		}

		register_rest_route( $this->namespace . '/v1', '/identity', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array( $this, 'restIdentity' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function restIdentity( WP_REST_Request $request ) {
		SQ_Classes_Helpers_Tools::setHeader( 'json' );

		$key = SQ_Classes_Helpers_SiteAuth::getSiteKey();
		if ( $key === '' ) {
			return new WP_REST_Response( array( 'error' => 'no_key' ), 404 );
		}

		$timestamp = (int) $request->get_header( 'X-SQ-Timestamp' );
		$nonce     = (string) $request->get_header( 'X-SQ-Nonce' );
		$url       = (string) $request->get_header( 'X-SQ-Url' );
		$sig       = (string) $request->get_header( 'X-SQ-Sig' );
		$body      = $request->get_body();

		if ( ! $timestamp || ! $nonce || ! $url || ! $sig ) {
			return new WP_REST_Response( array( 'error' => 'invalid_signature' ), 401 );
		}

		if ( abs( time() - $timestamp ) > SQ_Classes_Helpers_SiteAuth::SKEW_LIMIT ) {
			return new WP_REST_Response( array( 'error' => 'clock_skew', 'server_time' => time() ), 401 );
		}

		$canonical = SQ_Classes_Helpers_SiteAuth::canonical(
			'POST',
			'/wp-json/' . $this->namespace . '/v1/identity',
			$body,
			$url,
			$timestamp,
			$nonce
		);

		if ( ! SQ_Classes_Helpers_SiteAuth::verify( $canonical, $sig ) ) {
			return new WP_REST_Response( array( 'error' => 'invalid_signature' ), 401 );
		}

		$payload = json_decode( $body, true );
		$echo    = is_array( $payload ) && isset( $payload['challenge'] ) ? (string) $payload['challenge'] : '';

		$responseBody = wp_json_encode( array(
			'uuid' => SQ_Classes_Helpers_SiteAuth::getSiteUuid(),
			'echo' => $echo,
		) );

		$resTs    = time();
		$resNonce = wp_generate_password( 32, false, false );
		$resCanon = SQ_Classes_Helpers_SiteAuth::canonical( 'POST', '/identity-response', $responseBody, $url, $resTs, $resNonce );
		$resSig   = SQ_Classes_Helpers_SiteAuth::sign( $resCanon );

		$response = new WP_REST_Response( json_decode( $responseBody, true ), 200 );
		$response->header( 'X-SQ-Timestamp', (string) $resTs );
		$response->header( 'X-SQ-Nonce', $resNonce );
		$response->header( 'X-SQ-Sig', $resSig );
		return $response;
	}

	/*
	 * Deprecate since version 12.1.10
	 */
	public function deprecateRest() {
		register_rest_route( 'save', '/squirrly/', array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'savePost' ),
				'permission_callback' => '__return_true'
			) );

		register_rest_route( 'test', '/squirrly/', array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'testConnection' ),
				'permission_callback' => '__return_true'
			) );

		register_rest_route( 'get', '/squirrly/', array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'getData' ),
				'permission_callback' => '__return_true'
			) );
	}

	/**
	 * Test the connection
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 */
	public function testConnection( WP_REST_Request $request ) {
		SQ_Classes_Helpers_Tools::setHeader( 'json' );

		//get the token from API
		$token = $request->get_param( 'token' );
		if ( $token <> '' ) {
			$token =  preg_replace("/[^a-zA-Z0-9]/","",$token);
		}

		if ( ! $this->token || $this->token <> $token ) {
			exit( wp_json_encode( array(
				'connected' => false,
				'error'     => esc_html__( "Invalid Token. Please try again", 'squirrly-seo' )
			) ) );
		}

		echo wp_json_encode( array( 'connected' => true, 'error' => false ) );
		exit();
	}

	/**
	 * Save the Post
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 */
	public function savePost( WP_REST_Request $request ) {
		SQ_Classes_Helpers_Tools::setHeader( 'json' );

		//get the token from API
		$token = $request->get_param( 'token' );

		if ( $token <> '' ) {
			$token =  preg_replace("/[^a-zA-Z0-9]/","",$token);
		}

		if ( ! $this->token || $this->token <> $token ) {
			exit( wp_json_encode( array( 'error' => esc_html__( "Connection expired. Please try again", 'squirrly-seo' ) ) ) );
		}

		$post = $request->get_param( 'post' );
		if ( $post = json_decode( $post ) ) {
			if ( isset( $post->ID ) && $post->ID > 0 ) {
				$post     = new WP_Post( $post );
				$post->ID = 0;
				if ( isset( $post->post_author ) ) {
					if ( is_email( $post->post_author ) ) {
						if ( $user = get_user_by( 'email', $post->post_author ) ) {
							$post->post_author = $user->ID;
						} else {
							exit( wp_json_encode( array( 'error' => esc_html__( "Author not found", 'squirrly-seo' ) ) ) );
						}
					} else {
						exit( wp_json_encode( array( 'error' => esc_html__( "Author not found", 'squirrly-seo' ) ) ) );
					}
				} else {
					exit( wp_json_encode( array( 'error' => esc_html__( "Author not found", 'squirrly-seo' ) ) ) );
				}

				$post = $this->sanitizeIncomingPost( $post );

				$post_ID = wp_insert_post( $post->to_array() );
				if ( is_wp_error( $post_ID ) ) {
					echo wp_json_encode( array( 'error' => $post_ID->get_error_message() ) );
				} else {
					echo wp_json_encode( array(
						'saved'     => true,
						'post_ID'   => $post_ID,
						'permalink' => get_permalink( $post_ID )
					) );
				}
				exit();
			}
		}
		echo wp_json_encode( array( 'error' => true ) );
		exit();
	}

	/**
	 * Strip everything that doesn't belong in a savePost payload.
	 * Only Squirrly-namespaced meta keys are accepted, and _sq_jsonld_custom is
	 * forced through a JSON validator so XSS payloads can't be persisted.
	 */
	private function sanitizeIncomingPost( $post ) {
		$allowedMeta = array(
			'_sq_jsonld_custom',
			'_sq_pixel_custom',
			'_sq_keywords',
			'_sq_sla',
			'_sq_video',
			'_sq_old_slug',
			'_sq_woocommerce',
		);

		$raw   = isset( $post->meta_input ) ? (array) $post->meta_input : array();
		$clean = array();
		foreach ( $allowedMeta as $key ) {
			if ( ! array_key_exists( $key, $raw ) ) {
				continue;
			}
			$value = $raw[ $key ];

			if ( $key === '_sq_jsonld_custom' || $key === '_sq_pixel_custom' ) {
				$value = $this->sanitizeJsonValue( $value );
				if ( $value === null ) {
					continue;
				}
			} elseif ( is_string( $value ) ) {
				$value = wp_check_invalid_utf8( $value );
			}

			$clean[ $key ] = $value;
		}
		$post->meta_input = $clean;

		return $post;
	}

	/**
	 * Accept only a value that parses as a JSON object/array; re-encode to strip any
	 * smuggled HTML/script content. Returns null when the value is unusable.
	 */
	private function sanitizeJsonValue( $value ) {
		if ( ! is_string( $value ) ) {
			return null;
		}
		//Strip any script tags before parsing so a payload like
		//`</script><script>...</script><script type="application/ld+json">` cannot survive.
		$stripped = preg_replace( '#</?script[^>]*>#i', '', $value );
		$decoded  = json_decode( trim( $stripped ), true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}
		return wp_json_encode( $decoded );
	}

	/**
	 * Get data for the Focus Page Audit
	 *
	 * @param WP_REST_Request $request
	 */
	public function getData( WP_REST_Request $request ) {

		global $wpdb;
		$response = array();
		SQ_Classes_Helpers_Tools::setHeader( 'json' );

		//get the token from API
		$token = $request->get_param( 'token' );

		if ( $token <> '' ) {
			$token =  preg_replace("/[^a-zA-Z0-9]/","",$token);
		}

		if ( ! $this->token || $this->token <> $token ) {
			exit( wp_json_encode( array( 'error' => esc_html__( "Connection expired. Please try again", 'squirrly-seo' ) ) ) );
		}

		$select = $request->get_param( 'select' );

		switch ( $select ) {
			case 'innerlinks':

				$inner_links = array();
				$url         = esc_url_raw( $request->get_param( 'url' ) );
				$start       = (int) $request->get_param( 'start' );
				$limit       = (int) $request->get_param( 'limit' );

				if ( $url == '' ) {
					exit( wp_json_encode( array( 'error' => esc_html__( "Wrong Params", 'squirrly-seo' ) ) ) );
				}

				//define vars
				if ( $limit == 0 ) {
					$limit = 1000;
				}

				//prepare the url for query
				$url_backslash = str_replace( '/', '\/', str_replace( rtrim( home_url(), '/' ), '', $url ) );
				$url_encoded   = urlencode( str_replace( trim( home_url(), '/' ), '', $url ) );
				$url_decoded   = str_replace( trim( home_url(), '/' ), '', urldecode( $url ) );

				//get post inner links
				$select_table = $wpdb->prepare( "SELECT ID FROM `$wpdb->posts` WHERE `post_status` = %s ORDER BY ID DESC LIMIT %d,%d", 'publish', $start, $limit );
				if ( $ids = $wpdb->get_col( $select_table ) ) {
					$query = $wpdb->prepare( "SELECT `ID` FROM `$wpdb->posts` as p WHERE ID in (" . join( ',', array_values( $ids ) ) . ") AND (p.post_content LIKE %s OR p.post_content LIKE %s OR p.post_content LIKE %s OR p.post_content LIKE %s)", '%' . $url . '%', '%' . $url_backslash . '%', '%' . $url_encoded . '%', '%' . $url_decoded . '%' );

					if ( ! $inner_links = wp_cache_get( md5( $query ) ) ) {
						//prepare the inner_links array
						$inner_links = array();

						if ( $rows = $wpdb->get_results( $query ) ) {
							if ( ! empty( $rows ) ) {
								foreach ( $rows as $row ) {
									if ( untrailingslashit( get_permalink( $row->ID ) ) <> $url ) {
										$inner_links[] = get_permalink( $row->ID );
									}
								}
							}
						}

					}

					wp_cache_set( md5( $query ), $inner_links, '', 3600 );
				}

				$response = array( 'url' => $url, 'inner_links' => $inner_links );
				break;
			case 'keyword':

				$url     = esc_url_raw( $request->get_param( 'url' ) );
				$keyword = sanitize_text_field( $request->get_param( 'keyword' ) );
				$start   = (int) $request->get_param( 'start' );
				$limit   = (int) $request->get_param( 'limit' );

				if ( $url == '' || $keyword == '' ) {
					exit( wp_json_encode( array( 'error' => esc_html__( "Wrong Params", 'squirrly-seo' ) ) ) );
				}

				//define vars
				if ( $limit == 0 ) {
					$limit = 1000;
				}
				$regex = "\\b" . strtolower( $keyword ) . "\\b";

				//get post keywords found
				$select_table = $wpdb->prepare( "SELECT ID FROM `$wpdb->posts` WHERE `post_status` = %s ORDER BY ID DESC LIMIT %d,%d", 'publish', $start, $limit );
				if ( $ids = $wpdb->get_col( $select_table ) ) {
					$query = $wpdb->prepare( "SELECT `ID`, `post_content` FROM `$wpdb->posts` as p WHERE ID in (" . join( ',', array_values( $ids ) ) . ") AND (LOWER(p.post_content) REGEXP %s)", $regex );

					if ( ! $urls = wp_cache_get( md5( $query ) ) ) {
						//prepare the url for query
						$urls = array();

						if ( $rows = $wpdb->get_results( $query ) ) {
							if ( ! empty( $rows ) ) {
								foreach ( $rows as $row ) {

									if ( untrailingslashit( get_permalink( $row->ID ) ) <> $url ) {
										$row->post_content = str_replace( '\/', '/', $row->post_content );

										$urls[] = array(
											'post_id'   => $row->ID,
											'permalink' => get_permalink( $row->ID ),
											'innerlink' => strpos( $row->post_content, untrailingslashit($url) ) !== false
										);
									}
								}
							}
						}

						wp_cache_set( md5( $query ), $urls, '', 3600 );
					}

					$response = array( 'keyword' => $keyword, 'urls' => $urls );
				}else{

					$response = array( 'keyword' => '', 'urls' => array() );
				}

				break;
			case 'posts':
				//get post inner links
				$total_posts = 0;

				if ( $row = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(`ID`) as count FROM `$wpdb->posts` WHERE `post_status` = %s", 'publish' ) ) ) {
					$total_posts = $row->count;
				}

				$response = array( 'total_posts' => $total_posts );
				break;
			case 'post':

				$id = (int) $request->get_param( 'id' );

				if ( $id == 0 ) {
					wp_send_json_error( esc_html__( "Wrong Params", 'squirrly-seo' ) );
				}

				//get Squirrly SEO post metas
				if ( $post = SQ_Classes_ObjController::getClass( 'SQ_Models_Snippet' )->setPostByID( $id ) ) {
					$response = $post->toArray();
				}

				break;
			case 'squirrly':

				//Get Squirrly settings - strip auth state so the cloud never echoes back our keys
				if ( $options = SQ_Classes_Helpers_Tools::getOptions() ) {
					$response = (array) $options;
					foreach ( SQ_Classes_Helpers_SiteAuth::authOptionKeys() as $authKey ) {
						unset( $response[ $authKey ] );
					}
				}

				break;
		}

		echo wp_json_encode( $response );

		exit();

	}
}
