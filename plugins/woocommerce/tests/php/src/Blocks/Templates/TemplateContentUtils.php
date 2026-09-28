<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Templates;

/**
 * Helpers for comparing template HTML in tests.
 */
class TemplateContentUtils {

	/**
	 * Remove whitespace so template HTML can be compared without formatting differences.
	 *
	 * @param string $content Template HTML.
	 * @return string
	 */
	public static function strip_whitespace( string $content ): string {
		return preg_replace( '/\s+/', '', $content );
	}

	/**
	 * Remove whitespace and dynamic password form IDs so HTML can be compared.
	 *
	 * WordPress generates unique IDs like pwbox-123 on each call to get_the_password_form().
	 *
	 * @param string $content Template HTML.
	 * @return string
	 */
	public static function strip_whitespace_and_password_form_ids( string $content ): string {
		return preg_replace( '/pwbox-\d+/', '', self::strip_whitespace( $content ) );
	}
}
