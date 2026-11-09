<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Request-free service layer for the Squirrly Cloud writes: Briefcase keywords, page keywords,
 * Focus Pages and the GEO Audit. Mirrors what the admin screens send, without superglobals.
 *
 * Class SQ_Models_Api_Cloud
 */
class SQ_Models_Api_Cloud {

	/** @var int longest keyword the Briefcase accepts */
	const MAX_KEYWORD = 255;

	public function __construct() {
		//admin-only wiring elsewhere, so load it before the static calls below
		SQ_Classes_ObjController::getClass( 'SQ_Classes_RemoteController' );
		SQ_Classes_ObjController::getClass( 'SQ_Models_Api_Seo' );
	}

	/**
	 * Add a keyword to the Briefcase, optionally with labels and rank tracking.
	 * An existing keyword keeps its labels and its rank tracking; new labels are added to them.
	 *
	 * @param array $input keyword, labels (names), track_rank
	 *
	 * @return array|WP_Error
	 */
	public function addBriefcaseKeyword( $input ) {
		$input   = (array) $input;
		$keyword = $this->sanitizeKeyword( isset( $input['keyword'] ) ? $input['keyword'] : '' );

		if ( is_wp_error( $keyword ) ) {
			return $keyword;
		}

		$track  = ! empty( $input['track_rank'] );
		$labels = isset( $input['labels'] ) ? array_filter( array_map( 'sanitize_text_field', (array) $input['labels'] ) ) : array();

		$briefcase = $this->findBriefcaseKeyword( $keyword );

		if ( is_wp_error( $briefcase ) ) {
			return $briefcase;
		}

		$existing = $briefcase['keyword'];
		$result   = array( 'keyword' => $keyword, 'added' => ! $existing, 'tracking_rank' => $existing ? (bool) $existing->do_serp : $track );

		if ( ! $existing ) {
			$response = SQ_Classes_RemoteController::addBriefcaseKeyword( array( 'keyword' => $keyword, 'do_serp' => (int) $track, 'is_hidden' => 0 ) );

			if ( is_wp_error( $response ) ) {
				return $this->cloudError( $response );
			}
		} elseif ( $track && ! $existing->do_serp ) {
			//the add call would overwrite do_serp and hidden on an existing keyword, so use the SERP call
			$response = SQ_Classes_RemoteController::addSerpKeyword( array( 'keyword' => $keyword ) );

			if ( is_wp_error( $response ) ) {
				return $this->cloudError( $response );
			}

			$result['tracking_rank'] = true;
		}

		if ( ! empty( $labels ) ) {
			$known = array();
			foreach ( $briefcase['labels'] as $label ) {
				if ( isset( $label->id, $label->name ) && (int) $label->id > 0 ) {
					$known[ mb_strtolower( trim( $label->name ) ) ] = (int) $label->id;
				}
			}

			//saving labels replaces the keyword's list, so start from the ones it already has
			$ids = array();
			if ( $existing && ! empty( $existing->labels ) ) {
				foreach ( $existing->labels as $label ) {
					if ( isset( $label->lid ) ) {
						$ids[] = (int) $label->lid;
					}
				}
			}

			$applied = $unknown = array();
			foreach ( $labels as $name ) {
				$key = mb_strtolower( trim( $name ) );

				if ( isset( $known[ $key ] ) ) {
					$ids[]     = $known[ $key ];
					$applied[] = $name;
				} else {
					$unknown[] = $name;
				}
			}

			if ( ! empty( $applied ) ) {
				SQ_Classes_RemoteController::saveBriefcaseKeywordLabel( array( 'keyword' => $keyword, 'labels' => join( ',', array_unique( $ids ) ) ) );
				$result['labels'] = $applied;
			}

			if ( ! empty( $unknown ) ) {
				$result['skipped_labels'] = $unknown;
				$result['available_labels'] = array_keys( $known );
			}
		}

		if ( $result['tracking_rank'] && ( ! $existing || ! $existing->do_serp ) ) {
			$result['message'] = __( "Keyword saved. The rank check will be ready in a minute.", 'squirrly-seo' );
		} else {
			$result['message'] = $existing ? __( "The keyword was already in the Briefcase.", 'squirrly-seo' ) : __( "Keyword saved.", 'squirrly-seo' );
		}

		return $result;
	}

