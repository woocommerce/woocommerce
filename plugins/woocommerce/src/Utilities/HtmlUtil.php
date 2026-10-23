<?php
/**
 * A class of utilities for writing text into HTML.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Utilities;

/**
 * A class of utilities for writing text into HTML.
 *
 * @since 11.4.0
 */
final class HtmlUtil {
	/**
	 * Escapes text for an HTML text node so the page shows it exactly as stored, even after wptexturize() runs.
	 *
	 * Block themes texturize the whole rendered template, which turns `48x17` into `48×17` and ` (tm)` into ` ™`.
	 * Every printable ASCII character is written as a numeric entity: wptexturize() only rewrites ASCII patterns and
	 * skips entities. Other characters are kept, since they are neither rewritten nor special in HTML.
	 *
	 * @since 11.4.0
	 *
	 * @param string $text Plain text, such as an email address.
	 * @return string Text escaped for an HTML text node.
	 */
	public static function esc_html_exact( string $text ): string {
		$text    = wp_check_invalid_utf8( $text );
		$escaped = '';
		$length  = strlen( $text );

		for ( $i = 0; $i < $length; $i++ ) {
			$code     = ord( $text[ $i ] );
			$escaped .= ( $code >= 0x20 && $code <= 0x7E ) ? '&#' . $code . ';' : $text[ $i ];
		}

		return $escaped;
	}
}
