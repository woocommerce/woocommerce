<?php
/**
 * Tests for the order data meta box.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

require_once WC_ABSPATH . 'includes/admin/wc-meta-box-functions.php';
require_once WC_ABSPATH . 'includes/admin/meta-boxes/class-wc-meta-box-order-data.php';

/**
 * Tests for WC_Meta_Box_Order_Data.
 */
class WC_Meta_Box_Order_Data_Test extends WC_Unit_Test_Case {

	/**
	 * Orders created by a test.
	 *
	 * @var WC_Order[]
	 */
	private $orders = array();

	/**
	 * Products created by a test.
	 *
	 * @var WC_Product[]
	 */
	private $products = array();

	/**
	 * Whether the shipping calculation option existed before the test.
	 *
	 * @var bool
	 */
	private $had_shipping_calculation_option;

	/**
	 * Original shipping calculation option value.
	 *
	 * @var mixed
	 */
	private $original_shipping_calculation_option;

	/**
	 * Whether the global order existed before the test.
	 *
	 * @var bool
	 */
	private $had_global_order;

	/**
	 * Original global order value.
	 *
	 * @var mixed
	 */
	private $original_global_order;

	/**
	 * Shipping fields filter added by a test.
	 *
	 * @var callable|null
	 */
	private $shipping_fields_filter;

	/**
	 * Set up test state.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->had_shipping_calculation_option      = false !== get_option( 'woocommerce_calc_shipping', false );
		$this->original_shipping_calculation_option = get_option( 'woocommerce_calc_shipping' );
		$this->had_global_order                     = array_key_exists( 'theorder', $GLOBALS );
		$this->original_global_order                = $GLOBALS['theorder'] ?? null;

		update_option( 'woocommerce_calc_shipping', 'yes' );
	}

	/**
	 * Restore test state.
	 */
	public function tearDown(): void {
		if ( $this->shipping_fields_filter ) {
			remove_filter( 'woocommerce_admin_shipping_fields', $this->shipping_fields_filter );
		}

		foreach ( $this->orders as $order ) {
			$order->delete( true );
		}

		foreach ( $this->products as $product ) {
			$product->delete( true );
		}

		if ( $this->had_shipping_calculation_option ) {
			update_option( 'woocommerce_calc_shipping', $this->original_shipping_calculation_option );
		} else {
			delete_option( 'woocommerce_calc_shipping' );
		}

		if ( $this->had_global_order ) {
			$GLOBALS['theorder'] = $this->original_global_order;
		} else {
			unset( $GLOBALS['theorder'] );
		}

		parent::tearDown();
	}

	/**
	 * @testdox The read-only summary hides persisted shipping details when the order does not need shipping.
	 */
	public function test_hides_shipping_details_when_order_does_not_need_shipping(): void {
		$order = $this->create_order_with_shipping_data( false );

		$this->assertFalse( $order->needs_shipping_address(), 'The virtual-only order should not need a shipping address.' );
		$this->assertTrue( $order->has_shipping_address(), 'The order should retain its Store API compatibility shipping data.' );

		$summary = $this->render_shipping_address_summary( $order );

		$this->assertStringContainsString( 'No shipping address set.', $summary );
		$this->assertStringNotContainsString( 'Virtual Customer', $summary );
		$this->assertStringNotContainsString( '500 Billing Avenue', $summary );
		$this->assertStringNotContainsString( '555-0100', $summary );
	}

	/**
	 * @testdox The read-only summary allows extensions to display otherwise suppressed shipping details.
	 */
	public function test_filter_can_display_suppressed_shipping_details(): void {
		$order = $this->create_order_with_shipping_data( false );

		$show_shipping_details = function ( $hide_shipping_details, $filtered_order ) use ( $order ) {
			$this->assertTrue( $hide_shipping_details, 'The filter should receive the computed suppression decision.' );
			$this->assertSame( $order, $filtered_order, 'The filter should receive the order being rendered.' );

			return false;
		};
		add_filter( 'woocommerce_hide_order_admin_shipping_details', $show_shipping_details, 10, 2 );

		try {
			$summary = $this->render_shipping_address_summary( $order );
		} finally {
			remove_filter( 'woocommerce_hide_order_admin_shipping_details', $show_shipping_details );
		}

		$this->assertStringContainsString( 'Virtual Customer', $summary );
		$this->assertStringContainsString( '500 Billing Avenue', $summary );
		$this->assertStringContainsString( '555-0100', $summary );
		$this->assertStringNotContainsString( 'No shipping address set.', $summary );
	}

	/**
	 * @testdox The read-only summary displays persisted shipping details when the order needs shipping.
	 */
	public function test_displays_shipping_details_when_order_needs_shipping(): void {
		$order = $this->create_order_with_shipping_data( true );

		$this->assertTrue( $order->needs_shipping_address(), 'The flat-rate order should need a shipping address.' );

		$summary = $this->render_shipping_address_summary( $order );

		$this->assertStringContainsString( 'Virtual Customer', $summary );
		$this->assertStringContainsString( '500 Billing Avenue', $summary );
		$this->assertStringContainsString( '555-0100', $summary );
		$this->assertStringNotContainsString( 'No shipping address set.', $summary );
	}