	/**
	 * Attach a keyword to a page, the way the Live Assistant does, and optionally make it the
	 * page's main keyword. The keyword is added to the Briefcase too if it is not there yet.
	 *
	 * @param array $input page target, keyword, main
	 *
	 * @return array|WP_Error
	 */
	public function assignKeyword( $input ) {
		$input   = (array) $input;
		$keyword = $this->sanitizeKeyword( isset( $input['keyword'] ) ? $input['keyword'] : '' );

		if ( is_wp_error( $keyword ) ) {
			return $keyword;
		}

		//the Live Assistant sends no page row for this, so nothing is saved before the edit check
		$post = $this->resolvePage( $input, false );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( ! (int) $post->ID ) {
			return new WP_Error( 'sq_target_invalid', __( "Keywords can only be assigned to posts, pages and products.", 'squirrly-seo' ) );
		}

		if ( ! SQ_Models_Api_Seo::canEdit( (int) $post->ID ) ) {
			return new WP_Error( 'sq_forbidden', __( "You don't have permission to edit this page.", 'squirrly-seo' ) );
		}

		$response = SQ_Classes_RemoteController::addSLABriefcase( array( 'post_id' => (int) $post->ID, 'keyword' => $keyword ) );

		//already assigned is fine, the main flag below still applies
		if ( is_wp_error( $response ) && $response->get_error_message() <> 'keyword_exists' ) {
			return $this->cloudError( $response );
		}

		$main = ! isset( $input['main'] ) || ! empty( $input['main'] );

		if ( $main ) {
			$response = SQ_Classes_RemoteController::saveBriefcaseMainKeyword( array( 'post_id' => (int) $post->ID, 'keyword' => $keyword ) );

			if ( is_wp_error( $response ) ) {
				return $this->cloudError( $response );
			}

			//the Cloud answers with no data when it found no keyword row to mark as main
			if ( empty( $response ) ) {
				return new WP_Error( 'sq_cloud_error', __( "The keyword is assigned to this page, but it could not be set as the main keyword. Please try again.", 'squirrly-seo' ) );
			}
		}

		return array(
			'keyword' => $keyword,
			'post_id' => (int) $post->ID,
			'url'     => $post->url,
			'main'    => $main,
			'message' => $main ? __( "The keyword is now the main keyword of this page.", 'squirrly-seo' ) : __( "The keyword is assigned to this page.", 'squirrly-seo' ),
		);
	}

	/**
	 * Start monitoring a published page as a Focus Page.
	 *
	 * @param array $input page target
	 *
	 * @return array|WP_Error
	 */
	public function addFocusPage( $input ) {
		$post = $this->resolvePage( $input );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( $post->post_status <> 'publish' || ! (int) $post->ID ) {
			return new WP_Error( 'sq_not_public', __( "This page is not published, so it can't be a Focus Page. Publish it first.", 'squirrly-seo' ) );
		}

		$focuspage = SQ_Classes_RemoteController::addFocusPage( array( 'post_id' => (int) $post->ID, 'hash' => $post->hash, 'permalink' => $post->url ) );

		if ( is_wp_error( $focuspage ) ) {
			if ( $focuspage->get_error_message() == 'limit_exceed' ) {
				$checkin = SQ_Classes_RemoteController::checkin();

				if ( isset( $checkin->subscription_focus_pages, $checkin->subscription_max_focus_pages ) && ! $checkin->subscription_focus_pages ) {
					return new WP_Error( 'sq_limit', sprintf( __( "You have reached the maximum number of focus pages for this website (max: %d).", 'squirrly-seo' ), (int) $checkin->subscription_max_focus_pages ) );
				}

				if ( isset( $checkin->subscription_max_focus_pages_all ) ) {
					return new WP_Error( 'sq_limit', sprintf( __( "You have reached the maximum number of focus pages per your account (max: %d).", 'squirrly-seo' ), (int) $checkin->subscription_max_focus_pages_all ) );
				}

				return $this->cloudError( $focuspage );
			}

			return $this->cloudError( $focuspage );
		}

		if ( ! $focuspage ) {
			return new WP_Error( 'sq_cloud_error', __( "Error! Could not add the focus page.", 'squirrly-seo' ) );
		}

		if ( isset( $focuspage->user_post_id ) ) {
			set_transient( 'sq_auditpage_' . $focuspage->user_post_id, time() );
			SQ_Classes_Helpers_Tools::saveOptions( 'seoreport_time', false );
		}

		return array(
			'post_id'       => (int) $post->ID,
			'url'           => $post->url,
			'focus_page_id' => isset( $focuspage->user_post_id ) ? (int) $focuspage->user_post_id : 0,
			'message'       => __( "Focus page is added. The audit may take a while so please be patient.", 'squirrly-seo' ),
		);
	}

