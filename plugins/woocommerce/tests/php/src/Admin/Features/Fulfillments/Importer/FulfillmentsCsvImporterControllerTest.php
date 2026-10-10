<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Importer;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Importer\FulfillmentsCsvImporterController;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use ReflectionMethod;
use WC_Unit_Test_Case;

/**
 * Tests for the FulfillmentsCsvImporterController class.
 */
class FulfillmentsCsvImporterControllerTest extends WC_Unit_Test_Case {

	private const HANDLE = 'wc-admin-fulfillments-importer';

	/**
	 * The System Under Test.
	 *
	 * @var FulfillmentsCsvImporterController
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 *
	 * The feature is enabled and initialized inside the test transaction so the hooks it
	 * registers (importer, shipping providers) are restored after every test.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
		// A fresh instance so the per-request enqueue guard starts clear in every test.
		$this->sut = new FulfillmentsCsvImporterController();
	}

	/**
	 * Tear down process state the base class does not reset.
	 */
	public function tearDown(): void {
		try {
			unset( $_GET['fulfillments_importer'] );
			wp_deregister_script( self::HANDLE );
			wp_deregister_style( self::HANDLE );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Invoke a private method on the SUT.
	 *
	 * @param string $method Method name.
	 * @param mixed  ...$args Arguments.
	 * @return mixed
	 */
	private function call_private( string $method, ...$args ) {
		$reflection = new ReflectionMethod( $this->sut, $method );
		$reflection->setAccessible( true );
		return $reflection->invoke( $this->sut, ...$args );
	}

	/**
	 * Log in as a new user with the given role.
	 *
	 * @param string $role Role slug.
	 */
	private function log_in_as( string $role ): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => $role ) ) );
	}

	/**
	 * Whether the admin bundle exists on disk; CI may run PHPUnit without building it.
	 *
	 * @return bool
	 */
	private function assets_are_built(): bool {
		return (bool) glob( WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . 'wp-admin-scripts/fulfillments-importer*.asset.php' );
	}

	/**
	 * Decode the object wp_localize_script attached to the importer script.
	 *
	 * @return array
	 */
	private function get_localized_settings(): array {
		$data = wp_scripts()->get_data( self::HANDLE, 'data' );
		$this->assertIsString( $data, 'The importer script should carry localized data' );
		$json = rtrim( preg_replace( '/^var wcFulfillmentsImporterSettings = /', '', trim( $data ) ), ';' );
		return json_decode( $json, true );
	}

	/**
	 * @testdox Should render only on the orders list that matches the active order storage.
	 * @testWith ["yes", "woocommerce_page_wc-orders", true]
	 *           ["yes", "edit-shop_order", false]
	 *           ["no", "edit-shop_order", true]
	 *           ["no", "woocommerce_page_wc-orders", false]
	 *           ["no", "dashboard", false]
	 *
	 * @param string $hpos      Value of the HPOS usage option.
	 * @param string $screen_id Current screen id.
	 * @param bool   $expected  Whether the importer should render.
	 */
	public function test_should_render_importer_matches_the_orders_screen( string $hpos, string $screen_id, bool $expected ): void {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, $hpos );
		$this->log_in_as( 'shop_manager' );
		set_current_screen( $screen_id );

		$this->assertSame( $expected, $this->call_private( 'should_render_importer' ), "Screen {$screen_id} with HPOS set to {$hpos}" );
	}

	/**
	 * @testdox Should not render for users without the manage_woocommerce capability.
	 */
	public function test_should_render_importer_requires_manage_woocommerce(): void {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		$this->log_in_as( 'subscriber' );
		set_current_screen( 'woocommerce_page_wc-orders' );

		$this->assertFalse( $this->call_private( 'should_render_importer' ), 'Subscribers must not get the importer' );
	}

	/**
	 * @testdox Should output the modal mount point only on the orders list.
	 * @testWith ["woocommerce_page_wc-orders", "<div id=\"wc_fulfillments_importer_panel_container\"></div>"]
	 *           ["dashboard", ""]
	 *
	 * @param string $screen_id Current screen id.
	 * @param string $expected  Expected output.
	 */
	public function test_render_modal_slot( string $screen_id, string $expected ): void {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		$this->log_in_as( 'shop_manager' );
		set_current_screen( $screen_id );

		ob_start();
		$this->sut->render_modal_slot();
		$output = ob_get_clean();

		$this->assertSame( $expected, $output );
	}

	/**
	 * @testdox Should output nothing for users without the manage_woocommerce capability.
	 */
	public function test_render_modal_slot_requires_manage_woocommerce(): void {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		$this->log_in_as( 'subscriber' );
		set_current_screen( 'woocommerce_page_wc-orders' );

		ob_start();
		$this->sut->render_modal_slot();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	/**
	 * @testdox Should build the auto-open URL on the orders screen for the active order storage.
	 * @testWith ["yes", "admin.php?page=wc-orders"]
	 *           ["no", "edit.php?post_type=shop_order"]
	 *
	 * @param string $hpos          Value of the HPOS usage option.
	 * @param string $expected_base Orders screen path.
	 */
	public function test_get_importer_url_targets_the_active_orders_screen( string $hpos, string $expected_base ): void {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, $hpos );

		$url = $this->call_private( 'get_importer_url' );

		$this->assertStringStartsWith( admin_url( $expected_base ), $url );
		$this->assertStringContainsString( 'fulfillments_importer=open', $url );
	}

	/**
	 * @testdox Should list the auto-open arg as a removable query arg.
	 */
	public function test_register_removable_query_arg(): void {
		$this->assertContains( 'fulfillments_importer', wp_removable_query_args(), 'The arg must go through the removable_query_args filter' );
		$this->assertSame( 'not-an-array', $this->sut->register_removable_query_arg( 'not-an-array' ), 'Non-array input should be returned unchanged' );
	}

	/**
	 * @testdox Should not enqueue anything away from the orders list.
	 */
	public function test_enqueue_assets_skips_other_screens(): void {
		$this->log_in_as( 'shop_manager' );
		set_current_screen( 'dashboard' );

		$this->sut->enqueue_assets();

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
		$this->assertFalse( wp_style_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * @testdox Should enqueue the bundle and localize the route, providers and auto-open flag.
	 * @testWith [false, ""]
	 *           [true, "1"]
	 *
	 * @param bool   $auto_open     Whether the request carries fulfillments_importer=open.
	 * @param string $expected_flag Localized autoOpen value (wp_localize_script casts booleans).
	 */
	public function test_enqueue_assets_localizes_settings( bool $auto_open, string $expected_flag ): void {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		$this->log_in_as( 'shop_manager' );
		set_current_screen( 'woocommerce_page_wc-orders' );
		if ( $auto_open ) {
			$_GET['fulfillments_importer'] = 'open';
		}

		$this->sut->enqueue_assets();

		if ( ! $this->assets_are_built() ) {
			$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ), 'Without a build the importer must stay off the page instead of failing' );
			return;
		}

		$this->assertTrue( wp_script_is( self::HANDLE, 'enqueued' ), 'Script should be enqueued' );
		$this->assertTrue( wp_style_is( self::HANDLE, 'enqueued' ), 'Style should be enqueued' );

		$settings = $this->get_localized_settings();
		$this->assertSame( '/wc/v3/fulfillments/import', $settings['importRoute'] );
		$this->assertSame( $expected_flag, $settings['autoOpen'] );
		$this->assertNotEmpty( $settings['providers'], 'Built-in shipping providers should be localized' );
		$this->assertArrayHasKey( 'key', $settings['providers'][0] );
		$this->assertArrayHasKey( 'label', $settings['providers'][0] );
	}
}
