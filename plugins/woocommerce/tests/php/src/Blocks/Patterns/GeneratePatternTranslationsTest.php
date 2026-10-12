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

	/**
	 * @testdox Extracts titles and descriptions with quotes and backslashes without executing pattern content.
	 */
	public function test_extracts_metadata_without_executing_patterns(): void {
		$directory = sys_get_temp_dir() . '/' . uniqid( 'wc-pattern-translations-', true );
		wp_mkdir_p( $directory . '/patterns' );
		$pattern_file = $directory . '/patterns/example.php';
		$pattern      = <<<'PHP'
<?php
/**
 * Title: Merchant's "$pattern" \ sample
 * Description: A merchant's "special" pattern.
 */
throw new \RuntimeException( 'Pattern content must not execute.' );
PHP;

		try {
			// Write a local fixture, without loading WordPress's filesystem abstraction.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $pattern_file, $pattern );
			$translations = $this->extract_strings( $this->sut->generate( $directory ) );

			$this->assertCount( 2, $translations );
			$this->assertNotFalse( $translations->find( 'Pattern title', 'Merchant\'s "$pattern" \\ sample' ) );
			$this->assertNotFalse( $translations->find( 'Pattern description', 'A merchant\'s "special" pattern.' ) );
		} finally {
			wp_delete_file( $pattern_file );
			// Remove the temporary fixture directories created by this test.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			rmdir( $directory . '/patterns' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			rmdir( $directory );
		}
	}

	/**
	 * Extracts strings using WP-CLI’s PHP scanner.
	 *
	 * @param string $source Generated PHP source.
	 * @return Translations
	 */
	private function extract_strings( string $source ): Translations {
		$translations = new Translations();
		$translations->setDomain( 'woocommerce' );
		PhpCodeExtractor::fromString(
			$source,
			$translations,
			array(
				'file' => 'i18n/pattern-translations.php',
			)
		);
		return $translations;
	}
}
