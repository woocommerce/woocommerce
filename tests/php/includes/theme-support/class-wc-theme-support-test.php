<?php
/**
 * Tests for the theme support classes.
 *
 * @package WooCommerce\Tests\ThemeSupport
 */

use Automattic\WooCommerce\Theming\ThemeSupport;

/**
 * Tests for the theme support classes.
 */
class WC_Tests_Theme_Support extends WC_Unit_Test_Case {

	/**
	 * Runs before all the tests in the class.
	 */
	public static function setUpBeforeClass() {
		$theme_support_file_paths = self::get_all_theme_support_file_paths();
		foreach ( $theme_support_file_paths as $file_path ) {
			require_once $file_path;
		}
	}

	/**
	 * Runs after all the tests in the class.
	 */
	public static function tearDownAfterClass() {
		remove_theme_support( 'woocommerce' );
	}

	/**
	 * Runs before each test in the class.
	 */
	public function setUp() {
		remove_theme_support( 'woocommerce' );
	}

	/**
	 * @testdox All the theme support classes should define theme support for thumbnail and single image widths within ther init methods.
	 *
	 * @dataProvider data_provider_for_all_theme_support_file_paths
	 *
	 * @param string $theme_support_file_path The path of the theme support class to test.
	 */
	public function test_theme_defines_thumbnail_and_single_image_widths_on_init( $theme_support_file_path ) {
		$class_name = $this->class_name_from_file_path( $theme_support_file_path );
		$class_name::init();

		$theme_support = $this->get_instance_of( ThemeSupport::class );
		$this->assertNotNull( $theme_support->get_option( 'thumbnail_image_width' ) );
		$this->assertNotNull( $theme_support->get_option( 'single_image_width' ) );
	}

	/**
	 * Get the full pathnames of all the theme support files.
	 *
	 * @return array Full pathnames of all the theme support files.
	 */
	public static function get_all_theme_support_file_paths() {
		$theme_support_dir   = dirname( WC_PLUGIN_FILE ) . '/includes/theme-support';
		$theme_support_files = array_diff( scandir( $theme_support_dir ), array( '.', '..' ) );

		return array_map(
			function( $filename ) use ( $theme_support_dir ) {
				return $theme_support_dir . '/' . $filename;
			},
			$theme_support_files
		);
	}

	/**
	 * Get the full pathnames of all the theme support files, in PHPUnit data provider format.
	 *
	 * @return array Full pathnames of all the theme support files, each on its own single-item array.
	 */
	public function data_provider_for_all_theme_support_file_paths() {
		$pathnames = self::get_all_theme_support_file_paths();
		return array_map(
			function( $path ) {
				return array( $path );
			},
			$pathnames
		);
	}

	/**
	 * Converts a 'class-' file path to a class name ('some/path/class-wc-foo-bar.php' --> 'WC_Foo_Bar')
	 *
	 * @param string $file_path The complete file path.
	 *
	 * @return string The extracted class name.
	 */
	private function class_name_from_file_path( $file_path ) {
		$filename = substr( $file_path, strrpos( $file_path, '/' ) + 1 );
		$filename = str_replace( '.php', '', $filename );
		$filename = str_replace( 'class-wc-', 'WC-', $filename );

		$filename_parts              = explode( '-', $filename );
		$tittle_cased_filename_parts = array_map(
			function( $part ) {
				return ucfirst( $part );
			},
			$filename_parts
		);
		return join( '_', $tittle_cased_filename_parts );
	}
}
