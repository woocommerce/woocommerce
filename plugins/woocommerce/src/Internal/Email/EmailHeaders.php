<?php
/**
 * EmailHeaders class file
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Email;

/**
 * Helper class for building safe email headers.
 *
 * @internal Just for internal use.
 * @since 11.3.0
 */
final class EmailHeaders {

	/**
	 * Clean a customer-provided name for use in a Reply-to header.
	 *
	 * Line breaks are collapsed to spaces and commas removed, since wp_mail()
	 * splits Reply-to values on commas. The line break removal runs again
	 * after sanitize_text_field(), since its 'sanitize_text_field' filter
	 * runs last and could reintroduce them.
	 *
	 * @since 11.3.0
	 * @param string $name Name to clean.
	 * @return string
	 */
	public static function sanitize_reply_to_name( string $name ): string {
		$name = str_replace( array( "\r", "\n" ), ' ', sanitize_text_field( $name ) );
		return trim( str_replace( ',', '', $name ) );
	}
}