	/**
	 * Ask the Cloud to audit a Focus Page again.
	 *
	 * @param array $input page target, focus_page_id
	 *
	 * @return array|WP_Error
	 */
	public function recheckFocusPage( $input ) {
		$input = (array) $input;
		$id    = isset( $input['focus_page_id'] ) ? (int) $input['focus_page_id'] : 0;

		if ( $id <= 0 ) {
			return new WP_Error( 'sq_target_invalid', __( "Send the focus_page_id returned by get-focus-pages.", 'squirrly-seo' ) );
		}

		$post = $this->resolvePage( $input );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$focuspage = SQ_Classes_RemoteController::updateFocusPage( array( 'post_id' => $id, 'hash' => $post->hash, 'permalink' => $post->url, 'wp_post_id' => (int) $post->ID ) );

		if ( is_wp_error( $focuspage ) || ! $focuspage ) {
			return new WP_Error( 'sq_too_many', __( "You've made too many requests, please wait a few minutes.", 'squirrly-seo' ) );
		}

		set_transient( 'sq_auditpage_' . $id, time() );

		return array(
			'focus_page_id' => $id,
			'url'           => $post->url,
			'message'       => __( "Focus page sent for recheck. It may take a while so please be patient.", 'squirrly-seo' ),
		);
	}

	/**
	 * Add a page to the GEO Audit.
	 *
	 * @param array $input page target
	 *
	 * @return array|WP_Error
	 */
	public function addAuditPage( $input ) {
		$post = $this->resolvePage( $input );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( $post->post_status <> 'publish' ) {
			return new WP_Error( 'sq_not_public', __( "This page is not published, so it can't be audited. Publish it first.", 'squirrly-seo' ) );
		}

		$auditpage = SQ_Classes_RemoteController::addAuditPage( array( 'post_id' => (int) $post->ID, 'hash' => $post->hash, 'permalink' => $post->url ) );

		if ( is_wp_error( $auditpage ) ) {
			if ( $auditpage->get_error_message() == 'limit_exceed' ) {
				return new WP_Error( 'sq_limit', __( "You reached the maximum number of audit pages for your account.", 'squirrly-seo' ) );
			}

			return $this->cloudError( $auditpage );
		}

		if ( ! $auditpage ) {
			return new WP_Error( 'sq_cloud_error', __( "Error! Could not add the audit page.", 'squirrly-seo' ) );
		}

		set_transient( 'sq_auditpage_all', time() );

		return array(
			'post_id' => (int) $post->ID,
			'url'     => $post->url,
			'message' => __( "Audit page is added. The audit may take a while so please be patient.", 'squirrly-seo' ),
		);
	}

	/**
	 * Request a new GEO Audit for the whole site, or a recheck of one audited page.
	 *
	 * @param array $input optional page target
	 *
	 * @return array|WP_Error
	 */
	public function requestAudit( $input ) {
		$input = (array) $input;
		$args  = array();

		if ( ! empty( $input['post_id'] ) || ! empty( $input['url'] ) || ! empty( $input['term_id'] ) || ! empty( $input['homepage'] ) ) {
			$post = $this->resolvePage( $input );

			if ( is_wp_error( $post ) ) {
				return $post;
			}

			$args['post_id'] = (int) $post->ID;
		}

		$auditpage = SQ_Classes_RemoteController::updateAudit( $args );

		if ( is_wp_error( $auditpage ) ) {
			if ( $auditpage->get_error_message() == 'too_many_attempts' ) {
				return new WP_Error( 'sq_too_many', empty( $args ) ? __( "The audit for all pages can be made once an hour.", 'squirrly-seo' ) : __( "You've made too many requests, you can request one page audit per hour.", 'squirrly-seo' ) );
			}

			return $this->cloudError( $auditpage );
		}

		if ( empty( $args ) ) {
			set_transient( 'sq_auditpage_all', time() );
		}

		return array(
			'scope'   => empty( $args ) ? 'site' : 'page',
			'message' => empty( $args ) ? __( "A new GEO Audit is requested. It appears after all audited pages are crawled, which can take a while.", 'squirrly-seo' ) : __( "Audit page sent for recheck. It may take a while so please be patient.", 'squirrly-seo' ),
		);
	}

