<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class SQ_Models_Bulkseo_Visibility extends SQ_Models_Abstract_Assistant {

	protected $_category = 'visibility';

	protected $_patterns;


	public function init() {
		parent::init();

		//Get all the patterns
		$this->_patterns = SQ_Classes_Helpers_Tools::getOption( 'patterns' );

		//For post types who are not in automation, add the custom patterns
		if ( ! isset( $this->_patterns[ $this->_post->post_type ] ) ) {
			$this->_patterns[ $this->_post->post_type ] = $this->_patterns['custom'];
		}
	}

	public function setTasks( $tasks ) {
		parent::setTasks( $tasks );

		$this->_tasks[ $this->_category ] = array(
			'noindex'   => array(
				'title'       => esc_html__( "Visible on Google", 'squirrly-seo' ),
				'description' => sprintf( esc_html__( "Let Google Index this page. %s You need to make sure your settings are turned to green for the 'Let Google Index this Page' section of this URL's visibility settings.", 'squirrly-seo' ), '<br /><br />' ),
			),
			'nofollow'  => array(
				'title'       => esc_html__( "Send Authority to this page", 'squirrly-seo' ),
				'description' => sprintf( esc_html__( "Pass SEO authority to this page. %s If you want this page to really be visible, then you must allow the flow of authority from the previous pages to this one. %s The previous page means any page that leads to the current one. Passing authority from the previous page to this one will improve the current page's visibility. %s You need to make sure your settings are turned to green for the 'Send Authority to this page' section of this URL's visibility settings.", 'squirrly-seo' ), '<br /><br />', '<br /><br />', '<br /><br />' ),
			),
			'nositemap' => array(
				'title'       => esc_html__( "Add page in sitemap", 'squirrly-seo' ),
				'description' => sprintf( esc_html__( "Turn the 'Show it in Sitemap.xml' toggle to ON. %s That setting helps you control if the current URL should be found within the sitemap. There are pages you will want in the sitemap, and pages that you will want out of the sitemap. %s If your purpose is to maximize visibility for the current URL, then you need to add it to Sitemap.", 'squirrly-seo' ), '<br /><br />', '<br /><br />' ),
			),
			'redirect'  => array(
				'title'       => esc_html__( "301 Redirect", 'squirrly-seo' ),
				'value_title' => esc_html__( "Current Redirect", 'squirrly-seo' ),
				'value'       => ( ( isset( $this->_post->sq->redirect ) && $this->_post->sq->redirect <> '' ) ? urldecode( $this->_post->sq->redirect ) : esc_html__( "No Redirects", 'squirrly-seo' ) ),
				'description' => sprintf( esc_html__( "You don't have to set any redirect link if you don't want to redirect to a different URL. %s Squirrly will alert you if you add a redirect URL to make sure you know what you're doing. %s The redirect link will be used to redirect visitors to a different URL when they access the URL of the current post.", 'squirrly-seo' ), '<br /><br />', '<br /><br />' ),
			),
		);


	}

	/**
	 * Return the Category Tile
	 *
	 * @param  $title
	 *
	 * @return string
	 */
	public function getTitle( $title ) {
		if ( $this->_error ) {
			return esc_html__( "Some visibility options are inactive.", 'squirrly-seo' );
		}

		foreach ( $this->_tasks[ $this->_category ] as $task ) {
			if ( $task['completed'] === false ) {
				return '<img src="' . esc_url( _SQ_ASSETS_URL_ . 'img/assistant/tooltip.gif' ) . '" width="100">';
			}
		}

		return esc_html__( "Visibility is set correctly.", 'squirrly-seo' );

	}

	/**
	 * Show Current Post
	 *
	 * @return string
	 */
	public function getHeader() {
		$header = '<li class="completed">' . $this->getCurrentURL( $this->_post->url ) . '</li>';

		return $header;
	}

	/**
	 * Check if Noindex is set to 0
	 *
	 * @return bool|WP_Error
	 */
	public function checkNoindex( $task ) {
		$errors = array();
		if ( ! $this->_post->sq->doseo ) {
			$errors[] = esc_html__( "Squirrly Snippet is deactivated from this post.", 'squirrly-seo' );
		}

		if ( $this->_patterns[ $this->_post->post_type ]['noindex'] ) {
			$errors[] = sprintf( esc_html__( "Noindex for this post type is deactivated from %s Automation > Configuration %s.", 'squirrly-seo' ), '<a href="' . SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_automation', 'automation' ) . '#tab=sq_' . $this->_post->post_type . '" >', '</a>' );
		}

		if ( ! SQ_Classes_Helpers_Tools::getOption( 'sq_auto_noindex' ) ) {
			$errors[] = sprintf( esc_html__( "Robots Meta is deactivated from %s Technical SEO > SEO Metas %s.", 'squirrly-seo' ), '<a href="' . SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_seosettings', 'metas' ) . '" >', '</a>' );
		}

		if ( ! SQ_Classes_Helpers_Tools::getOption( 'sq_auto_metas' ) ) {
			$errors[] = sprintf( esc_html__( "SEO Metas is deactivated from %s Technical SEO > SEO Metas %s.", 'squirrly-seo' ), '<a href="' . SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_seosettings', 'metas' ) . '" >', '</a>' );
		}

		if ( ! empty( $errors ) ) {
			$task['error_message'] = join( '<br />', $errors );
			$task['penalty']       = 100;
			$task['error']         = true;
		}
		if ( get_option( 'blog_public' ) == 0 ) {
			$task['error_message'] = sprintf( esc_html__( "You selected '%s' in Settings > Reading. It's important to uncheck that option.", 'squirrly-seo' ), esc_html__( "Discourage search engines from indexing this site" ) );
			$task['completed']     = 0;
			$task['error']         = false;

			return $task;
		}

		$task['completed'] = ( (int) $this->_post->sq_adm->noindex <> 1 );

		return $task;
	}

	/**
	 * Check if Nofollow is set to 0
	 *
	 * @return bool|WP_Error
	 */
	public function checkNofollow( $task ) {
		$errors = array();
		if ( ! $this->_post->sq->doseo ) {
			$errors[] = esc_html__( "Squirrly Snippet is deactivated from this post.", 'squirrly-seo' );
		}

		if ( $this->_patterns[ $this->_post->post_type ]['nofollow'] ) {
			$errors[] = sprintf( esc_html__( "Nofollow for this post type is deactivated from %s Automation > Configuration %s.", 'squirrly-seo' ), '<a href="' . SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_automation', 'automation' ) . '#tab=sq_' . $this->_post->post_type . '" >', '</a>' );
		}

		if ( ! SQ_Classes_Helpers_Tools::getOption( 'sq_auto_noindex' ) ) {
			$errors[] = sprintf( esc_html__( "Robots Meta is deactivated from %s Technical SEO > SEO Metas %s.", 'squirrly-seo' ), '<a href="' . SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_seosettings', 'metas' ) . '" >', '</a>' );
		}

		if ( ! SQ_Classes_Helpers_Tools::getOption( 'sq_auto_metas' ) ) {
			$errors[] = sprintf( esc_html__( "SEO Metas is deactivated from %s Technical SEO > SEO Metas %s.", 'squirrly-seo' ), '<a href="' . SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_seosettings', 'metas' ) . '" >', '</a>' );
		}

		if ( ! empty( $errors ) ) {
			$task['error_message'] = join( '<br />', $errors );
			$task['error']         = true;
		}

		$task['completed'] = ( (int) $this->_post->sq_adm->nofollow <> 1 );

		return $task;
	}

	/**
	 * Check if Nofollow is set to 0
	 *
	 * @return bool|WP_Error
	 */
	public function checkNositemap( $task ) {
		$errors = array();
		if ( ! $this->_post->sq->doseo ) {
			$errors[] = esc_html__( "Squirrly Snippet is deactivated from this post.", 'squirrly-seo' );
		}

		if ( ! $this->_post->sq->do_sitemap ) {
			$errors[] = sprintf( esc_html__( "This post type is excluded from sitemap. See %s Automation > Configuration %s.", 'squirrly-seo' ), '<a href="' . SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_automation', 'automation' ) . '#tab=sq_' . $this->_post->post_type . '" >', '</a>' );
		}

		if ( ! SQ_Classes_Helpers_Tools::getOption( 'sq_auto_sitemap' ) ) {
			$errors[] = sprintf( esc_html__( "Sitemap XML is deactivated from %s Technical SEO > Tweaks And Sitemap > Sitemap XML %s.", 'squirrly-seo' ), '<a href="' . SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_seosettings', 'tweaks#tab=sitemap' ) . '" >', '</a>' );
		}


		if ( ! empty( $errors ) ) {
			$task['error_message'] = join( '<br />', $errors );
			$task['error']         = true;
		}

		$task['completed'] = ( (int) $this->_post->sq_adm->nositemap <> 1 );

		return $task;
	}

	public function checkRedirect( $task ) {
		if ( ! $this->_post->sq->doseo ) {
			$errors[] = esc_html__( "Squirrly Snippet is deactivated from this post.", 'squirrly-seo' );
		}

		if ( ! SQ_Classes_Helpers_Tools::getOption( 'sq_auto_redirects' ) ) {
			$errors[] = sprintf( esc_html__( "Redirect is deactivated from %s Technical SEO > Tweaks And Sitemap > SEO Links %s.", 'squirrly-seo' ), '<a href="' . SQ_Classes_Helpers_Tools::getAdminUrl( 'sq_seosettings', 'tweaks#tab=links' ) . '" >', '</a>' );
		}

		if ( ! empty( $errors ) ) {
			$task['error_message'] = join( '<br />', $errors );
			$task['error']         = true;
		}

		if ( isset( $this->_post->sq->redirect ) && $this->_post->sq->redirect <> '' ) {
			$task['completed'] = false;
			//            $task['error_message'] = '<span class="text-danger">'.esc_html__("Current Redirect:", 'squirrly-seo') . ' ' . ((isset($this->_post->sq->redirect) && $this->_post->sq->redirect <> '') ? urldecode($this->_post->sq->redirect) : esc_html__("No Redirects", 'squirrly-seo')).'</span>';
			//            $task['error'] = true;
			return $task;
		}

		$task['completed'] = true;

		return $task;
	}
}
