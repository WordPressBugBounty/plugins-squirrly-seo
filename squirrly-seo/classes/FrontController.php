<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * The main class for controllers
 */
class SQ_Classes_FrontController {

	/**
	 *
	 *
	 * @var object of the model class
	 */
	public $model;

	/**
	 *
	 *
	 * @var boolean
	 */
	public $flush = true;

	/**
	 *
	 *
	 * @var string name of the  class
	 */
	private $name;

	public function __construct() {
		// Load error class
		SQ_Classes_ObjController::getClass( 'SQ_Classes_Error' );

		/* get the name of the current class */
		$this->name = get_class( $this );

		/* load the model and hooks here for wordpress actions to take efect */
		/* create the model and view instances */
		$model_classname = str_replace( 'Controllers', 'Models', $this->name );
		if ( SQ_Classes_ObjController::getClassPath( $model_classname ) ) {
			$this->model = SQ_Classes_ObjController::getClass( $model_classname );
		}

		//IMPORTANT TO LOAD HOOKS HERE
		/* check if there is a hook defined in the controller clients class */
		SQ_Classes_ObjController::getClass( 'SQ_Classes_HookController' )->setHooks( $this );

		/* Load the Submit Actions Handler */
		SQ_Classes_ObjController::getClass( 'SQ_Classes_ActionController' );
		SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' );
		SQ_Classes_ObjController::getClass( 'SQ_Classes_Helpers_Cache' );
		SQ_Classes_ObjController::getClass( 'SQ_Classes_Helpers_SiteAuth' );

		// load the abstract classes
		SQ_Classes_ObjController::getClass( 'SQ_Models_Abstract_Domain' );
		SQ_Classes_ObjController::getClass( 'SQ_Models_Abstract_Models' );
		SQ_Classes_ObjController::getClass( 'SQ_Models_Abstract_Seo' );
	}

	public function getClass() {
		return $this->name;
	}

	/**
	 * load sequence of classes
	 * Function called usualy when the controller is loaded in WP
	 *
	 * @return mixed
	 */
	public function init() {
		$class = SQ_Classes_ObjController::getClassPath( $this->name );

		if ( ! $this->flush ) {
			return $this->get_view( $class['name'] );
		}

		SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->loadMedia( $class['name'] );
		$this->show_view( $class['name'] );

		return false;
	}

	/**
	 * Get the view block
	 *
	 * @param string $view Class name
	 *
	 * @return mixed
	 */
	public function get_view( $view ) {
		return SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->get_view( $view, $this );
	}

	/**
	 * Deprecated since version 11.1.11
	 *
	 * @param $view
	 *
	 * @return mixed
	 */
	public function getView( $view ) {
		return $this->get_view( $view );
	}

	/**
	 * Show the view block
	 *
	 * @param string $view Class name
	 *
	 * @return void
	 */
	public function show_view( $view ) {
		$content = SQ_Classes_ObjController::getClass( 'SQ_Classes_DisplayController' )->get_view( $view, $this );

		//Support for international languages
		if ( function_exists( 'iconv' ) && SQ_Classes_Helpers_Tools::getOption( 'sq_non_utf8_support' ) ) {
			if ( strpos( get_bloginfo( "language" ), 'en' ) === false ) {
				$content = iconv( 'UTF-8', 'UTF-8//IGNORE', $content );
			}
		}

		//echo the file from /view directory
		//already escaped in the view
		//Contains HTML output
		echo $content;
	}

	/**
	 * Called as menu callback to show the block
	 */
	public function show() {
		$this->flush = true;

		echo $this->init();
	}

	/**
	 * initialize settings
	 * Called from index
	 *
	 * @return void
	 */
	/**
	 * The retry runs from wp-cron, which boots like the frontend, so both paths register it
	 */
	public static function hookIndexnowRetry() {
		add_action( 'sq_indexnow_retry', function( $urls, $attempt = 1 ) {
			SQ_Classes_ObjController::getClass( 'SQ_Models_Indexnow' )->retry( $urls, $attempt );
		}, 10, 2 );
	}

	public function runAdmin() {
		self::hookIndexnowRetry();

		// load the remote controller in admin
		SQ_Classes_ObjController::getClass( 'SQ_Classes_RemoteController' );
		SQ_Classes_ObjController::getClass( 'SQ_Models_Abstract_Assistant' );

		// show the admin menu and post actions
		SQ_Classes_ObjController::getClass( 'SQ_Controllers_Menu' );

		//Check plugin compatibility
		SQ_Classes_ObjController::getClass( 'SQ_Models_Compatibility' )->hookBuildersBackend();
		SQ_Classes_ObjController::getClass( 'SQ_Models_Compatibility' )->checkMultilingual();
	}

	/**
	 * Run fron frontend
	 */
	public function runFrontend() {
		self::hookIndexnowRetry();

		//WooCommerce product searches as indexable pages; guarded so a missing file can never break the frontend
		if ( $searches = SQ_Classes_ObjController::getClass( 'SQ_Models_Searches' ) ) {
			$searches->hookFrontend();
		}
		//load the frontend builders support
		SQ_Classes_ObjController::getClass( 'SQ_Models_Compatibility' )->hookBuildersFrontend();

		//Check if there is an editor loading
		//Don't load Squirrly METAs while in frontend editors
		if ( SQ_Classes_ObjController::getClass( 'SQ_Models_Compatibility' )->checkBuilderPreview() ) {
			return;
		}

		//Load Frontend only if Squirrly SEO is enabled
		SQ_Classes_ObjController::getClass( 'SQ_Controllers_Frontend' );

		/* show the topbar admin menu and post actions */
		SQ_Classes_ObjController::getClass( 'SQ_Controllers_Snippet' );

		/* call the API for save posts */
		SQ_Classes_ObjController::getClass( 'SQ_Controllers_Api' );

		//Check plugin compatibility
		SQ_Classes_ObjController::getClass( 'SQ_Models_Compatibility' )->checkMultilingual();
	}

	/**
	 * Check if a ?tab= value may be called as a method on this controller.
	 *
	 * Tabs are view names. Lifecycle and dispatcher methods are public for
	 * other reasons and must never be reachable this way: action() would run
	 * the action switch outside the nonce check in the ActionController, and
	 * init() would call itself until the request runs out of memory.
	 *
	 * @param string $tab
	 *
	 * @return bool
	 */
	protected function isTabCallable( $tab ) {
		$reserved = array( 'action', 'init', 'construct', 'istabcallable' );
		$tab      = (string) $tab;

		if ( $tab == '' || in_array( strtolower( $tab ), $reserved ) ) {
			return false;
		}

		//hook* methods are WordPress callbacks, not tabs
		if ( strpos( strtolower( $tab ), 'hook' ) === 0 ) {
			return false;
		}

		return method_exists( $this, $tab );
	}

	/**
	 * first function call for any class
	 */
	protected function action() {
	}

	/**
	 * This function will load the media in the header for each class
	 *
	 * @return void
	 */
	public function hookHead() {
	}

}