	/**
	 * @testdox The read-only summary preserves explicit shipping details for non-Store API orders.
	 */
	public function test_displays_explicit_shipping_details_for_non_store_api_order(): void {
		$order = $this->create_order_with_shipping_data( false, 'rest-api' );

		$this->assertFalse( $order->needs_shipping_address(), 'The order should exercise the no-shipping-address path.' );

		$summary = $this->render_shipping_address_summary( $order );

		$this->assertStringContainsString( 'Virtual Customer', $summary );
		$this->assertStringContainsString( '500 Billing Avenue', $summary );
		$this->assertStringContainsString( '555-0100', $summary );
		$this->assertStringNotContainsString( 'No shipping address set.', $summary );
	}

	/**
	 * @testdox The read-only summary preserves shipping details for physical Store API pickup orders.
	 */
	public function test_displays_shipping_details_for_store_api_pickup_order(): void {
		$order = $this->create_order_with_shipping_data( true, 'store-api', 'local_pickup' );

		$this->assertFalse( $order->needs_shipping_address(), 'Local pickup should exercise the no-shipping-address path.' );

		$summary = $this->render_shipping_address_summary( $order );

		$this->assertStringContainsString( 'Virtual Customer', $summary );
		$this->assertStringContainsString( '500 Billing Avenue', $summary );
		$this->assertStringContainsString( '555-0100', $summary );
		$this->assertStringNotContainsString( 'No shipping address set.', $summary );
	}

	/**
	 * @testdox The read-only summary keeps shipping details after an ordered product is later made virtual.
	 */
	public function test_preserves_shipping_details_after_product_becomes_virtual(): void {
		$order = $this->create_order_with_shipping_data( true );

		$this->assertTrue( $order->needs_shipping_address(), 'The flat-rate order should need a shipping address.' );

		// Simulate a merchant editing the ordered catalog product to be virtual after the order was placed.
		foreach ( $this->products as $product ) {
			$product->set_virtual( true );
			$product->save();
		}

		// Re-read the order so its line items resolve the updated catalog product state.
		$order = wc_get_order( $order->get_id() );

		$summary = $this->render_shipping_address_summary( $order );

		$this->assertStringContainsString( 'Virtual Customer', $summary );
		$this->assertStringContainsString( '500 Billing Avenue', $summary );
		$this->assertStringContainsString( '555-0100', $summary );
		$this->assertStringNotContainsString( 'No shipping address set.', $summary );
	}

