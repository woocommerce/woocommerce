<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\LegacyReportsMenu;
use WC_Admin_Menus;
use WC_Admin_Reports;
use WC_Install;
use WC_Unit_Test_Case;

/**
 * Tests for the LegacyReportsMenu class.
 */
class LegacyReportsMenuTest extends WC_Unit_Test_Case {

	/**
	 * Admin menu globals that the tests mutate.
	 */
	private const MENU_GLOBALS = array( 'menu', 'submenu', 'admin_page_hooks', '_registered_pages', '_parent_pages', '_wp_menu_nopriv', '_wp_submenu_nopriv', 'pagenow', 'plugin_page', 'parent_file' );

	/**
	 * The System Under Test.
	 *
	 * @var LegacyReportsMenu
	 */
	private $sut;

	/**
	 * Backup of the admin menu globals.
	 *
	 * @var array
	 */
	private $globals_backup = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		foreach ( self::MENU_GLOBALS as $name ) {
			$this->globals_backup[ $name ] = $GLOBALS[ $name ] ?? null;
		}

		$GLOBALS['menu']    = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['submenu'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		update_option( 'woocommerce_analytics_enabled', 'yes' );
		update_option( WC_Install::INITIAL_INSTALLED_VERSION, '11.3.0' );
		update_option( WC_Install::NEWLY_INSTALLED_OPTION, 'no' );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$this->sut = new LegacyReportsMenu();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			foreach ( $this->globals_backup as $name => $value ) {
				$GLOBALS[ $name ] = $value; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
			// The container's LegacyReportsMenu remembers which items it hid.
			$this->reset_container_resolutions();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should treat the store as new only when the initial installed version is a valid 11.3.0 pre-release or later.
	 *
	 * @testWith ["11.3.0", false]
	 *           ["11.3.0-dev", false]
	 *           ["11.3.0-beta.1", false]
	 *           ["11.3.0-RC1", false]
	 *           ["11.4.0", false]
	 *           ["12.0.0", false]
	 *           ["11.2.9", true]
	 *           ["11.2.0-rc.1", true]
	 *           ["9.2.0", true]
	 *           ["", true]
	 *           ["abc", true]
	 *           ["99.0garbage", true]
	 *           ["12.0", true]
	 *           ["12.0.0-foo", true]
	 *
	 * @param string $initial_version The stored initial installed version.
	 * @param bool   $expected_show   Whether the menu is expected to be shown.
	 */
	public function test_new_store_detection( string $initial_version, bool $expected_show ): void {
		update_option( WC_Install::INITIAL_INSTALLED_VERSION, $initial_version );

		$this->assertSame( $expected_show, $this->is_menu_shown(), "Unexpected result for initial version '{$initial_version}'" );
	}

	/**
	 * @testdox Should treat a missing or non-string initial installed version as an existing store.
	 */
	public function test_missing_or_invalid_version_type_shows_menu(): void {
		delete_option( WC_Install::INITIAL_INSTALLED_VERSION );
		$this->assertTrue( $this->is_menu_shown(), 'Missing option should count as an existing store' );

		update_option( WC_Install::INITIAL_INSTALLED_VERSION, array( '11.3.0' ) );
		$this->assertTrue( $this->is_menu_shown(), 'Array value should count as an existing store' );
	}

	/**
	 * @testdox Should hide the menu on a new store without extensions.
	 */
	public function test_hidden_on_new_store_without_extensions(): void {
		$this->assertFalse( has_filter( 'woocommerce_admin_reports' ), 'Precondition: core does not hook the legacy reports filter' );

		$this->assertFalse( $this->is_menu_shown() );
	}

	/**
	 * @testdox Should show the menu on a new store when Analytics is disabled.
	 */
	public function test_shown_on_new_store_when_analytics_disabled(): void {
		update_option( 'woocommerce_analytics_enabled', 'no' );

		$this->assertTrue( $this->is_menu_shown() );
	}

	/**
	 * @testdox Should show the menu when an extension adds a legacy report group.
	 */
	public function test_shown_when_legacy_group_added(): void {
		add_filter(
			'woocommerce_admin_reports',
			function ( $reports ) {
				$reports['subscriptions'] = array(
					'title'   => 'Subscriptions',
					'reports' => array(
						'subscription_events_by_date' => array(
							'title'    => 'Subscription events',
							'callback' => '__return_empty_string',
						),
					),
				);
				return $reports;
			}
		);

		$this->assertTrue( $this->is_menu_shown() );
	}

	/**
	 * @testdox Should show the menu when an extension adds a report to a core group.
	 */
	public function test_shown_when_report_added_to_core_group(): void {
		add_filter(
			'woocommerce_admin_reports',
			function ( $reports ) {
				$reports['stock']['reports']['insufficient_stock'] = array(
					'title'    => 'Insufficient stock',
					'callback' => '__return_empty_string',
				);
				return $reports;
			}
		);

		$this->assertTrue( $this->is_menu_shown() );
	}

	/**
	 * @testdox Should show the menu when an extension adds reports with the legacy charts and function keys.
	 */
	public function test_shown_when_legacy_charts_keys_used(): void {
		add_filter(
			'woocommerce_reports_charts',
			function ( $reports ) {
				$reports['legacy'] = array(
					'title'  => 'Legacy',
					'charts' => array(
						'chart' => array(
							'title'    => 'Chart',
							'function' => '__return_empty_string',
						),
					),
				);
				return $reports;
			}
		);

		$this->assertTrue( $this->is_menu_shown() );
	}

	/**
	 * @testdox Should show the menu when an extension replaces the callback of a core report.
	 *
	 * @testWith ["callback"]
	 *           ["function"]
	 *
	 * @param string $key The report key used to replace the callback.
	 */
	public function test_shown_when_core_report_callback_replaced( string $key ): void {
		add_filter(
			'woocommerce_admin_reports',
			function ( $reports ) use ( $key ) {
				$reports['orders']['reports']['sales_by_date'][ $key ] = '__return_empty_string';
				return $reports;
			}
		);

		$this->assertTrue( $this->is_menu_shown() );
	}

	/**
	 * @testdox Should keep the menu hidden when an extension only adds an Analytics report.
	 */
	public function test_hidden_when_only_analytics_report_added(): void {
		add_filter(
			'woocommerce_admin_reports',
			function ( $reports ) {
				$reports[] = array(
					'slug'        => 'my-extension/stats',
					'description' => 'Stats from my extension.',
					'path'        => '/my-extension/v1/reports/stats',
				);
				return $reports;
			}
		);

		$this->assertFalse( $this->is_menu_shown() );
	}

	/**
	 * @testdox Should show the menu when a callback adds both an Analytics report and a legacy report group.
	 */
	public function test_shown_when_callback_returns_both_shapes(): void {
		add_filter(
			'woocommerce_admin_reports',
			function ( $reports ) {
				$reports[]           = array(
					'slug'        => 'my-extension/stats',
					'description' => 'Stats from my extension.',
				);
				$reports['my_group'] = array(
					'title'   => 'My group',
					'reports' => array(
						'my_report' => array(
							'title'    => 'My report',
							'callback' => '__return_empty_string',
						),
					),
				);
				return $reports;
			}
		);

		$this->assertTrue( $this->is_menu_shown() );
	}

	/**
	 * @testdox Should keep the menu hidden when an extension only removes reports.
	 */
	public function test_hidden_when_reports_only_removed(): void {
		add_filter(
			'woocommerce_admin_reports',
			function ( $reports ) {
				unset( $reports['customers'], $reports['orders']['reports']['downloads'] );
				return $reports;
			}
		);

		$this->assertFalse( $this->is_menu_shown() );
	}

	/**
	 * @testdox Should show the menu when an extension hooks the legacy report tabs or report path.
	 *
	 * @testWith ["wc_reports_tabs"]
	 *           ["wc_admin_reports_path"]
	 *
	 * @param string $hook The hook an extension uses.
	 */
	public function test_shown_when_legacy_report_hooks_used( string $hook ): void {
		add_filter( $hook, '__return_null' );

		$this->assertTrue( $this->is_menu_shown() );
	}

	/**
	 * @testdox Should let the override filter show or hide the menu, coercing its return value.
	 *
	 * @testWith [true, "11.3.0", true]
	 *           [false, "11.2.0", false]
	 *           ["yes", "11.3.0", true]
	 *           ["no", "11.2.0", false]
	 *           ["off", "11.2.0", false]
	 *           [1, "11.3.0", true]
	 *           [0, "11.2.0", false]
	 *           ["1", "11.3.0", true]
	 *           ["", "11.2.0", false]
	 *
	 * @param mixed  $filtered        The value returned by the filter.
	 * @param string $initial_version The stored initial installed version.
	 * @param bool   $expected        Whether the menu is expected to be shown.
	 */
	public function test_override_filter( $filtered, string $initial_version, bool $expected ): void {
		update_option( WC_Install::INITIAL_INSTALLED_VERSION, $initial_version );
		add_filter(
			'woocommerce_show_legacy_reports_menu',
			function () use ( $filtered ) {
				return $filtered;
			}
		);

		$this->assertSame( $expected, $this->is_menu_shown() );
	}

	/**
	 * @testdox Should fall back to the default when the override filter returns an invalid value.
	 *
	 * @testWith ["11.3.0", false]
	 *           ["11.2.0", true]
	 *
	 * @param string $initial_version The stored initial installed version.
	 * @param bool   $expected        Whether the menu is expected to be shown.
	 */
	public function test_override_filter_invalid_values_fall_back_to_default( string $initial_version, bool $expected ): void {
		update_option( WC_Install::INITIAL_INSTALLED_VERSION, $initial_version );

		foreach ( array( null, array( true ), new \stdClass(), 'maybe', 2 ) as $invalid ) {
			$callback = function () use ( $invalid ) {
				return $invalid;
			};
			add_filter( 'woocommerce_show_legacy_reports_menu', $callback );

			$this->assertSame( $expected, $this->is_menu_shown(), 'Invalid value: ' . wp_json_encode( $invalid ) );

			remove_filter( 'woocommerce_show_legacy_reports_menu', $callback );
		}
	}

	/**
	 * @testdox Should keep the core report list in sync with the unfiltered legacy reports.
	 */
	public function test_core_reports_list_matches_legacy_reports(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$expected = array();
		foreach ( WC_Admin_Reports::get_reports() as $group_key => $group ) {
			$expected[ $group_key ] = array_keys( $group['reports'] );
		}

		$core_reports = ( new \ReflectionClassConstant( LegacyReportsMenu::class, 'CORE_REPORTS' ) )->getValue();

		$this->assertSame( $expected, $core_reports, 'LegacyReportsMenu::CORE_REPORTS must list every core legacy report' );
	}

	/**
	 * @testdox Should treat a fresh install as new before admin_init records the initial installed version.
	 *
	 * @testWith ["yes", false]
	 *           ["no", true]
	 *           [null, true]
	 *
	 * @param string|null $newly_installed The woocommerce_newly_installed option value, or null when missing.
	 * @param bool        $expected_show   Whether the menu is expected to be shown.
	 */
	public function test_new_store_detection_before_version_is_recorded( ?string $newly_installed, bool $expected_show ): void {
		delete_option( WC_Install::INITIAL_INSTALLED_VERSION );
		if ( null === $newly_installed ) {
			delete_option( WC_Install::NEWLY_INSTALLED_OPTION );
		} else {
			update_option( WC_Install::NEWLY_INSTALLED_OPTION, $newly_installed );
		}

		$this->register_woocommerce_menu();
		$this->sut->handle_admin_menu();

		$this->assertSame( $expected_show, ! $this->has_hide_class( 'submenu', 'wc-reports' ) );
	}

	/**
	 * @testdox Should register the container instance on a late admin_menu from reports_menu(), so requests that only build the menu hide the item.
	 */
	public function test_reports_menu_registers_admin_menu_handler(): void {
		$this->reset_container_resolutions();
		remove_all_actions( 'admin_menu' );
		$menus = new WC_Admin_Menus();
		add_action( 'admin_menu', array( $menus, 'admin_menu' ), 9 );
		add_action( 'admin_menu', array( $menus, 'reports_menu' ), 20 );
		add_action( 'admin_menu', array( $menus, 'settings_menu' ), 50 );

		do_action( 'admin_menu', '' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$handler = array( wc_get_container()->get( LegacyReportsMenu::class ), 'handle_admin_menu' );
		$this->assertSame( PHP_INT_MAX, has_action( 'admin_menu', $handler ), 'reports_menu() must hook the handler late on admin_menu' );
		$this->assertTrue( $this->has_hide_class( 'submenu', 'wc-reports' ), 'The handler added during admin_menu must run in the same admin_menu' );
	}

	/**
	 * @testdox Should remember a found extension report across requests until a plugin is deactivated.
	 */
	public function test_extension_reports_stored_until_plugin_deactivated(): void {
		$calls = $this->add_counting_extension_report();

		$this->assertTrue( $this->is_menu_shown(), 'Precondition: the extension report is found' );
		$this->assertSame( 'yes', get_option( 'woocommerce_store_has_legacy_reports_related_plugins' ) );

		$this->assertTrue( $this->is_menu_shown(), 'A later request must reuse the stored result' );
		$this->assertSame( 1, $calls->count, 'A later request must not build the legacy reports again' );

		do_action( 'deactivated_plugin', 'reporting-extension/reporting-extension.php', false ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertSame( 'no', get_option( 'woocommerce_store_has_legacy_reports_related_plugins' ) );
		$this->assertTrue( $this->is_menu_shown() );
		$this->assertSame( 2, $calls->count, 'Deactivating a plugin must make the next request check again' );
	}

	/**
	 * @testdox Should not create the stored result when a plugin is deactivated before any check.
	 */
	public function test_plugin_deactivation_does_not_create_stored_result(): void {
		do_action( 'deactivated_plugin', 'some-plugin/some-plugin.php', false ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertFalse( get_option( 'woocommerce_store_has_legacy_reports_related_plugins' ) );
	}

	/**
	 * @testdox Should check again on every request while the stored result is no.
	 */
	public function test_extension_reports_checked_again_when_stored_no(): void {
		update_option( 'woocommerce_store_has_legacy_reports_related_plugins', 'no' );
		$this->add_counting_extension_report();

		$this->assertTrue( $this->is_menu_shown() );
		$this->assertSame( 'yes', get_option( 'woocommerce_store_has_legacy_reports_related_plugins' ) );
	}

	/**
	 * @testdox Should ignore the stored result once no plugin hooks the legacy report filters.
	 */
	public function test_stored_extension_reports_ignored_without_report_filters(): void {
		$this->add_counting_extension_report();
		$this->assertTrue( $this->is_menu_shown(), 'Precondition: the extension report is found' );

		remove_all_filters( 'woocommerce_admin_reports' );

		$this->assertFalse( $this->is_menu_shown() );
	}

	/**
	 * @testdox Should store no when no extension report is found, so the option is autoloaded.
	 */
	public function test_extension_reports_stored_no_when_none_found(): void {
		add_filter( 'woocommerce_admin_reports', fn( $reports ) => $reports );

		$this->assertFalse( $this->is_menu_shown() );
		$this->assertSame( 'no', get_option( 'woocommerce_store_has_legacy_reports_related_plugins' ) );
	}

	/**
	 * @testdox Should hide the WooCommerce > Reports item on a new store but keep the page accessible.
	 */
	public function test_admin_menu_hides_submenu_item_and_keeps_page_accessible(): void {
		$this->register_woocommerce_menu();

		$this->sut->handle_admin_menu();

		$this->assertStringContainsString( WC_Admin_Menus::HIDE_CSS_CLASS, $this->get_menu_item_classes( 'submenu', 'wc-reports' ) );
		$this->assertStringNotContainsString( WC_Admin_Menus::HIDE_CSS_CLASS, $this->get_menu_item_classes( 'submenu', 'wc-settings' ), 'Other items must stay visible' );
		$this->assertTrue( $this->can_access_reports_page(), 'The reports page must stay accessible by URL' );
	}

	/**
	 * @testdox Should not hide the WooCommerce > Reports item on an existing store.
	 */
	public function test_admin_menu_keeps_submenu_item_on_existing_store(): void {
		update_option( WC_Install::INITIAL_INSTALLED_VERSION, '11.2.0' );
		$this->register_woocommerce_menu();

		$this->sut->handle_admin_menu();

		$this->assertStringNotContainsString( WC_Admin_Menus::HIDE_CSS_CLASS, $this->get_menu_item_classes( 'submenu', 'wc-reports' ) );
	}

	/**
	 * @testdox Should not hide Reports when it is the first WooCommerce submenu item, since WordPress links the parent to it.
	 */
	public function test_admin_menu_keeps_reports_when_first_submenu_item(): void {
		$GLOBALS['submenu']['woocommerce'] = array( array( 'Reports', 'view_woocommerce_reports', 'wc-reports', 'Reports' ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->sut->handle_admin_menu();

		$this->assertStringNotContainsString( WC_Admin_Menus::HIDE_CSS_CLASS, $this->get_menu_item_classes( 'submenu', 'wc-reports' ) );
	}

	/**
	 * @testdox Should not recreate a Reports item that another plugin removed.
	 */
	public function test_admin_menu_does_not_recreate_removed_item(): void {
		$this->register_woocommerce_menu();
		remove_submenu_page( 'woocommerce', 'wc-reports' );
		$submenu_before = $GLOBALS['submenu'];

		$this->sut->handle_admin_menu();

		$this->assertSame( $submenu_before, $GLOBALS['submenu'] );
	}

	/**
	 * @testdox Should hide the top-level Sales reports item for users who can view reports but not the WooCommerce menu.
	 */
	public function test_admin_menu_hides_top_level_item_for_reports_only_user(): void {
		wp_set_current_user( $this->create_reports_only_user() );
		$this->assertFalse( WC_Admin_Menus::can_view_woocommerce_menu_item(), 'Precondition: user cannot see the WooCommerce menu' );

		( new WC_Admin_Menus() )->reports_menu();
		$this->sut->handle_admin_menu();

		$this->assertStringContainsString( WC_Admin_Menus::HIDE_CSS_CLASS, $this->get_menu_item_classes( 'menu', 'wc-reports' ) );
		$this->assertTrue( $this->can_access_reports_page(), 'The reports page must stay accessible by URL' );
	}

	/**
	 * @testdox Should keep denying the reports page to users without the reports capability.
	 */
	public function test_reports_page_denied_without_capability(): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );

		( new WC_Admin_Menus() )->reports_menu();
		$this->sut->handle_admin_menu();

		$this->assertFalse( $this->can_access_reports_page() );
	}

	/**
	 * Build the WooCommerce menu, run the admin_menu handler on a fresh instance, and report whether Reports is visible.
	 *
	 * @return bool
	 */
	private function is_menu_shown(): bool {
		$GLOBALS['menu']    = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['submenu'] = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->register_woocommerce_menu();

		( new LegacyReportsMenu() )->handle_admin_menu();

		return false === strpos( $this->get_menu_item_classes( 'submenu', 'wc-reports' ), WC_Admin_Menus::HIDE_CSS_CLASS );
	}

	/**
	 * Register the WooCommerce menu with Reports and Settings items, as admin_menu does.
	 */
	private function register_woocommerce_menu(): void {
		$menus = new WC_Admin_Menus();
		$menus->admin_menu();
		$menus->reports_menu();
		$menus->settings_menu();
	}

	/**
	 * Add a legacy report through woocommerce_admin_reports and count how often the filter runs.
	 *
	 * @return \stdClass Object whose count property holds the number of calls.
	 */
	private function add_counting_extension_report(): \stdClass {
		$calls = (object) array( 'count' => 0 );
		add_filter(
			'woocommerce_admin_reports',
			function ( $reports ) use ( $calls ) {
				++$calls->count;
				$reports['stock']['reports']['insufficient_stock'] = array(
					'title'    => 'Insufficient stock',
					'callback' => '__return_empty_string',
				);
				return $reports;
			}
		);

		return $calls;
	}

	/**
	 * Create a user that can view reports but cannot see the WooCommerce menu.
	 *
	 * @return int
	 */
	private function create_reports_only_user(): int {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		( new \WP_User( $user_id ) )->add_cap( 'view_woocommerce_reports' );

		return $user_id;
	}

	/**
	 * Whether the wc-* item in $menu or $submenu['woocommerce'] has the hide class.
	 *
	 * @param string $menu_global The global to search: 'menu' or 'submenu'.
	 * @param string $slug        The menu slug.
	 * @return bool
	 */
	private function has_hide_class( string $menu_global, string $slug ): bool {
		return false !== strpos( $this->get_menu_item_classes( $menu_global, $slug ), WC_Admin_Menus::HIDE_CSS_CLASS );
	}

	/**
	 * Get the CSS classes of the wc-* item in $menu or $submenu['woocommerce'].
	 *
	 * @param string $menu_global The global to search: 'menu' or 'submenu'.
	 * @param string $slug        The menu slug.
	 * @return string
	 */
	private function get_menu_item_classes( string $menu_global, string $slug ): string {
		$items = 'menu' === $menu_global ? $GLOBALS['menu'] : ( $GLOBALS['submenu']['woocommerce'] ?? array() );

		foreach ( $items as $item ) {
			if ( $slug === $item[2] ) {
				return (string) ( $item[4] ?? '' );
			}
		}

		$this->fail( "Menu item {$slug} not found in \${$menu_global}" );
	}

	/**
	 * Whether the current user can open admin.php?page=wc-reports.
	 *
	 * @return bool
	 */
	private function can_access_reports_page(): bool {
		$GLOBALS['pagenow']     = 'admin.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['plugin_page'] = 'wc-reports'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['parent_file'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		return user_can_access_admin_page();
	}
}
