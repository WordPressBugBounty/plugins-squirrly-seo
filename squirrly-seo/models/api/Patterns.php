<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Request-free service layer for the Automation patterns.
 *
 * Automation is the point of the plugin: one pattern per context decides the title, description
 * and structured data for every page of that type, and keeps adapting as the content changes.
 * Without a way to read them, an external client that finds a bad title can only pin a fixed
 * value onto the one page it was looking at, which detaches that page from Automation and leaves
 * every other page of the same type still broken. This service exists so a client can see the
 * pattern behind a page and fix the cause instead.
 *
 * Read-only. Writing patterns affects every page at once, so it stays in the admin for now.
 *
 * Class SQ_Models_Api_Patterns
 */
class SQ_Models_Api_Patterns {

	/**
	 * Fields of a pattern worth reporting. Everything else in the row is internal bookkeeping.
	 *
	 * @return array
	 */
	public static function reportedFields() {
		return array(
			'title'        => 'string',
			'description'  => 'string',
			'sep'          => 'string',
			'og_type'      => 'string',
			'jsonld_types' => 'array',
			'doseo'        => 'bool',
			'do_metas'     => 'bool',
			'do_jsonld'    => 'bool',
			'do_sitemap'   => 'bool',
			'noindex'      => 'bool',
			'nofollow'     => 'bool',
		);
	}

	/**
	 * Which pattern context a page belongs to.
	 *
	 * The homepage is normalized to 'home' upstream; taxonomies use the taxonomy name, except
	 * post_tag which is stored as 'tag'.
	 *
	 * @param object $post A resolved snippet post.
	 *
	 * @return string
	 */
	public function contextFor( $post ) {

		if ( isset( $post->post_type ) && $post->post_type <> '' ) {
			return ( $post->post_type === 'post_tag' ) ? 'tag' : $post->post_type;
		}

		if ( isset( $post->taxonomy ) && $post->taxonomy <> '' ) {
			return ( $post->taxonomy === 'post_tag' ) ? 'tag' : $post->taxonomy;
		}

		return '';
	}

	/**
	 * The pattern row for one context, or an empty array when the context has none.
	 *
	 * @param string $context
	 *
	 * @return array
	 */
	public function forContext( $context ) {
		$patterns = (array) SQ_Classes_Helpers_Tools::getOption( 'patterns' );

		return isset( $patterns[ $context ] ) ? (array) $patterns[ $context ] : array();
	}

	/**
	 * The context whose pattern actually drives a page.
	 *
	 * Most custom post types and custom taxonomies have no pattern of their own, and Squirrly
	 * falls back to the shared 'custom' pattern for them - see SQ_Models_Snippet::getPages().
	 * Reporting the requested context instead of the one that applies would send a client
	 * looking for a pattern that does not exist, so resolve the fallback the same way here.
	 *
	 * @param string $context The context a page belongs to.
	 *
	 * @return string The context that has a pattern, or an empty string when none does.
	 */
	public function effectiveContext( $context ) {
		$patterns = (array) SQ_Classes_Helpers_Tools::getOption( 'patterns' );

		if ( $context <> '' && isset( $patterns[ $context ] ) ) {
			return $context;
		}

		//taxonomy rows are stored with a tax- prefix
		if ( $context <> '' && isset( $patterns[ 'tax-' . $context ] ) ) {
			return 'tax-' . $context;
		}

		return isset( $patterns['custom'] ) ? 'custom' : '';
	}

	/**
	 * The {{tokens}} a pattern string uses, in the order they appear.
	 *
	 * @param string $pattern
	 *
	 * @return array
	 */
	public function tokensIn( $pattern ) {
		$found = array();

		if ( is_string( $pattern ) && preg_match_all( '/\{\{[a-z0-9_]+\}\}/i', $pattern, $matches ) ) {
			$found = array_values( array_unique( $matches[0] ) );
		}

		return $found;
	}

