<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\OrderCreate;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the order-create ability.
 */
class OrderCreateTest extends WC_Unit_Test_Case {

	/**
	 * Log in as an administrator, who may create orders.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Feature states.
	 *
	 * @return array<string, array{0: bool}>
	 */
	public function feature_states(): array {
		return array(
			'feature on'  => array( true ),
			'feature off' => array( false ),
		);
	}

	/**
	 * @testdox Should create a processing order with its customer, addresses, note and lines, reduce the stock and fire the paid status hooks.
	 *
	 * @dataProvider feature_states
	 * @param bool $enabled Whether the extensible abilities feature is on.
	 */
	public function test_creates_a_processing_order( bool $enabled ): void {
		$this->set_feature( $enabled );
		$customer  = self::factory()->user->create( array( 'role' => 'customer' ) );
		$simple    = WC_Helper_Product::create_simple_product(
			true,
			array(
				'regular_price'  => '10',
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);
		$variation = wc_get_product( WC_Helper_Product::create_variation_product()->get_children()[0] );
		$paid      = did_action( 'woocommerce_order_status_pending_to_processing' );
		$edits     = did_action( 'woocommerce_order_edit_status' );

		$result = $this->execute(
			array(
				'customer_id'    => $customer,
				'status'         => OrderStatus::PROCESSING,
				'billing'        => array(
					'first_name' => 'Ana',
					'email'      => 'ana@example.com',
					'country'    => 'PT',
					'postcode'   => '1000-001',
				),
				'shipping'       => array(
					'city'    => 'Lisbon',
					'country' => 'PT',
				),
				'customer_note'  => 'Phone order.',
				'line_items'     => array(
					array(
						'product_id' => $simple->get_id(),
						'quantity'   => 3,
					),
					array( 'product_id' => $variation->get_id() ),
				),
				'shipping_lines' => array(
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Flat rate',
						'total'        => '5',
					),
				),
			)
		);

		$order = wc_get_order( $result['order']['id'] );
		$lines = array_values( $order->get_items() );
		$this->assertSame( OrderStatus::PROCESSING, $order->get_status() );
		$this->assertSame( OrderCreate::CREATED_VIA, $order->get_created_via() );
		$this->assertSame( get_woocommerce_currency(), $order->get_currency() );
		$this->assertSame( $customer, $order->get_customer_id() );
		$this->assertSame( 'ana@example.com', $order->get_billing_email() );
		$this->assertSame( 'Lisbon', $order->get_shipping_city() );
		$this->assertSame( 'Phone order.', $order->get_customer_note() );
		$this->assertCount( 2, $lines );
		$this->assertSame( 3, $lines[0]->get_quantity() );
		$this->assertSame( $variation->get_id(), $lines[1]->get_variation_id() );
		$this->assertCount( 1, $order->get_shipping_methods() );
		$this->assertSame( '45.00', $order->get_total() );
		$this->assertEqualsCanonicalizing( array_keys( $order->get_items() ), array_column( $result['order']['line_items'], 'id' ) );
		$this->assertSame( array_keys( $order->get_shipping_methods() ), array_column( $result['order']['shipping_lines'], 'id' ) );
		$this->assertSame( 7, wc_get_product( $simple->get_id() )->get_stock_quantity(), 'A paid status should reduce the stock once.' );
		$this->assertSame( $paid + 1, did_action( 'woocommerce_order_status_pending_to_processing' ), 'The order should move from pending, which sends the order emails.' );
		$this->assertSame( $edits, did_action( 'woocommerce_order_edit_status' ), 'Creating an order is not a manual status change.' );
	}

	/**
	 * @testdox Should create a pending guest order without reducing the stock when no status or customer is sent.
	 *
	 * @dataProvider feature_states
	 * @param bool $enabled Whether the extensible abilities feature is on.
	 */
	public function test_creates_a_pending_guest_order( bool $enabled ): void {
		$this->set_feature( $enabled );
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);

		$result = $this->execute(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);