	/**
	 * @testdox The read-only summary shows explicitly edited shipping details on a Store API order without shipping.
	 */
	public function test_displays_explicitly_edited_shipping_details_on_store_api_order(): void {
		$order = $this->create_order_with_shipping_data( false );

		$this->assertFalse( $order->needs_shipping_address(), 'The virtual-only order should not need a shipping address.' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Simulate the nonce-verified meta-box save request.
		$previous_post = $_POST;
		$_POST         = array(
			'order_status'        => $order->get_status(),
			'_payment_method'     => $order->get_payment_method(),
			'customer_user'       => 0,
			'_shipping_address_1' => '742 Evergreen Terrace',
			'_shipping_city'      => 'Springfield',
			'_shipping_phone'     => '555-0199',
		);

		try {
			WC_Meta_Box_Order_Data::save( $order->get_id() );
		} finally {
			$_POST = $previous_post;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Re-read the order so the summary reflects the persisted, explicit values.
		$order = wc_get_order( $order->get_id() );

		$summary = $this->render_shipping_address_summary( $order );

		$this->assertCount( 0, $order->get_shipping_methods(), 'Editing shipping fields should not add a shipping method.' );
		$this->assertSame( '742 Evergreen Terrace', $order->get_shipping_address_1( 'edit' ) );
		$this->assertSame( '555-0199', $order->get_shipping_phone( 'edit' ) );
		$this->assertStringContainsString( '742 Evergreen Terrace', $summary );
		$this->assertStringContainsString( 'Springfield', $summary );
		$this->assertStringContainsString( '555-0199', $summary );
		$this->assertStringNotContainsString( 'No shipping address set.', $summary );
	}

	/**
	 * @testdox The read-only summary uses persisted address values when view filters make billing and shipping appear equal.
	 */
	public function test_displays_persisted_shipping_details_when_view_filters_make_addresses_match(): void {
		$order = $this->create_order_with_shipping_data( false );
		$order->set_shipping_address_1( '742 Evergreen Terrace' );
		$order->save();

		$normalize_address = static function () {
			return 'Normalized address';
		};
		add_filter( 'woocommerce_order_get_billing_address_1', $normalize_address );
		add_filter( 'woocommerce_order_get_shipping_address_1', $normalize_address );

		try {
			$summary = $this->render_shipping_address_summary( $order );
		} finally {
			remove_filter( 'woocommerce_order_get_billing_address_1', $normalize_address );
			remove_filter( 'woocommerce_order_get_shipping_address_1', $normalize_address );
		}

		$this->assertStringContainsString( '742 Evergreen Terrace', $summary );
		$this->assertStringNotContainsString( 'No shipping address set.', $summary );
	}

	/**
	 * @testdox The read-only summary shows a persisted shipping phone that differs from billing.
	 */
	public function test_displays_shipping_details_when_only_phone_differs_from_billing(): void {
		$order = $this->create_order_with_shipping_data( false );
		$order->set_shipping_phone( '555-0199' );
		$order->save();

		$summary = $this->render_shipping_address_summary( $order );

		$this->assertStringContainsString( '500 Billing Avenue', $summary );
		$this->assertStringContainsString( '555-0199', $summary );
		$this->assertStringNotContainsString( 'No shipping address set.', $summary );
	}

	/**
	 * @testdox The read-only summary preserves custom fields when the order does not need shipping.
	 */
	public function test_displays_custom_fields_when_order_does_not_need_shipping(): void {
		$order = $this->create_order_with_shipping_data( false );

		$this->shipping_fields_filter = static function ( array $fields ): array {
			$fields['order_routing_reference'] = array(
				'label' => 'Order routing reference',
				'value' => 'Routing reference 39300',
			);

			return $fields;
		};
		add_filter( 'woocommerce_admin_shipping_fields', $this->shipping_fields_filter );

		$summary = $this->render_shipping_address_summary( $order );

		$this->assertStringContainsString( 'Order routing reference: Routing reference 39300', $summary );
	}

	/**
	 * @testdox The read-only summary preserves a filtered phone field value when the order does not need shipping.
	 */
	public function test_displays_filtered_phone_value_when_order_does_not_need_shipping(): void {
		$order = $this->create_order_with_shipping_data( false );

		$this->shipping_fields_filter = static function ( array $fields ): array {
			$fields['phone'] = array(
				'label' => 'Extension contact reference',
				'value' => 'Extension reference 39300',
			);

			return $fields;
		};
		add_filter( 'woocommerce_admin_shipping_fields', $this->shipping_fields_filter );

		$summary = $this->render_shipping_address_summary( $order );

		$this->assertStringContainsString( 'Extension contact reference: Extension reference 39300', $summary );
	}

	/**
	 * Create an order with billing-derived shipping data.
	 *
	 * @param bool   $add_shipping_method Whether to add a shipping method to the order.
	 * @param string $created_via         Order creation source.
	 * @param string $shipping_method_id  Shipping method ID.
	 * @return WC_Order
	 */
	private function create_order_with_shipping_data( bool $add_shipping_method, string $created_via = 'store-api', string $shipping_method_id = 'flat_rate' ): WC_Order {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_name( 'Order admin display product' );
		$product->set_virtual( ! $add_shipping_method );
		$product->save();

		$order = wc_create_order( array( 'customer_id' => 0 ) );
		$order->set_created_via( $created_via );

		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 1 );
		$order->add_item( $item );

		$order->set_billing_first_name( 'Virtual' );
		$order->set_billing_last_name( 'Customer' );
		$order->set_billing_address_1( '500 Billing Avenue' );
		$order->set_billing_city( 'San Francisco' );
		$order->set_billing_state( 'CA' );
		$order->set_billing_postcode( '94105' );
		$order->set_billing_country( 'US' );
		$order->set_billing_phone( '555-0100' );
		$order->set_shipping_first_name( 'Virtual' );
		$order->set_shipping_last_name( 'Customer' );
		$order->set_shipping_address_1( '500 Billing Avenue' );
		$order->set_shipping_city( 'San Francisco' );
		$order->set_shipping_state( 'CA' );
		$order->set_shipping_postcode( '94105' );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_phone( '555-0100' );

		if ( $add_shipping_method ) {
			$shipping_item = new WC_Order_Item_Shipping();
			$shipping_item->set_method_title( 'Shipping method' );
			$shipping_item->set_method_id( $shipping_method_id );
			$order->add_item( $shipping_item );
		}

		$order->save();

		$this->products[] = $product;
		$this->orders[]   = $order;

		return $order;
	}

	/**
	 * Render the read-only shipping address summary.
	 *
	 * @param WC_Order $order Order to render.
	 * @return string
	 */
	private function render_shipping_address_summary( WC_Order $order ): string {
		$GLOBALS['theorder'] = null;

		ob_start();
		WC_Meta_Box_Order_Data::output( $order );
		$output = (string) ob_get_clean();

		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<!DOCTYPE html><html><body>' . $output . '</body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The order data meta box output should be valid enough for DOM parsing.' );

		$xpath = new DOMXPath( $document );
		$nodes = $xpath->query( "//div[contains(concat(' ', normalize-space(@class), ' '), ' order_data_column_shipping ')]/div[contains(concat(' ', normalize-space(@class), ' '), ' address ')]" );

		$this->assertNotFalse( $nodes, 'The shipping summary XPath query should be valid.' );
		$this->assertSame( 1, $nodes->length, 'The meta box should contain one read-only shipping summary.' );

		return trim( (string) preg_replace( '/\s+/', ' ', $nodes->item( 0 )->textContent ) );
	}
}
