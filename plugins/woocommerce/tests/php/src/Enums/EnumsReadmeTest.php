<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Enums;

use WC_Unit_Test_Case;

/**
 * Keeps `src/Enums/README.md` in step with the classes in that directory.
 *
 * The README is the index contributors and agents read to find out whether a vocabulary
 * already has an enumerator, so a class missing from it gets re-invented as raw string
 * literals, and a stale entry sends readers to a file that does not exist.
 */
class EnumsReadmeTest extends WC_Unit_Test_Case {

	/**
	 * Enumerator class names found in the directory, sorted.
	 *
	 * @return array<int, string>
	 */
	private function get_enum_class_names(): array {
		$names = array();

		foreach ( glob( WC_ABSPATH . 'src/Enums/*.php' ) ?: array() as $file ) {
			$names[] = basename( $file, '.php' );
		}

		sort( $names );

		return $names;
	}

	/**
	 * Enumerator names linked from the README's list, sorted.
	 *
	 * @return array<int, string>
	 */
	private function get_readme_entry_names(): array {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$readme = (string) file_get_contents( WC_ABSPATH . 'src/Enums/README.md' );

		preg_match_all( '/^- \[(\w+)\]\(\.\/(\w+)\.php\)/m', $readme, $matches );

		$names = $matches[1];
		sort( $names );

		return $names;
	}

	/**
	 * @testdox The README lists every enumerator class in src/Enums, and no others.
	 */
	public function test_readme_lists_every_enum_class(): void {
		$this->assertSame(
			$this->get_enum_class_names(),
			$this->get_readme_entry_names(),
			'src/Enums/README.md is out of date: add an entry for each new enumerator, and remove entries for classes that no longer exist.'
		);
	}

	/**
	 * @testdox Every file the README links to exists, and matches its link text.
	 */
	public function test_readme_links_resolve(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$readme = (string) file_get_contents( WC_ABSPATH . 'src/Enums/README.md' );

		preg_match_all( '/^- \[(\w+)\]\(\.\/(\w+)\.php\)/m', $readme, $matches, PREG_SET_ORDER );

		$this->assertNotEmpty( $matches, 'No enumerator entries were found in src/Enums/README.md.' );

		foreach ( $matches as $match ) {
			list( , $label, $target ) = $match;

			$this->assertSame( $label, $target, "README entry [{$label}] links to {$target}.php." );
			$this->assertFileExists(
				WC_ABSPATH . "src/Enums/{$target}.php",
				"README entry [{$label}] links to a file that does not exist."
			);
		}
	}
}
