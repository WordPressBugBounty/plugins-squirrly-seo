<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class SQ_Models_FocusPages {

	protected $_task;
	//Focus pages Categories
	protected $_task_categories_labels;
	protected $_task_categories;

	//Processed Assistant Tasks and Categories
	protected $_assistant_tasks;
	protected $_assistant_categories;

	/**
	 *
	 *
	 * @var SQ_Models_Domain_FocusPage
	 */
	protected $_focuspage;

	public function init() {

		$this->_task_categories = array(
			'indexability' => esc_html__( "Visibility", 'squirrly-seo' ),
			'keyword'      => esc_html__( "Keyword", 'squirrly-seo' ),
			'strategy'     => esc_html__( "Strategy", 'squirrly-seo' ),
			'content'      => esc_html__( "SEO Content", 'squirrly-seo' ),
			'length'       => esc_html__( "Words / Page", 'squirrly-seo' ),
			'onpage'       => esc_html__( "Platform SEO", 'squirrly-seo' ),
			'snippet'      => esc_html__( "Snippet", 'squirrly-seo' ),
			'image'        => esc_html__( "SEO Image", 'squirrly-seo' ),
			'traffic'      => esc_html__( "Traffic Health", 'squirrly-seo' ),
			'audit'        => esc_html__( "Platform Health", 'squirrly-seo' ),
			'authority'    => esc_html__( "Page Authority", 'squirrly-seo' ),
			'social'       => esc_html__( "Social Signals", 'squirrly-seo' ),
			'backlinks'    => esc_html__( "Backlinks", 'squirrly-seo' ),
			'innerlinks'   => esc_html__( "Inner Links", 'squirrly-seo' ),
			'nofollow'     => esc_html__( "Outbound Links", 'squirrly-seo' ),
			'accuracy'     => esc_html__( "Accuracy", 'squirrly-seo' ),
			'ctr'          => esc_html__( "CTR", 'squirrly-seo' ),
			'impressions'  => esc_html__( "Impressions", 'squirrly-seo' ),
			'clicks'       => esc_html__( "Clicks", 'squirrly-seo' ),
		);

		foreach ( $this->_task_categories as $category => $title ) {
			$this->_task_categories_labels[ $category ] = array(
				'color' => '#D32F2F',
				'name'  => $title,
				'show'  => false
			);
		}

		return $this;
	}

	/**
	 * Parse all categories for a single page
	 *
	 * @param SQ_Models_Domain_FocusPage $focuspage
	 * @param array $labels
	 *
	 * @return $this
	 */
	public function parseFocusPage( SQ_Models_Domain_FocusPage $focuspage, $labels = array() ) {
		//set focus pages from API
		$this->_focuspage = $focuspage;

		//call  focus page tasks for all categories
		$this->parseAllTasks();
		$assistant_tasks = apply_filters( 'sq_assistant_tasks', array() );

		$this->_assistant_categories[ $this->_focuspage->id ] = apply_filters( 'sq_assistant_categories', array() );
		//remove the filters for the next focus page
		remove_all_filters( 'sq_assistant_tasks' );
		remove_all_filters( 'sq_assistant_categories' );

		foreach ( $this->_task_categories as $category => $array ) {

			if ( ! empty( $assistant_tasks[ $category ] ) ) {
				$this->_assistant_categories[ $this->_focuspage->id ][ $category ]['assistant'] = $this->getAssistant( $category, $assistant_tasks[ $category ], $this->_assistant_categories[ $this->_focuspage->id ][ $category ] );
			}

			//if the category is NOT complete and doesn't have erros
			if ( isset( $this->_assistant_categories[ $this->_focuspage->id ][ $category ] ) ) {
				if ( ! $this->_assistant_categories[ $this->_focuspage->id ][ $category ]['completed'] && ! $this->_assistant_categories[ $this->_focuspage->id ][ $category ]['error'] ) {
					$this->_task_categories_labels[ $category ]['show'] = true;
				}
			}

		}

		$audit = $this->_focuspage->getAudit();

		if ( ! isset( $audit->properties ) || ! isset( $audit->data->sq_seo_keywords->value ) || $audit->data->sq_seo_keywords->value == '' ) {
			$this->_focuspage->visibility = 'N/A';
		} else {
			$post = $this->_focuspage->getWppost();
			if ( $post->post_status <> 'publish' ) { //just if the Focus Page is public
				$this->_focuspage->visibility  = 'N/A';
				$this->_focuspage->audit_error = 404;
			}
		}

		//set the categories for this page
		add_filter( 'sq_assistant_categories_page', array( $this, 'getAssistantCategories' ) );


		return $this;
	}

	public function getCategories() {
		return json_decode( wp_json_encode( $this->_task_categories ) );
	}

	public function getAssistantCategories( $id ) {
		return json_decode( wp_json_encode( $this->_assistant_categories[ $id ] ) );
	}

	public function getLabels() {
		return json_decode( wp_json_encode( apply_filters( 'sq_categories_labels', $this->_task_categories_labels ) ) );
	}

	public function getFocusPage() {
		return $this->_focuspage;
	}

	public function getAssistant( $category_name = '', $tasks = array(), $category = array() ) {
		$content = '';
		if ( ! empty( $tasks ) && ! empty( $category ) ) {
			$content .= '<ul id="sq_assistant_tasks_' . $category_name . '_' . $this->_focuspage->id . '" class="p-0 m-0" style="display:none;">';
			$content .= ( isset( $category['header'] ) ? $category['header'] : '' );

			foreach ( $tasks as $name => $task ) {
				$task_content = '<li class="sq_task row ' . ( isset( $task['status'] ) ? $task['status'] : '' ) . '" data-category="' . $category_name . '" data-name="' . $name . '" data-active="' . $task['active'] . '" data-completed="' . $task['completed'] . '"  data-dismiss="modal">
                            <i class="fa-solid fa-check" title="' . wp_strip_all_tags( $task['error_message'] ) . '"></i>
                            <h4>' . $task['title'] . '</h4>
                            <div class="description" style="display: none">' . $task['description'] . '</div>
                            <div class="message" style="display: none">' . $task['error_message'] . '</div>
                            </li>';

				//Change task format ondemand
				$content .= apply_filters( 'sq_assistant_' . $category_name . '_task_' . $name, $task_content, $task );

				//remove the filters for the next focus page
				remove_all_filters( 'sq_assistant_' . $category_name . '_task_' . $name );

			}

			$content .= '</ul>';
		}

		return $content;
	}


	/**
	 * Get the admin Menu Tabs
	 *
	 * @return void
	 */
	public function parseAllTasks() {
		foreach ( $this->_task_categories as $category => $title ) {

			SQ_Classes_ObjController::getNewClass( 'SQ_Models_Focuspages_' . ucfirst( $category ) )->setAudit( $this->_focuspage->getAudit() )//set the audit received from API
			                        ->setPost( $this->_focuspage->getWppost() )//set the local post in focuspage model
			                        ->init();
		}
	}
	/**
	 * The WordPress-side data the Focus Pages sections read (Snippet, Platform SEO, Indexability, Inner Links, Nofollow,
	 * Image, Content), sent with set-focus / update-focus so Squirrly Cloud can compute Focus Pages too. The plugin keeps
	 * computing them locally. Field names agreed with Squirrly API (2026-11-05).
	 *
	 * @param int $post_id
	 *
	 * @return string JSON, or '' when the post can't be read
	 */
	public function getSiteSignals( $post_id ) {
		try {
			$wp_post = get_post( (int) $post_id );
			if ( ! $wp_post ) {
				return '';
			}

			/** @var SQ_Models_Domain_Post $post */
			$post = SQ_Classes_ObjController::getClass( 'SQ_Models_Snippet' )->setPostByID( (int) $post_id );
			if ( ! $post || ! isset( $post->sq ) ) {
				return '';
			}

			$sq  = $post->sq;
			$adm = $post->sq_adm;

			//the same <img src> list the Image section reads, at most 20
			$images = array();
			if ( $wp_post->post_content <> '' && preg_match_all( '/<img[^>]*src=[\'"]([^\'"]+)[\'"][^>]*>/i', stripslashes( $wp_post->post_content ), $out ) ) {
				$images = array_slice( array_values( array_unique( $out[1] ) ), 0, 20 );
			}

			//the image Snippet uses when the OG / Twitter image is empty: featured image first, then the first content image
			$featured = '';
			$og       = SQ_Classes_ObjController::getNewClass( 'SQ_Models_Services_OpenGraph' );
			$og->setPost( $post );
			$post_images = $og->getPostImages();
			if ( ! empty( $post_images ) ) {
				$first    = current( $post_images );
				$featured = isset( $first['src'] ) ? (string) $first['src'] : '';
			}

			$patterns = SQ_Classes_Helpers_Tools::getOption( 'patterns' );
			$tasks    = json_decode( (string) get_option( SQ_TASKS ), true );
			$sitemap  = SQ_Classes_Helpers_Tools::getOption( 'sq_sitemap_show' );

			return wp_json_encode( array(
				'version'      => SQ_VERSION,
				'post'         => array(
					'post_id'           => (int) $wp_post->ID,
					'post_type'         => (string) $wp_post->post_type,
					'url'               => (string) $post->url,
					'post_modified_gmt' => (string) $wp_post->post_modified_gmt,
					'content_images'    => $images,
					'featured_image'    => $featured,
				),
				'snippet'      => array(
					'doseo'                    => (int) $sq->doseo,
					'title'                    => (string) $sq->title,
					'description'              => (string) $sq->description,
					'keywords'                 => (string) $sq->keywords,
					'og_title'                 => (string) $sq->og_title,
					'og_description'           => (string) $sq->og_description,
					'og_media'                 => (string) $sq->og_media,
					'tw_title'                 => (string) $sq->tw_title,
					'tw_description'           => (string) $sq->tw_description,
					'tw_media'                 => (string) $sq->tw_media,
					'og_title_maxlength'       => (int) $sq->og_title_maxlength,
					'og_description_maxlength' => (int) $sq->og_description_maxlength,
					'tw_title_maxlength'       => (int) $sq->tw_title_maxlength,
					'tw_description_maxlength' => (int) $sq->tw_description_maxlength,
					'adm_title'                => ( $adm ? (string) $adm->title : '' ),
				),
				'platform'     => array(
					'sq_auto_pattern' => (int) SQ_Classes_Helpers_Tools::getOption( 'sq_auto_pattern' ),
					'pattern_exists'  => (int) isset( $patterns[ $wp_post->post_type ] ),
					'do_pattern'      => (int) $sq->do_pattern,
					'adm_doseo'       => ( $adm ? (int) $adm->doseo : 0 ),
					'sq_auto_sitemap' => (int) SQ_Classes_Helpers_Tools::getOption( 'sq_auto_sitemap' ),
					'sq_sitemap_show' => array(
						'images' => (int) ( isset( $sitemap['images'] ) && $sitemap['images'] ),
						'videos' => (int) ( isset( $sitemap['videos'] ) && $sitemap['videos'] ),
					),
					'adm_nositemap'   => ( $adm ? (int) $adm->nositemap : 0 ),
					'sq_auto_robots'  => (int) SQ_Classes_Helpers_Tools::getOption( 'sq_auto_robots' ),
				),
				'indexability' => array(
					'do_sitemap'    => (int) ( $sq->do_sitemap && empty( $sq->nositemap ) ), //same rule as Indexability "Present in sitemap"
					'canonical'     => (string) $sq->canonical,
					'gscindex_done' => (int) ! empty( $tasks['indexability']['gscindex'][ (int) $wp_post->ID ] ),
				),
				'innerlinks'   => array( 'sq_auto_innelinks' => (int) SQ_Classes_Helpers_Tools::getOption( 'sq_auto_innelinks' ) ),
				'nofollow'     => array(
					'home_url'              => home_url(),
					'site_url'              => site_url(),
					'sq_external_exception' => array_slice( array_values( array_filter( (array) SQ_Classes_Helpers_Tools::getOption( 'sq_external_exception' ) ) ), 0, 100 ),
				),
				'site'         => array(
					'sq_og_image' => (string) SQ_Classes_Helpers_Tools::getOption( 'sq_og_image' ),
					'sq_tc_image' => (string) SQ_Classes_Helpers_Tools::getOption( 'sq_tc_image' ),
				),
			) );
		} catch ( Exception $e ) {
			return '';
		}
	}
}
