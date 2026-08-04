<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Request-free service layer for Squirrly's per-page SEO: no superglobals, no output.
 * Backs the snippet form, REST, WP-CLI and Abilities. SQ_Models_Snippet::saveSEO() wraps it.
 *
 * Class SQ_Models_Api_Seo
 */
class SQ_Models_Api_Seo {

	/**
	 * The SEO fields a client may write, mapped to how each one is sanitized.
	 * Every other property on SQ_Models_Domain_Sq is computed or internal.
	 *
	 * @return array
	 */
	public static function writableFields() {
		return array(
			'doseo'            => 'bool',
			'noindex'          => 'bool',
			'nofollow'         => 'bool',
			'nositemap'        => 'bool',
			'title'            => 'title',
			'description'      => 'description',
			'keywords'         => 'keywords',
			'canonical'        => 'link',
			'redirect'         => 'link',
			'primary_category' => 'text',
			'og_title'         => 'title',
			'og_description'   => 'description',
			'og_author'        => 'text',
			'og_type'          => 'text',
			'og_media'         => 'text',
			'tw_title'         => 'title',
			'tw_description'   => 'description',
			'tw_media'         => 'text',
			'tw_type'          => 'text',
			'jsonld'           => 'jsonld',
			'jsonld_types'     => 'list',
			'fpixel'           => 'pixel',
		);
	}

	/**
	 * Turn a loose target into the four values the snippet model needs.
	 * Accepts post_id, or term_id + taxonomy, or url, or homepage.
	 *
	 * @param array $target
	 *
	 * @return array|WP_Error
	 */
	public function normalizeTarget( $target ) {
		$target = (array) $target;

		$normalized = array(
			'post_id'   => isset( $target['post_id'] ) ? (int) $target['post_id'] : 0,
			'term_id'   => isset( $target['term_id'] ) ? (int) $target['term_id'] : 0,
			'taxonomy'  => isset( $target['taxonomy'] ) ? sanitize_key( $target['taxonomy'] ) : '',
			'post_type' => isset( $target['post_type'] ) ? sanitize_key( $target['post_type'] ) : '',
		);

		//the home page has no post ID of its own unless one is set in Settings > Reading
		if ( ! empty( $target['homepage'] ) ) {
			$normalized['post_type'] = 'home';

			return $normalized;
		}

		//resolve a permalink to a post ID so agents can address pages by URL
		if ( $normalized['post_id'] === 0 && $normalized['term_id'] === 0 && ! empty( $target['url'] ) ) {
			$post_id = url_to_postid( esc_url_raw( $target['url'] ) );

			if ( $post_id > 0 ) {
				$normalized['post_id'] = (int) $post_id;
			} else {
				return new WP_Error( 'sq_target_not_found', esc_html__( "Couldn't find a page for this URL", 'squirrly-seo' ) );
			}
		}

		if ( $normalized['term_id'] > 0 && $normalized['taxonomy'] === '' ) {
			return new WP_Error( 'sq_target_invalid', esc_html__( "A taxonomy is required when targeting a term", 'squirrly-seo' ) );
		}

		if ( $normalized['post_id'] === 0 && $normalized['term_id'] === 0 && $normalized['post_type'] === '' ) {
			return new WP_Error( 'sq_target_invalid', esc_html__( "Specify a post_id, a term_id with its taxonomy, a url or the homepage", 'squirrly-seo' ) );
		}

		return $normalized;
	}

	/**
	 * Load the Squirrly post object for a target.
	 *
	 * @param array $target
	 *
	 * @return SQ_Models_Domain_Post|WP_Error
	 */
	public function resolveTarget( $target ) {
		$target = $this->normalizeTarget( $target );

		if ( is_wp_error( $target ) ) {
			return $target;
		}

		/** @var SQ_Models_Snippet $snippet */
		$snippet = SQ_Classes_ObjController::getClass( 'SQ_Models_Snippet' );

		$post = $snippet->getCurrentSnippet( $target['post_id'], $target['term_id'], $target['taxonomy'], $target['post_type'] );

		if ( ! $post || ! isset( $post->hash ) || $post->hash == '' ) {
			return new WP_Error( 'sq_target_not_found', esc_html__( "Couldn't find the page", 'squirrly-seo' ) );
		}

		return $post;
	}