	/**
	 * Look a keyword up in the Briefcase and return it with the site's labels.
	 *
	 * @param string $keyword
	 *
	 * @return array|WP_Error keyword (object or false) and labels
	 */
	protected function findBriefcaseKeyword( $keyword ) {
		//search on the longest part without quotes or backslashes, then compare exactly here
		$parts  = preg_split( '/[\'"\\\\]+/', $keyword );
		$search = '';
		foreach ( $parts as $part ) {
			if ( strlen( trim( $part ) ) > strlen( $search ) ) {
				$search = trim( $part );
			}
		}

		$response = SQ_Classes_RemoteController::getBriefcase( array( 'search' => $search, 'limit' => 100 ) );

		if ( is_wp_error( $response ) && $response->get_error_message() <> 'no_data' ) {
			return new WP_Error( 'sq_cloud_error', __( "Could not read the Briefcase. Please try again.", 'squirrly-seo' ) );
		}

		$found = false;
		if ( ! is_wp_error( $response ) && ! empty( $response->keywords ) ) {
			foreach ( (array) $response->keywords as $row ) {
				if ( isset( $row->keyword ) && mb_strtolower( trim( html_entity_decode( $row->keyword, ENT_QUOTES, 'UTF-8' ) ) ) === mb_strtolower( $keyword ) ) {
					$found = $row;
					break;
				}
			}
		}

		$labels = ( ! is_wp_error( $response ) && ! empty( $response->labels ) ) ? (array) $response->labels : array();

		return array( 'keyword' => $found, 'labels' => $labels );
	}

	/**
	 * Load a page and save its row locally, as the admin screens do before every Cloud call.
	 *
	 * @param array $input
	 * @param bool $save
	 *
	 * @return SQ_Models_Domain_Post|WP_Error
	 */
	protected function resolvePage( $input, $save = true ) {
		$post = SQ_Classes_ObjController::getClass( 'SQ_Models_Api_Seo' )->resolveTarget( (array) $input );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( $save ) {
			SQ_Classes_ObjController::getClass( 'SQ_Models_Snippet' )->savePost( $post );
		}

		return $post;
	}

	/**
	 * @param mixed $keyword
	 *
	 * @return string|WP_Error
	 */
	protected function sanitizeKeyword( $keyword ) {
		$keyword = is_scalar( $keyword ) ? trim( preg_replace( '/\s+/u', ' ', sanitize_text_field( (string) $keyword ) ) ) : '';

		if ( $keyword === '' ) {
			return new WP_Error( 'sq_invalid_keyword', __( "Send the keyword to save.", 'squirrly-seo' ) );
		}

		if ( mb_strlen( $keyword ) > self::MAX_KEYWORD ) {
			return new WP_Error( 'sq_invalid_keyword', sprintf( __( "The keyword is too long. Use at most %d characters.", 'squirrly-seo' ), self::MAX_KEYWORD ) );
		}

		return $keyword;
	}

	/**
	 * Turn a Cloud error into one the client can act on.
	 *
	 * @param WP_Error $error
	 *
	 * @return WP_Error
	 */
	protected function cloudError( $error ) {
		$code = $error->get_error_message();

		switch ( $code ) {
			case 'too_many_attempts':
				return new WP_Error( 'sq_too_many', __( "You've made too many requests, please wait a few minutes.", 'squirrly-seo' ) );
			case 'limit_exceed':
				return new WP_Error( 'sq_limit', __( "Your Squirrly plan limit is reached for this feature.", 'squirrly-seo' ) );
			case 'invalid_keyword':
				return new WP_Error( 'sq_invalid_keyword', __( "This keyword is not in the Briefcase. Add it with add-briefcase-keyword first.", 'squirrly-seo' ) );
		}

		return new WP_Error( 'sq_cloud_error', __( "Squirrly Cloud could not save this. Please try again in a few minutes.", 'squirrly-seo' ) );
	}

}
