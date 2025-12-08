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
 */
class StringHelper {

	/**
	 * Convert value to boolean string ('true' or 'false')
	 *
	 * @param mixed $value Value to convert.
	 * @return string 'true' or 'false'.
	 */
	public static function bool_string( $value ): string {
		$value = strtolower( (string) $value );
		return ( 'true' === $value || '1' === $value || 'yes' === $value ) ? 'true' : 'false';
	}

	/**
	 * Truncate text to specified length
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
