<?php defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' ); ?>
<?php
/*
 * Copyright (c) 2012-2026, Squirrly.
 * The copyrights to the software code in this file are licensed under the (revised) BSD open source license.

 * Plugin Name: GEO Plugin by Squirrly SEO
 * Plugin URI: https://wordpress.org/plugins/squirrly-seo/
 * Description: SEO, AEO and GEO for WordPress: rank on Google and get cited by ChatGPT, Perplexity, Gemini and AI Overviews. GEO/AEO Audit, LLM Indexing, llms.txt, Schema, Inner Links, AI Keyword Research.
 * Author: Squirrly
 * Author URI: https://plugin.squirrly.co
 * Version: 14.2.2
 * Requires at least: 5.3
 * Requires PHP: 7.0
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: squirrly-seo
 * Domain Path: /languages
 */

if ( ! defined( 'SQ_VERSION' ) ) {
	/* SET THE CURRENT VERSION ABOVE AND BELOW */
	define( 'SQ_VERSION', '14.2.2' );
	//The last stable version
	define( 'SQ_STABLE_VERSION', '14.2.1' );
	// Call config files
	try {
		include_once dirname( __FILE__ ) . '/config/config.php';
		include_once dirname( __FILE__ ) . '/debug/index.php';

		/* important to check the PHP version */
		// inport main classes
		include_once _SQ_CLASSES_DIR_ . 'ObjController.php';

		// Load helpers
		SQ_Classes_ObjController::getClass( 'SQ_Classes_Helpers_Tools' );
		SQ_Classes_ObjController::getClass( 'SQ_Classes_Helpers_Sanitize' );
		// Load the Front and Block controller
		SQ_Classes_ObjController::getClass( 'SQ_Classes_FrontController' );
		SQ_Classes_ObjController::getClass( 'SQ_Classes_BlockController' );

		// Upgrade Squirrly call.
		register_activation_hook( __FILE__, array(
			SQ_Classes_ObjController::getClass( 'SQ_Classes_Helpers_Tools' ),
			'sq_activate'
		) );
		register_deactivation_hook( __FILE__, array(
			SQ_Classes_ObjController::getClass( 'SQ_Classes_Helpers_Tools' ),
			'sq_deactivate'
		) );

		// Expose Squirrly through the WordPress Abilities API so external apps and AI
		// tools can reach it. Loaded before the admin/frontend split because a REST or
		// MCP request is neither. No-op on WordPress older than 6.9.
		SQ_Classes_ObjController::getClass( 'SQ_Classes_AbilitiesController' );

		if ( SQ_Classes_Helpers_Tools::isBackedAdmin() ) {
			SQ_Classes_ObjController::getClass( 'SQ_Classes_FrontController' )->runAdmin();
		} else {
			SQ_Classes_ObjController::getClass( 'SQ_Classes_FrontController' )->runFrontend();
		}


	} catch ( Exception $e ) {
	}
}
