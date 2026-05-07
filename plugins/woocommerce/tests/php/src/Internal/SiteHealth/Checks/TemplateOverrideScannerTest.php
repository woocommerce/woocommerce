<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\TemplateOverrideScanner;
use WC_Unit_Test_Case;

class TemplateOverrideScannerTest extends WC_Unit_Test_Case {

	/** @var string */
	private string $tmp;

	public function setUp(): void {
		parent::setUp();
		$this->tmp = sys_get_temp_dir() . '/wc-template-override-test-' . uniqid();
		mkdir( $this->tmp, 0777, true );
		add_filter(
			'woocommerce_site_health_check_outdated_templates_scan_path',
			function () {
				return $this->tmp . '/';
			}
		);
	}

	public function tearDown(): void {
		remove_all_filters( 'woocommerce_site_health_check_outdated_templates_scan_path' );
		remove_all_filters( 'woocommerce_site_health_check_outdated_templates_enabled' );
		$this->remove_dir( $this->tmp );
		parent::tearDown();
	}

	public function test_good_when_no_overrides(): void {
		$result = ( new TemplateOverrideScanner() )->run();
		$this->assertSame( 'good', $result['status'] );
	}

	public function test_good_when_overrides_match_core_version(): void {
		$core_meta = get_file_data( WC()->plugin_path() . '/templates/cart/cart.php', array( 'version' => 'version' ) );
		$this->write_template( 'cart/cart.php', $core_meta['version'] );

		$result = ( new TemplateOverrideScanner() )->run();
		$this->assertSame( 'good', $result['status'] );
	}

	public function test_recommended_when_override_two_minor_versions_behind(): void {
		$this->write_template( 'cart/cart.php', '0.0.1' );

		$result = ( new TemplateOverrideScanner() )->run();
		$this->assertSame( 'recommended', $result['status'] );
	}

	public function test_enabled_filter_returns_empty_when_disabled(): void {
		$this->write_template( 'cart/cart.php', '0.0.1' );
		add_filter( 'woocommerce_site_health_check_outdated_templates_enabled', '__return_false' );

		$result = ( new TemplateOverrideScanner() )->run();
		$this->assertSame( array(), $result );
	}

	private function write_template( string $rel, string $version ): void {
		$path = $this->tmp . '/' . $rel;
		if ( ! is_dir( dirname( $path ) ) ) {
			mkdir( dirname( $path ), 0777, true );
		}
		file_put_contents( $path, "<?php\n/**\n * Template.\n *\n * @version {$version}\n */\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	private function remove_dir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$items = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $items as $item ) {
			$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		}
		rmdir( $dir );
	}
}