	/**
	 * Work out which qss row to write and what to store in its 'post' column.
	 * A caller that knows the row passes 'hash' directly (the snippet form does); else resolve.
	 *
	 * @param array $target
	 *
	 * @return array|WP_Error
	 */
	protected function resolveWriteTarget( $target ) {
		$target = (array) $target;

		if ( ! empty( $target['hash'] ) ) {
			//every hash Squirrly writes is an md5; anything else creates junk rows or
			//collides with a real one via a truncated hash, so reject it
			$hash = strtolower( preg_replace( '/[^a-f0-9]/i', '', $target['hash'] ) );

			if ( strlen( $hash ) !== 32 ) {
				return new WP_Error( 'sq_target_invalid', esc_html__( "Error! Invalid request.", 'squirrly-seo' ) );
			}

			return array(
				'hash'      => $hash,
				'url'       => isset( $target['url'] ) ? esc_url_raw( $target['url'] ) : '',
				'post_id'   => isset( $target['post_id'] ) ? (int) $target['post_id'] : 0,
				'term_id'   => isset( $target['term_id'] ) ? (int) $target['term_id'] : 0,
				'taxonomy'  => isset( $target['taxonomy'] ) ? $target['taxonomy'] : '',
				'post_type' => isset( $target['post_type'] ) ? $target['post_type'] : '',
			);
		}

		$post = $this->resolveTarget( $target );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		return array(
			'hash'      => $post->hash,
			'url'       => $post->url,
			'post_id'   => (int) $post->ID,
			'term_id'   => (int) $post->term_id,
			'taxonomy'  => $post->taxonomy,
			'post_type' => $post->post_type,
		);
	}

	/**
	 * Can the current user edit the SEO of this target?
	 * Same check the snippet form makes: the Squirrly cap, else the post's own edit cap.
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public static function canEdit( $post_id = 0 ) {
		if ( SQ_Classes_Helpers_Tools::userCan( 'sq_manage_snippets' ) ) {
			return true;
		}

		return (bool) SQ_Classes_Helpers_Tools::userCan( 'edit_post', (int) $post_id );
	}

	/**
	 * Read the SEO of a page. 'seo' is what is stored; 'computed' is what the frontend
	 * outputs and may come from an Automation pattern, so never write it back blindly.
	 *
	 * @param array $target
	 *
	 * @return array|WP_Error
	 */
	public function getSeo( $target ) {
		$post = $this->resolveTarget( $target );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		/** @var SQ_Models_Domain_Sq $stored */
		$stored = SQ_Classes_ObjController::getClass( 'SQ_Models_Qss' )->getSqSeo( $post->hash );

		$seo = array();
		foreach ( array_keys( self::writableFields() ) as $field ) {
			$seo[ $field ] = $stored->$field;
		}

		$computed = array();
		if ( $effective = $post->sq ) {
			$computed = array(
				'title'       => $effective->title,
				'description' => $effective->description,
				'keywords'    => $effective->keywords,
			);
		}

		return array(
			'target'   => array(
				'post_id'   => (int) $post->ID,
				'term_id'   => (int) $post->term_id,
				'taxonomy'  => $post->taxonomy,
				'post_type' => $post->post_type,
				'hash'      => $post->hash,
			),
			'url'      => $post->url,
			'seo'      => $seo,
			'computed' => $computed,
		);
	}

