<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Patterns;

use Automattic\WooCommerce\Tools\Generate_Pattern_Translations;
use Gettext\Translations;
use WP_CLI\I18n\PhpCodeExtractor;

/**
 * Tests extraction independently of the WP-CLI mocks used by other test classes.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GeneratePatternTranslationsTest extends \WC_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var Generate_Pattern_Translations
	 */
	private $sut;

	/**
	 * Loads the translation tooling.
	 */
	public function setUp(): void {
		parent::setUp();
		require_once WC_ABSPATH . 'bin/composer/wp/vendor/autoload.php';
		require_once WC_ABSPATH . 'bin/class-generate-pattern-translations.php';
		$this->sut = new Generate_Pattern_Translations();
	}

	/**
	 * @testdox The generated source is current and exposes every bundled pattern title to plugin string extraction.
	 */
	public function test_bundled_pattern_titles_are_extractable(): void {
		$source = $this->sut->generate( WC_ABSPATH );
		// The generated file must stay in sync so WordPress.org can scan release ZIPs.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$this->assertSame( $source, file_get_contents( WC_ABSPATH . 'i18n/pattern-translations.php' ), 'Run composer generate-pattern-translations after changing pattern headers.' );
		$translations = $this->extract_strings( $source );

		$pattern_files = glob( WC_ABSPATH . 'patterns/*.php' );
		$this->assertNotEmpty( $pattern_files );
		foreach ( $pattern_files as $pattern_file ) {
			$metadata = get_file_data( $pattern_file, array( 'title' => 'Title' ) );
			$this->assertNotFalse(
				$translations->find( 'Pattern title', $metadata['title'] ),
				sprintf( 'The title in %s must be available for translation.', basename( $pattern_file ) )
			);
		}
	}
}
