<?php
/**
 * Product attribute slug length utilities.
 *
 * @package WooCommerce\Classes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductAttributes;

/**
 * Utilities for communicating the product attribute slug length limit to users.
 *
 * @internal
 *
 * @since 11.1.0
 */
class AttributeSlugLength {

	/**
	 * Estimate how many characters fit in a product attribute slug for a given locale.
	 *
	 * The slug limit is enforced in bytes (see wc_get_attribute_slug_max_byte_length()),
	 * but users think in characters. UTF-8 encodes a character in 1-4 bytes depending on
	 * its script, so the character budget depends on the language. This maps the locale to
	 * the typical byte width of its script and divides the byte budget by it, yielding a
	 * rough, user-friendly maximum.
	 *
	 * This is a heuristic: a locale predicts the script its users most likely type,
	 * not the actual slug they enter (a Greek-language store may still use Latin slugs),
	 * so callers should present the result as an approximation and keep the byte limit
	 * authoritative.
	 *
	 * @since 11.1.0
	 * @param string $locale Locale to inspect, e.g. 'pt_BR'. Defaults to the current user's locale.
	 * @return int Approximate maximum number of characters, never less than 1.
	 */
	public static function get_character_estimate( string $locale = '' ): int {
		if ( '' === $locale ) {
			// Admin screens render in the user's profile language (get_user_locale()),
			// which can differ from the site language; estimate for what the user
			// actually reads and types.
			$locale = get_user_locale();
		}

		// Reduce the locale to its language subtag, e.g. 'pt_BR' or 'zh-Hans' -> 'pt' / 'zh'.
		$language = strtolower( (string) strtok( $locale, '_-' ) );

		// Space-padded lists of language subtags grouped by the typical UTF-8 byte width of
		// their script, covering the locale IDs WordPress core actually installs — including
		// its three-letter IDs (kir, ckb, snd, sah, ...) — plus common ISO aliases (ky, be).
		// Three bytes: CJK, Thai, Georgian, Ethiopic, Tibetan, and Brahmic (Indic) scripts.
		// Two bytes: Cyrillic, Greek, Hebrew, Arabic-script, and Armenian. Everything else
		// (Latin and unknown) is treated as single-byte. The padding makes each match whole-word.
		// Latin-script languages with heavy diacritics (e.g. Vietnamese) are intentionally
		// single-byte: slugs pass through wc_sanitize_taxonomy_name(), whose sanitize_title()
		// call transliterates accented Latin letters to plain ASCII before the byte limit
		// applies ('Tiếng Việt đậm nhạt' is stored as 'tieng-viet-dam-nhat').
		$three_byte = ' zh ja ko th ka hi bn ne ta te mr gu kn ml pa or si km lo my am as bo dzo ';
		$two_byte   = ' ru uk bg sr be bel mk kk ky kir tg tt mn sah el he ar ary azb ckb fa haz ps skr snd ug ur hy ';

		if ( false !== strpos( $three_byte, " {$language} " ) ) {
			$byte_width = 3;
		} elseif ( false !== strpos( $two_byte, " {$language} " ) ) {
			$byte_width = 2;
		} else {
			$byte_width = 1;
		}

		return max( 1, intdiv( wc_get_attribute_slug_max_byte_length(), $byte_width ) );
	}
}
