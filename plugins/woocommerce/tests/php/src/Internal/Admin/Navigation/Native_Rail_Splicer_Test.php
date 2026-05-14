<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Navigation;

use Automattic\WooCommerce\Internal\Admin\Navigation\Native_Rail_Splicer;

/**
 * @covers \Automattic\WooCommerce\Internal\Admin\Navigation\Native_Rail_Splicer
 */
class Native_Rail_Splicer_Test extends \WC_Unit_Test_Case {

	/** @var array|null */
	private $menu_backup;
	/** @var array|null */
	private $submenu_backup;

	public function setUp(): void {
		parent::setUp();
		global $menu, $submenu;
		$this->menu_backup    = is_array( $menu ) ? $menu : null;
		$this->submenu_backup = is_array( $submenu ) ? $submenu : null;
		$menu                 = array();
		$submenu              = array();
		remove_all_filters( 'parent_file' );
		remove_all_filters( 'submenu_file' );
	}

	public function tearDown(): void {
		global $menu, $submenu;
		$menu    = null === $this->menu_backup ? array() : $this->menu_backup;
		$submenu = null === $this->submenu_backup ? array() : $this->submenu_backup;
		parent::tearDown();
	}

	public function test_class_exists(): void {
		$this->assertTrue( class_exists( Native_Rail_Splicer::class ) );
	}

	public function test_splice_relabels_dashboard_and_swaps_icon(): void {
		global $menu, $submenu;
		// WP's typical index.php registration: position 2, title "Dashboard",
		// cap "read", slug "index.php", page_title "Dashboard", empty class,
		// hookname "menu-dashboard", icon "dashicons-dashboard".
		$menu[2] = array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' );
		$submenu['index.php'] = array(
			array( 'Home', 'read', 'index.php' ),
			array( 'Updates', 'read', 'update-core.php' ),
		);

		$tree = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-admin'    => array( 'parent' => 'woocommerce', 'title' => 'Home', 'position' => 10, 'capability' => 'manage_woocommerce' ),
		);

		// Force is_woo_page to true by visiting wc-admin.
		$_GET['page']               = 'wc-admin';
		$GLOBALS['pagenow']         = 'admin.php';

		( new Native_Rail_Splicer() )->splice( $tree );

