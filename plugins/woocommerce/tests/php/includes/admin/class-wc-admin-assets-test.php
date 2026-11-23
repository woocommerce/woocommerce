<?php
declare( strict_types = 1 );

// WC_Admin_Assets reads the marketplace suggestions, and nothing in the bootstrap loads that class.
require_once WC_ABSPATH . 'includes/admin/marketplace-suggestions/class-wc-marketplace-suggestions.php';

/**
 * Tests for WC_Admin_Assets.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_Assets_Test extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_Admin_Assets
	 */
	private $sut;

	/**
	 * Directories and files a test created for a stub asset registry, in creation order.
	 *
	 * @var string[]
	 */
	private $created_asset_paths = array();

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_Admin_Assets();
		$this->sut->register_scripts();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		try {
			unset( $_GET['page'] );
			wp_dequeue_script( 'woocommerce_admin' );
			wp_dequeue_script( 'woocommerce_quick-edit' );
			wp_dequeue_script( 'jquery-ui-datepicker' );
			wp_dequeue_script( 'heartbeat' );
			// wp_localize_script() appends to a handle's data, and nothing resets wp_scripts() between tests.
			wp_deregister_script( 'wc-admin-command-palette' );
			wp_deregister_script( 'wc-admin-command-palette-analytics' );
			foreach ( array_reverse( $this->created_asset_paths ) as $path ) {
				if ( is_dir( $path ) ) {
					rmdir( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
				} else {
					wp_delete_file( $path );
				}
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Quick Edit remains loadable when the optional datepicker is unavailable.
	 */
	public function test_quick_edit_does_not_depend_on_datepicker(): void {
		set_current_screen();
		$screen            = get_current_screen();
		$screen->id        = 'edit-product';
		$screen->base      = 'edit';
		$screen->post_type = 'product';

		$this->sut->admin_scripts();

		$quick_edit = wp_scripts()->registered['woocommerce_quick-edit'];

		$this->assertNotContains( 'jquery-ui-datepicker', $quick_edit->deps, 'Quick Edit should load even when another plugin deregisters the datepicker.' );
		$this->assertTrue( wp_script_is( 'jquery-ui-datepicker', 'enqueued' ), 'The datepicker should still be requested when it is available.' );
	}

	/**
	 * @testdox Should set up the lost connection notice and heartbeat correctly per screen, and never re-enqueue autosave.
	 * @testWith ["woocommerce_page_wc-orders", "woocommerce_page_wc-orders", "", false, true]
	 *           ["shop_order", "post", "shop_order", false, true]
	 *           ["product", "post", "product", false, false]
	 *           ["toplevel_page_woocommerce", "toplevel_page_woocommerce", "", true, false]
	 *
	 * @param string $screen_id   Screen id.
	 * @param string $base        Screen base.
	 * @param string $post_type   Screen post type, if any.
	 * @param bool   $is_wc_admin Whether to simulate a wc-admin React page.
	 * @param bool   $expected    Expected show_lost_connection_notice value.
	 */
	public function test_admin_scripts_lost_connection_notice_setup( string $screen_id, string $base, string $post_type, bool $is_wc_admin, bool $expected ): void {
		set_current_screen();
		$screen            = get_current_screen();
		$screen->id        = $screen_id;
		$screen->base      = $base;
		$screen->post_type = $post_type;
		$_GET['page']      = $is_wc_admin ? 'wc-admin' : '';

		$this->sut->admin_scripts();

		$localized = wp_scripts()->get_data( 'woocommerce_admin', 'data' );
		$this->assertIsString( $localized, 'woocommerce_admin should be localized on this screen' );
		$this->assertStringContainsString(
			'"show_lost_connection_notice":"' . ( $expected ? '1' : '' ) . '"',
			$localized,
			'show_lost_connection_notice should be ' . ( $expected ? 'true' : 'false' ) . " for screen '{$screen_id}'"
		);

		$this->assertSame(
			$expected,
			wp_scripts()->query( 'heartbeat', 'enqueued' ),
			'heartbeat should be enqueued exactly on the screens that use the notice'
		);

		$this->assertFalse(
			wp_scripts()->query( 'autosave', 'enqueued' ),
			'autosave must never be enqueued, since it turns on unrelated post.js handlers'
		);
	}

	/**
	 * @testdox Should render the lost connection notice markup only where expected.
	 * @testWith ["woocommerce_page_wc-orders", "woocommerce_page_wc-orders", "", true]
	 *           ["shop_order", "post", "shop_order", true]
	 *           ["product", "post", "product", false]
	 *
	 * @param string $screen_id     Screen id.
	 * @param string $base          Screen base.
	 * @param string $post_type     Screen post type, if any.
	 * @param bool   $should_render Whether the markup should render at all.
	 */
	public function test_render_lost_connection_notice_markup( string $screen_id, string $base, string $post_type, bool $should_render ): void {
		set_current_screen();
		$screen            = get_current_screen();
		$screen->id        = $screen_id;
		$screen->base      = $base;
		$screen->post_type = $post_type;

		ob_start();
		$this->sut->render_lost_connection_notice();
		$output = ob_get_clean();

		if ( ! $should_render ) {
			$this->assertSame( '', $output, "No notice should render for screen '{$screen_id}'" );
			return;
		}

		$this->assertStringContainsString( 'id="wc-lost-connection-notice"', $output );
	}

	/**
	 * @testdox Should localize the Analytics reports from Analytics::get_report_pages() for the command palette.
	 */
	public function test_enqueue_command_palette_assets_localizes_analytics_reports(): void {
		$this->ensure_wp_admin_script_asset_registry( 'command-palette' );
		$this->ensure_wp_admin_script_asset_registry( 'command-palette-analytics' );
		add_filter(
			'woocommerce_analytics_report_menu_items',
			static function () {
				return array(
					array(
						'id'    => 'test-analytics-revenue',
						'title' => 'Revenue <em>report</em>',
						'path'  => '/analytics/revenue',
					),
					array(
						'id'    => 'test-analytics-customers',
						'title' => 'Customers',
						'path'  => '/customers',
					),
				);
			}
		);

		$this->sut->enqueue_command_palette_assets();

		$this->assertTrue( wp_script_is( 'wc-admin-command-palette-analytics', 'enqueued' ), 'The Analytics command palette bundle should be enqueued' );
		$localized = $this->get_localized_object( 'wc-admin-command-palette-analytics', 'wcCommandPaletteAnalytics' );
		$this->assertIsArray( $localized->reports ?? null, 'Reports should reach JS as an array, the only shape the command palette registers' );
		$this->assertSame( array( 'Revenue report', 'Customers' ), array_column( $localized->reports, 'title' ), 'Each report title should be passed with its HTML stripped' );
		$this->assertSame( array( '/analytics/revenue', '/customers' ), array_column( $localized->reports, 'path' ), 'Each report path should be passed unchanged' );
	}

	/**
	 * @testdox Should localize the Analytics reports as an array when stock management is off and the Stock report is left out.
	 */
	public function test_enqueue_command_palette_assets_localizes_reports_as_array_without_stock_report(): void {
		$this->ensure_wp_admin_script_asset_registry( 'command-palette' );
		$this->ensure_wp_admin_script_asset_registry( 'command-palette-analytics' );
		update_option( 'woocommerce_manage_stock', 'no' );

		$this->sut->enqueue_command_palette_assets();

		$localized = $this->get_localized_object( 'wc-admin-command-palette-analytics', 'wcCommandPaletteAnalytics' );
		$this->assertIsArray( $localized->reports ?? null, 'Reports should stay an array once the missing Stock report is dropped, or the command palette registers none of them' );
		$this->assertNotContains( '/analytics/stock', array_column( $localized->reports, 'path' ), 'The Stock report should not be offered when stock management is off' );
	}

	/**
	 * Writes a stub asset registry for a wp-admin-scripts bundle that is not built, as in the CI PHPUnit jobs.
	 * tearDown() removes every directory and file this creates.
	 *
	 * @param string $script_name Bundle name under wp-admin-scripts.
	 */
	private function ensure_wp_admin_script_asset_registry( string $script_name ): void {
		$dir = WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts';
		if ( is_readable( "{$dir}/{$script_name}.asset.php" ) || is_readable( "{$dir}/{$script_name}.min.asset.php" ) ) {
			return;
		}

		$missing_dirs = array();
		$path         = $dir;
		while ( ! is_dir( $path ) ) {
			array_unshift( $missing_dirs, $path );
			$path = dirname( $path );
		}
		$this->assertTrue( wp_mkdir_p( $dir ), "{$dir} should be writable for the stub asset registry" );
		$this->created_asset_paths = array_merge( $this->created_asset_paths, $missing_dirs );

		$file = "{$dir}/{$script_name}.asset.php";
		file_put_contents( $file, "<?php return array( 'dependencies' => array(), 'version' => 'test' );" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->created_asset_paths[] = $file;
	}

	/**
	 * Decodes the JS global a script handle's localized data assigns.
	 *
	 * @param string $handle      Script handle.
	 * @param string $object_name JS global name.
	 * @return mixed The decoded value.
	 */
	private function get_localized_object( string $handle, string $object_name ) {
		$data   = (string) wp_scripts()->get_data( $handle, 'data' );
		$prefix = "var {$object_name} = ";
		$this->assertStringStartsWith( $prefix, $data, "{$handle} should localize {$object_name}" );

		return json_decode( rtrim( substr( $data, strlen( $prefix ) ), ';' ) );
	}
}
