<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Request-free service layer for Squirrly's global settings. The SQ_OPTION row is one JSON
 * blob holding Cloud credentials too, so reads strip deniedKeys() and writes use writableKeys().
 *
 * Class SQ_Models_Api_Settings
 */
class SQ_Models_Api_Settings {

	/**
	 * Keys that must never leave the site or be written from outside.
	 * Credentials, pairing state and internal bookkeeping.
	 *
	 * @return array
	 */
	public static function deniedKeys() {
		$denied = array(
			'sq_api',
			'sq_cloud_token',
			'sq_cloud_connect',
			'sq_version',
			'sq_installed',
			'sq_onboarding',
			'sq_onboarding_data',
			'sq_menu_visited',
			'sq_user_posts',
		);

		//the site key / uuid / origin used to sign Cloud requests
		if ( class_exists( 'SQ_Classes_Helpers_SiteAuth' ) ) {
			$denied = array_merge( $denied, SQ_Classes_Helpers_SiteAuth::authOptionKeys() );
		}

		return array_values( array_unique( $denied ) );
	}

	/**
	 * Settings an external client may change, mapped to the type each is cast to.
	 * Narrower than what can be read: Automation patterns stay read-only, they affect every page.
	 *
	 * @return array
	 */
	public static function writableKeys() {
		return array(
			//metas and titles
			'sq_auto_metas'              => 'bool',
			'sq_auto_title'              => 'bool',
			'sq_auto_description'        => 'bool',
			'sq_auto_keywords'           => 'bool',
			'sq_auto_canonical'          => 'bool',
			'sq_auto_pattern'            => 'bool',
			'sq_auto_noindex'            => 'bool',
			'sq_auto_dublincore'         => 'bool',

			//social
			'sq_auto_facebook'           => 'bool',
			'sq_auto_twitter'            => 'bool',

			//structured data
			'sq_auto_jsonld'             => 'bool',
			'sq_auto_jsonld_local'       => 'bool',
			'sq_jsonld_breadcrumbs'      => 'bool',

			//crawling and indexing
			'sq_auto_sitemap'            => 'bool',
			'sq_sitemap_ping'            => 'bool',
			'sq_sitemap_exclude_noindex' => 'bool',
			'sq_sitemap_perpage'         => 'int',
			'sq_auto_robots'             => 'bool',
			'sq_auto_feed'               => 'bool',
			'sq_auto_indexnow'           => 'bool',
			'sq_attachment_redirect'     => 'bool',
			'sq_term_noindex_empty'      => 'bool',

			//AI / AEO / GEO
			'sq_auto_llms'               => 'bool',

			//links
			'sq_auto_links'              => 'bool',
			'sq_auto_innelinks'          => 'bool',
			'sq_external_nofollow'       => 'bool',
			'sq_external_blank'          => 'bool',

			//other output
			'sq_auto_favicon'            => 'bool',
			'sq_auto_amp'                => 'bool',
			'sq_auto_redirects'          => 'bool',
		);
	}

	/**
	 * Read the settings, minus anything sensitive.
	 *
	 * @return array
	 */
	public function getSettings() {
		$options = SQ_Classes_Helpers_Tools::getOptions();

		if ( ! is_array( $options ) ) {
			return array();
		}

		foreach ( self::deniedKeys() as $key ) {
			unset( $options[ $key ] );
		}

		return $options;
	}

	/**
	 * Write one or more settings. Unknown or denied keys land in 'skipped' instead of
	 * failing the call, so a partly wrong payload still applies its valid part.
	 *
	 * @param array $values
	 *
	 * @return array|WP_Error
	 */
	public function saveSettings( $values ) {
		$values = (array) $values;

		if ( empty( $values ) ) {
			return new WP_Error( 'sq_settings_empty', esc_html__( "No settings to save.", 'squirrly-seo' ) );
		}

		if ( ! SQ_Classes_Helpers_Tools::userCan( 'sq_manage_settings' ) ) {
			return new WP_Error( 'sq_forbidden', esc_html__( "You do not have permission to perform this action", 'squirrly-seo' ) );
		}

		$writable = self::writableKeys();
		$denied   = self::deniedKeys();

		//hydrate the options cache first - saveOptions() persists whatever is in it,
		//so writing without a prior read would drop every unloaded setting
		SQ_Classes_Helpers_Tools::getOption( 'sq_version' );

		$saved   = array();
		$skipped = array();

		foreach ( $values as $key => $value ) {
			if ( ! isset( $writable[ $key ] ) || in_array( $key, $denied ) ) {
				$skipped[] = $key;
				continue;
			}

			$clean = $this->sanitizeValue( $writable[ $key ], $value );

			SQ_Classes_Helpers_Tools::saveOptions( $key, $clean );
			$saved[ $key ] = $clean;
		}

		if ( empty( $saved ) ) {
			return new WP_Error(
				'sq_settings_unknown',
				esc_html__( "None of the settings sent can be changed here.", 'squirrly-seo' ),
				array( 'skipped' => $skipped )
			);
		}

		do_action( 'sq_settings_saved', $saved );

		return array(
			'saved'   => $saved,
			'skipped' => $skipped,
		);
	}

	/**
	 * @param string $type
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected function sanitizeValue( $type, $value ) {
		switch ( $type ) {
			case 'bool':
				return (int) (bool) $value;

			case 'int':
				return (int) $value;

			case 'text':
			default:
				return is_string( $value ) ? sanitize_text_field( $value ) : '';
		}
	}

}
