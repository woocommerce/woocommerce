<?php
/**
 * Shipping Label Banner Display Rules tests.
 *
 * @package WooCommerce\Admin\Tests\ShippingLabelBannerDisplayRules
 */

use Automattic\WooCommerce\Internal\Admin\ShippingLabelBannerDisplayRules;

/**
 * Class WC_Admin_Tests_Shipping_Label_Banner_Display_Rules
 */
class WC_Admin_Tests_Shipping_Label_Banner_Display_Rules extends WC_Unit_Test_Case {

	/**
	 * Stores the default WordPress options stored in the database.
	 *
	 * @var array
	 */
	private static $modified_options = array(
		'woocommerce_default_country'              => null,
		'woocommerce_currency'                     => null,
		'woocommerce_shipping_prompt_ab'           => null,
		'woocommerce_shipping_dismissed_timestamp' => null,
	);

	/**
	 * Setup for every single test.
	 */
	public function setUp(): void {
		parent::setup();

		update_option( 'woocommerce_default_country', 'US' );
		update_option( 'woocommerce_currency', 'USD' );
	}

	/**
	 * Setup for the whole test class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		foreach ( self::$modified_options as $option_name => $option_value ) {
			self::$modified_options[ $option_name ] = $option_value;
		}
	}

	/**
	 * Cleans up test data once all test have run.
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		foreach ( self::$modified_options as $option_name => $option_value ) {
			update_option( $option_name, $option_value );
		}
	}

	/**
	 * Test if the banner is displayed when all conditions are satisfied:
	 *   - Banner NOT dismissed
	 *   - Jetpack Connected
	 *   - No incompatible extensions installed:
	 *       - Shipstation not installed
	 *       - UPS not Installed
	 *       - Fedex not installed
	 *       - ShippingEasy not installed
	 *   - Order contains physical products which need to be shipped (we should check that the order status is not set to complete)
	 *   - Store is located in US
	 *   - Store currency is set to USD
	 *   - WCS plugin not installed OR WCS is installed
	 */
	public function test_display_banner_if_all_conditions_are_met() {
		$this->with_order(
			function ( $that ) {
				$shipping_label_banner_display_rules = new ShippingLabelBannerDisplayRules( true, null, false );

				$that->assertEquals( $shipping_label_banner_display_rules->should_display_banner(), true );
			}
		);
	}

	/**
	 * Test if the banner is hidden when Jetpack is not active.
	 */
	public function test_if_banner_hidden_when_jetpack_disconnected() {
		$shipping_label_banner_display_rules = new ShippingLabelBannerDisplayRules( null, null, null );

		$this->assertEquals( $shipping_label_banner_display_rules->should_display_banner(), false );
	}

	/**
	 * Test if the banner is hidden when a dismiss banner option is checked.
	 */
	public function test_if_banner_hidden_when_dismiss_option_enabled() {
		update_option( 'woocommerce_shipping_dismissed_timestamp', -1 );
		$shipping_label_banner_display_rules = new ShippingLabelBannerDisplayRules( true, '1.22.5', false );

		$this->assertEquals( $shipping_label_banner_display_rules->should_display_banner(), false );
	}

	/**
	 * Banner should not show if it was dismissed 2 hours ago.
	 */
	public function test_if_banner_hidden_when_dismiss_was_clicked_2_hrs_ago() {
		$two_hours_from_ago = ( time() - 2 * 60 * 60 ) * 1000;
		update_option( 'woocommerce_shipping_dismissed_timestamp', $two_hours_from_ago );

		$shipping_label_banner_display_rules = new ShippingLabelBannerDisplayRules( true, '1.22.5', false );

		$this->assertEquals( $shipping_label_banner_display_rules->should_display_banner(), false );
	}

	/**
	 * Banner should show if it was dismissed 24 hours and 1 second ago.
	 */
	public function test_if_banner_hidden_when_dismiss_was_clicked_24_hrs_1s_ago() {
		$twenty_four_hours_one_sec_ago = ( time() - 24 * 60 * 60 - 1 ) * 1000;
		update_option( 'woocommerce_shipping_dismissed_timestamp', $twenty_four_hours_one_sec_ago );

		$this->with_order(
			function ( $that ) {
				$shipping_label_banner_display_rules = new ShippingLabelBannerDisplayRules( true, null, false, false );

				$that->assertEquals( $shipping_label_banner_display_rules->should_display_banner(), true );
			}
		);
	}

	/**
	 * Test if the banner is hidden when no shippable product available.
	 */
	public function test_if_banner_hidden_when_no_shippable_product() {
		$shipping_label_banner_display_rules = new ShippingLabelBannerDisplayRules( true, '1.22.5', false );

		$this->assertEquals( $shipping_label_banner_display_rules->should_display_banner(), false );
	}

	/**
	 * Test if the banner is displayed when the store is in the US.
	 */
	public function test_if_banner_hidden_when_store_is_not_in_us() {
		update_option( 'woocommerce_default_country', 'ES' );
		$this->with_order(
			function ( $that ) {
				$shipping_label_banner_display_rules = new ShippingLabelBannerDisplayRules( true, '1.22.5', false, false );

				$that->assertEquals( $shipping_label_banner_display_rules->should_display_banner(), false );
			}
		);
	}

	/**
	 * Test if the banner is displayed when the store's currency is USD.
	 */
	public function test_if_banner_hidden_when_currency_is_not_usd() {
		update_option( 'woocommerce_currency', 'EUR' );
		$this->with_order(
			function ( $that ) {
				$shipping_label_banner_display_rules = new ShippingLabelBannerDisplayRules( true, '1.22.5', false );

				$that->assertEquals( $shipping_label_banner_display_rules->should_display_banner(), false );
			}
		);
	}

	/**
	 * Test if the banner is hidden when an incompatible plugin is installed
	 */
	public function test_if_banner_hidden_when_incompatible_plugin_installed() {
		$this->with_order(
			function ( $that ) {
				$shipping_label_banner_display_rules = new ShippingLabelBannerDisplayRules( true, '1.22.5', false, true );

				$that->assertEquals( $shipping_label_banner_display_rules->should_display_banner(), false );
			}
		);
	}

	/**
	 * Test if the banner is hidden when WooCommerce Shipping & Tax is installed but not up to date.
	 */
	public function test_if_banner_hidden_when_wcs_not_installed() {
		$this->with_order(
			function ( $that ) {
				$shipping_label_banner_display_rules = new ShippingLabelBannerDisplayRules( true, '1.22.4', false );

				$that->assertEquals( $shipping_label_banner_display_rules->should_display_banner(), false );
			}
		);
	}

	/**
	 * Creates a test order.
	 */
	private function create_order() {
		$product = WC_Helper_Product::create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );

		global $theorder;
		$theorder = $order;
		return $order;
	}

	/**
	 * Destroys the test order.
	 *
	 * @param object $order to destroy.
	 */
	private function destroy_order( $order ) {
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$product->delete( true );
			$item->delete( true );
		}

		$order->delete( true );
	}

	/**
	 * Wraps a function call within an order creation/deletion lifecycle.
	 *
	 * @param function $callback to wrap.
	 */
	private function with_order( $callback ) {
		$order = $this->create_order();

		$callback( $this );

		$this->destroy_order( $order );
	}
}
