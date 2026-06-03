<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class SQ_Classes_Helpers_SiteAuth {

	const OPT_SITE_KEY    = 'sq_site_key';
	const OPT_SITE_UUID   = 'sq_site_uuid';
	const OPT_SITE_ORIGIN = 'sq_site_origin';
	const OPT_TIME_OFFSET = 'sq_time_offset';

	/**
	 * Option keys that must never appear in a settings backup file and must be
	 * preserved from local state (not from imported file) on restore.
	 */
	public static function authOptionKeys() {
		return array(
			self::OPT_SITE_KEY,
			self::OPT_SITE_UUID,
			self::OPT_SITE_ORIGIN,
			self::OPT_TIME_OFFSET,
			'sq_user_blog_id',
			'sq_legacy_auth',
		);
	}

	const SIG_PREFIX = 'sha256=';
	const SKEW_LIMIT = 300;

	public static function getSiteKey() {
		if ( defined( 'SQUIRRLY_SITE_KEY' ) && SQUIRRLY_SITE_KEY ) {
			$hex = (string) SQUIRRLY_SITE_KEY;
			if ( strlen( $hex ) === 64 && ctype_xdigit( $hex ) ) {
				return hex2bin( $hex );
			}
		}

		$hex = SQ_Classes_Helpers_Tools::getOption( self::OPT_SITE_KEY );
		if ( $hex && strlen( $hex ) === 64 && ctype_xdigit( $hex ) ) {
			return hex2bin( $hex );
		}

		return '';
	}

	public static function getSiteKeyHex() {
		$key = self::getSiteKey();
		return $key === '' ? '' : bin2hex( $key );
	}

	public static function getSiteUuid() {
		$uuid = SQ_Classes_Helpers_Tools::getOption( self::OPT_SITE_UUID );
		return $uuid ? (string) $uuid : '';
	}

	public static function getSiteOrigin() {
		$origin = SQ_Classes_Helpers_Tools::getOption( self::OPT_SITE_ORIGIN );
		return $origin ? (string) $origin : '';
	}

	public static function ensureSiteKey() {
		if ( self::getSiteKey() !== '' ) {
			return;
		}

		if ( ! defined( 'SQUIRRLY_SITE_KEY' ) ) {
			$hex = bin2hex( self::randomBytes( 32 ) );
			SQ_Classes_Helpers_Tools::saveOptions( self::OPT_SITE_KEY, $hex );
		}

		if ( self::getSiteUuid() === '' ) {
			SQ_Classes_Helpers_Tools::saveOptions( self::OPT_SITE_UUID, self::generateUuid() );
		}

		if ( self::getSiteOrigin() === '' ) {
			SQ_Classes_Helpers_Tools::saveOptions( self::OPT_SITE_ORIGIN, (string) get_bloginfo( 'url' ) );
		}
	}

	public static function clearSiteKey() {
		SQ_Classes_Helpers_Tools::saveOptions( self::OPT_SITE_KEY, false );
		SQ_Classes_Helpers_Tools::saveOptions( self::OPT_SITE_UUID, false );
		SQ_Classes_Helpers_Tools::saveOptions( self::OPT_SITE_ORIGIN, false );
		SQ_Classes_Helpers_Tools::saveOptions( self::OPT_TIME_OFFSET, false );
	}

	public static function canonical( $method, $path, $body, $url, $timestamp, $nonce ) {
		return strtoupper( $method ) . "\n"
			. $path . "\n"
			. hash( 'sha256', (string) $body ) . "\n"
			. $url . "\n"
			. (string) $timestamp . "\n"
			. $nonce;
	}

	public static function sign( $canonical ) {
		$key = self::getSiteKey();
		if ( $key === '' ) {
			return '';
		}
		return self::SIG_PREFIX . hash_hmac( 'sha256', $canonical, $key );
	}

	public static function signRaw( $payload ) {
		$key = self::getSiteKey();
		if ( $key === '' ) {
			return '';
		}
		return self::SIG_PREFIX . hash_hmac( 'sha256', $payload, $key );
	}

	public static function verify( $canonical, $providedSig ) {
		$expected = self::sign( $canonical );
		if ( $expected === '' || strpos( (string) $providedSig, self::SIG_PREFIX ) !== 0 ) {
			return false;
		}
		return hash_equals( $expected, (string) $providedSig );
	}

	public static function buildSignedHeaders( $method, $path, $body, $url, $blogId, $userToken ) {
		$key = self::getSiteKey();
		if ( $key === '' || ! $blogId ) {
			return array();
		}

		$timestamp = self::now();
		$nonce     = bin2hex( self::randomBytes( 16 ) );
		$canonical = self::canonical( $method, $path, $body, $url, $timestamp, $nonce );

		return array(
			'X-SQ-Blog-Id'    => (string) $blogId,
			'X-SQ-User-Token' => (string) $userToken,
			'X-SQ-Url'        => $url,
			'X-SQ-Origin'     => self::getSiteOrigin() ?: $url,
			'X-SQ-Timestamp'  => (string) $timestamp,
			'X-SQ-Nonce'      => $nonce,
			'X-SQ-Sig'        => self::sign( $canonical ),
		);
	}

	public static function now() {
		$offset = (int) SQ_Classes_Helpers_Tools::getOption( self::OPT_TIME_OFFSET );
		return time() + $offset;
	}

	public static function setServerTime( $serverTime ) {
		$serverTime = (int) $serverTime;
		if ( $serverTime <= 0 ) {
			return;
		}
		SQ_Classes_Helpers_Tools::saveOptions( self::OPT_TIME_OFFSET, $serverTime - time() );
	}

	public static function generateUuid() {
		$b = self::randomBytes( 16 );
		$b[6] = chr( ( ord( $b[6] ) & 0x0f ) | 0x40 );
		$b[8] = chr( ( ord( $b[8] ) & 0x3f ) | 0x80 );
		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $b ), 4 ) );
	}

	private static function randomBytes( $len ) {
		if ( function_exists( 'random_bytes' ) ) {
			return random_bytes( $len );
		}
		if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return openssl_random_pseudo_bytes( $len );
		}
		$bytes = '';
		for ( $i = 0; $i < $len; $i++ ) {
			$bytes .= chr( mt_rand( 0, 255 ) );
		}
		return $bytes;
	}
}