	/**
	 * Write the SEO of a page. Partial: only keys present in $fields are touched,
	 * empty string clears. The admin form sends everything, so it still replaces all.
	 *
	 * @param array $target
	 * @param array $fields
	 *
	 * @return array|WP_Error
	 */
	public function saveSeo( $target, $fields ) {
		$write = $this->resolveWriteTarget( $target );

		if ( is_wp_error( $write ) ) {
			return $write;
		}

		if ( $write['hash'] === '' ) {
			return new WP_Error( 'sq_target_invalid', esc_html__( "Error! Invalid request.", 'squirrly-seo' ) );
		}

		if ( ! self::canEdit( $write['post_id'] ) ) {
			return new WP_Error( 'sq_forbidden', esc_html__( "You don't have enough pemission to edit this article", 'squirrly-seo' ) );
		}

		$url  = $write['url'];
		$hash = $write['hash'];

		/** @var SQ_Models_Domain_Sq $sq */
		$sq = SQ_Classes_ObjController::getClass( 'SQ_Models_Qss' )->getSqSeo( $hash );

		$writable = self::writableFields();
		foreach ( $writable as $field => $type ) {
			if ( ! array_key_exists( $field, (array) $fields ) ) {
				continue;
			}

			$sq->$field = $this->sanitizeField( $type, $fields[ $field ] );
		}

		//Filter the SQ before save
		// Send SQ_Models_Domain_Sq object
		$sq = apply_filters( 'sq_seo_before_save', $sq, (int) $write['post_id'], $write['post_type'], (int) $write['term_id'], $write['taxonomy'], $hash );

		//Filter the URL before save
		$url = apply_filters( 'sq_url_before_save', $url, $hash );

		//Prevent broken url in canonical link
		if ( strpos( $sq->canonical, '//' ) === false ) {
			$sq->canonical = '';
		}

		if ( strpos( $sq->redirect, '//' ) === false || $sq->redirect === $url ) {
			$sq->redirect = '';
		}

		/** @var SQ_Models_Qss $qssModel */
		$qssModel = SQ_Classes_ObjController::getClass( 'SQ_Models_Qss' );

		try {

			$saved = $qssModel->saveSqSEO( $url, $hash, maybe_serialize( array(
				'ID'        => (int) $write['post_id'],
				'post_type' => $write['post_type'],
				'term_id'   => (int) $write['term_id'],
				'taxonomy'  => $write['taxonomy'],
			) ), maybe_serialize( $sq->toArray() ), gmdate( 'Y-m-d H:i:s' ) );

		} catch ( Exception $e ) {
			return new WP_Error( 'sq_save_failed', esc_html__( "Error! Could not save the data.", 'squirrly-seo' ) );
		}

		if ( ! $saved ) {
			//the table may be missing or out of date on sites upgraded from old versions
			$qssModel->checkTableExists();
			$qssModel->alterTable();

			return new WP_Error( 'sq_save_failed', esc_html__( "Error! Could not save the data.", 'squirrly-seo' ) );
		}

		//trigger action after SEO is saved in Squirrly DB
		do_action( 'sq_save_seo_after' );

		//echo back what is now stored, without re-resolving the target
		$seo = array();
		foreach ( array_keys( self::writableFields() ) as $field ) {
			$seo[ $field ] = $sq->$field;
		}

		return array(
			'saved'  => true,
			'target' => array(
				'post_id'   => (int) $write['post_id'],
				'term_id'   => (int) $write['term_id'],
				'taxonomy'  => $write['taxonomy'],
				'post_type' => $write['post_type'],
				'hash'      => $hash,
			),
			'url'    => $url,
			'seo'    => $seo,
		);
	}

	/**
	 * Sanitize one incoming value the same way the snippet form always has.
	 *
	 * @param string $type one of the types in writableFields()
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected function sanitizeField( $type, $value ) {
		switch ( $type ) {
			case 'bool':
				return (int) (bool) $value;

			case 'title':
				return wp_encode_emoji( SQ_Classes_Helpers_Sanitize::clearTitle( is_string( $value ) ? $value : '' ) );

			case 'description':
				return wp_encode_emoji( SQ_Classes_Helpers_Sanitize::clearDescription( is_string( $value ) ? $value : '' ) );

			case 'keywords':
				return SQ_Classes_Helpers_Sanitize::clearKeywords( is_string( $value ) ? $value : '' );

			case 'link':
				//not esc_url_raw(): it turns a malformed value into "http://value",
				//slipping past the "//" check that clears bad canonical/redirect URLs
				return is_string( $value ) ? sanitize_text_field( trim( $value ) ) : '';

			case 'list':
				return is_array( $value ) ? array_filter( array_map( 'sanitize_text_field', $value ) ) : array();

			case 'jsonld':
				//keep the JSON only - wp_kses first so nothing but a script wrapper can survive,
				//then strip_tags removes the wrapper itself
				if ( ! is_string( $value ) ) {
					return '';
				}

				return strip_tags( trim( wp_kses( $value, array( 'script' => array( 'type' => array() ) ) ) ) );

			case 'pixel':
				//tracking pixels legitimately need their script/noscript wrapper
				if ( ! is_string( $value ) ) {
					return '';
				}

				return trim( wp_kses( $value, array( 'script' => array(), 'noscript' => array() ) ) );

			case 'text':
			default:
				return is_string( $value ) ? sanitize_text_field( $value ) : '';
		}
	}

}
