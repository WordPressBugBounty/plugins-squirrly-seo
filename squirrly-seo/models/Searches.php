<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Product searches that return results become indexable pages and are listed in the sitemap.
 * Runs only with WooCommerce, the Squirrly sitemap and Automation > Search > Include In Sitemap on.
 */
class SQ_Models_Searches {

	const TABLE = 'qss_searches';

	//one row per search and visitor, so a visitor counts once however the visits interleave
	const VISITORS = 'qss_searches_visitors';

	/** @var array Qualification results cached for the request, keyed by term hash */
	private $qualified = array();

	/**
	 * Is the feature switched on for this site?
	 *
	 * @return bool
	 */
	public function isEnabled() {
		if ( ! SQ_Classes_Helpers_Tools::getOption( 'sq_auto_sitemap' ) || ! SQ_Classes_Helpers_Tools::isEcommerce() ) {
			return false;
		}

		$patterns = SQ_Classes_Helpers_Tools::getOption( 'patterns' );

		return ! empty( $patterns['search']['do_sitemap'] );
	}

	/**
	 * Register the frontend hooks. Nothing is added when the feature is off.
	 */
	public function hookFrontend() {
		if ( ! $this->isEnabled() ) {
			return;
		}

		add_filter( 'request', array( $this, 'routeQualified' ) );
		add_action( 'template_redirect', array( $this, 'handleSearchPage' ), 5 );
		add_filter( 'sq_post', array( $this, 'setCanonicalUrl' ), 20 );
		add_filter( 'sq_option_sq_sitemap', array( $this, 'addSitemap' ) );
	}

	/**
	 * Minimum products found and distinct visitors before a search is published
	 *
	 * @return array
	 */
	public function getThresholds() {
		return array(
			'results' => max( 1, (int) apply_filters( 'sq_search_min_results', 3 ) ),
			'hits'    => max( 1, (int) apply_filters( 'sq_search_min_hits', 3 ) ),
		);
	}

