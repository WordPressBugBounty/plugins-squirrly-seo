<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Exposes Squirrly through the WordPress Abilities API (core, since WP 6.9), so MCP clients,
 * REST apps and WP-CLI all reach it with an Application Password. Inert below WP 6.9.
 *
 * Every ability sets meta.public: MCP servers hide abilities that do not, so without it
 * an AI client sees nothing. Execution is still gated by each permission_callback.
 *
 * Class SQ_Classes_AbilitiesController
 */
class SQ_Classes_AbilitiesController {

	/** @var string the ability category all Squirrly abilities belong to */
	const CATEGORY = 'squirrly-seo';

	/** @var int seconds to cache a Cloud response for */
	const CACHE_TTL = 300;

	/** @var int hard ceiling on how many records one call may ask the Cloud for */
	const MAX_LIMIT = 50;

	public function __construct() {
		//WordPress < 6.9 has no Abilities API - stay completely out of the way
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( $this, 'registerCategories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'registerAbilities' ) );
	}

	/**
	 * Abilities must belong to a category, and the category must exist first.
	 */
	public function registerCategories() {
		wp_register_ability_category( self::CATEGORY, array(
			'label'       => esc_html__( 'Squirrly SEO', 'squirrly-seo' ),
			'description' => esc_html__( 'Read and manage SEO, AEO and GEO data handled by Squirrly SEO: per-page SEO, global settings, keywords, rankings and Focus Pages.', 'squirrly-seo' ),
		) );
	}

	/**
	 * Register every Squirrly ability.
	 */
	public function registerAbilities() {
		$this->registerSeoAbilities();
		$this->registerPatternAbilities();
		$this->registerSettingsAbilities();
		$this->registerCloudAbilities();
	}

	/**
	 * Per-page SEO. These are local and immediate.
	 */
	protected function registerSeoAbilities() {
		$target_schema = array(
			'post_id'  => array(
				'type'        => 'integer',
				'description' => 'ID of the post, page or custom post type.',
			),
			'term_id'  => array(
				'type'        => 'integer',
				'description' => 'ID of the taxonomy term. Requires taxonomy.',
			),
			'taxonomy' => array(
				'type'        => 'string',
				'description' => 'Taxonomy name, for example category or post_tag. Used with term_id.',
			),
			'url'      => array(
				'type'        => 'string',
				'description' => 'Full permalink of the page. Resolved to a post ID.',
			),
			'homepage' => array(
				'type'        => 'boolean',
				'description' => 'Set to true to target the site home page.',
			),
		);

		wp_register_ability( 'squirrly/get-seo', array(
			'label'               => esc_html__( 'Get page SEO', 'squirrly-seo' ),
			'description'         => 'Read the SEO that Squirrly stores for one page: title, meta description, keywords, canonical, robots flags, Open Graph, Twitter Card and JSON-LD. Identify the page with post_id, or term_id plus taxonomy, or url, or homepage. Returns "seo" (the values actually saved for this page, which is what update-seo would overwrite) and "computed" (what the page currently outputs). When "seo" fields are null and "computed" has values, this page is following an Automation pattern - "computed.source" names that pattern and flags any token in it that currently resolves to nothing. Diagnosing from "computed" alone will mislead you: a wrong title usually means a wrong pattern, shared by every page of that type, so read squirrly/get-patterns before deciding what to change.',
			'category'            => self::CATEGORY,
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => $target_schema,
				//without a default, core passes null when a client sends no input at all
				//and validation fails with "input is not of type object"
				'default'    => array(),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( $this, 'executeGetSeo' ),
			'permission_callback' => array( $this, 'canReadSeo' ),
			'meta'                => array(
				'public'       => true,
				//MCP servers read the nested key, not the flat one above: is_ability_mcp_public()
				//checks meta['mcp']['public'], so without this every Squirrly ability is filtered
				//out of discover-abilities and no MCP client can see it. 'annotations' below is
				//read flat, which is why the two sit at different depths.
				'mcp'          => array( 'public' => true ),
				'show_in_rest' => true,
				'annotations'  => array( 'readonly' => true, 'idempotent' => true ),
			),
		) );

		$update_properties = $target_schema;
		$update_properties['seo'] = array(
			'type'        => 'object',
			'description' => 'The fields to change. Only the fields you include are modified; everything else keeps its stored value. Send an empty string to clear a field.',
			'properties'  => array(
				'title'            => array( 'type' => 'string', 'description' => 'SEO title. Supports Squirrly patterns such as {{title}}, {{sitename}} and {{sep}}.' ),
				'description'      => array( 'type' => 'string', 'description' => 'Meta description.' ),
				'keywords'         => array( 'type' => 'string', 'description' => 'Comma separated keywords.' ),
				'canonical'        => array( 'type' => 'string', 'description' => 'Canonical URL. Must be a full URL or it is ignored.' ),
				'redirect'         => array( 'type' => 'string', 'description' => 'Redirect this page to another URL. Must be a full URL.' ),
				'noindex'          => array( 'type' => 'boolean', 'description' => 'Ask search engines not to index this page.' ),
				'nofollow'         => array( 'type' => 'boolean', 'description' => 'Ask search engines not to follow links on this page.' ),
				'nositemap'        => array( 'type' => 'boolean', 'description' => 'Exclude this page from the Squirrly sitemap.' ),
				'og_title'         => array( 'type' => 'string', 'description' => 'Open Graph title used by Facebook and LinkedIn.' ),
				'og_description'   => array( 'type' => 'string', 'description' => 'Open Graph description.' ),
				'og_type'          => array( 'type' => 'string', 'description' => 'Open Graph type, for example article or website.' ),
				'og_media'         => array( 'type' => 'string', 'description' => 'Open Graph image URL.' ),
				'tw_title'         => array( 'type' => 'string', 'description' => 'Twitter Card title.' ),
				'tw_description'   => array( 'type' => 'string', 'description' => 'Twitter Card description.' ),
				'tw_media'         => array( 'type' => 'string', 'description' => 'Twitter Card image URL.' ),
				'tw_type'          => array( 'type' => 'string', 'description' => 'Twitter Card type, for example summary or summary_large_image.' ),
				'jsonld_types'     => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'JSON-LD schema types for this page. Replacing this list removes the schema data of any type you leave out.',
				),
				'primary_category' => array( 'type' => 'string', 'description' => 'Primary category for this page.' ),
			),
		);

		wp_register_ability( 'squirrly/update-seo', array(
			'label'               => esc_html__( 'Update page SEO', 'squirrly-seo' ),
			'description'         => 'Change the SEO Squirrly stores for one page. Identify the page the same way as get-seo and pass the fields to change in "seo". This is a partial update: fields you omit keep their current value. Read the page with get-seo first so you know what is already set. Writing a field here pins a fixed value to this one page and detaches it from Automation, so the page stops adapting when its content or the site changes. Use it when this specific page genuinely needs wording of its own. When the problem is shared by pages of the same type - a separator left stranded by an empty token, a brand name that is wrong everywhere - the pattern is at fault, not the page: read squirrly/get-patterns and correct it there instead, and every page of that type follows.',
			'category'            => self::CATEGORY,
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => $update_properties,
				'required'   => array( 'seo' ),
				'default'    => array(),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( $this, 'executeUpdateSeo' ),
			'permission_callback' => array( $this, 'canWriteSeo' ),
			'meta'                => array(
				'public'       => true,
				//MCP servers read the nested key, not the flat one above: is_ability_mcp_public()
				//checks meta['mcp']['public'], so without this every Squirrly ability is filtered
				//out of discover-abilities and no MCP client can see it. 'annotations' below is
				//read flat, which is why the two sit at different depths.
				'mcp'          => array( 'public' => true ),
				'show_in_rest' => true,
				'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ),
			),
		) );
	}

