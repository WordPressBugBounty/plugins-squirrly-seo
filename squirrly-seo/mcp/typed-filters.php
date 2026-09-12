<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * GPLv2-compatible stand-in for wp-media/apply-filters-typed.
 *
 * The bundled mcp-oauth library calls wpm_apply_filters_typed() in four places, with the
 * types "boolean", "string" and "array". The upstream package that provides it is
 * GPL-3.0-or-later, which Squirrly cannot bundle without relicensing the whole plugin, so
 * the contract is reimplemented here instead.
 *
 * Both functions are guarded: another plugin (WP Rocket ships the real package) may already
 * have declared them. Composer "files" autoloads run while plugin main files are included,
 * which is before plugins_loaded fires, and this file is only loaded on plugins_loaded - so
 * by the time we get here a real copy has always declared itself already.
 */

if ( ! function_exists( 'wpm_is_type' ) ) {
	/**
	 * Whether a value matches a gettype()-style type name.
	 *
	 * @param string $type  Type name, optionally prefixed with "?" for nullable.
	 * @param mixed  $value Value to test.
	 *
	 * @return bool
	 */
	function wpm_is_type( $type, $value ) {
		$type = strtolower( $type );

		if ( '?' === substr( $type, 0, 1 ) ) {
			$type = substr( $type, 1 );

			if ( is_null( $value ) ) {
				return true;
			}
		}

		switch ( $type ) {
			case 'boolean':
				return is_bool( $value );
			case 'integer':
				return is_int( $value );
			case 'double':
				return is_float( $value );
			case 'string':
				return is_string( $value );
			case 'array':
				return is_array( $value );
			case 'object':
				return is_object( $value );
			case 'null':
				return is_null( $value );
			case 'false':
				return false === $value;
			case 'true':
				return true === $value;
			default:
				return false;
		}
	}
}

if ( ! function_exists( 'wpm_apply_filters_typed' ) ) {
	/**
	 * apply_filters() that discards a filtered value of the wrong type.
	 *
	 * A filter that returns the wrong type would otherwise reach code with a declared
	 * parameter type and cause a TypeError, so the unfiltered value is returned instead.
	 *
	 * @param string $type      Expected type of the return value. "a|b" allows either.
	 * @param string $hook_name Filter hook name.
	 * @param mixed  $value     Value to filter.
	 * @param mixed  ...$args   Further arguments passed to the callbacks.
	 *
	 * @return mixed
	 */
	function wpm_apply_filters_typed( $type, $hook_name, $value, ...$args ) {
		$next_value = apply_filters( $hook_name, $value, ...$args ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound

		$types = ( false !== strpos( $type, '|' ) ) ? explode( '|', $type ) : array( $type );

		foreach ( $types as $single_type ) {
			if ( wpm_is_type( $single_type, $next_value ) ) {
				return $next_value;
			}
		}

		return $value;
	}
}

if ( ! function_exists( 'wpm_apply_filters_typesafe' ) ) {
	/**
	 * wpm_apply_filters_typed() with the type taken from the unfiltered value.
	 *
	 * @param string $hook_name Filter hook name.
	 * @param mixed  $value     Value to filter.
	 * @param mixed  ...$args   Further arguments passed to the callbacks.
	 *
	 * @return mixed
	 */
	function wpm_apply_filters_typesafe( $hook_name, $value, ...$args ) {
		return wpm_apply_filters_typed( gettype( $value ), $hook_name, $value, ...$args );
	}
}