	/**
	 * Lowercase, single spaced, no markup
	 *
	 * @param string $term
	 *
	 * @return string
	 */
	public function normalize( $term ) {
		$term = sanitize_text_field( wp_unslash( (string) $term ) );
		$term = trim( preg_replace( '/\s+/u', ' ', $term ) );

		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $term, 'UTF-8' ) : strtolower( $term );
	}

	/**
	 * A term worth recording: a few words, no links, emails or phone numbers
	 *
	 * @param string $term Normalized term
	 *
	 * @return bool
	 */
	public function isValidTerm( $term ) {
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $term, 'UTF-8' ) : strlen( $term );

		if ( $length < 3 || $length > 60 || count( explode( ' ', $term ) ) > 6 ) {
			return false;
		}

		if ( preg_match( '#(://|www\.|@|\d{6,}|[<>{}\[\]\\\\])#', $term ) || ! preg_match( '/\p{L}/u', $term ) ) {
			return false;
		}

		return (bool) apply_filters( 'sq_search_valid_term', true, $term );
	}

	/**
	 * Is this search published as a page and in the sitemap?
	 *
	 * @param string $term
	 *
	 * @return bool
	 */
	public function isQualified( $term ) {
		global $wpdb;

		$term = $this->normalize( $term );
		$hash = md5( $term );

		if ( isset( $this->qualified[ $hash ] ) ) {
			return $this->qualified[ $hash ];
		}

		$this->qualified[ $hash ] = false;

		if ( $term === '' || ! $this->isValidTerm( $term ) ) {
			return false;
		}

		$limits = $this->getThresholds();
		$wpdb->suppress_errors( true );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT hits, results FROM `{$wpdb->prefix}" . self::TABLE . "` WHERE term_hash = %s", $hash ) );
		$wpdb->suppress_errors( false );

		$this->qualified[ $hash ] = ( $row && (int) $row->hits >= $limits['hits'] && (int) $row->results >= $limits['results'] );

		return $this->qualified[ $hash ];
	}

	/**
	 * The clean URL of a search page, the same one used in the sitemap and the canonical
	 *
	 * @param string $term
	 *
	 * @return string
	 */
	public function getUrl( $term ) {
		return get_search_link( $this->normalize( $term ) );
	}

	/**
	 * A qualified search opened without a post type shows the WooCommerce product results
	 *
	 * @param array $query_vars
	 *
	 * @return array
	 */
	public function routeQualified( $query_vars ) {
		if ( isset( $query_vars['s'] ) && is_string( $query_vars['s'] ) && empty( $query_vars['post_type'] ) ) {
			//on /search/term/ the term is still encoded here; WP_Query decodes it the same way later
			$term = empty( $_GET['s'] ) ? urldecode( $query_vars['s'] ) : $query_vars['s'];

			if ( $this->isQualified( $term ) ) {
				$query_vars['post_type'] = 'product';
			}
		}

		return $query_vars;
	}

	/**
	 * Is the main query a product search?
	 *
	 * @return bool
	 */
	private function isProductSearch() {
		return is_search() && ! is_admin() && class_exists( 'WooCommerce' ) && in_array( 'product', (array) get_query_var( 'post_type' ), true );
	}

	/**
	 * On a product search: record it, and make a qualified one indexable
	 */
	public function handleSearchPage() {
		if ( ! $this->isProductSearch() || is_paged() ) {
			return;
		}

		global $wp_query;
		$term = $this->normalize( get_query_var( 's' ) );

		if ( $this->isQualified( $term ) ) {
			remove_filter( 'wp_robots', 'wp_robots_noindex_search' );
			add_filter( 'sq_noindex', array( $this, 'removeNoindex' ), 50 );
		}

		$results = (int) $wp_query->found_posts;
		$limits  = $this->getThresholds();

		if ( $results < $limits['results'] || ! $this->isValidTerm( $term ) || $this->isIgnoredVisitor() ) {
			return;
		}

		if ( ! apply_filters( 'sq_search_record', true, $term, $results ) ) {
			return;
		}

		//written after the page is served, so the visitor never waits for it
		add_action( 'shutdown', function() use ( $term, $results ) {
			$this->record( $term, $results );
		} );
	}

	/**
	 * Bots and the site's own editors are not visitors
	 *
	 * @return bool
	 */
	private function isIgnoredVisitor() {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

		if ( $agent === '' || preg_match( '/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python|headless|lighthouse|inspectiontool|googleother|pagespeed|validator|uptime|pingdom|gtmetrix/i', $agent ) ) {
			return true;
		}

		return is_user_logged_in() && current_user_can( 'edit_posts' );
	}

	/**
	 * Add or update the search. A repeat from the same visitor does not count again.
	 *
	 * @param string $term Normalized term
	 * @param int $results
	 *
	 * @return bool
	 */
	public function record( $term, $results ) {
		global $wpdb;

		$table   = $wpdb->prefix . self::TABLE;
		$visitor = wp_hash( isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '' );
		$now     = current_time( 'mysql', true );

		$hash     = md5( $term );
		$visitors = $wpdb->prefix . self::VISITORS;

		$wpdb->suppress_errors( true );
		$before = $wpdb->get_row( $wpdb->prepare( "SELECT hits, results FROM `$table` WHERE term_hash = %s", $hash ) );

		$seen = $wpdb->prepare( "INSERT IGNORE INTO `$visitors` (term_hash, visitor, seen) VALUES (%s, %s, %s)", $hash, $visitor, $now );
		$new  = $wpdb->query( $seen );

		if ( $new === false && $this->createTable() ) {
			$new = $wpdb->query( $seen );
		}

		$add = ( (int) $new === 1 ) ? 1 : 0;

		$query = $wpdb->prepare( "INSERT INTO `$table` (term_hash, term, results, hits, visitor, first_seen, last_seen) VALUES (%s, %s, %d, %d, %s, %s, %s)
			ON DUPLICATE KEY UPDATE hits = hits + %d, visitor = VALUES(visitor), results = VALUES(results), last_seen = VALUES(last_seen)",
			$hash, $term, (int) $results, $add, $visitor, $now, $now, $add );

		$saved = $wpdb->query( $query );

		if ( $saved === false && $this->createTable() ) {
			$saved = $wpdb->query( $query );
		}
		$wpdb->suppress_errors( false );

		//the sitemaps change only when a search starts or stops qualifying
		if ( $saved !== false ) {
			$limits = $this->getThresholds();
			$was    = $before && (int) $before->hits >= $limits['hits'] && (int) $before->results >= $limits['results'];
			$hits   = $before ? (int) $before->hits + $add : $add;
			$is     = $hits >= $limits['hits'] && (int) $results >= $limits['results'];

			if ( $was !== $is ) {
				$this->refreshSitemaps();
			}
		}

		//prune now and then instead of on a schedule
		if ( mt_rand( 1, 200 ) === 1 ) {
			$this->prune();
		}

		return $saved !== false;
	}

	/**
	 * Rebuild the Squirrly sitemap cache and drop the page cache copies of the index and the search sitemap
	 */
	public function refreshSitemaps() {
		if ( $cache = SQ_Classes_ObjController::getClass( 'SQ_Classes_Helpers_Cache' ) ) {
			$cache->invalidateCache();
		}

		foreach ( array( 'sitemap.xml', 'sitemap-search.xml' ) as $file ) {
			do_action( 'litespeed_purge_url', home_url( '/' . $file ) );
		}

		do_action( 'sq_search_sitemaps_refreshed' );
	}

	/**
	 * Drop searches nobody repeated within 30 days, and any search unused for 180 days
	 */
	public function prune() {
		global $wpdb;

		$limits   = $this->getThresholds();
		$table    = $wpdb->prefix . self::TABLE;
		$visitors = $wpdb->prefix . self::VISITORS;

		$wpdb->query( $wpdb->prepare( "DELETE FROM `$table` WHERE last_seen < %s OR (hits < %d AND last_seen < %s)",
			gmdate( 'Y-m-d H:i:s', time() - 180 * DAY_IN_SECONDS ), $limits['hits'], gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ) ) );
		$wpdb->query( "DELETE v FROM `$visitors` v LEFT JOIN `$table` s ON s.term_hash = v.term_hash WHERE s.id IS NULL" );
	}

	/**
	 * @return bool
	 */
	public function createTable() {
		global $wpdb;

		$visitors = false !== $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}" . self::VISITORS . "` (
			`term_hash` char(32) NOT NULL,
			`visitor` char(32) NOT NULL,
			`seen` datetime NOT NULL,
			PRIMARY KEY (`term_hash`, `visitor`)
		) " . $wpdb->get_charset_collate() );

		return $visitors && false !== $wpdb->query( "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}" . self::TABLE . "` (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`term_hash` char(32) NOT NULL,
			`term` varchar(191) NOT NULL,
			`results` int(10) unsigned NOT NULL DEFAULT 0,
			`hits` int(10) unsigned NOT NULL DEFAULT 1,
			`visitor` char(32) NOT NULL DEFAULT '',
			`first_seen` datetime NOT NULL,
			`last_seen` datetime NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `term_hash` (`term_hash`),
			KEY `qualified` (`hits`, `results`)
		) " . $wpdb->get_charset_collate() );
	}

	/**
	 * The published searches, most searched first
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array of objects with term and last_seen
	 */
	public function getQualified( $limit = 500, $offset = 0 ) {
		global $wpdb;

		$limits = $this->getThresholds();
		$wpdb->suppress_errors( true );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT term, last_seen FROM `{$wpdb->prefix}" . self::TABLE . "` WHERE hits >= %d AND results >= %d ORDER BY hits DESC, id ASC LIMIT %d OFFSET %d",
			$limits['hits'], $limits['results'], (int) $limit, (int) $offset ) );
		$wpdb->suppress_errors( false );

		return (array) $rows;
	}

	/**
	 * Remove noindex from the robots list of a qualified search page
	 *
	 * @param array $robots
	 *
	 * @return array
	 */
	public function removeNoindex( $robots ) {
		return is_array( $robots ) ? array_values( array_diff( $robots, array( 'noindex' ) ) ) : $robots;
	}

	/**
	 * A qualified search page has one URL: the clean search link
	 *
	 * @param SQ_Models_Domain_Post $post
	 *
	 * @return SQ_Models_Domain_Post
	 */
	public function setCanonicalUrl( $post ) {
		if ( isset( $post->post_type ) && $post->post_type === 'search' && $this->isProductSearch() && ! is_paged() ) {
			$term = get_query_var( 's' );

			if ( $this->isQualified( $term ) ) {
				$post->url = $this->getUrl( $term );
			}
		}

		return $post;
	}

	/**
	 * Add the search sitemap to the sitemap list on the frontend
	 *
	 * @param array $sitemaps
	 *
	 * @return array
	 */
	public function addSitemap( $sitemaps ) {
		if ( is_array( $sitemaps ) && ! isset( $sitemaps['sitemap-search'] ) ) {
			$sitemaps['sitemap-search'] = array( 'sitemap-search.xml', 1 );
		}

		return $sitemaps;
	}
}
