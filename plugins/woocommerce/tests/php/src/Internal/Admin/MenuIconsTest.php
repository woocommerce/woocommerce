<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\MenuIcons;
use WC_Unit_Test_Case;

/**
 * Tests for WooCommerce admin menu icons.
 */
class MenuIconsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var MenuIcons
	 */
	private $sut;

	/**
	 * Globals changed by these tests.
	 *
	 * @var array
	 */
	private $globals_backup;

	/**
	 * Whether the test owns the WooCommerce icon collection.
	 *
	 * @var bool
	 */
	private $owns_collection;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->globals_backup  = array(
			'menu'       => $GLOBALS['menu'] ?? null,
			'wp_version' => $GLOBALS['wp_version'],
		);
		$this->sut             = wc_get_container()->get( MenuIcons::class );
		$this->owns_collection = class_exists( '\WP_Icon_Collections_Registry' ) && ! \WP_Icon_Collections_Registry::get_instance()->is_registered( 'woocommerce' );
	}

	/**
	 * Restore globals that WordPress does not reset.
	 */
	public function tearDown(): void {
		try {
			if ( $this->owns_collection && \WP_Icon_Collections_Registry::get_instance()->is_registered( 'woocommerce' ) ) {
				foreach ( array( 'woocommerce/logo', 'woocommerce/megaphone' ) as $name ) {
					if ( \WP_Icons_Registry::get_instance()->is_registered( $name ) ) {
						wp_unregister_icon( $name );
					}
				}
				wp_unregister_icon_collection( 'woocommerce' );
			}
			foreach ( $this->globals_backup as $name => $value ) {
				$GLOBALS[ $name ] = $value; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore test globals.
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should keep legacy icons on WordPress 7.1 and earlier even when the icon API is available.
	 * @testWith ["6.9.4"]
	 *           ["7.0"]
	 *           ["7.1.3"]
	 *
	 * @param string $version WordPress version.
	 */
	public function test_legacy_icons( string $version ): void {
		$this->require_icon_api();
		$GLOBALS['wp_version'] = $version; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate supported WordPress versions.

		$this->assertSame( 'dashicons-archive', $this->sut->get_icon( 'core-admin/tag', 'dashicons-archive' ) );
		$this->assertSame( 'data:image/svg+xml;base64,legacy', $this->sut->get_icon( 'woocommerce/logo', 'data:image/svg+xml;base64,legacy' ) );
	}

	/**
	 * @testdox Should use registered icons on WordPress 7.2, including prereleases.
	 * @testWith ["7.2-beta4-65250"]
	 *           ["7.2-RC1"]
	 *           ["7.2"]
	 *           ["7.3-alpha"]
	 *
	 * @param string $version WordPress version.
	 */
	public function test_registered_icons( string $version ): void {
		$this->require_icon_api();
		$GLOBALS['wp_version'] = $version; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate WordPress versions.
		$this->sut->handle_admin_menu_registration();

		$this->assertSame( 'core-admin/tag', $this->sut->get_icon( 'core-admin/tag', 'dashicons-archive' ) );
		$this->assertSame( 'core-admin/payment', $this->sut->get_icon( 'core-admin/payment', 'legacy' ) );
		$this->assertSame( 'woocommerce/logo', $this->sut->get_icon( 'woocommerce/logo', 'legacy' ) );
		$this->assertStringContainsString( '<svg', wp_get_icon( 'woocommerce/logo' ) );
		$this->assertStringContainsString( 'stroke="currentColor"', wp_get_icon( 'woocommerce/megaphone' ) );
		$this->assertStringContainsString( 'fill: none', wp_get_icon( 'woocommerce/megaphone' ) );
	}

	/**
	 * @testdox Should fall back when an icon is not registered.
	 */
	public function test_missing_icon_falls_back(): void {
		$this->require_icon_api();
		$GLOBALS['wp_version'] = '7.2'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Exercise the missing-icon fallback on a supported version.
		$this->assertSame( 'dashicons-archive', $this->sut->get_icon( 'woocommerce/missing', 'dashicons-archive' ) );
	}

	/**
	 * @testdox Should update only default Products and Orders menu icons without changing post type metadata.
	 */
	public function test_post_type_menu_icons(): void {
		$this->require_icon_api();
		$GLOBALS['wp_version'] = '7.2'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Exercise the supported menu path independently of the host version.
		$this->sut->handle_admin_menu_registration();
		$GLOBALS['menu'] = array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Set up menu fixtures.
			55 => array( 'Products', 'edit_products', 'edit.php?post_type=product', 'Products', '', 'menu-posts-product', 'dashicons-archive' ),
			56 => array( 'Orders', 'edit_shop_orders', 'edit.php?post_type=shop_order', 'Orders', '', 'menu-posts-shop_order', 'dashicons-admin-post' ),
			57 => array( 'Posts', 'edit_posts', 'edit.php', 'Posts', '', 'menu-posts', 'dashicons-admin-post' ),
		);

		$this->sut->handle_admin_menu();

		$this->assertSame( 'core-admin/tag', $GLOBALS['menu'][55][6] );
		$this->assertSame( 'core-admin/receipt', $GLOBALS['menu'][56][6] );
		$this->assertSame( 'dashicons-admin-post', $GLOBALS['menu'][57][6] );
		$this->assertSame( 'edit_products', $GLOBALS['menu'][55][1] );
		$this->assertSame( 'edit.php?post_type=product', $GLOBALS['menu'][55][2] );
		$this->assertSame( 'dashicons-archive', get_post_type_object( 'product' )->menu_icon );
	}

	/**
	 * @testdox Should preserve extension-provided icons and leave legacy WordPress menus unchanged.
	 * @testWith ["7.1.3", "dashicons-archive"]
	 *           ["7.2", "https://example.com/custom.svg"]
	 *
	 * @param string $version WordPress version.
	 * @param string $icon    Existing menu icon.
	 */
	public function test_preserves_existing_icons( string $version, string $icon ): void {
		$GLOBALS['wp_version'] = $version; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate WordPress versions.
		$GLOBALS['menu']       = array( array( 'Products', 'edit_products', 'edit.php?post_type=product', 'Products', '', 'menu-posts-product', $icon ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Set up menu fixtures.

		$this->sut->handle_admin_menu();

		$this->assertSame( $icon, $GLOBALS['menu'][0][6] );
	}

	/**
	 * Skip registry tests on older WordPress versions.
	 */
	private function require_icon_api(): void {
		if ( ! function_exists( 'wp_is_valid_icon_name' ) ) {
			$this->markTestSkipped( 'Requires the WordPress 7.2 icon API.' );
		}
	}
}