	/**
	 * Global settings. Reads are broad, writes are limited to a curated list.
	 */
	/**
	 * Automation patterns. Read-only: one pattern drives every page of its type.
	 */
	protected function registerPatternAbilities() {
		wp_register_ability( 'squirrly/get-patterns', array(
			'label'               => esc_html__( 'Get Automation patterns', 'squirrly-seo' ),
			'description'         => 'Read Squirrly\'s Automation patterns - the templates that generate the title, meta description, Open Graph type and Schema types for every page of a given type, and keep them adapting as content changes. Pass "context" to read one (for example "home", "post", "page", "product", "category"), or omit it for all of them. Returns each pattern with the {{tokens}} it uses, the site-wide token values, "empty_tokens" naming any token that currently resolves to nothing and how to fix it, and "available_tokens" listing every token you may use. Read this before changing the SEO of an individual page: if a title or description is wrong on more than one page, the pattern is the cause and editing single pages will not fix it.',
			'category'            => self::CATEGORY,
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'context' => array(
						'type'        => 'string',
						'description' => 'One pattern context, for example "home", "post", "page", "product", "category". Omit to read every context.',
					),
				),
				//without a default, core passes null when a client sends no input at all
				'default'    => array(),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( $this, 'executeGetPatterns' ),
			'permission_callback' => array( $this, 'canReadSeo' ),
			'meta'                => array(
				'public'       => true,
				//MCP servers read the nested key, not the flat one above: is_ability_mcp_public()
				//checks meta['mcp']['public'], so without this every Squirrly ability is filtered
				//out of discover-abilities and no MCP client can see it. 'annotations' below is
				//read flat, which is why the two sit at different depths.
				'mcp'          => array( 'public' => true ),
				'show_in_rest' => true,
				'annotations'  => array( 'readonly' => true, 'idempotent' => true ),
			),
		) );
	}

	protected function registerSettingsAbilities() {
		wp_register_ability( 'squirrly/get-settings', array(
			'label'               => esc_html__( 'Get Squirrly settings', 'squirrly-seo' ),
			'description'         => 'Read the global Squirrly SEO settings for this site: which SEO, AEO and GEO features are switched on, sitemap and robots options, llms.txt generation, JSON-LD options and Automation patterns. Cloud credentials are never included.',
			'category'            => self::CATEGORY,
			'input_schema'        => array( 'type' => 'object', 'properties' => array(), 'default' => array() ),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( $this, 'executeGetSettings' ),
			'permission_callback' => array( $this, 'canManageSettings' ),
			'meta'                => array(
				'public'       => true,
				//MCP servers read the nested key, not the flat one above: is_ability_mcp_public()
				//checks meta['mcp']['public'], so without this every Squirrly ability is filtered
				//out of discover-abilities and no MCP client can see it. 'annotations' below is
				//read flat, which is why the two sit at different depths.
				'mcp'          => array( 'public' => true ),
				'show_in_rest' => true,
				'annotations'  => array( 'readonly' => true, 'idempotent' => true ),
			),
		) );

		//there is no spl_autoload_register in this plugin - the locator loads classes
		//on demand, so a class has to be pulled in before its static methods are used
		SQ_Classes_ObjController::getClass( 'SQ_Models_Api_Settings' );

		$writable = array_keys( SQ_Models_Api_Settings::writableKeys() );

		wp_register_ability( 'squirrly/update-settings', array(
			'label'               => esc_html__( 'Update Squirrly settings', 'squirrly-seo' ),
			'description'         => 'Switch Squirrly features on or off for the whole site. Only these settings can be changed here: ' . implode( ', ', $writable ) . '. Anything else you send is reported back in "skipped" and ignored. These changes affect every page on the site, so confirm with the user before calling this.',
			'category'            => self::CATEGORY,
			'input_schema'        => array(
				'type'                 => 'object',
				'description'          => 'A map of setting name to value. Feature switches take true or false.',
				'additionalProperties' => true,
				'default'              => array(),
			),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => array( $this, 'executeUpdateSettings' ),
			'permission_callback' => array( $this, 'canManageSettings' ),
			'meta'                => array(
				'public'       => true,
				//MCP servers read the nested key, not the flat one above: is_ability_mcp_public()
				//checks meta['mcp']['public'], so without this every Squirrly ability is filtered
				//out of discover-abilities and no MCP client can see it. 'annotations' below is
				//read flat, which is why the two sit at different depths.
				'mcp'          => array( 'public' => true ),
				'show_in_rest' => true,
				'annotations'  => array( 'readonly' => false, 'destructive' => false, 'idempotent' => true ),
			),
		) );
	}

	/**
	 * Briefcase, rankings, Focus Pages and Live Assistant tasks - each a Cloud round-trip.
	 * Read-only on purpose: the Cloud delete calls remove data a client cannot restore.
	 */
	protected function registerCloudAbilities() {
		$paging = array(
			'start' => array( 'type' => 'integer', 'description' => 'Offset to start from, for paging.' ),
			'limit' => array(
				'type'        => 'integer',
				'description' => 'How many records to return. Capped at ' . self::MAX_LIMIT . '.',
				'maximum'     => self::MAX_LIMIT,
			),
		);

		$cloud = array(
			array(
				'name'        => 'squirrly/get-briefcase',
				'label'       => esc_html__( 'Get Briefcase keywords', 'squirrly-seo' ),
				'method'      => 'getBriefcase',
				'cap'         => 'sq_manage_snippet',
				'schema'      => $paging,
				'description' => 'List the keywords saved in the Squirrly Briefcase for this site, with their labels and research data. Use this to find out which keywords the site is already targeting before suggesting new ones.',
			),
			array(
				'name'        => 'squirrly/get-ranks',
				'label'       => esc_html__( 'Get keyword rankings', 'squirrly-seo' ),
				'method'      => 'getRanks',
				'cap'         => 'sq_manage_focuspages',
				'schema'      => $paging,
				'description' => 'Read the current Google ranking positions Squirrly tracks for this site\'s keywords.',
			),
			array(
				'name'        => 'squirrly/get-focus-pages',
				'label'       => esc_html__( 'Get Focus Pages', 'squirrly-seo' ),
				'method'      => 'getFocusPages',
				'cap'         => 'sq_manage_focuspages',
				'schema'      => $paging,
				'description' => 'List the Focus Pages being tracked for this site along with their audit scores, so you can see which pages Squirrly considers most important and how they are performing.',
			),
			array(
				'name'        => 'squirrly/get-keyword-research-history',
				'label'       => esc_html__( 'Get keyword research history', 'squirrly-seo' ),
				'method'      => 'getKRHistory',
				'cap'         => 'sq_manage_snippet',
				'schema'      => $paging,
				'description' => 'List keyword researches already performed for this site. This reads past results only and does not start a new research, so it never consumes Squirrly credits.',
			),
			array(
				'name'        => 'squirrly/get-live-assistant-tasks',
				'label'       => esc_html__( 'Get Live Assistant tasks', 'squirrly-seo' ),
				'method'      => 'getSLATasks',
				'cap'         => 'sq_manage_snippet',
				'schema'      => array_merge( $paging, array(
					'keyword' => array( 'type' => 'string', 'description' => 'Keyword to get the optimization tasks for.' ),
				) ),
				'description' => 'Read the Squirrly Live Assistant optimization tasks for a keyword: the checklist Squirrly uses to score how well a page is optimized.',
			),
		);

		foreach ( $cloud as $ability ) {
			$this->registerCloudAbility( $ability );
		}
	}

	/**
	 * @param array $ability
	 */
	protected function registerCloudAbility( $ability ) {
		$method = $ability['method'];
		$cap    = $ability['cap'];
		$self   = $this;

		wp_register_ability( $ability['name'], array(
			'label'               => $ability['label'],
			'description'         => $ability['description'] . ' Requires the site to be connected to Squirrly Cloud.',
			'category'            => self::CATEGORY,
			'input_schema'        => array( 'type' => 'object', 'properties' => $ability['schema'], 'default' => array() ),
			'output_schema'       => array( 'type' => 'object' ),
			'execute_callback'    => function ( $input = array() ) use ( $self, $method ) {
				return $self->cloudCall( $method, $input );
			},
			'permission_callback' => function () use ( $cap ) {
				return (bool) SQ_Classes_Helpers_Tools::userCan( $cap );
			},
			'meta'                => array(
				'public'       => true,
				//MCP servers read the nested key, not the flat one above: is_ability_mcp_public()
				//checks meta['mcp']['public'], so without this every Squirrly ability is filtered
				//out of discover-abilities and no MCP client can see it. 'annotations' below is
				//read flat, which is why the two sit at different depths.
				'mcp'          => array( 'public' => true ),
				'show_in_rest' => true,
				'annotations'  => array( 'readonly' => true, 'idempotent' => true ),
			),
		) );
	}

	// ------------------------------------------------------------------ callbacks

	public function canReadSeo( $input = array() ) {
		return $this->canWriteSeo( $input );
	}

	public function canWriteSeo( $input = array() ) {
		$input   = (array) $input;
		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;

		//load before the static call - see the note in registerSettingsAbilities()
		SQ_Classes_ObjController::getClass( 'SQ_Models_Api_Seo' );

		return SQ_Models_Api_Seo::canEdit( $post_id );
	}

	public function canManageSettings() {
		return (bool) SQ_Classes_Helpers_Tools::userCan( 'sq_manage_settings' );
	}

	public function executeGetSeo( $input = array() ) {
		/** @var SQ_Models_Api_Seo $service */
		$service = SQ_Classes_ObjController::getClass( 'SQ_Models_Api_Seo' );

		return $service->getSeo( (array) $input );
	}

	public function executeUpdateSeo( $input = array() ) {
		$input = (array) $input;
		$seo   = isset( $input['seo'] ) ? (array) $input['seo'] : array();

		if ( empty( $seo ) ) {
			return new WP_Error( 'sq_no_fields', esc_html__( "No SEO fields to save.", 'squirrly-seo' ) );
		}

		/** @var SQ_Models_Api_Seo $service */
		$service = SQ_Classes_ObjController::getClass( 'SQ_Models_Api_Seo' );

		return $service->saveSeo( $input, $seo );
	}

	public function executeGetPatterns( $input = array() ) {
		$context = isset( $input['context'] ) ? (string) $input['context'] : '';

		return SQ_Classes_ObjController::getClass( 'SQ_Models_Api_Patterns' )->getPatterns( $context );
	}

	public function executeGetSettings() {
		/** @var SQ_Models_Api_Settings $service */
		$service = SQ_Classes_ObjController::getClass( 'SQ_Models_Api_Settings' );

		return $service->getSettings();
	}

	public function executeUpdateSettings( $input = array() ) {
		/** @var SQ_Models_Api_Settings $service */
		$service = SQ_Classes_ObjController::getClass( 'SQ_Models_Api_Settings' );

		return $service->saveSettings( (array) $input );
	}

	/**
	 * Call the Squirrly Cloud for a read-only ability. Cached briefly so an agent working
	 * through pages doesn't hammer it; RemoteController is loaded explicitly (admin-only wiring).
	 *
	 * @param string $method a read method on SQ_Classes_RemoteController
	 * @param array $input
	 *
	 * @return array|WP_Error
	 */
	public function cloudCall( $method, $input = array() ) {
		if ( ! SQ_Classes_Helpers_Tools::getOption( 'sq_api' ) ) {
			return new WP_Error(
				'sq_not_connected',
				esc_html__( "This site is not connected to Squirrly Cloud, so this data is not available. Connect the site from the Squirrly SEO dashboard first.", 'squirrly-seo' )
			);
		}

		$args = $this->sanitizeCloudArgs( $input );

		SQ_Classes_ObjController::getClass( 'SQ_Classes_RemoteController' );

		if ( ! is_callable( array( 'SQ_Classes_RemoteController', $method ) ) ) {
			return new WP_Error( 'sq_unavailable', esc_html__( "This feature is not available.", 'squirrly-seo' ) );
		}

		$key    = 'sq_ability_' . md5( $method . wp_json_encode( $args ) );
		$cached = get_transient( $key );

		if ( $cached !== false ) {
			return $cached;
		}

		$response = call_user_func( array( 'SQ_Classes_RemoteController', $method ), $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		//an empty response means "nothing set up yet", not an error
		$result = array(
			'results' => $response === false
				? array()
				: $this->sanitizeCloudResult( json_decode( wp_json_encode( $response ), true ) ),
		);

		set_transient( $key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Reduce a Cloud response to plain text before it leaves the site.
	 *
	 * Some Cloud endpoints answer with material meant for the WordPress admin screens rather
	 * than for a machine - the Live Assistant checklist arrives as markup with jQuery in it.
	 * An AI client gets nothing usable from that, and passing markup straight from a remote
	 * service into a model's context, and from there into whatever renders the answer, is an
	 * injection channel we should not open. Cloud output is treated as untrusted here for the
	 * same reason SQ_Classes_Helpers_Sanitize::cloudHtml() exists for admin notifications.
	 *
	 * Entities are decoded before tags are stripped, and the pair repeats until the string
	 * stops changing, so an encoded "&lt;script&gt;" cannot survive as markup.
	 *
	 * @param mixed $value Decoded Cloud response.
	 *
	 * @return mixed
	 */
	protected function sanitizeCloudResult( $value ) {

		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				$clean[ $key ] = $this->sanitizeCloudResult( $item );
			}

			return $clean;
		}

		if ( ! is_string( $value ) || $value === '' ) {
			return $value;
		}

		//script and style bodies carry no readable text, so drop them whole rather than
		//leaving their contents behind as loose words
		$value = preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $value );

		$previous = null;
		$passes   = 0;

		while ( $previous !== $value && $passes < 3 ) {
			$previous = $value;
			$value    = wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES, 'UTF-8' ) );
			$passes ++;
		}

		return trim( preg_replace( '/[ \t]+/', ' ', $value ) );
	}

	/**
	 * Keep whatever we forward to the Cloud small and well formed.
	 *
	 * @param array $input
	 *
	 * @return array
	 */
	protected function sanitizeCloudArgs( $input ) {
		$args = array();

		foreach ( (array) $input as $key => $value ) {
			$key = sanitize_key( $key );

			if ( $key === 'limit' ) {
				$value = (int) $value;
				$args[ $key ] = ( $value > 0 && $value <= self::MAX_LIMIT ) ? $value : self::MAX_LIMIT;
			} elseif ( $key === 'start' ) {
				$args[ $key ] = max( 0, (int) $value );
			} elseif ( is_scalar( $value ) ) {
				$args[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		if ( ! isset( $args['limit'] ) ) {
			$args['limit'] = self::MAX_LIMIT;
		}

		return $args;
	}

}