		$this->assertSame( 'Dashboard', $menu[2][0] );
		$this->assertSame( 'index.php', $menu[2][2] );
		$this->assertSame( 'dashicons-arrow-left-alt', $menu[2][6] );
		$this->assertSame( array(), $submenu['index.php'] ?? array(), 'Dashboard submenu cleared.' );
	}

	public function test_splice_removes_non_woo_top_level_entries_keeping_dashboard_and_woocommerce(): void {
		global $menu, $submenu;
		$menu = array(
			2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
			5  => array( 'Posts', 'edit_posts', 'edit.php', 'Posts', '', 'menu-posts', 'dashicons-admin-post' ),
			10 => array( 'Media', 'upload_files', 'upload.php', 'Media', '', 'menu-media', 'dashicons-admin-media' ),
			55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
			56 => array( 'Plugins', 'activate_plugins', 'plugins.php', 'Plugins', '', 'menu-plugins', 'dashicons-admin-plugins' ),
		);
		$submenu['index.php']  = array( array( 'Home', 'read', 'index.php' ) );
		$submenu['woocommerce'] = array( array( 'Home', 'manage_woocommerce', 'wc-admin' ) );

		$tree = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-admin'    => array( 'parent' => 'woocommerce', 'title' => 'Home', 'position' => 10, 'capability' => 'manage_woocommerce' ),
		);

		$_GET['page']               = 'wc-admin';
		$GLOBALS['pagenow']         = 'admin.php';

		( new Native_Rail_Splicer() )->splice( $tree );

		$remaining_slugs = array_values( array_filter( array_map(
			static fn( $entry ) => is_array( $entry ) && isset( $entry[2] ) ? $entry[2] : null,
			$menu
		) ) );
		sort( $remaining_slugs );
		// `wc-admin` is inserted as a native top-level entry by insert_woo_roots().
		$this->assertSame( array( 'index.php', 'wc-admin', 'woocommerce' ), $remaining_slugs );
	}

	public function test_splice_inserts_woo_roots_into_menu_with_icon_and_capability(): void {
		global $menu, $submenu;
		$menu = array(
			2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
			55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
		);
		$submenu['woocommerce'] = array( array( 'Home', 'manage_woocommerce', 'wc-admin' ) );

		$tree = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-admin'    => array(
				'parent'     => 'woocommerce',
				'title'      => 'Home',
				'icon'       => 'dashicons-admin-home',
				'position'   => 10,
				'capability' => 'manage_woocommerce',
			),
			'wc-orders'   => array(
				'parent'     => 'woocommerce',
				'title'      => 'Orders',
				'icon'       => 'dashicons-list-view',
				'position'   => 20,
				'capability' => 'edit_shop_orders',
			),
		);

		$_GET['page']       = 'wc-admin';
		$GLOBALS['pagenow'] = 'admin.php';

		( new Native_Rail_Splicer() )->splice( $tree );

		// Collect rail entries in the order they will render (by numeric key asc).
		ksort( $menu );
		$entries_by_slug = array();
		foreach ( $menu as $entry ) {
			if ( isset( $entry[2] ) ) {
				$entries_by_slug[ $entry[2] ] = $entry;
			}
		}

		$this->assertArrayHasKey( 'wc-admin', $entries_by_slug );
		$this->assertSame( 'Home', $entries_by_slug['wc-admin'][0] );
		$this->assertSame( 'manage_woocommerce', $entries_by_slug['wc-admin'][1] );
		$this->assertSame( 'dashicons-admin-home', $entries_by_slug['wc-admin'][6] );

		$this->assertArrayHasKey( 'wc-orders', $entries_by_slug );
		$this->assertSame( 'edit_shop_orders', $entries_by_slug['wc-orders'][1] );

		// Rail order: Dashboard (preserved), woocommerce (hidden), then Woo roots in tree position order.
		$ordered_slugs = array_values( array_map(
			static fn( $entry ) => $entry[2] ?? null,
			$menu
		) );
		$this->assertSame(
			array( 'index.php', 'woocommerce', 'wc-admin', 'wc-orders' ),
			$ordered_slugs,
			'Rail order must follow tree position (wc-admin position 10 before wc-orders position 20).'
		);

		// The original woocommerce entry is preserved but hidden.
		$this->assertArrayHasKey( 'woocommerce', $entries_by_slug );
		$this->assertStringContainsString( 'hide-if-js', (string) $entries_by_slug['woocommerce'][4] );
	}

	public function test_splice_populates_submenu_for_each_root_with_first_level_children(): void {
		global $menu, $submenu;
		$menu = array(
			2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
			55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
		);

		$tree = array(
			'woocommerce'           => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-admin&path=/marketing' => array(
				'parent'     => 'woocommerce',
				'title'      => 'Marketing',
				'icon'       => 'dashicons-megaphone',
				'position'   => 40,
				'capability' => 'manage_woocommerce',
			),
			'wc-admin&path=/marketing/overview' => array(
				'parent'     => 'wc-admin&path=/marketing',
				'title'      => 'Overview',
				'position'   => 10,
				'capability' => 'manage_woocommerce',
			),
			'wc-admin&path=/marketing/coupons'  => array(
				'parent'     => 'wc-admin&path=/marketing',
				'title'      => 'Coupons',
				'position'   => 20,
				'capability' => 'manage_woocommerce',
			),
		);

		$_GET['page']       = 'wc-admin';
		$_GET['path']       = '/marketing';
		$GLOBALS['pagenow'] = 'admin.php';

		( new Native_Rail_Splicer() )->splice( $tree );

		$this->assertArrayHasKey( 'wc-admin&path=/marketing', $submenu );
		$slugs = array_map( static fn( $entry ) => $entry[2], $submenu['wc-admin&path=/marketing'] );
		// Compound bare slugs are rewritten to `admin.php?page=…` form so that
		// WP's naked-href fallback in menu-header.php emits a valid relative URL.
		$this->assertSame(
			array(
				'admin.php?page=wc-admin&path=/marketing/overview',
				'admin.php?page=wc-admin&path=/marketing/coupons',
			),
			$slugs
		);
		$this->assertSame( 'Overview', $submenu['wc-admin&path=/marketing'][0][0] );
		$this->assertSame( 'manage_woocommerce', $submenu['wc-admin&path=/marketing'][0][1] );
	}

	public function test_splice_sets_parent_file_and_submenu_file_via_filters_for_compound_current_slug(): void {
		global $menu, $submenu;
		$menu                  = array(
			2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
			55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
		);

		$tree = array(
			'woocommerce'                       => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-admin&path=/marketing'          => array(
				'parent'     => 'woocommerce',
				'title'      => 'Marketing',
				'position'   => 40,
				'capability' => 'manage_woocommerce',
			),
			'wc-admin&path=/marketing/coupons'  => array(
				'parent'     => 'wc-admin&path=/marketing',
				'title'      => 'Coupons',
				'position'   => 20,
				'capability' => 'manage_woocommerce',
			),
		);

		// Pretend we're on the Coupons page.
		$_GET['page']       = 'wc-admin';
		$_GET['path']       = '/marketing/coupons';
		$GLOBALS['pagenow'] = 'admin.php';

		( new Native_Rail_Splicer() )->splice( $tree );

		$this->assertSame( 'wc-admin&path=/marketing', apply_filters( 'parent_file', 'something-else' ) );
		// `submenu_file` is rewritten to the renderable URL so it matches the
		// transformed `$sub_item[2]` in $submenu (which the renderer compares
		// via `$submenu_file === $sub_item[2]` for `current` highlighting).
		$this->assertSame( 'admin.php?page=wc-admin&path=/marketing/coupons', apply_filters( 'submenu_file', 'something-else' ) );
	}

	public function test_splice_preserves_grandchild_access_check_entries_as_hide_if_js(): void {
		global $menu, $submenu;
		$menu = array(
			2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
			55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
		);
		// Pretend WP already had `wc-status` registered as a child of woocommerce
		// so direct visits to ?page=wc-status pass the access check.
		$submenu['woocommerce'] = array(
			array( 'Home',   'manage_woocommerce', 'wc-admin' ),
			array( 'Status', 'manage_woocommerce', 'wc-status' ),
		);

		$tree = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-admin'    => array( 'parent' => 'woocommerce', 'title' => 'Home',    'position' => 10, 'capability' => 'manage_woocommerce' ),
			'wc-tools'    => array( 'parent' => 'woocommerce', 'title' => 'Tools',   'position' => 80, 'capability' => 'manage_woocommerce' ),
			'wc-status'   => array(
				// `wc-status` is a grandchild of `woocommerce` in the tree
				// (nested under Tools) but WP registered it as a direct child
				// of `woocommerce`.
				'parent'     => 'wc-tools',
				'title'      => 'Status',
				'position'   => 10,
				'capability' => 'manage_woocommerce',
			),
		);

		$_GET['page']       = 'wc-admin';
		$GLOBALS['pagenow'] = 'admin.php';

		( new Native_Rail_Splicer() )->splice( $tree );

		// `wc-status` should remain present under SOME parent submenu so the
		// access check still resolves. Either kept under `woocommerce` with
		// `hide-if-js`, or attached under `wc-tools`. We require at least one.
		$found_access_entry = false;
		foreach ( $submenu as $parent => $entries ) {
			foreach ( $entries as $entry ) {
				if ( ( $entry[2] ?? null ) !== 'wc-status' ) {
					continue;
				}
				$found_access_entry = true;
				// If kept under `woocommerce`, must be hide-if-js so it doesn't render.
				if ( 'woocommerce' === $parent ) {
					$this->assertStringContainsString( 'hide-if-js', (string) ( $entry[4] ?? '' ) );
				}
			}
		}
		$this->assertTrue( $found_access_entry, 'wc-status access-check entry must survive splice.' );
	}

	public function test_splice_registers_hookname_stubs_for_every_admin_page_hooks_page_type(): void {
		global $menu, $submenu, $admin_page_hooks;
		$menu = array(
			2  => array( 'Dashboard', 'read', 'index.php', 'Dashboard', '', 'menu-dashboard', 'dashicons-dashboard' ),
			55 => array( 'WooCommerce', 'manage_woocommerce', 'woocommerce', 'WooCommerce', '', 'toplevel_page_woocommerce', 'dashicons-cart' ),
		);
		// Simulate WP's $admin_page_hooks state with at least one non-Woo
		// page type (`product` for the Products CPT toplevel) — that's the
		// page_type whose absence on `woocommerce_page_wc-orders` caused
		// the Products-page naked-href regression.
		$admin_page_hooks                                  = is_array( $admin_page_hooks ) ? $admin_page_hooks : array();
		$admin_page_hooks['woocommerce']                   = 'woocommerce';
		$admin_page_hooks['edit.php?post_type=product']    = 'product';

		$tree = array(
			'woocommerce' => array( 'parent' => null, 'title' => 'WooCommerce', 'position' => 2 ),
			'wc-orders'   => array( 'parent' => 'woocommerce', 'title' => 'Orders', 'position' => 20, 'capability' => 'manage_woocommerce' ),
		);

		$_GET['page']       = 'wc-admin';
		$GLOBALS['pagenow'] = 'admin.php';

		( new Native_Rail_Splicer() )->splice( $tree );

		// Every $admin_page_hooks page_type, plus the `admin`/`toplevel`
		// defaults `get_plugin_page_hookname()` falls back to, must have a
		// matching `<page_type>_page_<slug>` callback registered.
		$this->assertNotFalse( has_action( 'woocommerce_page_wc-orders' ) );
		$this->assertNotFalse( has_action( 'product_page_wc-orders' ) );
		$this->assertNotFalse( has_action( 'admin_page_wc-orders' ) );
		$this->assertNotFalse( has_action( 'toplevel_page_wc-orders' ) );
	}
}
