<?php
/**
 *  String Helper class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * String utility helper functions
 *
 * @since 10.5.0
 */
class StringHelper {
	/**
	 * Convert value to boolean string ('true' or 'false')
	 *
	 * @since 10.5.0
	 *
	 * @param mixed $value Value to convert.
	 * @return string 'true' or 'false'.
	 */
	public static function bool_string( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		if ( is_scalar( $value ) || null === $value ) {
			$value = strtolower( (string) $value );
		} else {
			$value = '';
		}
		return ( 'true' === $value || '1' === $value || 'yes' === $value ) ? 'true' : 'false';
	}

	/**
	 * Truncate text to specified length
	 *
	 * @since 10.5.0
	 *
	 * @param string $text Text to truncate.
	 * @param int    $max_length Maximum length.
	 * @return string Truncated text.
	 */
	public static function truncate( string $text, int $max_length ): string {
		if ( mb_strlen( $text ) > $max_length ) {
			return mb_substr( $text, 0, $max_length );
		}
		return $text;
	}
}
