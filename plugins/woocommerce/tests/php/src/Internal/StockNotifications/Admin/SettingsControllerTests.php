<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\SettingsController;
use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountEndpoint;
use WC_Helper_Product;
use WC_Settings_Advanced;
use WC_Settings_Products;
use WP_REST_Request;

/**
 * SettingsControllerTests data tests.
 */
class SettingsControllerTests extends \WC_Settings_Unit_Test_Case {

	/**
	 * The controller whose hooks the tests exercise.
	 *
	 * @var SettingsController
	 */
	private $controller;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		// Built directly rather than through the container, whose cached instance loses its hooks after the first test.
		$this->controller = new SettingsController();
	}

	/**
	 * @testdox The Customer stock notifications section is added to the Products tab right after Inventory.
	 */
	public function test_customer_stock_notifications_section_is_added_after_inventory() {
		$sut = new WC_Settings_Products();

		// Other features hook sections in too, so assert the position rather than the full list.
		$section_ids = array_keys( $sut->get_sections() );
		$inventory   = array_search( 'inventory', $section_ids, true );

		$this->assertNotFalse( $inventory );
		$this->assertSame( 'customer_stock_notifications', $section_ids[ $inventory + 1 ] );
	}

	/**
	 * @testdox The Customer stock notifications section is appended when there is no Inventory section to follow.
	 */
	public function test_customer_stock_notifications_section_is_appended_without_inventory() {
		$sections = $this->controller->add_customer_stock_notifications_section( array( '' => 'General' ) );

		$this->assertSame( array( '', 'customer_stock_notifications' ), array_keys( $sections ) );
	}

	/**
	 * @testdox get_settings('customer_stock_notifications') should return all the settings for the customer stock notifications section.
	 */
	public function test_get_customer_stock_notifications_settings_returns_all_settings() {
		$sut = new WC_Settings_Products();

		$settings              = $sut->get_settings_for_section( 'customer_stock_notifications' );
		$setting_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'product_customer_stock_notifications_options' => array( 'title', 'sectionend' ),
			'woocommerce_customer_stock_notifications_allow_signups' => 'checkbox',
			'woocommerce_customer_stock_notifications_require_double_opt_in' => 'checkbox',
			'woocommerce_customer_stock_notifications_require_account' => 'checkbox',
			'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold' => 'number',
		);

		$this->assertEquals( $expected, $setting_ids_and_types );
	}

	/**
	 * @testdox Outside admin, only the settings filters are attached.
	 */
	public function test_only_settings_filters_are_attached_outside_admin(): void {
		$this->assertFalse( is_admin() );

		$sut = new SettingsController();

		$this->assertSame( 100, has_filter( 'woocommerce_get_sections_products', array( $sut, 'add_customer_stock_notifications_section' ) ) );
		$this->assertSame( 100, has_filter( 'woocommerce_get_settings_products', array( $sut, 'add_customer_stock_notifications_settings' ) ) );
		$this->assertSame( 100, has_filter( 'woocommerce_get_settings_advanced', array( $sut, 'add_my_account_endpoint_setting' ) ) );
		$this->assertFalse( has_action( 'admin_notices', array( $sut, 'output_admin_notices' ) ) );
		$this->assertFalse( has_action( 'woocommerce_product_options_stock_status', array( $sut, 'add_disable_stock_notifications_checkbox' ) ) );
		$this->assertFalse( has_action( 'woocommerce_admin_process_product_object', array( $sut, 'process_product_object' ) ) );
	}

	/**
	 * @testdox In admin, the product edit and notice hooks are attached as well.
	 */
	public function test_admin_hooks_are_attached_in_admin(): void {
		set_current_screen( 'edit-post' );
		$this->assertTrue( is_admin() );

		$sut = new SettingsController();

		$this->assertSame( 100, has_filter( 'woocommerce_get_sections_products', array( $sut, 'add_customer_stock_notifications_section' ) ) );
		$this->assertSame( 100, has_filter( 'woocommerce_get_settings_products', array( $sut, 'add_customer_stock_notifications_settings' ) ) );
		$this->assertSame( 100, has_filter( 'woocommerce_get_settings_advanced', array( $sut, 'add_my_account_endpoint_setting' ) ) );
		$this->assertSame( 10, has_action( 'admin_notices', array( $sut, 'output_admin_notices' ) ) );
		$this->assertSame( 20, has_action( 'woocommerce_product_options_stock_status', array( $sut, 'add_disable_stock_notifications_checkbox' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_admin_process_product_object', array( $sut, 'process_product_object' ) ) );
	}

	/**
	 * @testdox The My Account endpoint setting is added to the Advanced tab, inside the account endpoints group, right after Downloads.
	 */
	public function test_my_account_endpoint_setting_is_added_to_the_advanced_tab() {
		$sut = new WC_Settings_Advanced();

		$settings = $sut->get_settings_for_section( '' );
		$ids      = wp_list_pluck( $settings, 'id' );

		$setting_index = array_search( MyAccountEndpoint::ENDPOINT_OPTION, $ids, true );

		$this->assertNotFalse( $setting_index, 'The endpoint setting should be registered.' );

		// It must land inside the account endpoints group, before its sectionend.
		$group_end = null;
		foreach ( $settings as $index => $setting ) {
			if ( isset( $setting['type'], $setting['id'] ) && 'sectionend' === $setting['type'] && 'account_endpoint_options' === $setting['id'] ) {
				$group_end = $index;
				break;
			}
		}

		$this->assertNotNull( $group_end, 'The account endpoints group should exist.' );
		$this->assertLessThan( $group_end, $setting_index );
		$this->assertSame( 'woocommerce_myaccount_downloads_endpoint', $ids[ $setting_index - 1 ], 'The endpoint setting should sit right after Downloads.' );
		$this->assertSame( 'text', $settings[ $setting_index ]['type'] );
		$this->assertSame( MyAccountEndpoint::ENDPOINT, $settings[ $setting_index ]['default'] );
	}

	/**
	 * @testdox The My Account endpoint setting is sanitized as an endpoint slug on save.
	 */
	public function test_my_account_endpoint_setting_is_sanitized_on_save() {
		$hook = 'woocommerce_admin_settings_sanitize_option_' . MyAccountEndpoint::ENDPOINT_OPTION;

		$this->assertSame( 10, has_filter( $hook, 'wc_sanitize_endpoint_slug' ) );
		$this->assertSame( 'restock-alerts', apply_filters( $hook, 'Restock Alerts' ) );
		$this->assertSame( 'stock-notifications', apply_filters( $hook, 'stock-notifications' ) );
	}

	/**
	 * @testdox The My Account endpoint setting is sanitized the same way when saved through the REST settings API.
	 */
	public function test_my_account_endpoint_setting_is_sanitized_via_rest() {
		// Routes must register on rest_api_init; firing it explicitly makes the test's outcome
		// independent of whatever REST server state an earlier test in the process left behind.
		do_action( 'rest_api_init' );

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/settings/advanced/' . MyAccountEndpoint::ENDPOINT_OPTION );
		$request->set_body_params( array( 'value' => 'Restock Alerts' ) );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status(), 'Saving the endpoint setting via REST should succeed.' );
		$this->assertSame( 'restock-alerts', get_option( MyAccountEndpoint::ENDPOINT_OPTION ), 'The value saved via the REST settings API should be sanitized as an endpoint slug, matching the classic admin settings save path.' );
	}

	/**
	 * @return array<string, array{0: string, 1: bool, 2: string}>
	 */
	public function provider_product_signups_checkbox(): array {
		return array(
			'no meta, checkbox unticked' => array( '', false, 'no' ),
			'no meta, checkbox ticked'   => array( '', true, '' ),
			'disabled, checkbox ticked'  => array( 'no', true, 'yes' ),
		);
	}

	/**
	 * @testdox Saving a product stores the "Stock notifications" checkbox when it differs from the effective value.
	 *
	 * @dataProvider provider_product_signups_checkbox
	 *
	 * @param string $stored_value   Meta value before saving.
	 * @param bool   $posted_enabled Whether the checkbox is posted.
	 * @param string $expected_value Meta value after saving.
	 */
	public function test_process_product_object_saves_signups_checkbox( string $stored_value, bool $posted_enabled, string $expected_value ): void {
		update_option( 'woocommerce_customer_stock_notifications_allow_signups', 'yes' );
		$meta_key = Config::get_product_signups_meta_key();
		$product  = WC_Helper_Product::create_simple_product();
		if ( '' !== $stored_value ) {
			$product->update_meta_data( $meta_key, $stored_value );
			$product->save();
		}

		$nonce = wp_create_nonce( 'woocommerce-customer-stock-notifications-edit-product' );
		$_POST['customer_stock_notifications_edit_product_security']    = $nonce;
		$_REQUEST['customer_stock_notifications_edit_product_security'] = $nonce;
		if ( $posted_enabled ) {
			$_POST[ $meta_key ] = 'on';
		}

		SettingsController::process_product_object( $product );

		$this->assertSame( $expected_value, $product->get_meta( $meta_key ) );
	}

	/**
	 * Reset the REST server and request globals so this test does not leak them into tests that run after it.
	 */
	public function tearDown(): void {
		unset( $_POST['customer_stock_notifications_edit_product_security'], $_REQUEST['customer_stock_notifications_edit_product_security'], $_POST[ Config::get_product_signups_meta_key() ] );
		$this->clear_rest_server();
		parent::tearDown();
	}
}
