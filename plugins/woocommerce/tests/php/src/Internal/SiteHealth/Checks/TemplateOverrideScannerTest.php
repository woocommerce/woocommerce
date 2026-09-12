<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\TemplateOverrideScanner;
use WC_Unit_Test_Case;

/**
 * Tests for the TemplateOverrideScanner class.
 */
class TemplateOverrideScannerTest extends WC_Unit_Test_Case {

	/** @var string */
	private string $tmp;

	/** Sets up a temporary template directory and points the scan-path filter at it. */
	public function setUp(): void {
		parent::setUp();
		$this->tmp = sys_get_temp_dir() . '/wc-template-override-test-' . uniqid();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Managing local temp fixtures for the test.
		mkdir( $this->tmp, 0777, true );
		add_filter(
			'woocommerce_site_health_check_outdated_templates_scan_path',
			function () {
				return $this->tmp . '/';
			}
		);
	}

	/** Removes the scan-path/enabled filters and deletes the temporary template directory. */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_site_health_check_outdated_templates_scan_path' );
		remove_all_filters( 'woocommerce_site_health_check_outdated_templates_enabled' );
		$this->remove_dir( $this->tmp );
		parent::tearDown();
	}

	/** Verifies a good status when no template overrides are present. */
	public function test_good_when_no_overrides(): void {
		$result = ( new TemplateOverrideScanner() )->run();
		$this->assertSame( 'good', $result['status'] );
	}

	/** Verifies a good status when an override matches the core template version. */
	public function test_good_when_overrides_match_core_version(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Managing local temp fixtures for the test.
		$core_contents = file_get_contents( WC()->plugin_path() . '/templates/cart/cart.php', false, null, 0, 4096 );
		preg_match( '/@version\s+([0-9][0-9a-zA-Z.\-]*)/i', $core_contents, $matches );
		$core_version = $matches[1] ?? '0';
		$this->write_template( 'cart/cart.php', $core_version );

		$result = ( new TemplateOverrideScanner() )->run();
		$this->assertSame( 'good', $result['status'] );
	}

	/** Verifies a recommended status when an override is two minor versions behind core. */
	public function test_recommended_when_override_two_minor_versions_behind(): void {
		$this->write_template( 'cart/cart.php', '0.0.1' );

		$result = ( new TemplateOverrideScanner() )->run();
		$this->assertSame( 'recommended', $result['status'] );
	}

	/** Verifies the check returns an empty result when disabled by the enabled filter. */
	public function test_enabled_filter_returns_empty_when_disabled(): void {
		$this->write_template( 'cart/cart.php', '0.0.1' );
		add_filter( 'woocommerce_site_health_check_outdated_templates_enabled', '__return_false' );

		$result = ( new TemplateOverrideScanner() )->run();
		$this->assertSame( array(), $result );
	}

	/**
	 * Writes a template fixture with the given relative path and version header.
	 *
	 * @param string $rel     Relative path of the template within the temp directory.
	 * @param string $version Version string for the template's @version header.
	 */
	private function write_template( string $rel, string $version ): void {
		$path = $this->tmp . '/' . $rel;
		if ( ! is_dir( dirname( $path ) ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Managing local temp fixtures for the test.
			mkdir( dirname( $path ), 0777, true );
		}
		file_put_contents( $path, "<?php\n/**\n * Template.\n *\n * @version {$version}\n */\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Recursively removes a directory and all of its contents.
	 *
	 * @param string $dir Absolute path of the directory to remove.
	 */
	private function remove_dir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$items = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $items as $item ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir, WordPress.WP.AlternativeFunctions.unlink_unlink -- Managing local temp fixtures for the test.
			$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Managing local temp fixtures for the test.
		rmdir( $dir );
	}
}