	/**
	 * Site-wide token values, so a client can see which of them are currently empty.
	 *
	 * These are the tokens that go blank through configuration rather than through content -
	 * an unset WordPress tagline empties {{sitedesc}} on every page at once - and an empty one
	 * leaves the separator around it stranded in the output.
	 *
	 * @return array
	 */
	public function siteTokens() {
		return array(
			'{{sitename}}' => (string) get_bloginfo( 'name' ),
			'{{sitedesc}}' => (string) get_bloginfo( 'description' ),
		);
	}

	/**
	 * Site-level tokens that resolve to nothing right now, with what to do about each.
	 *
	 * @param array $tokens Tokens used by the patterns being reported.
	 *
	 * @return array
	 */
	public function emptySiteTokens( $tokens ) {
		$empty = array();
		$site  = $this->siteTokens();

		foreach ( $tokens as $token ) {
			if ( isset( $site[ $token ] ) && trim( $site[ $token ] ) === '' ) {
				$empty[ $token ] = ( '{{sitedesc}}' === $token )
					//no HTML escaping here: this is JSON data for a client, not markup, and
					//esc_html__() would turn an arrow into &gt; in the middle of the sentence
					? __( "The WordPress tagline is empty, so this token resolves to nothing on every page that uses it. Set it in Settings / General / Tagline, or take the token out of the pattern.", 'squirrly-seo' )
					: __( "This site-wide value is empty, so the token resolves to nothing on every page that uses it.", 'squirrly-seo' );
			}
		}

		return $empty;
	}

	/**
	 * Read the Automation patterns.
	 *
	 * @param string $context Limit to one context. Empty returns every context.
	 *
	 * @return array|WP_Error
	 */
	public function getPatterns( $context = '' ) {
		$patterns = (array) SQ_Classes_Helpers_Tools::getOption( 'patterns' );

		if ( empty( $patterns ) ) {
			return new WP_Error( 'sq_no_patterns', esc_html__( "This site has no Automation patterns saved.", 'squirrly-seo' ) );
		}

		$context = sanitize_key( $context );

		if ( $context <> '' ) {
			if ( ! isset( $patterns[ $context ] ) ) {
				return new WP_Error(
					'sq_pattern_not_found',
					sprintf(
					/* translators: %s: the pattern context that was asked for */
						esc_html__( "There is no Automation pattern for '%s'.", 'squirrly-seo' ),
						$context
					)
				);
			}

			$patterns = array( $context => $patterns[ $context ] );
		}

		$reported   = self::reportedFields();
		$out        = array();
		$all_tokens = array();

		foreach ( $patterns as $key => $row ) {
			$row   = (array) $row;
			$entry = array( 'context' => (string) $key );

			foreach ( $reported as $field => $type ) {
				if ( ! isset( $row[ $field ] ) ) {
					continue;
				}

				switch ( $type ) {
					case 'bool':
						$entry[ $field ] = (bool) $row[ $field ];
						break;
					case 'array':
						$entry[ $field ] = array_values( (array) $row[ $field ] );
						break;
					default:
						$entry[ $field ] = (string) $row[ $field ];
				}
			}

			$entry['tokens_used'] = array(
				'title'       => $this->tokensIn( isset( $row['title'] ) ? $row['title'] : '' ),
				'description' => $this->tokensIn( isset( $row['description'] ) ? $row['description'] : '' ),
			);

			$all_tokens = array_merge( $all_tokens, $entry['tokens_used']['title'], $entry['tokens_used']['description'] );

			$out[] = $entry;
		}

		$all_tokens = array_values( array_unique( $all_tokens ) );

		return array(
			'patterns'         => $out,
			'site_tokens'      => $this->siteTokens(),
			'empty_tokens'     => $this->emptySiteTokens( $all_tokens ),
			'available_tokens' => json_decode( SQ_ALL_PATTERNS, true ),
		);
	}
}
