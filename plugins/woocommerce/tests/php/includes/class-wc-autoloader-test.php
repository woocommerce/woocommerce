<?php
declare( strict_types = 1 );

/**
 * Tests for the WC_Autoloader class.
 */
class WC_Autoloader_Test extends WC_Unit_Test_Case {

	/**
	 * Returns the CLASS_MAP constant of WC_Autoloader.
	 *
	 * @return array<string, string>
	 */
	private function get_class_map(): array {
		return ( new ReflectionClass( WC_Autoloader::class ) )->getConstant( 'CLASS_MAP' );
	}

	/**
	 * @testdox Every CLASS_MAP entry points to an existing file that declares the mapped class, interface or trait.
	 */
	public function test_class_map_entries_point_to_files_declaring_the_mapped_name(): void {
		$class_map = $this->get_class_map();

		$this->assertNotEmpty( $class_map, 'CLASS_MAP should not be empty' );

		foreach ( $class_map as $class_name => $relative_path ) {
			$path = WC_ABSPATH . 'includes/' . $relative_path;

			$this->assertFileExists( $path, "CLASS_MAP entry for '{$class_name}' points to a missing file" );

			$declared = $this->get_declared_name( $path );
			$this->assertSame(
				$class_name,
				strtolower( $declared ),
				"CLASS_MAP entry '{$class_name}' maps to {$relative_path}, but that file declares '{$declared}'"
			);
		}
	}

	/**
	 * @testdox Every CLASS_MAP class, interface and trait is resolvable through autoloading.
	 */
	public function test_class_map_entries_are_resolvable(): void {
		foreach ( array_keys( $this->get_class_map() ) as $class_name ) {
			$resolvable = class_exists( $class_name ) || interface_exists( $class_name ) || trait_exists( $class_name );
			$this->assertTrue( $resolvable, "Class '{$class_name}' in CLASS_MAP could not be resolved via autoloading" );
		}
	}

	/**
	 * Extracts the first class/interface/trait name declared in a PHP file.
	 *
	 * @param string $path File path.
	 * @return string The declared name, or an empty string if none found.
	 */
	private function get_declared_name( string $path ): string {
		$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( preg_match( '/^\s*(?:abstract\s+|final\s+)?(?:class|interface|trait)\s+([A-Za-z0-9_]+)/m', $content, $matches ) ) {
			return $matches[1];
		}
		return '';
	}
}
