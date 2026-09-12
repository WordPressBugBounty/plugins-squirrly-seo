<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * PSR-4 autoloader for the bundled mcp-oauth library.
 *
 * The library is published on Packagist as wp-media/mcp-oauth and maps the namespace
 * WPMedia\MCP\OAuth\ onto its inc/ directory. It references no Composer runtime classes,
 * so this replaces the generated Composer autoloader entirely.
 *
 * Only ever reached from SQ_Classes_McpOauthController, which has already checked the PHP
 * version - the library is PHP 7.4 source and cannot be parsed below that.
 */

spl_autoload_register( function ( $class ) {
	$prefix = 'WPMedia\\MCP\\OAuth\\';
	$length = strlen( $prefix );

	if ( strncmp( $prefix, $class, $length ) !== 0 ) {
		return;
	}

	$relative = str_replace( '\\', '/', substr( $class, $length ) );

	//The class name becomes a file path, so it must not be able to walk out of inc/. PHP class
	//names cannot contain a dot or a slash, but spl_autoload_register() hands us whatever string
	//the caller passed - a plugin doing class_exists() on request input would otherwise let
	//"WPMedia\MCP\OAuth\..\..\uploads\evil" include an arbitrary .php file. Allowing only the
	//characters a real class name can contain removes the traversal entirely.
	if ( ! preg_match( '#^[A-Za-z0-9_]+(?:/[A-Za-z0-9_]+)*$#', $relative ) ) {
		return;
	}

	$file = __DIR__ . '/mcp-oauth/inc/' . $relative . '.php';

	if ( is_readable( $file ) ) {
		require_once $file;
	}
} );
