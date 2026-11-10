<?php
/**
 * Unit tests for WC_WCCOM_Site_Installation_Step_Activate_Product.
 *
 * @package WooCommerce\Tests\WCCOM_Site
 */

declare( strict_types = 1 );

/**
 * Class WC_WCCOM_Site_Installation_Step_Activate_Product_Test.
 */
class WC_WCCOM_Site_Installation_Step_Activate_Product_Test extends WC_Unit_Test_Case {

	/**
	 * The WooCommerce.com product ID of the installed fixture.
	 */
	private const PRODUCT_ID = 9999901;

	/**
	 * Directories the test created, removed in teardown.
	 *
	 * @var string[]
	 */
	private $fixture_dirs = array();

	/**
	 * Load the installer classes, which WooCommerce only loads for WooCommerce.com REST requests.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once WC_ABSPATH . 'includes/wccom-site/class-wc-wccom-site-installer.php';
		require_once WC_ABSPATH . 'includes/wccom-site/rest-api/class-wc-rest-wccom-site-installer-error-codes.php';
		require_once WC_ABSPATH . 'includes/wccom-site/rest-api/class-wc-rest-wccom-site-installer-error.php';
		require_once WC_ABSPATH . 'includes/wccom-site/installation/class-wc-wccom-site-installation-state.php';
		require_once WC_ABSPATH . 'includes/wccom-site/installation/installation-steps/interface-installaton-step.php';
		require_once WC_ABSPATH . 'includes/wccom-site/installation/installation-steps/class-wc-wccom-site-installation-step-activate-product.php';
	}

	/**
	 * Remove the fixture product from disk, and the WooCommerce and WordPress caches that list it.
	 */
	public function tearDown(): void {
		try {
			foreach ( $this->fixture_dirs as $dir ) {
				$this->rmdir( $dir );
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test fixture directory.
			}
			wp_clean_plugins_cache();
			wp_clean_themes_cache();
			WC_Helper::flush_local_woo_products_cache();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Activation finds a product installed after the list of WooCommerce.com products was cached in the same request.
	 *
	 * @testWith ["plugin"]
	 *           ["theme"]
	 *
	 * @param string $product_type The product type, plugin or theme.
	 */
	public function test_activates_product_installed_after_the_product_list_was_cached( string $product_type ): void {
		// Earlier installer steps read the list before the product exists, as get_product_info does through WC_Helper_Updater::get_update_data().
		WC_Helper::get_local_woo_plugins();
		WC_Helper::get_local_woo_themes();

		$this->install_fixture( $product_type );

		// No installed_path, so activation has to find the product in the list rather than fall back to the folder move_product reported.
		$state = WC_WCCOM_Site_Installation_State::initiate_new( self::PRODUCT_ID, 'activate-product-test' );
		$state->set_product_type( $product_type );
		$sut = new WC_WCCOM_Site_Installation_Step_Activate_Product( $state );

		$sut->run();

		if ( 'plugin' === $product_type ) {
			$this->assertTrue( is_plugin_active( 'woo-activate-fixture/woo-activate-fixture.php' ), 'The installed plugin should be activated' );
		} else {
			$this->assertSame( 'woo-activate-fixture', get_stylesheet(), 'The installed theme should be activated' );
		}
	}

	/**
	 * Write a WooCommerce.com plugin or theme to disk, as the move_product step does.
	 *
	 * @param string $product_type The product type, plugin or theme.
	 */
	private function install_fixture( string $product_type ): void {
		$dir = ( 'plugin' === $product_type ? WP_PLUGIN_DIR : get_theme_root() ) . '/woo-activate-fixture';
		wp_mkdir_p( $dir );
		$this->fixture_dirs[] = $dir;

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture files.
		if ( 'plugin' === $product_type ) {
			file_put_contents( $dir . '/woo-activate-fixture.php', "<?php\n/**\n * Plugin Name: Woo Activate Fixture\n * Version: 1.0.0\n * Woo: " . self::PRODUCT_ID . ":abc123\n */\n" );
		} else {
			file_put_contents( $dir . '/style.css', "/*\nTheme Name: Woo Activate Fixture\nVersion: 1.0.0\nWoo: " . self::PRODUCT_ID . ":def456\n*/\n" );
			file_put_contents( $dir . '/index.php', "<?php\n" );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
}
