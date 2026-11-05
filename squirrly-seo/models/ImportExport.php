<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class SQ_Models_ImportExport {

	public function __construct() {
		add_filter( 'sq_themes', array( $this, 'getAvailableThemes' ), 10, 1 );
		add_filter( 'sq_importList', array( $this, 'importList' ) );
	}

	public function importList() {
		if ( $list = SQ_Classes_Helpers_Tools::getOption( 'importList' ) ) {
			return $list;
		}

		$themes = array(
			'builder'      => array(
				'title'        => '_builder_seo_title',
				'description' => '_builder_seo_description',
				'keywords'     => '_builder_seo_keywords',
			),
			'catalyst'     => array(
				'title'        => '_catalyst_title',
				'description' => '_catalyst_description',
				'keywords'     => '_catalyst_keywords',
				'noindex'      => '_catalyst_noindex',
				'nofollow'     => '_catalyst_nofollow',
				'noarchive'    => '_catalyst_noarchive',
			),
			'frugal'       => array(
				'title'        => '_title',
				'description' => '_description',
				'keywords'     => '_keywords',
				'noindex'      => '_noindex',
				'nofollow'     => '_nofollow',
			),
			'genesis'      => array(
				'title'        => '_genesis_title',
				'description' => '_genesis_description',
				'keywords'     => '_genesis_keywords',
				'noindex'      => '_genesis_noindex',
				'nofollow'     => '_genesis_nofollow',
				'noarchive'    => '_genesis_noarchive',
				'canonical'    => '_genesis_canonical_uri',
				'redirect'     => 'redirect',
			),
			'headway'      => array(
				'title'        => '_title',
				'description' => '_description',
				'keywords'     => '_keywords',
			),
			'hybrid'       => array(
				'title'        => 'Title',
				'description' => 'Description',
				'keywords'     => 'Keywords',
			),
			'thesis'       => array(
				'title'       => 'thesis_title',
				'description' => 'thesis_description',
				'keywords'    => 'thesis_keywords',
				'redirect'    => 'thesis_redirect',
			),
			'wooframework' => array(
				'title'       => 'seo_title',
				'description' => 'seo_description',
				'keywords'    => 'seo_keywords',
			),
		);

		$plugins = array(
			'add-meta-tags'              => array(
				'title'       => '_amt_title',
				'description' => '_amt_description',
				'keywords'    => '_amt_keywords',
			),
			'gregs-high-performance-seo' => array(
				'title'       => '_ghpseo_secondary_title',
				'description' => '_ghpseo_alternative_description',
				'keywords'    => '_ghpseo_keywords',
			),
			'headspace2'                 => array(
				'title'       => '_headspace_page_title',
				'description' => '_headspace_description',
				'keywords'    => '_headspace_keywords',
			),
			'platinum-seo-pack'          => array(
				'title'       => 'title',
				'description' => 'description',
				'keywords'    => 'keywords',
			),
			'seo-pressor'                => array(
				'title'       => '_seopressor_meta_title',
				'description' => '_seopressor_meta_description',
			),
			'wp-seopress'                => array(
				'title'          => '_seopress_titles_title',
				'description'    => '_seopress_titles_desc',
				'keywords'       => '_seopress_analysis_target_kw',
				'canonical'      => '_seopress_robots_canonical',
				'og_title'       => '_seopress_social_fb_title',
				'og_description' => '_seopress_social_fb_desc',
				'og_media'       => '_seopress_social_fb_img',
				'tw_title'       => '_seopress_social_twitter_title',
				'tw_description' => '_seopress_social_twitter_desc',
				'tw_media'       => '_seopress_social_twitter_img',
				'redirect'       => '_seopress_redirections_value',
				'redirect_type'  => '_seopress_redirections_type',
				'noindex'        => '_seopress_robots_index',
				'nofollow'       => '_seopress_robots_follow',
			),
			'seo-title-tag'              => array(
				//keys are Squirrly's field names, like every other platform (the labels used before were never read)
				'title'       => 'title_tag',
				'description' => 'meta_description',
			),
			'seo-ultimate'               => array(
				'title'       => '_su_title',
				'description' => '_su_description',
				'keywords'    => '_su_keywords',
				'noindex'     => '_su_meta_robots_noindex',
				'nofollow'    => '_su_meta_robots_nofollow',
			),
			'seo-by-rank-math'           => array(
				'title'          => 'rank_math_title',
				'description'    => 'rank_math_description',
				'keywords'       => 'rank_math_focus_keyword',
				'canonical'      => 'rank_math_canonical_url',
				'og_title'       => 'rank_math_facebook_title',
				'og_description' => 'rank_math_facebook_description',
				'og_media'       => 'rank_math_facebook_image',
				'tw_title'       => 'rank_math_twitter_title',
				'tw_description' => 'rank_math_twitter_description',
				'tw_media'       => 'rank_math_twitter_image',
				'robots'         => 'rank_math_robots'
			),
			'wordpress-seo'              => array(
				'title'            => '_yoast_wpseo_title',
				'description'      => '_yoast_wpseo_metadesc',
				'keywords'         => '_yoast_wpseo_focuskw',
				'noindex'          => '_yoast_wpseo_meta-robots-noindex',
				'nofollow'         => '_yoast_wpseo_meta-robots-nofollow',
				'robots'           => '_yoast_wpseo_meta-robots-adv',
				'canonical'        => '_yoast_wpseo_canonical',
				'redirect'         => '_yoast_wpseo_redirect',
				'focuspage'        => 'yst_is_cornerstone',
				'og_title'         => '_yoast_wpseo_opengraph-title',
				'og_description'   => '_yoast_wpseo_opengraph-description',
				'og_media'         => '_yoast_wpseo_opengraph-image',
				'tw_title'         => '_yoast_wpseo_twitter-title',
				'tw_description'   => '_yoast_wpseo_twitter-description',
				'tw_media'         => '_yoast_wpseo_twitter-image',
				'primary_category' => '_yoast_wpseo_primary_category',
			),
			'all-in-one-seo-pack'        => array(
				'title'          => '_aioseo_title',
				'description'    => '_aioseo_description',
				'keywords'       => '_aioseo_keywords',
				'noindex'        => '_aioseo_noindex',
				'nofollow'       => '_aioseo_nofollow',
				'canonical'      => '_aioseo_custom_link',
				'og_title'       => '_aioseo_og_title',
				'og_description' => '_aioseo_og_description',
				'tw_title'       => '_aioseo_twitter_title',
				'tw_description' => '_aioseo_twitter_description',
			),
			'autodescription'            => array(
				'title'          => '_genesis_title',
				'description'    => '_genesis_description',
				'noindex'        => '_genesis_noindex',
				'nofollow'       => '_genesis_nofollow',
				'canonical'      => '_genesis_canonical_uri',
				'og_title'       => '_open_graph_title',
				'og_description' => '_open_graph_description',
				'og_media'       => '_social_image_url',
				'tw_title'       => '_twitter_title',
				'tw_description' => '_twitter_description',
				'redirect'       => 'redirect',
				'redirect_type'  => '301',
			),
		);
		$themes  = apply_filters( 'sq_themes', $themes );
		$plugins = apply_filters( 'sq_plugins', $plugins );

		$list = array_merge( (array) $plugins, (array) $themes );

		return $list;
	}

	/**
	 * Get the actual name of the plugin/theme
	 *
	 * @param  $path
	 *
	 * @return string
	 */
	public function getName( $path ) {
		switch ( $path ) {
			case 'wordpress-seo':
				return 'Yoast SEO';
			case 'wp-seopress':
				return 'SEO Press';
			case 'seo-by-rank-math':
				return 'Rank Math';
			case 'autodescription':
				return 'SEO Framework';
			default:
				return ucwords( str_replace( '-', ' ', $path ) );
		}
	}


	/**
	 * Rename all the plugin names with a hash
	 */
	public function getAvailablePlugins( $plugins ) {
		$found = array();

		$all_plugins = (array) get_option( 'active_plugins', array() );
		$plugins     = array_keys( $plugins );

		if ( is_multisite() ) {
			$all_plugins = array_merge( $all_plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins' ) ) );
		}

		foreach ( $all_plugins as $plugin ) {
			if ( strpos( $plugin, '/' ) !== false ) {
				$plugin = substr( $plugin, 0, strpos( $plugin, '/' ) );
			}

			if ( in_array( $plugin, $plugins ) ) {
				$found[ $plugin ] = $plugin;
			}
		}

		return $found;
	}

	public function getActivePlugins( $plugins ) {
		$found = array();

		$all_plugins = get_option( 'active_plugins' );

		foreach ( $all_plugins as $plugin ) {
			if ( strpos( $plugin, '/' ) !== false ) {
				$plugin = substr( $plugin, 0, strpos( $plugin, '/' ) );
			}
			if ( isset( $plugins[ $plugin ] ) ) {
				$found[ $plugin ] = $plugins[ $plugin ];
			}
		}

		return $found;
	}

	/**
	 * Rename all the themes name with a hash
	 */
	public function getAvailableThemes( $themes ) {
		$found = array();

		$all_themes = search_theme_directories();

		foreach ( $all_themes as $theme => $value ) {
			if ( isset( $themes[ $theme ] ) ) {
				$found[] = $themes[ $theme ];
			}
		}

		return $found;
	}

	/**
	 * @param  $platform
	 *
	 * @return boolean
	 */
	public function importDBSettings( $platform ) {
		$imported  = false;
		$platforms = apply_filters( 'sq_importList', false );
		if ( $platform <> '' && isset( $platforms[ $platform ] ) ) {

			if ( $platform == 'wordpress-seo' ) {

				if ( $yoast_socials = get_option( 'wpseo_social' ) ) {
					$socials = SQ_Classes_Helpers_Tools::getOption( 'socials' );
					$codes   = SQ_Classes_Helpers_Tools::getOption( 'codes' );
					foreach ( $yoast_socials as $key => $yoast_social ) {
						if ( $yoast_social <> '' && isset( $socials[ $key ] ) ) {
							$socials[ $key ] = $yoast_social;
						}
					}
					if ( ! empty( $socials ) ) {
						if ( isset( $yoast_socials['plus-publisher'] ) && $yoast_socials['plus-publisher'] <> '' ) {
							$socials['plus_publisher'] = $yoast_socials['plus-publisher'];
						}
						if ( isset( $yoast_socials['pinterestverify'] ) && $yoast_socials['pinterestverify'] <> '' ) {
							$codes['pinterest_verify'] = $yoast_socials['pinterestverify'];
						}
						SQ_Classes_Helpers_Tools::saveOptions( 'socials', $socials );
						SQ_Classes_Helpers_Tools::saveOptions( 'codes', $codes );
						$imported = true;
					}
				}

				if ( $yoast_titles = get_option( 'wpseo_titles' ) ) {
					$jsonld = SQ_Classes_Helpers_Tools::getOption( 'sq_jsonld' );
					if ( isset( $yoast_titles['company_logo'] ) && $yoast_titles['company_logo'] <> '' ) {
						$jsonld['Organization']['logo'] = $yoast_titles['company_logo'];
					}
					if ( isset( $yoast_titles['person_logo'] ) && $yoast_titles['person_logo'] <> '' ) {
						$jsonld['Person']['image']['url'] = $yoast_titles['person_logo'];
					}
					if ( isset( $yoast_titles['company_name'] ) && $yoast_titles['company_name'] <> '' ) {
						$jsonld['Organization']['name'] = $yoast_titles['company_name'];
					}
					if ( isset( $yoast_titles['company_or_person'] ) && $yoast_titles['company_or_person'] == 'person' ) {
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_jsonld_type', 'Person' );
						if ( isset( $yoast_titles['company_or_person_user_id'] ) && (int) $yoast_titles['company_or_person_user_id'] > 0 ) {
							$user = get_userdata( (int) $yoast_titles['company_or_person_user_id'] );
							if ( $user && isset( $user->display_name ) ) {
								$jsonld['Person']['name'] = $user->display_name;
							}
						}
					}
					SQ_Classes_Helpers_Tools::saveOptions( 'sq_jsonld', $jsonld );

				}

				if ( $yoast_codes = get_option( 'wpseo' ) ) {
					$codes = SQ_Classes_Helpers_Tools::getOption( 'codes' );
					if ( ! empty( $codes ) ) {
						if ( isset( $yoast_codes['msverify'] ) && $yoast_codes['msverify'] <> '' ) {
							$codes['bing_wt'] = $yoast_codes['msverify'];
						}
						if ( isset( $yoast_codes['googleverify'] ) && $yoast_codes['googleverify'] <> '' ) {
							$codes['google_wt'] = $yoast_codes['googleverify'];
						}
						SQ_Classes_Helpers_Tools::saveOptions( 'codes', $codes );
						$imported = true;
					}
				}
			}

			if ( $platform == 'all-in-one-seo-pack' ) {
				if ( $options = get_option( 'aioseo_options' ) ) {
					$options = json_decode( $options, true );

					$socials = SQ_Classes_Helpers_Tools::getOption( 'socials' );
					$codes   = SQ_Classes_Helpers_Tools::getOption( 'codes' );

					if ( isset( $options['social']['profiles']['urls']['facebookPageUrl'] ) && $options['social']['profiles']['urls']['facebookPageUrl'] <> '' ) {
						$socials['facebook_site'] = $options['social']['profiles']['urls']['facebookPageUrl'];
					}
					if ( isset( $options['social']['profiles']['urls']['twitterUrl'] ) && $options['social']['profiles']['urls']['twitterUrl'] <> '' ) {
						$socials['twitter_site'] = $options['social']['profiles']['urls']['twitterUrl'];
					}
					if ( isset( $options['social']['profiles']['urls']['instagramUrl'] ) && $options['social']['profiles']['urls']['instagramUrl'] <> '' ) {
						$socials['instagram_url'] = $options['social']['profiles']['urls']['instagramUrl'];
					}
					if ( isset( $options['social']['profiles']['urls']['pinterestUrl'] ) && $options['social']['profiles']['urls']['pinterestUrl'] <> '' ) {
						$socials['pinterest_url'] = $options['social']['profiles']['urls']['pinterestUrl'];
					}
					if ( isset( $options['social']['profiles']['urls']['youtubeUrl'] ) && $options['social']['profiles']['urls']['youtubeUrl'] <> '' ) {
						$socials['youtube_url'] = $options['social']['profiles']['urls']['youtubeUrl'];
					}
					if ( isset( $options['social']['profiles']['urls']['linkedinUrl'] ) && $options['social']['profiles']['urls']['linkedinUrl'] <> '' ) {
						$socials['linkedin_url'] = $options['social']['profiles']['urls']['linkedinUrl'];
					}
					if ( isset( $options['social']['profiles']['urls']['myspace_url'] ) && $options['social']['profiles']['urls']['myspace_url'] <> '' ) {
						$socials['myspaceUrl'] = $options['social']['profiles']['urls']['myspace_url'];
					}

					if ( isset( $options['social']['facebook']['general']['defaultImagePosts'] ) && $options['social']['facebook']['general']['defaultImagePosts'] <> '' ) {
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_og_image', $options['social']['facebook']['general']['defaultImagePosts'] );
					}
					if ( isset( $options['social']['twitter']['general']['defaultImagePosts'] ) && $options['social']['twitter']['general']['defaultImagePosts'] <> '' ) {
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_og_image', $options['social']['twitter']['general']['defaultImagePosts'] );
					}

					$jsonld = SQ_Classes_Helpers_Tools::getOption( 'sq_jsonld' );

					if ( isset( $options['searchAppearance']['global']['schema']['organizationName'] ) && $options['searchAppearance']['global']['schema']['organizationName'] <> '' ) {
						$jsonld['Organization']['name'] = $options['searchAppearance']['global']['schema']['organizationName'];
					}
					if ( isset( $options['searchAppearance']['global']['schema']['organizationLogo'] ) && $options['searchAppearance']['global']['schema']['organizationLogo'] <> '' ) {
						$jsonld['Organization']['logo'] = $options['searchAppearance']['global']['schema']['organizationLogo'];
					}
					if ( isset( $options['searchAppearance']['global']['schema']['phone'] ) && $options['searchAppearance']['global']['schema']['phone'] <> '' ) {
						$jsonld['Organization']['contactPoint']['telephone'] = $options['searchAppearance']['global']['schema']['phone'];
					}
					if ( isset( $options['searchAppearance']['global']['schema']['contactType'] ) && $options['searchAppearance']['global']['schema']['contactType'] <> '' ) {
						$jsonld['Organization']['contactPoint']['contactType'] = $options['searchAppearance']['global']['schema']['contactType'];
					}
					if ( isset( $options['searchAppearance']['global']['schema']['personName'] ) && $options['searchAppearance']['global']['schema']['personName'] <> '' ) {
						$jsonld['Person']['name'] = $options['searchAppearance']['global']['schema']['personName'];
					}
					if ( isset( $options['searchAppearance']['global']['schema']['personLogo'] ) && $options['searchAppearance']['global']['schema']['personLogo'] <> '' ) {
						$jsonld['Person']['image']['url'] = $options['searchAppearance']['global']['schema']['personLogo'];
					}

					if ( isset( $options['webmasterTools']['bing'] ) && $options['webmasterTools']['bing'] <> '' ) {
						$codes['bing_wt'] = $options['webmasterTools']['bing'];
					}
					if ( isset( $options['webmasterTools']['pinterest'] ) && $options['webmasterTools']['pinterest'] <> '' ) {
						$codes['pinterest_verify'] = $options['webmasterTools']['pinterest'];
					}
					if ( isset( $options['webmasterTools']['google'] ) && $options['webmasterTools']['google'] <> '' ) {
						//AIOSEO's Google value is the Search Console verification code, not an Analytics ID
						$codes['google_wt'] = $options['webmasterTools']['google'];
					}
					if ( isset( $options['webmasterTools']['yandex'] ) && $options['webmasterTools']['yandex'] <> '' ) {
						$codes['yandex_wt'] = $options['webmasterTools']['yandex'];
					}
					if ( isset( $options['webmasterTools']['baidu'] ) && $options['webmasterTools']['baidu'] <> '' ) {
						$codes['baidu_wt'] = $options['webmasterTools']['baidu'];
					}
					if ( isset( $options['webmasterTools']['norton'] ) && $options['webmasterTools']['norton'] <> '' ) {
						$codes['norton_verify'] = $options['webmasterTools']['norton'];
					}

					SQ_Classes_Helpers_Tools::saveOptions( 'socials', $socials );
					SQ_Classes_Helpers_Tools::saveOptions( 'codes', $codes );
					SQ_Classes_Helpers_Tools::saveOptions( 'sq_jsonld', $jsonld );

					$imported = true;
				}
			}

			if ( $platform == 'seo-by-rank-math' ) {

				if ( $options = get_option( 'rank-math-options-general' ) ) {
					$codes = SQ_Classes_Helpers_Tools::getOption( 'codes' );

					if ( isset( $options['attachment_redirect_urls'] ) ) {
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_attachment_redirect', ( $options['attachment_redirect_urls'] == 'on' ) );
					}

					if ( isset( $options['nofollow_external_links'] ) ) {
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_external_nofollow', ( $options['nofollow_external_links'] == 'on' ) );
					}

					if ( isset( $options['new_window_external_links'] ) ) {
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_external_blank', ( $options['new_window_external_links'] == 'on' ) );
					}

					if ( isset( $options['breadcrumbs'] ) ) {
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_jsonld_breadcrumbs', ( $options['breadcrumbs'] == 'on' ) );
					}

					if ( isset( $options['product_brand'] ) ) {
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_jsonld_product_custom', ( $options['product_brand'] == 'on' ) );
					}

					if ( isset( $options['google_verify'] ) && $options['google_verify'] <> '' ) {
						$codes['google_wt'] = $options['google_verify'];
					}
					if ( isset( $options['bing_verify'] ) && $options['bing_verify'] <> '' ) {
						$codes['bing_wt'] = $options['bing_verify'];
					}
					if ( isset( $options['pinterest_verify'] ) && $options['pinterest_verify'] <> '' ) {
						$codes['pinterest_verify'] = $options['pinterest_verify'];
					}
					if ( isset( $options['yandex_verify'] ) && $options['yandex_verify'] <> '' ) {
						$codes['yandex_wt'] = $options['yandex_verify'];
					}
					if ( isset( $options['baidu_verify'] ) && $options['baidu_verify'] <> '' ) {
						$codes['baidu_wt'] = $options['baidu_verify'];
					}
					if ( isset( $options['norton_verify'] ) && $options['norton_verify'] <> '' ) {
						$codes['norton_verify'] = $options['norton_verify'];
					}

					SQ_Classes_Helpers_Tools::saveOptions( 'codes', $codes );

					$imported = true;
				}

				if ( $options = get_option( 'rank-math-options-titles' ) ) {
					$jsonld  = SQ_Classes_Helpers_Tools::getOption( 'sq_jsonld' );
					$socials = SQ_Classes_Helpers_Tools::getOption( 'socials' );

					if ( isset( $options['price_range'] ) && $options['price_range'] <> '' ) {
						$jsonld['sq_jsonld_local']['priceRange'] = $options['price_range'];
					}
					if ( isset( $options['local_address']['streetAddress'] ) && $options['local_address']['streetAddress'] <> '' ) {
						$jsonld['Organization']['address']['streetAddress'] = $options['local_address']['streetAddress'];
					}
					if ( isset( $options['local_address']['addressLocality'] ) && $options['local_address']['addressLocality'] <> '' ) {
						$jsonld['Organization']['address']['addressLocality'] = $options['local_address']['addressLocality'];
					}
					if ( isset( $options['local_address']['addressRegion'] ) && $options['local_address']['addressRegion'] <> '' ) {
						$jsonld['Organization']['address']['addressRegion'] = $options['local_address']['addressRegion'];
					}
					if ( isset( $options['local_address']['addressCountry'] ) && $options['local_address']['addressCountry'] <> '' ) {
						$jsonld['Organization']['address']['addressCountry'] = $options['local_address']['addressCountry'];
					}
					if ( isset( $options['local_address']['postalCode'] ) && $options['local_address']['postalCode'] <> '' ) {
						$jsonld['Organization']['address']['postalCode'] = $options['local_address']['postalCode'];
					}
					if ( isset( $options['knowledgegraph_logo'] ) && $options['knowledgegraph_logo'] <> '' ) {
						$jsonld['Organization']['logo'] = $options['knowledgegraph_logo'];
					}
					if ( isset( $options['knowledgegraph_name'] ) && $options['knowledgegraph_name'] <> '' ) {
						$jsonld['Organization']['name'] = $options['knowledgegraph_name'];
					}


					if ( isset( $options['social_url_facebook'] ) && $options['social_url_facebook'] <> '' ) {
						$socials['facebook_site'] = $options['social_url_facebook'];
					}
					if ( isset( $options['twitter_author_names'] ) && $options['twitter_author_names'] <> '' ) {
						$socials['twitter_site'] = $options['twitter_author_names'];
					}
					if ( isset( $options['facebook_admin_id'] ) && $options['facebook_admin_id'] <> '' ) {
						$socials['fb_admins'] = array( $options['facebook_admin_id'] );
					}
					if ( isset( $options['facebook_app_id'] ) && $options['facebook_app_id'] <> '' ) {
						$socials['fbadminapp'] = $options['facebook_app_id'];
					}

					SQ_Classes_Helpers_Tools::saveOptions( 'sq_jsonld', $jsonld );
					SQ_Classes_Helpers_Tools::saveOptions( 'socials', $socials );

				}


			}

			if ( $platform == 'autodescription' ) {
				if ( $options = get_option( 'autodescription-site-settings' ) ) {
					$jsonld  = SQ_Classes_Helpers_Tools::getOption( 'sq_jsonld' );
					$socials = SQ_Classes_Helpers_Tools::getOption( 'socials' );
					$codes   = SQ_Classes_Helpers_Tools::getOption( 'codes' );

					if ( isset( $options['attachment_redirect_urls'] ) ) {
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_attachment_redirect', ( $options['attachment_redirect_urls'] == 'on' ) );
					}

					if ( isset( $options['facebook_appid'] ) && $options['facebook_appid'] <> '' ) {
						$socials['fbadminapp'] = $options['facebook_appid'];
					}
					if ( isset( $options['facebook_publisher'] ) && $options['facebook_publisher'] <> '' ) {
						$socials['facebook_site'] = $options['facebook_publisher'];
					}
					if ( isset( $options['twitter_site'] ) && $options['twitter_site'] <> '' ) {
						$socials['twitter_site'] = $options['twitter_site'];
					}

					if ( isset( $options['knowledge_name'] ) && $options['knowledge_name'] <> '' ) {
						$jsonld['Organization']['name'] = $options['knowledge_name'];
					}
					if ( isset( $options['knowledge_logo_url'] ) && $options['knowledge_logo_url'] <> '' ) {
						$jsonld['Organization']['logo'] = $options['knowledge_logo_url'];
					}

					if ( isset( $options['google_verification'] ) && $options['google_verification'] <> '' ) {
						$codes['google_wt'] = $options['google_verification'];
					}
					if ( isset( $options['bing_verification'] ) && $options['bing_verification'] <> '' ) {
						$codes['bing_wt'] = $options['bing_verification'];
					}
					if ( isset( $options['pint_verification'] ) && $options['pint_verification'] <> '' ) {
						$codes['pinterest_verify'] = $options['pint_verification'];
					}

					SQ_Classes_Helpers_Tools::saveOptions( 'codes', $codes );
					SQ_Classes_Helpers_Tools::saveOptions( 'socials', $socials );
					SQ_Classes_Helpers_Tools::saveOptions( 'sq_jsonld', $jsonld );

					$imported = true;
				}
			}

			if ( $platform == 'premium-seo-pack' ) {
				if ( $options = json_decode( get_option( '_psp_options' ), true ) ) {
					$socials = $options['socials'];
					$codes   = $options['codes'];
					$jsonld  = $options['psp_jsonld'];

					SQ_Classes_Helpers_Tools::saveOptions( 'socials', $socials );
					SQ_Classes_Helpers_Tools::saveOptions( 'codes', $codes );
					SQ_Classes_Helpers_Tools::saveOptions( 'sq_jsonld', $jsonld );

					$imported = true;
				}
			}

			if ( $platform == 'wp-seopress' ) {
				if ( $options = get_option( 'seopress_social_option_name' ) ) {

					$jsonld  = SQ_Classes_Helpers_Tools::getOption( 'sq_jsonld' );
					$socials = SQ_Classes_Helpers_Tools::getOption( 'socials' );


					if ( isset( $options['seopress_social_knowledge_name'] ) && $options['seopress_social_knowledge_name'] <> '' ) {
						$jsonld['Organization']['name'] = $options['seopress_social_knowledge_name'];
					}
					if ( isset( $options['seopress_social_knowledge_img'] ) && $options['seopress_social_knowledge_img'] <> '' ) {
						$jsonld['Organization']['logo'] = $options['seopress_social_knowledge_img'];
					}
					if ( isset( $options['seopress_social_knowledge_phone'] ) && $options['seopress_social_knowledge_phone'] <> '' ) {
						$jsonld['Organization']['contactPoint']['telephone'] = $options['seopress_social_knowledge_phone'];
					}
					if ( isset( $options['seopress_social_knowledge_contact_type'] ) && $options['seopress_social_knowledge_contact_type'] <> '' ) {
						$jsonld['Organization']['contactPoint']['contactType'] = $options['seopress_social_knowledge_contact_type'];
					}

					if ( isset( $options['seopress_social_accounts_facebook'] ) && $options['seopress_social_accounts_facebook'] <> '' ) {
						$socials['facebook_site'] = $options['seopress_social_accounts_facebook'];
					}
					if ( isset( $options['seopress_social_accounts_twitter'] ) && $options['seopress_social_accounts_twitter'] <> '' ) {
						$socials['twitter_site'] = $options['seopress_social_accounts_twitter'];
					}
					if ( isset( $options['seopress_social_accounts_pinterest'] ) && $options['seopress_social_accounts_pinterest'] <> '' ) {
						$socials['pinterest_url'] = $options['seopress_social_accounts_pinterest'];
					}
					if ( isset( $options['seopress_social_accounts_instagram'] ) && $options['seopress_social_accounts_instagram'] <> '' ) {
						$socials['instagram_url'] = $options['seopress_social_accounts_instagram'];
					}
					if ( isset( $options['seopress_social_accounts_youtube'] ) && $options['seopress_social_accounts_youtube'] <> '' ) {
						$socials['youtube_url'] = $options['seopress_social_accounts_youtube'];
					}
					if ( isset( $options['seopress_social_accounts_linkedin'] ) && $options['seopress_social_accounts_linkedin'] <> '' ) {
						$socials['linkedin_url'] = $options['seopress_social_accounts_linkedin'];
					}


					if ( isset( $options['seopress_social_facebook_img'] ) && $options['seopress_social_facebook_img'] <> '' ) {
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_og_image', $options['seopress_social_facebook_img'] );
					}

					if ( isset( $options['seopress_social_twitter_card_img'] ) && $options['seopress_social_twitter_card_img'] <> '' ) {
						SQ_Classes_Helpers_Tools::saveOptions( 'sq_tc_image', $options['seopress_social_twitter_card_img'] );
					}

					if ( isset( $options['seopress_social_facebook_admin_id'] ) && $options['seopress_social_facebook_admin_id'] <> '' ) {
						$socials['fb_admins'] = array( $options['seopress_social_facebook_admin_id'] );
					}
					if ( isset( $options['seopress_social_facebook_app_id'] ) && $options['seopress_social_facebook_app_id'] <> '' ) {
						$socials['fbadminapp'] = $options['seopress_social_facebook_app_id'];
					}

					SQ_Classes_Helpers_Tools::saveOptions( 'socials', $socials );
					SQ_Classes_Helpers_Tools::saveOptions( 'sq_jsonld', $jsonld );

					$imported = true;
				}

				if ( $options = get_option( 'seopress_google_analytics_option_name' ) ) {
					$codes = SQ_Classes_Helpers_Tools::getOption( 'codes' );

					if ( isset( $options['seopress_google_analytics_ua'] ) && $options['seopress_google_analytics_ua'] <> '' ) {
						$codes['google_analytics'] = $options['seopress_google_analytics_ua'];
					}
					if ( isset( $options['seopress_google_analytics_ga4'] ) && $options['seopress_google_analytics_ga4'] <> '' ) {
						$codes['google_analytics'] = $options['seopress_google_analytics_ga4'];
					}

					SQ_Classes_Helpers_Tools::saveOptions( 'codes', $codes );

				}
			}

		}

		return $imported;
	}

	public function importDBSeo( $platform ) {
		global $wpdb;

		$platforms = apply_filters( 'sq_importList', false );
		if ( $platform <> '' && isset( $platforms[ $platform ] ) ) {
			$meta_keys = $platforms[ $platform ];
			$metas     = array();

			if ( ! empty( $meta_keys ) ) {
				$placeholders = array_fill( 0, count( $meta_keys ), '%s' );

				$meta_keys = array_flip( $meta_keys );

				$query = "SELECT * FROM `$wpdb->postmeta` WHERE meta_key IN (" . join( ",", $placeholders ) . ");";
				if ( $rows = $wpdb->get_results( $wpdb->prepare( $query, array_keys( $meta_keys ) ), OBJECT ) ) {
					foreach ( $rows as $row ) {

						if ( isset( $meta_keys[ $row->meta_key ] ) && $row->meta_value <> '' ) {
							if ( $post = get_post( $row->post_id ) ) {

								//set the hash for each post type
								if ( in_array( $post->post_type, array(
									'post',
									'page',
									'product',
									'cartflows_step'
								) ) ) {
									$hash = md5( $post->ID );
								} else {
									$hash = md5( $post->post_type . $post->ID );
								}

								$metas[ $hash ]['post_id']   = $post->ID;
								$metas[ $hash ]['post_type'] = $post->post_type;
								$metas[ $hash ]['url']       = get_permalink( $post->ID );

								$value = $row->meta_value;

								//encode the emoji to prevent unserialize errors
								$value = wp_encode_emoji( $value );

								if ( strpos( $value, '%%' ) !== false ) {
									$value = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', $value );
								}

								if ( function_exists( 'mb_detect_encoding' ) && function_exists( 'iconv' ) ) {
									if ( $encoding = mb_detect_encoding( $value ) ) {
										if ( $encoding <> 'UTF-8' ) {

											//support for international languages
											if ( function_exists( 'iconv' ) && SQ_Classes_Helpers_Tools::getOption( 'sq_non_utf8_support' ) ) {
												if ( strpos( get_bloginfo( "language" ), 'en' ) === false ) {
													$value = iconv( $encoding, 'UTF-8', $value );
												}
											}


										}
									}
								}

								if ( $row->meta_key == 'rank_math_robots' ) {
									$value                      = @unserialize( $value, array( 'allowed_classes' => false ) );
									$metas[ $hash ]['noindex']  = in_array( 'noindex', (array) $value );
									$metas[ $hash ]['nofollow'] = in_array( 'nofollow', (array) $value );
								} else {
									$metas[ $hash ][ $meta_keys[ $row->meta_key ] ] = stripslashes( $value );
								}
							}
						}
					}
				}


				if ( $platform == 'seo-by-rank-math' ) {
					$query = "SELECT * FROM `$wpdb->termmeta` WHERE meta_key IN (" . join( ",", $placeholders ) . ");";
					if ( $rows = $wpdb->get_results( $wpdb->prepare( $query, array_keys( $meta_keys ) ), OBJECT ) ) {
						foreach ( $rows as $row ) {

							if ( isset( $meta_keys[ $row->meta_key ] ) && $row->meta_value <> '' ) {
								if ( $term = get_term( $row->term_id ) ) {
									//set the hash for each post type
									$hash                       = md5( $term->taxonomy . $term->term_id );
									$metas[ $hash ]['term_id']  = $term->term_id;
									$metas[ $hash ]['taxonomy'] = $term->taxonomy;
									$metas[ $hash ]['url']      = get_term_link( $term->term_id, $term->taxonomy );

									$value = $row->meta_value;

									//encode the emoji to prevent unserialize errors
									$value = wp_encode_emoji( $value );

									if ( strpos( $value, '%%' ) !== false ) {
										$value = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', $value );
									}

									if ( function_exists( 'mb_detect_encoding' ) && function_exists( 'iconv' ) ) {
										if ( $encoding = mb_detect_encoding( $value ) ) {
											if ( $encoding <> 'UTF-8' ) {

												//support for international languages
												if ( function_exists( 'iconv' ) && SQ_Classes_Helpers_Tools::getOption( 'sq_non_utf8_support' ) ) {
													if ( strpos( get_bloginfo( "language" ), 'en' ) === false ) {
														$value = iconv( $encoding, 'UTF-8', $value );
													}
												}

											}
										}
									}

									if ( $row->meta_key == 'rank_math_robots' ) {
										$value                      = @unserialize( $value, array( 'allowed_classes' => false ) );
										$metas[ $hash ]['noindex']  = in_array( 'noindex', (array) $value );
										$metas[ $hash ]['nofollow'] = in_array( 'nofollow', (array) $value );
									} else {
										$metas[ $hash ][ $meta_keys[ $row->meta_key ] ] = stripslashes( $value );
									}
								}
							}

						}

					}
				}
			}

			if ( $platform == 'wordpress-seo' ) {
				$meta_keys    = array(
					'title'       => 'wpseo_title',
					'description' => 'wpseo_metadesc',
					'noindex'     => 'wpseo_noindex_author',
				);
				$placeholders = array_fill( 0, count( $meta_keys ), '%s' );

				// get the users data
				$query = "SELECT * FROM `$wpdb->usermeta` WHERE meta_key IN (" . join( ",", $placeholders ) . ");";

				$meta_keys = array_flip( $meta_keys );
				if ( $rows = $wpdb->get_results( $wpdb->prepare( $query, array_keys( $meta_keys ) ), OBJECT ) ) {
					foreach ( $rows as $row ) {
						if ( isset( $meta_keys[ $row->meta_key ] ) && $row->meta_value <> '' ) {
							//set the hash for each profile
							$hash                        = md5( 'profile' . $row->user_id );
							$metas[ $hash ]['post_id']   = (int) $row->user_id;
							$metas[ $hash ]['post_type'] = 'profile';
							if ( function_exists( 'bp_core_get_user_domain' ) ) {
								$metas[ $hash ]['url'] = bp_core_get_user_domain( $row->user_id );
							} else {
								$metas[ $hash ]['url'] = get_author_posts_url( $row->user_id );
							}

							$value = $row->meta_value;

							//encode the emoji to prevent unserialize errors
							$value = wp_encode_emoji( $value );

							if ( strpos( $value, '%%' ) !== false ) {
								$value = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', $value );
							}

							if ( function_exists( 'mb_detect_encoding' ) && function_exists( 'iconv' ) ) {
								if ( $encoding = mb_detect_encoding( $value ) ) {
									if ( $encoding <> 'UTF-8' ) {

										//support for international languages
										if ( function_exists( 'iconv' ) && SQ_Classes_Helpers_Tools::getOption( 'sq_non_utf8_support' ) ) {
											if ( strpos( get_bloginfo( "language" ), 'en' ) === false ) {
												$value = iconv( $encoding, 'UTF-8', $value );
											}
										}
									}
								}
							}

							if ( $meta_keys[ $row->meta_key ] == 'noindex' ) {
								$metas[ $hash ][ $meta_keys[ $row->meta_key ] ] = ( $value == 'on' );
							} else {
								$metas[ $hash ][ $meta_keys[ $row->meta_key ] ] = stripslashes( $value );
							}

						}
					}

				}

				//get taxonomies
				if ( $taxonomies = get_option( 'wpseo_taxonomy_meta' ) ) {
					if ( ! empty( $taxonomies ) ) {
						foreach ( $taxonomies as $taxonomie => $terms ) {
							if ( ! empty( $terms ) ) {
								if ( $taxonomie <> 'category' ) {
									$taxonomie = 'tax-' . $taxonomie;
								}
								foreach ( $terms as $term_id => $taxmetas ) {
									if ( ! empty( $taxmetas ) ) {
										if ( ! is_wp_error( get_term_link( $term_id ) ) ) {
											$metas[ md5( $taxonomie . $term_id ) ]['url']       = get_term_link( $term_id );
											$metas[ md5( $taxonomie . $term_id ) ]['term_id']   = $term_id;
											$metas[ md5( $taxonomie . $term_id ) ]['taxonomie'] = $taxonomie;
											foreach ( $taxmetas as $meta_key => $meta_value ) {
												if ( $meta_key == 'wpseo_desc' ) {
													$meta_key = '_yoast_wpseo_metadesc';
												} else {
													$meta_key = '_yoast_' . $meta_key;
												}

												if ( isset( $meta_keys[ $meta_key ] ) ) {
													$metas[ md5( $taxonomie . $term_id ) ][ $meta_keys[ $meta_key ] ] = stripslashes( $meta_value );
												}
											}
										}
									}
								}
							}
						}
					}
				}

				//get all patterns from Yoast
				if ( $yoast_patterns = get_option( 'wpseo_titles' ) ) {
					if ( ! empty( $yoast_patterns ) ) {
						$patterns = SQ_Classes_Helpers_Tools::getOption( 'patterns' );

						foreach ( $patterns as $path => &$values ) {
							switch ( $path ) {
								case 'profile':
									$path = 'author';
									break;
								case 'category':
									$path = 'tax-category';
									break;
								case 'tag':
									$path = 'tax-post_tag';
									break;
							}

							if ( isset( $yoast_patterns["title-pt$path"] ) && $yoast_patterns["title-pt$path"] <> '' ) {
								$values['title'] = $yoast_patterns["title-pt$path"];
							}
							if ( isset( $yoast_patterns["metadesc-pt$path"] ) && $yoast_patterns["metadesc-pt$path"] <> '' ) {
								$values['description'] = $yoast_patterns["metadesc-pt$path"];
							}
							if ( isset( $yoast_patterns["noindex-pt$path"] ) && $yoast_patterns["noindex-pt$path"] <> '' ) {
								$values['noindex'] = $yoast_patterns["noindex-pt$path"];
							}
							if ( isset( $yoast_patterns['separator'] ) && $yoast_patterns['separator'] <> '' ) {
								$values['sep'] = $yoast_patterns['separator'];
							}
							if ( isset( $yoast_patterns["title-$path-wpseo"] ) && $yoast_patterns["title-$path-wpseo"] <> '' ) {
								$values['title'] = $yoast_patterns["title-$path-wpseo"];
							}
							if ( isset( $yoast_patterns["metadesc-$path-wpseo"] ) && $yoast_patterns["metadesc-$path-wpseo"] <> '' ) {
								$values['description'] = $yoast_patterns["metadesc-$path-wpseo"];
							}
							if ( isset( $yoast_patterns["noindex-$path-wpseo"] ) ) {
								$values['noindex'] = (int) $yoast_patterns["noindex-$path-wpseo"];
							}
							if ( isset( $yoast_patterns["disable-$path-wpseo"] ) ) {
								$values['disable'] = (int) $yoast_patterns["disable-$path-wpseo"];
							}

							if ( isset( $yoast_patterns["title-$path"] ) && $yoast_patterns["title-$path"] <> '' ) {
								$values['title'] = $yoast_patterns["title-$path"];
							}
							if ( isset( $yoast_patterns["metadesc-$path"] ) && $yoast_patterns["metadesc-$path"] <> '' ) {
								$values['description'] = $yoast_patterns["metadesc-$path"];
							}
							if ( isset( $yoast_patterns["noindex-$path"] ) ) {
								$values['noindex'] = (int) $yoast_patterns["noindex-$path"];
							}
							if ( isset( $yoast_patterns["disable-$path"] ) ) {
								$values['disable'] = (int) $yoast_patterns["disable-$path"];
							}

							foreach ( $values as &$value ) {

								if ( is_string( $value ) && strpos( $value, '%%' ) !== false ) {
									//encode the emoji to prevent unserialize errors
									$value = wp_encode_emoji( $value );

									$value = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', $value );
								}
							}

							//Replace all patterns for imported metas
							if ( ! empty( $metas ) ) {
								foreach ( $metas as &$meta ) {
									if ( is_array( $meta ) ) {
										foreach ( $meta as &$field ) {
											$field = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', $field );
										}
									}
								}
							}
						}

						SQ_Classes_Helpers_Tools::saveOptions( 'patterns', $patterns );
					}
				}


				// get the woocommerce seo data
				$posts = $wpdb->get_results( 'SELECT `post_id`,`meta_value` FROM `' . $wpdb->postmeta . '` WHERE `meta_key` = "wpseo_global_identifier_values";', OBJECT );

				if ( ! empty( $posts ) ) {
					$wc_fields = array(
						'mpn'  => 'mpn',
						'gtin' => 'gtin8',
						'ean'  => 'gtin13',
						'upc'  => 'gtin12',
						'isbn' => 'isbn'
					);

					foreach ( $posts as $post ) {
						if ( $post->meta_value <> '' ) {
							$sq_woocommerce = array();

							$data = @unserialize( $post->meta_value, array( 'allowed_classes' => false ) );
							if ( ! is_array( $data ) ) {
								continue;
							}
							foreach ( $wc_fields as $field => $value ) {
								if ( isset( $data[ $value ] ) && $data[ $value ] <> '' ) {
									$sq_woocommerce[ $field ] = $data[ $value ];
								}
							}

							if ( ! empty( $sq_woocommerce ) ) {
								update_post_meta( $post->post_id, '_sq_woocommerce', $sq_woocommerce );
							}

						}
					}
				}
			}

			if ( $platform == 'all-in-one-seo-pack' ) {
				if ( $options = get_option( 'aioseo_options' ) ) {
					$options = json_decode( $options, true );
					if ( isset( $options['searchAppearance']['dynamic'] ) ) {
						$options = $options['searchAppearance']['dynamic'];
					}

					$patterns    = SQ_Classes_Helpers_Tools::getOption( 'patterns' );
					$findreplace = array(
						'page_title'           => 'title',
						'post_title'           => 'title',
						'post_excerpt'         => 'excerpt',
						'post_content'         => 'excerpt',
						'page_excerpt'         => 'excerpt',
						'page_content'         => 'excerpt',
						'post_date'            => 'date',
						'page_date'            => 'date',
						'archive_title'        => 'title',
						'site_title'           => 'sitename',
						'tagline'              => 'sitedesc',
						'blog_description'     => 'sitedesc',
						'category_title'       => 'category',
						'author_name'          => 'name',
						'author_first_name'    => 'name',
						'author_last_name'     => 'name',
						'categories'           => 'category',
						'tax_name'             => 'term_title',
						'taxonomy_title'       => 'term_title',
						'page_author_nicename' => 'name',
						'description'          => 'excerpt',
						'request_words'        => 'searchphrase',
						'search'               => 'searchphrase',
						'current_date'         => 'currentdate',
						'current_day'          => 'currentday',
						'current_month'        => 'currentmonth',
						'current_year'         => 'currentyear',
						'separator_sa'         => 'sep',
						'permalink'            => 'guid',
					);

					//Replace all patterns for imported metas
					if ( ! empty( $metas ) ) {
						foreach ( $metas as &$meta ) {
							if ( is_array( $meta ) ) {
								foreach ( $meta as &$field ) {
									$field = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $field ) );
								}
							}
						}
					}

					//replace the patterns automation
					if ( isset( $options['postTypes']['post']['title'] ) && $options['postTypes']['post']['title'] <> '' ) {
						$patterns['post']['title'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['postTypes']['post']['title'] ) );
					}
					if ( isset( $options['postTypes']['post']['metaDescription'] ) && $options['postTypes']['post']['metaDescription'] <> '' ) {
						$patterns['post']['description'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['postTypes']['post']['metaDescription'] ) );
					}


					if ( isset( $options['postTypes']['page']['title'] ) && $options['postTypes']['page']['title'] <> '' ) {
						$patterns['page']['title'] = preg_replace( '/#([a-z_]+)\s/', '{{$1}} ', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['postTypes']['page']['title'] ) );
					}
					if ( isset( $options['postTypes']['page']['metaDescription'] ) && $options['postTypes']['page']['metaDescription'] <> '' ) {
						$patterns['page']['description'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['postTypes']['page']['metaDescription'] ) );
					}

					if ( isset( $options['postTypes']['attachment']['title'] ) && $options['postTypes']['attachment']['title'] <> '' ) {
						$patterns['attachment']['title'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['postTypes']['attachment']['title'] ) );
					}
					if ( isset( $options['postTypes']['attachment']['metaDescription'] ) && $options['postTypes']['attachment']['metaDescription'] <> '' ) {
						$patterns['attachment']['description'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['postTypes']['attachment']['metaDescription'] ) );
					}

					if ( isset( $options['postTypes']['product']['title'] ) && $options['postTypes']['product']['title'] <> '' ) {
						$patterns['product']['title'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['postTypes']['product']['title'] ) );
					}
					if ( isset( $options['postTypes']['product']['metaDescription'] ) && $options['postTypes']['product']['metaDescription'] <> '' ) {
						$patterns['product']['description'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['postTypes']['product']['metaDescription'] ) );
					}

					if ( isset( $options['postTypes']['product']['title'] ) && $options['postTypes']['product']['title'] <> '' ) {
						$patterns['product']['title'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['postTypes']['product']['title'] ) );
					}
					if ( isset( $options['postTypes']['product']['metaDescription'] ) && $options['postTypes']['product']['metaDescription'] <> '' ) {
						$patterns['product']['description'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['postTypes']['product']['metaDescription'] ) );
					}

					if ( isset( $options['taxonomies']['category']['title'] ) && $options['taxonomies']['category']['title'] <> '' ) {
						$patterns['category']['title'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['taxonomies']['category']['title'] ) );
					}
					if ( isset( $options['taxonomies']['category']['metaDescription'] ) && $options['taxonomies']['category']['metaDescription'] <> '' ) {
						$patterns['category']['description'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['taxonomies']['category']['metaDescription'] ) );
					}

					if ( isset( $options['taxonomies']['post_tag']['title'] ) && $options['taxonomies']['post_tag']['title'] <> '' ) {
						$patterns['tag']['title'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['taxonomies']['post_tag']['title'] ) );
					}
					if ( isset( $options['taxonomies']['post_tag']['metaDescription'] ) && $options['taxonomies']['post_tag']['metaDescription'] <> '' ) {
						$patterns['tag']['description'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['taxonomies']['post_tag']['metaDescription'] ) );
					}

					if ( isset( $options['taxonomies']['product_cat']['title'] ) && $options['taxonomies']['product_cat']['title'] <> '' ) {
						$patterns['tax-product_cat']['title'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['taxonomies']['product_cat']['title'] ) );
					}
					if ( isset( $options['taxonomies']['product_cat']['metaDescription'] ) && $options['taxonomies']['product_cat']['metaDescription'] <> '' ) {
						$patterns['tax-product_cat']['description'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['taxonomies']['product_cat']['metaDescription'] ) );
					}

					if ( isset( $options['taxonomies']['product_tag']['title'] ) && $options['taxonomies']['product_tag']['title'] <> '' ) {
						$patterns['tax-product_tag']['title'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['taxonomies']['product_tag']['title'] ) );
					}
					if ( isset( $options['taxonomies']['product_tag']['metaDescription'] ) && $options['taxonomies']['product_tag']['metaDescription'] <> '' ) {
						$patterns['tax-product_tag']['description'] = preg_replace( '/#([a-z_]+)/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['taxonomies']['product_tag']['metaDescription'] ) );
					}


					if ( isset( $options['postTypes']['post']['advanced']['robotsMeta']['noindex'] ) ) {
						$patterns['post']['noindex'] = (int) $options['postTypes']['post']['advanced']['robotsMeta']['noindex'];
					}
					if ( isset( $options['postTypes']['post']['advanced']['robotsMeta']['nofollow'] ) ) {
						$patterns['post']['nofollow'] = (int) $options['postTypes']['post']['advanced']['robotsMeta']['nofollow'];
					}
					if ( isset( $options['postTypes']['page']['advanced']['robotsMeta']['noindex'] ) ) {
						$patterns['page']['noindex'] = (int) $options['postTypes']['page']['advanced']['robotsMeta']['noindex'];
					}
					if ( isset( $options['postTypes']['page']['advanced']['robotsMeta']['nofollow'] ) ) {
						$patterns['page']['nofollow'] = (int) $options['postTypes']['page']['advanced']['robotsMeta']['nofollow'];
					}
					if ( isset( $options['postTypes']['attachment']['advanced']['robotsMeta']['noindex'] ) ) {
						$patterns['attachment']['noindex'] = (int) $options['postTypes']['attachment']['advanced']['robotsMeta']['noindex'];
					}
					if ( isset( $options['postTypes']['attachment']['advanced']['robotsMeta']['nofollow'] ) ) {
						$patterns['attachment']['nofollow'] = (int) $options['postTypes']['attachment']['advanced']['robotsMeta']['nofollow'];
					}
					if ( isset( $options['postTypes']['product']['advanced']['robotsMeta']['noindex'] ) ) {
						$patterns['product']['noindex'] = (int) $options['postTypes']['product']['advanced']['robotsMeta']['noindex'];
					}
					if ( isset( $options['postTypes']['product']['advanced']['robotsMeta']['nofollow'] ) ) {
						$patterns['product']['nofollow'] = (int) $options['postTypes']['product']['advanced']['robotsMeta']['nofollow'];
					}
					if ( isset( $options['taxonomies']['category']['advanced']['robotsMeta']['noindex'] ) ) {
						$patterns['category']['noindex'] = (int) $options['taxonomies']['category']['advanced']['robotsMeta']['noindex'];
					}
					if ( isset( $options['taxonomies']['category']['advanced']['robotsMeta']['nofollow'] ) ) {
						$patterns['category']['nofollow'] = (int) $options['taxonomies']['category']['advanced']['robotsMeta']['nofollow'];
					}
					if ( isset( $options['taxonomies']['post_tag']['advanced']['robotsMeta']['noindex'] ) ) {
						$patterns['tag']['noindex'] = (int) $options['taxonomies']['post_tag']['advanced']['robotsMeta']['noindex'];
					}
					if ( isset( $options['taxonomies']['post_tag']['advanced']['robotsMeta']['nofollow'] ) ) {
						$patterns['tag']['nofollow'] = (int) $options['taxonomies']['post_tag']['advanced']['robotsMeta']['nofollow'];
					}
					if ( isset( $options['taxonomies']['product_cat']['advanced']['robotsMeta']['noindex'] ) ) {
						$patterns['tax-product_cat']['noindex'] = (int) $options['taxonomies']['product_cat']['advanced']['robotsMeta']['noindex'];
					}
					if ( isset( $options['taxonomies']['product_cat']['advanced']['robotsMeta']['nofollow'] ) ) {
						$patterns['tax-product_cat']['nofollow'] = (int) $options['taxonomies']['product_cat']['advanced']['robotsMeta']['nofollow'];
					}
					if ( isset( $options['taxonomies']['product_tag']['advanced']['robotsMeta']['noindex'] ) ) {
						$patterns['tax-product_tag']['noindex'] = (int) $options['taxonomies']['product_tag']['advanced']['robotsMeta']['noindex'];
					}
					if ( isset( $options['taxonomies']['product_tag']['advanced']['robotsMeta']['nofollow'] ) ) {
						$patterns['tax-product_tag']['nofollow'] = (int) $options['taxonomies']['product_tag']['advanced']['robotsMeta']['nofollow'];
					}

					SQ_Classes_Helpers_Tools::saveOptions( 'patterns', $patterns );
				}
			}

			if ( $platform == 'wp-seopress' ) {
				if ( $options = get_option( 'seopress_titles_option_name' ) ) {

					$patterns    = SQ_Classes_Helpers_Tools::getOption( 'patterns' );
					$findreplace = array(
						'sitetitle'             => 'sitename',
						'tagline'               => 'sitedesc',
						'post_title'            => 'title',
						'post_excerpt'          => 'excerpt',
						'post_date'             => 'date',
						'post_modified_date'    => 'modified',
						'post_author'           => 'name',
						'post_category'         => 'category',
						'post_tag'              => 'tag',
						'_category_title'       => 'title',
						'_category_description' => 'category_description',
						'tag_title'             => 'title',
						'tag_description'       => 'tag_description',
						'term_title'            => 'term_title',
						'term_description'      => 'term_description',
						'search_keywords'       => 'searchphrase',
						'current_pagination'    => 'page',
						'cpt_plural'            => 'title',
						'archive_title'         => 'title',
						'archive_date'          => 'date',
						'archive_date_day'      => 'date',
						'archive_date_month'    => 'date',
						'archive_date_year'     => 'date',
						'author_bio'            => 'excerpt',
						'wc_single_cat'         => 'primary_category',
						'wc_single_tag'         => 'tag',
						'wc_single_short_desc'  => 'excerpt',
						'wc_single_price'       => 'product_price',
					);

					//Replace all patterns for imported metas
					if ( ! empty( $metas ) ) {
						foreach ( $metas as &$meta ) {
							if ( is_array( $meta ) ) {
								foreach ( $meta as $name => &$field ) {
									if ( $name == 'title' || $name == 'description' ) {
										$field = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $field ) );
									}
								}
							}
						}
					}

					if ( isset( $options['seopress_titles_home_site_title'] ) && $options['seopress_titles_home_site_title'] <> '' ) {
						$patterns['home']['title'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_home_site_title'] ) );
					}
					if ( isset( $options['seopress_titles_home_site_desc'] ) && $options['seopress_titles_home_site_desc'] <> '' ) {
						$patterns['home']['description'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_home_site_desc'] ) );
					}

					if ( isset( $options['seopress_titles_single_titles']['post']['title'] ) && $options['seopress_titles_single_titles']['post']['title'] <> '' ) {
						$patterns['post']['title'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_single_titles']['post']['title'] ) );
					}
					if ( isset( $options['seopress_titles_single_titles']['post']['description'] ) && $options['seopress_titles_single_titles']['post']['description'] <> '' ) {
						$patterns['post']['description'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_single_titles']['post']['description'] ) );
					}

					if ( isset( $options['seopress_titles_single_titles']['page']['title'] ) && $options['seopress_titles_single_titles']['page']['title'] <> '' ) {
						$patterns['page']['title'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_single_titles']['page']['title'] ) );
					}
					if ( isset( $options['seopress_titles_single_titles']['page']['description'] ) && $options['seopress_titles_single_titles']['page']['description'] <> '' ) {
						$patterns['page']['description'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_single_titles']['page']['description'] ) );
					}

					if ( isset( $options['seopress_titles_single_titles']['product']['title'] ) && $options['seopress_titles_single_titles']['product']['title'] <> '' ) {
						$patterns['product']['title'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_single_titles']['product']['title'] ) );
					}
					if ( isset( $options['seopress_titles_single_titles']['product']['description'] ) && $options['seopress_titles_single_titles']['product']['description'] <> '' ) {
						$patterns['product']['description'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_single_titles']['product']['description'] ) );
					}

					if ( isset( $options['seopress_titles_tax_titles']['category']['title'] ) && $options['seopress_titles_tax_titles']['category']['title'] <> '' ) {
						$patterns['category']['title'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_tax_titles']['category']['title'] ) );
					}
					if ( isset( $options['seopress_titles_tax_titles']['category']['description'] ) && $options['seopress_titles_tax_titles']['category']['description'] <> '' ) {
						$patterns['category']['description'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_tax_titles']['category']['description'] ) );
					}

					if ( isset( $options['seopress_titles_tax_titles']['post_tag']['title'] ) && $options['seopress_titles_tax_titles']['post_tag']['title'] <> '' ) {
						$patterns['tax-post_tag']['title'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_tax_titles']['post_tag']['title'] ) );
					}
					if ( isset( $options['seopress_titles_tax_titles']['post_tag']['description'] ) && $options['seopress_titles_tax_titles']['post_tag']['description'] <> '' ) {
						$patterns['tax-post_tag']['description'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_tax_titles']['post_tag']['description'] ) );
					}

					if ( isset( $options['seopress_titles_tax_titles']['product_cat']['title'] ) && $options['seopress_titles_tax_titles']['product_cat']['title'] <> '' ) {
						$patterns['tax-product_cat']['title'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_tax_titles']['product_cat']['title'] ) );
					}
					if ( isset( $options['seopress_titles_tax_titles']['product_cat']['description'] ) && $options['seopress_titles_tax_titles']['product_cat']['description'] <> '' ) {
						$patterns['tax-product_cat']['description'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_tax_titles']['product_cat']['description'] ) );
					}

					if ( isset( $options['seopress_titles_tax_titles']['product_tag']['title'] ) && $options['seopress_titles_tax_titles']['product_tag']['title'] <> '' ) {
						$patterns['tax-product_tag']['title'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_tax_titles']['product_tag']['title'] ) );
					}
					if ( isset( $options['seopress_titles_tax_titles']['product_tag']['description'] ) && $options['seopress_titles_tax_titles']['product_tag']['description'] <> '' ) {
						$patterns['tax-product_tag']['description'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_tax_titles']['product_tag']['description'] ) );
					}

					if ( isset( $options['seopress_titles_archive_titles']['post']['title'] ) && $options['seopress_titles_archive_titles']['post']['title'] <> '' ) {
						$patterns['archive']['title'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_archive_titles']['post']['title'] ) );
					}

					if ( isset( $options['seopress_titles_archives_author_title'] ) && $options['seopress_titles_archives_author_title'] <> '' ) {
						$patterns['profile']['title'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_archives_author_title'] ) );
					}

					if ( isset( $options['seopress_titles_archives_search_title'] ) && $options['seopress_titles_archives_search_title'] <> '' ) {
						$patterns['search']['title'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_archives_search_title'] ) );
					}

					if ( isset( $options['seopress_titles_archives_404_title'] ) && $options['seopress_titles_archives_404_title'] <> '' ) {
						$patterns['404']['title'] = preg_replace( '/%%([^\%]+)%%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['seopress_titles_archives_404_title'] ) );
					}

					SQ_Classes_Helpers_Tools::saveOptions( 'patterns', $patterns );
				}
			}

			if ( $platform == 'seo-by-rank-math' ) {
				if ( $options = get_option( 'rank-math-options-titles' ) ) {

					$patterns    = SQ_Classes_Helpers_Tools::getOption( 'patterns' );
					$findreplace = array(
						'search_query'           => 'searchphrase',
						'term'                   => 'term_title',
						'category'               => 'primary_category',
						'categories'             => 'category',
						'term_title_description' => 'term_description',
					);

					//Replace all patterns for imported metas
					if ( ! empty( $metas ) ) {
						foreach ( $metas as &$meta ) {
							if ( is_array( $meta ) ) {
								foreach ( $meta as $name => &$field ) {
									if ( $name == 'title' || $name == 'description' ) {
										$field = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $field ) );
									}
								}
							}
						}
					}

					if ( isset( $options['homepage_title'] ) && $options['homepage_title'] <> '' ) {
						$patterns['home']['title'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['homepage_title'] ) );

					}
					if ( isset( $options['homepage_description'] ) && $options['homepage_description'] <> '' ) {
						$patterns['home']['description'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['homepage_description'] ) );
					}

					if ( isset( $options['pt_post_title'] ) && $options['pt_post_title'] <> '' ) {
						$patterns['post']['title'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['pt_post_title'] ) );
					}
					if ( isset( $options['pt_post_description'] ) && $options['pt_post_description'] <> '' ) {
						$patterns['post']['description'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['pt_post_description'] ) );
					}

					if ( isset( $options['pt_page_title'] ) && $options['pt_page_title'] <> '' ) {
						$patterns['page']['title'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['pt_page_title'] ) );
					}
					if ( isset( $options['pt_page_description'] ) && $options['pt_page_description'] <> '' ) {
						$patterns['page']['description'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['pt_page_description'] ) );
					}

					if ( isset( $options['pt_attachment_title'] ) && $options['pt_attachment_title'] <> '' ) {
						$patterns['attachment']['title'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['pt_attachment_title'] ) );
					}
					if ( isset( $options['pt_attachment_description'] ) && $options['pt_attachment_description'] <> '' ) {
						$patterns['attachment']['description'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['pt_attachment_description'] ) );
					}

					if ( isset( $options['pt_product_title'] ) && $options['pt_product_title'] <> '' ) {
						$patterns['product']['title'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['pt_product_title'] ) );
					}
					if ( isset( $options['pt_product_description'] ) && $options['pt_product_description'] <> '' ) {
						$patterns['product']['description'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['pt_product_description'] ) );
					}

					if ( isset( $options['tax_category_title'] ) && $options['tax_category_title'] <> '' ) {
						$patterns['category']['title'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['tax_category_title'] ) );
					}
					if ( isset( $options['tax_category_description'] ) && $options['tax_category_description'] <> '' ) {
						$patterns['category']['description'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['tax_category_description'] ) );
					}

					if ( isset( $options['tax_post_tag_title'] ) && $options['tax_post_tag_title'] <> '' ) {
						$patterns['tag']['title'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['tax_post_tag_title'] ) );
					}
					if ( isset( $options['tax_post_tag_description'] ) && $options['tax_post_tag_description'] <> '' ) {
						$patterns['tag']['description'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['tax_post_tag_description'] ) );
					}

					if ( isset( $options['tax_post_format_title'] ) && $options['tax_post_format_title'] <> '' ) {
						$patterns['tax-post_format']['title'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['tax_post_format_title'] ) );
					}

					if ( isset( $options['tax_product_cat_title'] ) && $options['tax_product_cat_title'] <> '' ) {
						$patterns['tax-product_cat']['title'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['tax_product_cat_title'] ) );
					}
					if ( isset( $options['tax_product_cat_description'] ) && $options['tax_product_cat_description'] <> '' ) {
						$patterns['tax-product_cat']['description'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['tax_product_cat_description'] ) );
					}

					if ( isset( $options['tax_product_tag_title'] ) && $options['tax_product_tag_title'] <> '' ) {
						$patterns['tax-product_tag']['title'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['tax_product_tag_title'] ) );
					}
					if ( isset( $options['tax_product_tag_description'] ) && $options['tax_product_tag_description'] <> '' ) {
						$patterns['tax-product_tag']['description'] = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['tax_product_tag_description'] ) );
					}

					SQ_Classes_Helpers_Tools::saveOptions( 'patterns', $patterns );

					if ( ( isset( $options['homepage_title'] ) && $options['homepage_title'] <> '' ) || isset( $options['homepage_description'] ) && $options['homepage_description'] <> '' ) {
						$url  = home_url();
						$post = SQ_Classes_ObjController::getClass( 'SQ_Models_Snippet' )->setHomePage();

						$post->sq->doseo          = 1;
						$post->sq->title          = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['homepage_title'] ) );
						$post->sq->description    = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['homepage_description'] ) );
						$post->sq->og_title       = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['homepage_facebook_title'] ) );
						$post->sq->og_description = preg_replace( '/%([^\%]+)%/', '{{$1}}', str_replace( array_keys( $findreplace ), array_values( $findreplace ), $options['homepage_facebook_description'] ) );

						if ( isset( $options['homepage_facebook_image'] ) && $options['homepage_facebook_image'] <> '' ) {
							$post->sq->og_media = $options['homepage_facebook_image'];
						}

						SQ_Classes_ObjController::getClass( 'SQ_Models_Qss' )->saveSqSEO( $url, $post->hash, maybe_serialize( array(
									'ID'        => 0,
									'post_type' => 'home',
									'term_id'   => 0,
									'taxonomy'  => '',
								) ), maybe_serialize( $post->sq->toArray() ), gmdate( 'Y-m-d H:i:s' ) );
					}
				}


			}

			if ( $platform == 'premium-seo-pack' ) {
				global $wpdb;

				$tables = $wpdb->get_col( 'SHOW TABLES' );
				foreach ( $tables as $table ) {
					if ( $table == $wpdb->prefix . strtolower( 'psp' ) ) {
						$table = $wpdb->prefix . "psp";
						if ( $rows = $wpdb->get_results( "SELECT * FROM `$table`", OBJECT ) ) {
							foreach ( $rows as $row ) {
								$metas[ $row->url_hash ]['post_id'] = $row->post_id;
								$metas[ $row->url_hash ]['url']     = $row->URL;
								$metas[ $row->url_hash ]['seo']     = $row->seo;
							}
						}
						break;
					}
				}

				return $metas;
			}

			if ( $platform == 'autodescription' ) {
				if ( $options = get_option( 'autodescription-site-settings' ) ) {
					///////////////////////////////////////////
					/////////////////////////////FIRST PAGE OPTIMIZATION
					if ( ( isset( $options['homepage_title'] ) && $options['homepage_title'] <> '' ) || isset( $options['homepage_description'] ) && $options['homepage_description'] <> '' ) {
						$url  = home_url();
						$post = SQ_Classes_ObjController::getClass( 'SQ_Models_Snippet' )->setHomePage();

						$post->sq->doseo       = 1;
						$post->sq->title       = $options['homepage_title'];
						$post->sq->description = $options['homepage_description'];

						if ( isset( $options['homepage_social_image_url'] ) && $options['homepage_social_image_url'] <> '' ) {
							$post->sq->og_media = $options['homepage_social_image_url'];
						}

						SQ_Classes_ObjController::getClass( 'SQ_Models_Qss' )->saveSqSEO( $url, $post->hash, maybe_serialize( array(
									'ID'        => 0,
									'post_type' => 'home',
									'term_id'   => 0,
									'taxonomy'  => '',
								) ), maybe_serialize( $post->sq->toArray() ), gmdate( 'Y-m-d H:i:s' ) );
					}
				}
			}
		}

		return $metas;
	}

	/**
	 * Restore a Squirrly SEO backup
	 *
	 * @param  $queries
	 * @param bool $overwrite
	 *
	 * @return bool
	 */
	public function executeSql( $queries, $overwrite = true ) {
		global $wpdb;

		if ( is_array( $queries ) && ! empty( $queries ) ) {

			foreach ( (array) $queries as $query ) {
				$query = trim( $query, PHP_EOL );
				if ( ! empty( $query ) && strlen( $query ) > 1 ) {

					if ( strpos( $query, 'CREATE TABLE' ) !== false ) {
						continue;
					}

					//get each row from query
					if ( strpos( $query, '(' ) !== false && strpos( $query, ')' ) !== false && strpos( $query, 'VALUES' ) !== false ) {
						$fields = substr( $query, strpos( $query, '(' ) + 1 );
						$fields = substr( $fields, 0, strpos( $fields, ')' ) );
						$fields = explode( ",", trim( $fields ) );

						$values = substr( $query, strpos( $query, 'VALUES' ) + 6 );
						if ( strpos( $query, 'ON DUPLICATE' ) !== false ) {
							$values = substr( $values, 0, strpos( $values, 'ON DUPLICATE' ) );
						}

						$values = explode( "','", trim( trim( $values ), '();' ) );
						$values = array_map( function ( $value ) {
							return trim( $value, "'" );
						}, $values );

						//Correct the old backups
						if ( in_array( 'post_id', $fields ) ) {
							foreach ( $fields as $index => $field ) {
								if ( $field == 'post_id' ) {
									unset( $fields[ $index ] );
									unset( $values[ $index ] );
								}
							}
						}

						//Make sure the values match with the fields
						if ( ! empty( $fields ) && ! empty( $values ) && count( $fields ) == count( $values ) ) {

							$placeholders = array_fill( 0, count( $values ), '%s' );

							if ( $overwrite ) {
								$query = "INSERT INTO `" . $wpdb->prefix . _SQ_DB_ . "` (" . join( ",", $fields ) . ") 
                                          VALUES (" . join( ",", $placeholders ) . ") ON DUPLICATE KEY 
                                          UPDATE " . join( " = %s,", $fields ) . " = %s";
							} else {
								$query = "INSERT INTO `" . $wpdb->prefix . _SQ_DB_ . "` (" . join( ",", $fields ) . ") 
                                          VALUES (" . join( ",", $placeholders ) . ") ";
							}
							$wpdb->query( $wpdb->prepare( $query, array_merge( $values, $values ) ) );

						}

					}

				}
			}

			return true;
		}

		return false;
	}
	/**
	 * The Squirrly settings to put in a backup: everything except what belongs to this site's connection
	 * (login keys and the Cloud token are credentials, not settings to carry over).
	 *
	 * @return array
	 */
	public function getSettingsForExport() {
		$options = SQ_Classes_Helpers_Tools::$options;

		foreach ( SQ_Classes_Helpers_SiteAuth::authOptionKeys() as $authKey ) {
			unset( $options[ $authKey ] );
		}
		unset( $options['sq_api'], $options['sq_cloud_token'] );

		return $options;
	}

	/**
	 * Restore the Squirrly settings from a backup and keep what belongs to this site: the Cloud connection, the login
	 * keys, the journey state, the menu and the white-label values.
	 *
	 * @param array $options
	 *
	 * @return bool false when the data is not a Squirrly settings backup
	 */
	public function restoreSettings( $options ) {

		//Backups made since 14.2.8 have no sq_api (the Cloud token is left out), so they are recognized by their settings.
		//Requiring sq_api made every recent settings backup "not valid".
		if ( ! is_array( $options ) || ! ( isset( $options['sq_api'] ) || isset( $options['patterns'] ) || isset( $options['sq_auto_sitemap'] ) ) ) {
			return false;
		}

		//keep the local values; sq_cloud_token too, or restoring a backup disconnected the site from Squirrly Cloud
		foreach ( array( 'sq_api', 'sq_cloud_token', 'sq_seojourney', 'menu', 'sq_auto_devkit', 'sq_devkit_logo', 'sq_devkit_name', 'sq_devkit_menu_name', 'sq_devkit_audit_success', 'sq_devkit_audit_fail' ) as $key ) {
			$options[ $key ] = SQ_Classes_Helpers_Tools::getOption( $key );
		}

		//never accept auth state from an imported backup - always keep what's local
		foreach ( SQ_Classes_Helpers_SiteAuth::authOptionKeys() as $authKey ) {
			$options[ $authKey ] = SQ_Classes_Helpers_Tools::getOption( $authKey );
		}

		SQ_Classes_Helpers_Tools::$options = $options;
		SQ_Classes_Helpers_Tools::saveOptions();

		//Check if there is an old backup from Squirrly
		SQ_Classes_Helpers_Tools::getOptions();

		//reset the report time
		SQ_Classes_Helpers_Tools::saveOptions( 'seoreport_time', false );

		return true;
	}

	/**
	 * Read the whole Briefcase from Squirrly Cloud, page by page, with the labels of each keyword.
	 *
	 * @param callable|null $each called with ( $labels, $keywords ) for every page; without it, all pages are returned at once
	 *
	 * @return array|WP_Error array( 'labels' => array of {id,name,color}, 'keywords' => array of keyword rows )
	 */
	public function getCloudBriefcase( $each = null ) {
		//classes load through the ObjController; the Cloud client isn't loaded yet outside the admin screens (WP-CLI, cron)
		SQ_Classes_ObjController::getClass( 'SQ_Classes_RemoteController' );

		$labels = $keywords = array();
		$start  = 0;
		$limit  = 1000;

		do {
			//the Briefcase screen arguments: with them the API also returns the labels
			$response = SQ_Classes_RemoteController::getBriefcase( array(
				'start'  => $start,
				'limit'  => $limit,
				'sort'   => 'keyword',
				'order'  => 'asc',
				'search' => '',
			) );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( empty( $response ) ) {
				break;
			}

			if ( empty( $labels ) && ! empty( $response->labels ) ) {
				//id 0 is the "Keywords without labels" filter, not a label
				$labels = array_values( array_filter( (array) $response->labels, function ( $label ) {
					return isset( $label->id, $label->name ) && (int) $label->id > 0;
				} ) );
			}

			$rows  = ( ! empty( $response->keywords ) ? (array) $response->keywords : array() );
			$total = ( isset( $response->results ) ? (int) $response->results : 0 );
			$start += $limit;

			if ( $each ) {
				call_user_func( $each, $labels, $rows );
			} else {
				$keywords = array_merge( $keywords, $rows );
			}

		} while ( count( $rows ) == $limit && $start < $total );

		return array( 'labels' => $labels, 'keywords' => $keywords );
	}

	/**
	 * Which Advanced Pack table each export part uses.
	 *
	 * @return array part => table name without prefix
	 */
	public function getPackParts() {
		return array( 'redirects' => 'qss_redirects', 'schemas' => 'qss_jsonld' );
	}

	/**
	 * The Advanced Pack tables that hold user data and exist on this site (redirect rules, JSON-LD schemas).
	 * The redirect log is left out: it's a log, not data to restore.
	 *
	 * @param array $parts redirects and/or schemas
	 *
	 * @return array name => full table name
	 */
	public function getPackTables( $parts = array( 'redirects', 'schemas' ) ) {
		global $wpdb;

		$tables = array();
		foreach ( array_intersect_key( $this->getPackParts(), array_flip( $parts ) ) as $name ) {
			$table = $wpdb->prefix . $name;
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
				$tables[ $name ] = $table;
			}
		}

		return $tables;
	}

	/**
	 * Export everything Squirrly keeps for this site, one record per line (JSON Lines), written as it is read:
	 * memory stays the same for 100 pages or 100,000. The parts: settings, seo (every page, with the keyword
	 * optimizations and inner links), briefcase (keywords with their labels), redirects and schemas (Advanced Pack).
	 *
	 * @param array $parts
	 * @param callable $write receives each line
	 *
	 * @return void
	 */
	public function streamExport( $parts, $write ) {
		global $wpdb;

		$line = function ( $type, $data ) use ( $write ) {
			call_user_func( $write, wp_json_encode( array( 't' => $type, 'd' => $data ) ) . "\n" );
		};

		$line( 'meta', array( 'squirrly_backup' => 2, 'plugin_version' => SQ_VERSION, 'site' => home_url(), 'created' => gmdate( 'Y-m-d H:i:s' ), 'parts' => array_values( $parts ) ) );

		if ( in_array( 'settings', $parts, true ) ) {
			$line( 'settings', $this->getSettingsForExport() );
		}

		//SEO of every page, exactly as stored, 1000 rows at a time
		if ( in_array( 'seo', $parts, true ) ) {
			$table = $wpdb->prefix . _SQ_DB_;
			$last  = 0;
			do {
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, URL, url_hash, post, seo, date_time FROM `$table` WHERE blog_id = %d AND id > %d ORDER BY id LIMIT 1000", get_current_blog_id(), $last ), ARRAY_A );
				foreach ( (array) $rows as $row ) {
					$last = (int) $row['id'];
					unset( $row['id'] );
					$line( 'seo', $row );
				}
			} while ( ! empty( $rows ) && count( $rows ) == 1000 );
		}

		//Briefcase from Squirrly Cloud (when the site is connected): the labels first, then the keywords
		if ( in_array( 'briefcase', $parts, true ) ) {
			$labels_sent = false;
			$this->getCloudBriefcase( function ( $labels, $keywords ) use ( $line, &$labels_sent ) {
				$names = array();
				foreach ( $labels as $label ) {
					$names[ (int) $label->id ] = (string) $label->name;
					if ( ! $labels_sent ) {
						$line( 'label', array( 'name' => (string) $label->name, 'color' => ( isset( $label->color ) ? (string) $label->color : '' ) ) );
					}
				}
				$labels_sent = true;

				foreach ( $keywords as $row ) {
					if ( ! isset( $row->keyword ) || $row->keyword === '' ) {
						continue;
					}

					$row_labels = array();
					foreach ( ( ! empty( $row->labels ) ? (array) $row->labels : array() ) as $label ) {
						if ( isset( $label->name ) ) {
							$row_labels[] = (string) $label->name;
						} elseif ( isset( $label->lid, $names[ (int) $label->lid ] ) ) {
							$row_labels[] = $names[ (int) $label->lid ];
						}
					}

					$line( 'keyword', array( 'keyword' => (string) $row->keyword, 'labels' => array_values( array_unique( $row_labels ) ) ) );
				}
			} );
		}

		//Advanced Pack rows, 1000 at a time
		foreach ( $this->getPackTables( $parts ) as $name => $pack_table ) {
			$last = 0;
			do {
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$pack_table` WHERE id > %d ORDER BY id LIMIT 1000", $last ), ARRAY_A );
				foreach ( (array) $rows as $row ) {
					$last = (int) $row['id'];
					$line( $name, $row );
				}
			} while ( ! empty( $rows ) && count( $rows ) == 1000 );
		}
	}

	/**
	 * Folder for the uploaded import file while it is imported. The file name is random and the folder refuses
	 * direct access on Apache; the file is deleted when the import ends or is cancelled.
	 *
	 * @return string|false
	 */
	private function getImportDir() {
		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) ) {
			return false;
		}

		$dir = trailingslashit( $upload['basedir'] ) . 'squirrly-import/';
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		if ( ! file_exists( $dir . 'index.php' ) ) {
			@file_put_contents( $dir . 'index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore
			@file_put_contents( $dir . '.htaccess', "Deny from all\n" ); // phpcs:ignore
		}

		return $dir;
	}

	/**
	 * Restore a backup made with the older, separate Backup buttons, so those files still work after the buttons
	 * were replaced by Export Everything: Settings (JSON), SEO (SQL of the Squirrly table), Briefcase (keyword list),
	 * and the Advanced Pack's Redirects and Schemas (SQL of their tables). The SQL backups are base64 encoded.
	 *
	 * @param string $file
	 * @param array $parts the switches that are on
	 *
	 * @return string|array an error message, or array( 'message' => what was restored )
	 */
	private function importLegacyBackup( $file, $parts ) {
		$raw  = trim( (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$text = $raw;

		if ( $raw === '' ) {
			return esc_html__( "Error! The backup is not valid.", 'squirrly-seo' );
		}

		//the SQL backups and some settings backups are base64 encoded
		if ( strpos( $raw, 'INSERT INTO' ) === false && ! in_array( $raw[0], array( '{', '[' ), true ) && preg_match( '/^[A-Za-z0-9+\/=\r\n]+$/', substr( $raw, 0, 2000 ) ) ) {
			$decoded = base64_decode( $raw, true );
			if ( $decoded !== false && ( strpos( $decoded, 'INSERT INTO' ) !== false || in_array( substr( ltrim( $decoded ), 0, 1 ), array( '{', '[' ), true ) ) ) {
				$text = trim( $decoded );
			}
		}

		$need = function ( $part, $name ) use ( $parts ) {
			return in_array( $part, $parts, true ) ? '' : sprintf( esc_html__( "This backup file holds %s: switch on that part to import it.", 'squirrly-seo' ), $name );
		};

		//SQL backups: the table name says which one
		if ( strpos( $text, 'INSERT INTO' ) !== false ) {
			$queries = explode( 'INSERT INTO', $text );

			if ( preg_match( '/INSERT INTO\s+`?\w*qss_redirects`?/', $text ) ) {
				if ( $error = $need( 'redirects', esc_html__( "Redirects", 'squirrly-seo' ) ) ) {
					return $error;
				}
				if ( ! class_exists( 'SQP_Classes_ObjController' ) ) {
					return esc_html__( "Activate Squirrly SEO - Advanced Pack first, then import the redirects and schemas again.", 'squirrly-seo' );
				}
				SQP_Classes_ObjController::getClass( 'SQP_Models_Redirects_Admin' )->restoreTableBackup( $queries );

				return array( 'message' => esc_html__( "Redirects restored from the backup file.", 'squirrly-seo' ) );
			}

			if ( preg_match( '/INSERT INTO\s+`?\w*qss_jsonld`?/', $text ) ) {
				if ( $error = $need( 'schemas', esc_html__( "Schemas", 'squirrly-seo' ) ) ) {
					return $error;
				}
				if ( ! class_exists( 'SQP_Classes_ObjController' ) ) {
					return esc_html__( "Activate Squirrly SEO - Advanced Pack first, then import the redirects and schemas again.", 'squirrly-seo' );
				}
				SQP_Classes_ObjController::getClass( 'SQP_Models_Jsonld_Database' )->restoreTableBackup( $queries );

				return array( 'message' => esc_html__( "Schemas restored from the backup file.", 'squirrly-seo' ) );
			}

			if ( $error = $need( 'seo', esc_html__( "SEO", 'squirrly-seo' ) ) ) {
				return $error;
			}
			$this->executeSql( $queries );

			return array( 'message' => esc_html__( "SEO restored from the backup file.", 'squirrly-seo' ) );
		}

		$json = json_decode( $text, true );

		//Settings backup: the options as a JSON object
		if ( is_array( $json ) && ( isset( $json['patterns'] ) || isset( $json['sq_api'] ) || isset( $json['sq_auto_sitemap'] ) ) ) {
			if ( $error = $need( 'settings', esc_html__( "Settings", 'squirrly-seo' ) ) ) {
				return $error;
			}

			return ( $this->restoreSettings( $json ) ? array( 'message' => esc_html__( "Settings restored from the backup file.", 'squirrly-seo' ) ) : esc_html__( "Error! The backup is not valid.", 'squirrly-seo' ) );
		}

		//Briefcase backup: one keyword per line (or CSV, first column), or a JSON list of {keyword}
		$keywords = array();
		if ( is_array( $json ) ) {
			foreach ( $json as $row ) {
				if ( isset( $row['keyword'] ) && is_string( $row['keyword'] ) ) {
					$keywords[] = $row['keyword'];
				}
			}
		} else {
			foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
				$cells      = str_getcsv( $line, ( strpos( $line, ';' ) !== false ? ';' : ',' ) );
				$keywords[] = ( isset( $cells[0] ) ? $cells[0] : '' );
			}
		}

		$keywords = array_values( array_unique( array_filter( array_map( function ( $keyword ) {
			$keyword = trim( sanitize_text_field( (string) $keyword ) );

			return ( $keyword === '' || strlen( $keyword ) > 255 || is_numeric( $keyword ) ) ? '' : $keyword;
		}, $keywords ) ) ) );

		if ( empty( $keywords ) ) {
			return esc_html__( "Error! The backup is not valid.", 'squirrly-seo' );
		}

		if ( $error = $need( 'briefcase', esc_html__( "Briefcase", 'squirrly-seo' ) ) ) {
			return $error;
		}

		SQ_Classes_ObjController::getClass( 'SQ_Classes_RemoteController' );
		$added = $failed = 0;
		foreach ( array_chunk( $keywords, 500 ) as $chunk ) {
			if ( is_wp_error( SQ_Classes_RemoteController::importBriefcaseKeywords( array( 'keywords' => wp_json_encode( $chunk ) ) ) ) ) {
				$failed += count( $chunk );
			} else {
				$added += count( $chunk );
			}
		}

		if ( ! $added && $failed ) {
			return esc_html__( "Briefcase not imported: connect the site to Squirrly Cloud first.", 'squirrly-seo' );
		}

		return array( 'message' => sprintf( esc_html__( "Briefcase: %d keywords imported from the backup file.", 'squirrly-seo' ), $added ) );
	}

	/**
	 * The import in progress, if any.
	 *
	 * @return array|false
	 */
	public function getImportJob() {
		$job = get_option( 'sq_import_job' );

		return ( is_array( $job ) && ! empty( $job['file'] ) ? $job : false );
	}

	/**
	 * Stop the import in progress and delete its file.
	 *
	 * @return void
	 */
	public function cancelImport() {
		if ( $job = $this->getImportJob() ) {
			if ( file_exists( $job['file'] ) ) {
				@unlink( $job['file'] ); // phpcs:ignore
			}
		}

		delete_option( 'sq_import_job' );
	}

	/**
	 * Take an uploaded Export Everything file and get the import ready: unpack it (gzip) into a private file and
	 * remember what to import. The work itself is done by importStep(), in short steps.
	 *
	 * @param string $upload path of the uploaded file
	 * @param array $parts settings, seo, briefcase, redirects, schemas
	 *
	 * @return true|string|array true when the stepped import starts, an error message, or array( 'message' ) when an
	 *                           older backup file was restored right away
	 */
	public function startImport( $upload, $parts ) {
		$this->cancelImport();

		if ( ! $dir = $this->getImportDir() ) {
			return esc_html__( "The uploads folder is not writable, so the file can't be imported.", 'squirrly-seo' );
		}

		$file = $dir . 'import-' . wp_generate_password( 24, false ) . '.jsonl';

		//unpack, 1MB at a time (gzopen also reads a file that isn't compressed)
		$in  = @gzopen( $upload, 'rb' ); // phpcs:ignore
		$out = @fopen( $file, 'wb' ); // phpcs:ignore
		if ( ! $in || ! $out ) {
			return esc_html__( "Error! The backup is not valid.", 'squirrly-seo' );
		}
		while ( ! gzeof( $in ) ) {
			$chunk = gzread( $in, 1048576 );
			if ( $chunk === false ) {
				break;
			}
			fwrite( $out, $chunk );
		}
		gzclose( $in );
		fclose( $out );

		//the first line says what the file is
		$fp   = fopen( $file, 'rb' );
		$meta = json_decode( (string) fgets( $fp ), true );
		fclose( $fp );

		if ( ! isset( $meta['t'], $meta['d']['squirrly_backup'] ) || $meta['t'] !== 'meta' ) {
			//not an Export Everything file: one of the older backups (settings, SEO, Briefcase, redirects, schemas)
			$legacy = $this->importLegacyBackup( $file, $parts );
			@unlink( $file ); // phpcs:ignore

			return $legacy;
		}

		$job = array(
			'file'      => $file,
			'size'      => filesize( $file ),
			'offset'    => 0,
			'parts'     => array_values( $parts ),
			'started'   => time(),
			'label_ids' => array(),
			'existing'  => array(),
			'count'     => array( 'settings' => 0, 'seo' => 0, 'skipped' => 0, 'labels' => 0, 'keywords' => 0, 'failed' => 0, 'qss_redirects' => 0, 'qss_jsonld' => 0 ),
			'notes'     => array(),
		);

		//the Briefcase as it is now, so only what's missing gets added
		if ( in_array( 'briefcase', $parts, true ) ) {
			$current = $this->getCloudBriefcase();
			if ( is_wp_error( $current ) ) {
				$job['parts']   = array_values( array_diff( $job['parts'], array( 'briefcase' ) ) );
				$job['notes'][] = esc_html__( "Briefcase not imported: connect the site to Squirrly Cloud first.", 'squirrly-seo' );
			} else {
				foreach ( $current['labels'] as $label ) {
					$job['label_ids'][ strtolower( trim( $label->name ) ) ] = (int) $label->id;
				}
				foreach ( $current['keywords'] as $row ) {
					if ( isset( $row->keyword ) ) {
						//keep the label ids it has: the label call replaces a keyword's labels, and the import only adds
						$lids = array();
						foreach ( ( ! empty( $row->labels ) ? (array) $row->labels : array() ) as $label ) {
							if ( ! empty( $label->lid ) ) {
								$lids[] = (int) $label->lid;
							}
						}
						$job['existing'][ strtolower( trim( $row->keyword ) ) ] = join( ',', $lids );
					}
				}
			}
		}

		//Advanced Pack tables that exist here, and their columns
		$job['pack'] = array();
		foreach ( $this->getPackTables( $parts ) as $name => $table ) {
			$job['pack'][ $name ] = $GLOBALS['wpdb']->get_col( "SHOW COLUMNS FROM `$table`" );
		}
		if ( array_intersect( array( 'redirects', 'schemas' ), $parts ) && empty( $job['pack'] ) ) {
			$job['notes'][] = esc_html__( "Activate Squirrly SEO - Advanced Pack first, then import the redirects and schemas again.", 'squirrly-seo' );
		}

		update_option( 'sq_import_job', $job, false );

		return true;
	}

	/**
	 * Import the next part of the file: as many lines as fit in a few seconds, so no request gets near a server
	 * time limit, whatever the size of the file.
	 *
	 * @param int $seconds
	 *
	 * @return array done, percent, count, notes
	 */
	public function importStep( $seconds = 8 ) {
		if ( ! $job = $this->getImportJob() ) {
			return array( 'done' => true, 'percent' => 100, 'error' => esc_html__( "There is no import in progress.", 'squirrly-seo' ) );
		}

		if ( ! $fp = @fopen( $job['file'], 'rb' ) ) { // phpcs:ignore
			$this->cancelImport();

			return array( 'done' => true, 'percent' => 100, 'error' => esc_html__( "The import file is gone. Upload it again.", 'squirrly-seo' ) );
		}

		fseek( $fp, (int) $job['offset'] );
		$until   = microtime( true ) + $seconds;
		$groups  = array();
		$pending = array();

		while ( microtime( true ) < $until && ( $raw = fgets( $fp ) ) !== false ) {
			$row = json_decode( $raw, true );
			if ( isset( $row['t'], $row['d'] ) ) {
				$this->importLine( $row['t'], $row['d'], $job, $groups, $pending );
			}

			//send the Briefcase keywords in batches, so a step never waits on thousands of them at once
			if ( count( $pending ) >= 200 || array_sum( array_map( 'count', $groups ) ) >= 200 ) {
				$this->flushBriefcase( $job, $pending, $groups );
			}
		}

		$job['offset'] = ftell( $fp );
		$done          = feof( $fp );
		fclose( $fp );

		$this->flushBriefcase( $job, $pending, $groups );

		$result = array(
			'done'    => $done,
			'percent' => ( $job['size'] ? min( 100, floor( $job['offset'] * 100 / $job['size'] ) ) : 100 ),
			'count'   => $job['count'],
			'notes'   => $job['notes'],
		);

		if ( $done ) {
			$this->cancelImport();
		} else {
			update_option( 'sq_import_job', $job, false );
		}

		return $result;
	}

	/**
	 * Import one line of the file, if its part was selected.
	 *
	 * @param string $type
	 * @param array $data
	 * @param array $job
	 * @param array $groups keywords per label set, for this step
	 * @param array $pending keywords to add to the Briefcase, for this step
	 *
	 * @return void
	 */
	private function importLine( $type, $data, &$job, &$groups, &$pending ) {
		global $wpdb;
		$parts = $job['parts'];

		if ( $type === 'settings' && in_array( 'settings', $parts, true ) ) {
			$job['count']['settings'] = ( $this->restoreSettings( $data ) ? 1 : 0 );

		} elseif ( $type === 'seo' && in_array( 'seo', $parts, true ) ) {
			if ( $this->importSeoRow( $data ) ) {
				$job['count']['seo'] ++;
			} else {
				$job['count']['skipped'] ++;
			}

		} elseif ( $type === 'label' && in_array( 'briefcase', $parts, true ) ) {
			$name = ( isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '' );
			//"Keywords without labels" is the Briefcase filter for unlabeled keywords, not a label
			if ( $name === '' || isset( $job['label_ids'][ strtolower( $name ) ] ) || strtolower( $name ) === 'keywords without labels' ) {
				return;
			}

			$color = ( isset( $data['color'] ) && preg_match( '/^#[a-fA-F0-9]{3,6}$/', $data['color'] ) ? $data['color'] : '#ffffff' );
			if ( ! is_wp_error( SQ_Classes_RemoteController::addBriefcaseLabel( array( 'name' => $name, 'color' => $color ) ) ) ) {
				$job['count']['labels'] ++;

				//read the id of the new label
				$labels = SQ_Classes_RemoteController::getBriefcaseLabels();
				if ( ! is_wp_error( $labels ) ) {
					foreach ( (array) $labels as $label ) {
						if ( isset( $label->id, $label->name ) && (int) $label->id > 0 ) {
							$job['label_ids'][ strtolower( trim( $label->name ) ) ] = (int) $label->id;
						}
					}
				}
			}

		} elseif ( $type === 'keyword' && in_array( 'briefcase', $parts, true ) ) {
			$keyword = ( isset( $data['keyword'] ) ? trim( sanitize_text_field( $data['keyword'] ) ) : '' );
			if ( $keyword === '' || strlen( $keyword ) > 255 ) {
				return;
			}

			//added in batches by flushBriefcase(); the bulk import leaves rank tracking off, so no rank checks are spent
			$lower = strtolower( $keyword );
			if ( ! isset( $job['existing'][ $lower ] ) ) {
				$pending[]                = $keyword;
				$job['existing'][ $lower ] = '';
			}

			$ids = array();
			foreach ( ( ! empty( $data['labels'] ) && is_array( $data['labels'] ) ? $data['labels'] : array() ) as $name ) {
				$name = strtolower( trim( (string) $name ) );
				if ( isset( $job['label_ids'][ $name ] ) ) {
					$ids[] = $job['label_ids'][ $name ];
				}
			}
			//add to the labels the keyword has; no call when it already has them all
			$have = ( $job['existing'][ $lower ] === '' ? array() : array_map( 'intval', explode( ',', $job['existing'][ $lower ] ) ) );
			if ( array_diff( $ids, $have ) ) {
				$ids = array_unique( array_merge( $have, $ids ) );
				sort( $ids );
				$job['existing'][ $lower ]     = join( ',', $ids );
				$groups[ join( ',', $ids ) ][] = $keyword;
			}

		} elseif ( isset( $job['pack'][ $type ] ) && in_array( array_search( $type, $this->getPackParts(), true ), $parts, true ) && is_array( $data ) ) {
			//only the columns this table has; rows keep their ids, so patterns pointing to a reusable schema still find it
			$data = array_intersect_key( $data, array_flip( $job['pack'][ $type ] ) );
			foreach ( $data as $value ) {
				if ( $this->hasSerializedObject( $value ) ) {
					return;
				}
			}
			if ( ! empty( $data ) && $wpdb->replace( $wpdb->prefix . $type, $data ) ) {
				$job['count'][ $type ] ++;
			}
		}
	}

	/**
	 * Add the waiting keywords to the Briefcase (api/briefcase/import, 500 per call) and give the keywords their
	 * labels (api/briefcase/label/keywords, 200 per call, one call per label set: it replaces a keyword's labels).
	 *
	 * @param array $job
	 * @param array $pending
	 * @param array $groups
	 *
	 * @return void
	 */
	private function flushBriefcase( &$job, &$pending, &$groups ) {
		foreach ( array_chunk( $pending, 500 ) as $chunk ) {
			$added = SQ_Classes_RemoteController::importBriefcaseKeywords( array( 'keywords' => wp_json_encode( $chunk ) ) );
			if ( is_wp_error( $added ) ) {
				$job['count']['failed'] += count( $chunk );
			} else {
				$job['count']['keywords'] += count( $chunk );
			}
		}
		$pending = array();

		foreach ( $groups as $labels => $keywords ) {
			foreach ( array_chunk( $keywords, 200 ) as $chunk ) {
				SQ_Classes_RemoteController::saveBriefcaseKeywordsLabel( array( 'keywords' => $chunk, 'labels' => (string) $labels ) );
			}
		}
		$groups = array();
	}

	/**
	 * Whether a stored (serialized) value holds an object: an uploaded file must never bring objects into the database.
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	private function hasSerializedObject( $value ) {
		return is_string( $value ) && (bool) preg_match( '/(^|[;{}])\s*[OC]:\+?\d+:"/', $value );
	}

	/**
	 * Save one row of the SEO table from the file.
	 *
	 * @param array $row
	 *
	 * @return bool
	 */
	private function importSeoRow( $row ) {
		if ( ! is_array( $row ) || ! isset( $row['url_hash'], $row['seo'] ) || ! preg_match( '/^[a-f0-9]{32}$/', (string) $row['url_hash'] ) ) {
			return false;
		}

		$post = ( isset( $row['post'] ) ? (string) $row['post'] : '' );
		$seo  = (string) $row['seo'];

		if ( $this->hasSerializedObject( $post ) || $this->hasSerializedObject( $seo ) ) {
			return false;
		}

		$date = ( isset( $row['date_time'] ) && preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $row['date_time'] ) ? $row['date_time'] : gmdate( 'Y-m-d H:i:s' ) );
		$url  = ( isset( $row['URL'] ) ? esc_url_raw( (string) $row['URL'] ) : '' );

		return ( SQ_Classes_ObjController::getClass( 'SQ_Models_Qss' )->saveSqSEO( $url, (string) $row['url_hash'], $post, $seo, $date ) !== false );
	}
}
