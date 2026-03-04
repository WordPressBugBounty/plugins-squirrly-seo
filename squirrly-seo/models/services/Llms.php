<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class SQ_Models_Services_Llms extends SQ_Models_Abstract_Seo {


	public function __construct() {
		parent::__construct();
		add_filter( 'sq_llms', array( $this, 'generateLlms' ) );
		add_filter( 'sq_llms', array( $this, 'showLlms' ), 11 );
	}

	public function generateLlms( $llms = '' ) {
		$llms .= "\n";

		$llms_permission = (array) SQ_Classes_Helpers_Tools::getOption( 'sq_llms_permission' );
		$llms_permission = array_filter( $llms_permission );

		if ( empty( $llms_permission ) ) {
			// Default llms.txt rules
			$llms_permission = array(
				'User-Agent: *',
				'Allow: /',

				'# Sensitive paths',
				'Disallow: /wp-admin/',
				'Disallow: /wp-login.php',
				'Disallow: /xmlrpc.php',
				'Disallow: /wp-json/',
				'Disallow: /?author=',

				'# Usage policy',
				'Training: disallowed',
				'Summarization: allowed',
				'Embedding: allowed',

				'# Attribution',
				'Require-Attribution: true'
			);
		}

		foreach ( $llms_permission as $row ) {
			if ( is_string( $row ) ) {
				if ( strpos( $row, '#' ) !== false ) {
					$llms .= PHP_EOL ;
				}
				$llms .= $row . PHP_EOL;
			}
		}

		$llms .= PHP_EOL . PHP_EOL;

		return apply_filters( 'sq_custom_llms', $llms );
	}

	public function showLlms( $robots = '' ) {
		/**
		 *
		 * display llms.txt
		 */
		header( 'Status: 200 OK', true, 200 );
		header( 'Content-type: text/plain; charset=' . get_bloginfo( 'charset' ) );

		echo esc_textarea( sanitize_textarea_field( $robots ) );
		exit();
	}
}