		$order = wc_get_order( $result['order']['id'] );
		$this->assertSame( OrderStatus::PENDING, $order->get_status() );
		$this->assertSame( 0, $order->get_customer_id() );
		$this->assertSame( '20.00', $order->get_total() );
		$this->assertSame( 10, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should move an order without a status from pending to the default status that an extension sets, and reduce the stock.
	 *
	 * @dataProvider feature_states
	 * @param bool $enabled Whether the extensible abilities feature is on.
	 */
	public function test_uses_the_filtered_default_status( bool $enabled ): void {
		$this->set_feature( $enabled );
		add_filter( 'woocommerce_default_order_status', static fn() => OrderStatus::ON_HOLD );
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);
		$held    = did_action( 'woocommerce_order_status_pending_to_on-hold' );

		$result = $this->execute(
			array(
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);

		$this->assertSame( OrderStatus::ON_HOLD, wc_get_order( $result['order']['id'] )->get_status() );
		$this->assertSame( $held + 1, did_action( 'woocommerce_order_status_pending_to_on-hold' ) );
		$this->assertSame( 8, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should refuse every bad customer, address and line in one error, and create no order.
	 *
	 * @dataProvider feature_states
	 * @param bool $enabled Whether the extensible abilities feature is on.
	 */
	public function test_refuses_bad_input_and_creates_nothing( bool $enabled ): void {
		$this->set_feature( $enabled );
		$variable = WC_Helper_Product::create_variation_product();
		$before   = $this->get_order_ids();

		$error = $this->execute_error(
			array(
				'customer_id'    => PHP_INT_MAX,
				'status'         => OrderStatus::PROCESSING,
				'billing'        => array( 'email' => 'not-an-email' ),
				'line_items'     => array(
					array( 'product_id' => PHP_INT_MAX ),
					array( 'product_id' => $variable->get_id() ),
				),
				'shipping_lines' => array(
					array(
						'method_title' => 'Express',
						'total'        => '-1',
					),
				),
			)
		);

		$this->assertEqualsCanonicalizing(
			array(
				'woocommerce_ability_order_customer_not_found',
				'woocommerce_ability_order_billing_email_invalid',
				'woocommerce_ability_order_product_not_found',
				'woocommerce_ability_order_product_variable',
				'woocommerce_ability_order_shipping_line_total_invalid',
			),
			$error->get_error_codes()
		);
		$this->assertEqualsCanonicalizing( $before, $this->get_order_ids() );
	}

	/**
	 * @testdox Should return the saved order, not changes that a listener made in memory after the only save, with the feature on.
	 */
	public function test_returns_the_saved_order(): void {
		$this->set_feature( true );
		$product = WC_Helper_Product::create_simple_product();
		add_action(
			'woocommerce_after_order_object_save',
			static function ( \WC_Order $order ): void {
				$order->set_billing_email( 'unsaved@example.com' );
			}
		);

		$result = $this->execute(
			array(
				'billing'    => array( 'email' => 'ana@example.com' ),
				'line_items' => array( array( 'product_id' => $product->get_id() ) ),
			)
		);

		$this->assertSame( 'ana@example.com', $result['order']['billing_email'] );
		$this->assertArrayHasKey( 'date_modified', $result['order'] );
		$this->assertCount( 1, $result['order']['line_items'] );
	}

	/**
	 * @testdox Should roll back the saved order when its status cannot be set with the feature off.
	 */
	public function test_rolls_back_the_order_when_the_status_change_fails(): void {
		$this->set_feature( false );
		$before  = $this->get_order_ids();
		$product = WC_Helper_Product::create_simple_product();
		add_action(
			'woocommerce_before_order_object_save',
			static function ( \WC_Order $order ): void {
				if ( $order->get_id() && OrderStatus::PROCESSING === $order->get_status() ) {
					throw new \Exception( 'Forced status save failure' );
				}
			}
		);

		$error = $this->execute_error(
			array(
				'status'     => OrderStatus::PROCESSING,
				'line_items' => array( array( 'product_id' => $product->get_id() ) ),
			)
		);

		$this->assertSame( 'woocommerce_order_status_update_failed', $error->get_error_code() );
		$this->assertEqualsCanonicalizing( $before, $this->get_order_ids() );
	}

	/**
	 * @testdox Should accept a customer from another site of a multisite network only for a user who can manage network users.
	 *
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $network_admin Whether the current user can manage network users.
	 */
	public function test_accepts_a_customer_from_another_site_only_for_a_network_admin( bool $network_admin ): void {
		$customer = self::factory()->user->create( array( 'role' => 'customer' ) );
		$product  = WC_Helper_Product::create_simple_product();
		$this->register_legacy_proxy_function_mocks(
			array(
				'is_multisite'           => static fn() => true,
				'is_user_member_of_blog' => static fn() => false,
				'user_can'               => static fn() => $network_admin,
			)
		);

		$result = wp_get_ability( OrderCreate::ABILITY_NAME )->execute(
			array(
				'customer_id' => $customer,
				'line_items'  => array( array( 'product_id' => $product->get_id() ) ),
			)
		);

		if ( $network_admin ) {
			$this->assertIsArray( $result );
			$this->assertSame( $customer, wc_get_order( $result['order']['id'] )->get_customer_id() );
		} else {
			$this->assertWPError( $result );
			$this->assertSame( array( 'woocommerce_ability_order_customer_not_found' ), $result->get_error_codes() );
		}
	}

	/**
	 * @testdox Should refuse a shipping total in exponent notation, which formatting would save as another amount.
	 */
	public function test_refuses_a_shipping_total_with_an_exponent(): void {
		$product = WC_Helper_Product::create_simple_product();

		$error = $this->execute_error(
			array(
				'line_items'     => array( array( 'product_id' => $product->get_id() ) ),
				'shipping_lines' => array(
					array(
						'method_title' => 'Express',
						'total'        => '1e3',
					),
				),
			)
		);

		$this->assertSame( array( 'woocommerce_ability_order_shipping_line_total_invalid' ), $error->get_error_codes() );
	}

	/**
	 * @testdox Should refuse and roll back a pending order whose recalculated totals cannot be saved, with the feature off.
	 */
	public function test_refuses_the_order_when_the_recalculation_does_not_save(): void {
		$this->set_feature( false );
		$before  = $this->get_order_ids();
		$product = WC_Helper_Product::create_simple_product();
		$saves   = 0;
		add_action(
			'woocommerce_before_order_object_save',
			static function () use ( &$saves ): void {
				if ( ++$saves > 1 ) {
					throw new \Exception( 'Forced recalculation save failure' );
				}
			}
		);

		$error = $this->execute_error(
			array( 'line_items' => array( array( 'product_id' => $product->get_id() ) ) )
		);

		$this->assertSame( 'woocommerce_ability_save_failed', $error->get_error_code() );
		$this->assertEqualsCanonicalizing( $before, $this->get_order_ids() );
	}

	/**
	 * @testdox Should refuse an input without a line item, a shipping line without a method title, and a line item without a product.
	 *
	 * @testWith [{}]
	 *           [{"customer_note": "Call back."}]
	 *           [{"line_items": [{"product_id": 1}], "shipping_lines": [{"total": "3"}]}]
	 *           [{"line_items": [{"quantity": 2}]}]
	 *
	 * @param array $input Ability input.
	 */
	public function test_schema_refuses_missing_lines( array $input ): void {
		$schema = wp_get_ability( OrderCreate::ABILITY_NAME )->get_input_schema();

		$this->assertWPError( rest_validate_value_from_schema( $input, $schema, 'input' ) );
	}

	/**
	 * @testdox Should refuse a user who cannot create orders.
	 */
	public function test_refuses_user_without_create_capability(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->assertFalse( OrderCreate::can_create_order() );
	}

	/**
	 * Run order-create and check it succeeded.
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	private function execute( array $input ): array {
		$result = wp_get_ability( OrderCreate::ABILITY_NAME )->execute( $input );

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );

		return $result;
	}

	/**
	 * Run order-create and check it was refused.
	 *
	 * @param array $input Ability input.
	 * @return \WP_Error
	 */
	private function execute_error( array $input ): \WP_Error {
		$result = wp_get_ability( OrderCreate::ABILITY_NAME )->execute( $input );

		$this->assertWPError( $result );

		return $result;
	}

	/**
	 * IDs of every order, of any status.
	 *
	 * @return int[]
	 */
	private function get_order_ids(): array {
		return wc_get_orders(
			array(
				'limit'  => -1,
				'return' => 'ids',
				'status' => array_keys( wc_get_order_statuses() ),
			)
		);
	}

	/**
	 * Turn the extensible abilities feature on or off. The test transaction rolls the option back.
	 *
	 * @param bool $enabled Whether the feature is on.
	 */
	private function set_feature( bool $enabled ): void {
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );
	}
}
