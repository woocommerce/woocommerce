<?php
/**
 * Attribute slug length tests.
 *
 * @package WooCommerce\Tests\Internal\ProductAttributes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductAttributes;

use Automattic\WooCommerce\Internal\ProductAttributes\AttributeSlugLength;
use WC_Unit_Test_Case;

/**
 * Tests for the attribute slug length utility.
 */
class AttributeSlugLengthTest extends WC_Unit_Test_Case {

	/**
	 * Reset the current user so a locale-specific admin created by a test cannot
	 * leak into later tests, even if an assertion fails before an inline reset.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @testdox Should derive the character estimate from the typical byte width of the locale's script.
	 * @dataProvider locale_estimate_provider
	 *
	 * @param string $locale     Locale under test.
	 * @param int    $byte_width Typical UTF-8 byte width of the locale's script.
	 */
	public function test_estimates_characters_by_script_byte_width( string $locale, int $byte_width ): void {
		$this->assertSame(
			intdiv( wc_get_attribute_slug_max_byte_length(), $byte_width ),
			AttributeSlugLength::get_character_estimate( $locale ),
			"Locale {$locale} should be treated as {$byte_width} byte(s) per character"
		);
	}

	/**
	 * Data provider for the per-locale estimate test.
	 *
	 * @return array
	 */
	public function locale_estimate_provider(): array {
		return array(
			'English (Latin)'                 => array( 'en_US', 1 ),
			'Portuguese (Latin)'              => array( 'pt_BR', 1 ),
			'Unknown locale (Latin)'          => array( 'xx_YY', 1 ),
			// Vietnamese diacritics are transliterated to ASCII by sanitize_title()
			// before the byte limit applies, so it gets the full Latin budget.
			'Vietnamese (transliterated)'     => array( 'vi', 1 ),
			'Russian (Cyrillic)'              => array( 'ru_RU', 2 ),
			'Belarusian (Cyrillic, 3-letter)' => array( 'bel', 2 ),
			'Kyrgyz (Cyrillic, 3-letter)'     => array( 'kir', 2 ),
			'Sakha (Cyrillic, 3-letter)'      => array( 'sah', 2 ),
			'Greek'                           => array( 'el', 2 ),
			'Hebrew'                          => array( 'he_IL', 2 ),
			'Sorani Kurdish (Arabic script)'  => array( 'ckb', 2 ),
			'Uyghur (Arabic script)'          => array( 'ug_CN', 2 ),
			'Chinese (CJK)'                   => array( 'zh_CN', 3 ),
			'Chinese (hyphenated tag)'        => array( 'zh-Hans', 3 ),
			'Japanese (CJK)'                  => array( 'ja', 3 ),
			'Thai'                            => array( 'th', 3 ),
			'Amharic (Ethiopic)'              => array( 'am', 3 ),
			'Tibetan'                         => array( 'bo', 3 ),
			'Hindi (Devanagari)'              => array( 'hi_IN', 3 ),
			'Nepali (Devanagari)'             => array( 'ne_NP', 3 ),
		);
	}

	/**
	 * @testdox Should default to the current user's locale rather than the site locale.
	 */
	public function test_defaults_to_user_locale(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'   => 'administrator',
				'locale' => 'ru_RU',
			)
		);
		wp_set_current_user( $user_id );

		$this->assertSame(
			AttributeSlugLength::get_character_estimate( 'ru_RU' ),
			AttributeSlugLength::get_character_estimate(),
			'The default estimate should follow the user profile locale, not the site locale'
		);
	}

	/**
	 * @testdox Should never estimate fewer than one character.
	 */
	public function test_estimate_is_at_least_one_character(): void {
		$this->assertGreaterThanOrEqual( 1, AttributeSlugLength::get_character_estimate( 'zh_CN' ) );
	}
}
