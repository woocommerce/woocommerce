<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Unit_Test_Case;

/**
 * Tests for the address, shipping line and product line rules of the order-update ability.
 */
class OrderUpdateTest extends WC_Unit_Test_Case {

	/**
	 * Log in as an administrator, who may edit orders.
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
	 * @testdox Should set only the sent address fields and format the postcode for its country.
	 */
	public function test_sets_only_sent_address_fields(): void {
		$order = WC_Helper_Order::create_order();

		$this->execute(
			array(
				'id'       => $order->get_id(),
				'billing'  => array(
					'first_name' => 'Ana',
					'email'      => 'ana@example.com',
				),
				'shipping' => array(
					'city'     => 'London',
					'country'  => 'GB',
					'postcode' => 'sw1a1aa',
				),
			)
		);

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'Ana', $order->get_billing_first_name() );
		$this->assertSame( 'Sormani', $order->get_billing_last_name(), 'A field that is not sent should keep its value.' );
		$this->assertSame( 'ana@example.com', $order->get_billing_email() );
		$this->assertSame( 'London', $order->get_shipping_city() );
		$this->assertSame( 'GB', $order->get_shipping_country() );
		$this->assertSame( 'SW1A 1AA', $order->get_shipping_postcode() );
	}

	/**
	 * @testdox Should refuse every bad address field in one error, check the state against the saved country, and save nothing.
	 */
	public function test_refuses_every_bad_address_field_and_saves_nothing(): void {
		$order = WC_Helper_Order::create_order();

		$error = $this->execute_error(
			array(
				'id'            => $order->get_id(),
				'billing'       => array(
					'email'    => 'not-an-email',
					'state'    => 'ZZ',
					'postcode' => 'ABCDE',
				),
				'shipping'      => array(
					'country'  => 'XX',
					'postcode' => 'not checked',
				),
				'customer_note' => 'Leave at the door.',
			)
		);

		$this->assertEqualsCanonicalizing(
			array(
				'woocommerce_ability_order_billing_email_invalid',
				'woocommerce_ability_order_billing_state_invalid',
				'woocommerce_ability_order_billing_postcode_invalid',
				'woocommerce_ability_order_shipping_country_invalid',
			),
			$error->get_error_codes(),
			'A bad country should skip the postcode check, which depends on the country.'
		);

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'admin@example.org', $order->get_billing_email() );
		$this->assertSame( 'NY', $order->get_billing_state() );
		$this->assertSame( '', $order->get_customer_note(), 'A refused address should stop the other fields from saving.' );
	}

	/**
	 * @testdox Should strip tags from the customer note.
	 */
	public function test_sanitizes_customer_note(): void {
		$order = WC_Helper_Order::create_order();

		$this->execute(
			array(
				'id'            => $order->get_id(),
				'customer_note' => '<b>Leave</b> at the door.',
			)
		);

		$this->assertSame( 'Leave at the door.', wc_get_order( $order->get_id() )->get_customer_note() );
	}

	/**
	 * @testdox Should change a shipping line, add one, and recalculate the totals.
	 *
	 * @dataProvider feature_states
	 * @param bool $enabled Whether the extensible abilities feature is on.
	 */
	public function test_changes_and_adds_shipping_lines( bool $enabled ): void {
		$this->set_feature( $enabled );
		$order       = WC_Helper_Order::create_order();
		$shipping_id = array_key_first( $order->get_shipping_methods() );

		$this->execute(
			array(
				'id'             => $order->get_id(),
				'shipping_lines' => array(
					array(
						'id'    => $shipping_id,
						'total' => '5',
					),
					array(
						'method_id'    => 'local_pickup',
						'method_title' => 'Pickup',
						'total'        => '0',
					),
				),
			)
		);

		$order = wc_get_order( $order->get_id() );
		$lines = array_values( $order->get_shipping_methods() );
		$this->assertCount( 2, $lines );
		$this->assertSame( '5', $lines[0]->get_total() );
		$this->assertSame( 'Flat rate shipping', $lines[0]->get_method_title(), 'A field that is not sent should keep its value.' );
		$this->assertSame( 'local_pickup', $lines[1]->get_method_id() );
		$this->assertSame( 'Pickup', $lines[1]->get_method_title() );
		$this->assertSame( '45.00', $order->get_total(), 'Four products at 10 plus 5 shipping.' );
	}

	/**
	 * @testdox Should refuse every bad shipping line in one error and save nothing.
	 */
	public function test_refuses_bad_shipping_lines(): void {
		$order = WC_Helper_Order::create_order();

		$error = $this->execute_error(
			array(
				'id'             => $order->get_id(),
				'shipping_lines' => array(
					array( 'total' => '3' ),
					array(
						'method_title' => 'Express',
						'total'        => '-1',
					),
					array(
						'id'    => PHP_INT_MAX,
						'total' => '3',
					),
				),
			)
		);

		$this->assertEqualsCanonicalizing(
			array(
				'woocommerce_ability_order_shipping_line_method_title_required',
				'woocommerce_ability_order_shipping_line_total_invalid',
				'woocommerce_ability_order_line_not_found',
			),
			$error->get_error_codes()
		);
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_shipping_methods() );
	}

	/**
	 * @testdox Should accept a shipping total that has no digit before or after the decimal point.
	 *
	 * @testWith [".5", "0.50"]
	 *           ["5.", "5.00"]
	 *
	 * @param string $total    Shipping total sent.
	 * @param string $expected Shipping total saved.
	 */
	public function test_accepts_a_shipping_total_without_leading_or_trailing_digits( string $total, string $expected ): void {
		$order = WC_Helper_Order::create_order();
		$line  = current( $order->get_shipping_methods() );

		$this->execute(
			array(
				'id'             => $order->get_id(),
				'shipping_lines' => array(
					array(
						'id'    => $line->get_id(),
						'total' => $total,
					),
				),
			)
		);

		$this->assertSame( $expected, wc_format_decimal( current( wc_get_order( $order->get_id() )->get_shipping_methods() )->get_total(), 2 ) );
	}

	/**
	 * @testdox Should add a simple product and a variation, with one as the default quantity, and recalculate the totals.
	 *
	 * @dataProvider feature_states
	 * @param bool $enabled Whether the extensible abilities feature is on.
	 */
	public function test_adds_product_lines( bool $enabled ): void {
		$this->set_feature( $enabled );
		$order     = WC_Helper_Order::create_order();
		$simple    = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '7' ) );
		$variation = wc_get_product( WC_Helper_Product::create_variation_product()->get_children()[0] );

		$result = $this->execute(
			array(
				'id'         => $order->get_id(),
				'line_items' => array(
					array(
						'product_id' => $simple->get_id(),
						'quantity'   => 2,
					),
					array( 'product_id' => $variation->get_id() ),
				),
			)
		);

		$order = wc_get_order( $order->get_id() );
		$added = array_values( array_slice( $order->get_items(), 1 ) );
		$this->assertCount( 2, $added );
		$this->assertSame( $simple->get_id(), $added[0]->get_product_id() );
		$this->assertSame( 2, $added[0]->get_quantity() );
		$this->assertSame( $variation->get_id(), $added[1]->get_variation_id() );
		$this->assertSame( 1, $added[1]->get_quantity() );
		$this->assertEqualsCanonicalizing( array_keys( $order->get_items() ), array_column( $result['order']['line_items'], 'id' ), 'The output should carry the IDs of the added lines.' );
		$this->assertSame( '74.00', $order->get_total() );
	}

	/**
	 * @testdox Should refuse an added line whose recalculated totals cannot be saved, with the feature off.
	 */
	public function test_refuses_a_line_when_the_recalculation_does_not_save(): void {
		$this->set_feature( false );
		$order   = WC_Helper_Order::create_order();
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
			array(
				'id'         => $order->get_id(),
				'line_items' => array( array( 'product_id' => $product->get_id() ) ),
			)
		);

		$this->assertSame( 'woocommerce_ability_save_failed', $error->get_error_code() );
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_items() );
	}

	/**
	 * @testdox Should refuse every bad product line in one error and save nothing.
	 */
	public function test_refuses_bad_product_lines(): void {
		$order    = WC_Helper_Order::create_order();
		$variable = WC_Helper_Product::create_variation_product();
		$simple   = WC_Helper_Product::create_simple_product();
		add_filter( 'woocommerce_quantity_input_min_admin', static fn() => 5 );

		$error = $this->execute_error(
			array(
				'id'         => $order->get_id(),
				'line_items' => array(
					array( 'product_id' => PHP_INT_MAX ),
					array( 'product_id' => $variable->get_id() ),
					array(
						'product_id' => $simple->get_id(),
						'quantity'   => 2,
					),
					array(
						'id'         => array_key_first( $order->get_items() ),
						'product_id' => $simple->get_id(),
					),
				),
			)
		);

		$this->assertEqualsCanonicalizing(
			array(
				'woocommerce_ability_order_product_not_found',
				'woocommerce_ability_order_product_variable',
				'woocommerce_ability_order_line_item_quantity_invalid',
				'woocommerce_ability_order_line_item_target_invalid',
			),
			$error->get_error_codes()
		);
		$this->assertCount( 1, wc_get_order( $order->get_id() )->get_items() );
	}

	/**
	 * @testdox Should reduce the stock of an added product on an on-hold order and note the change.
	 *
	 * @dataProvider feature_states
	 * @param bool $enabled Whether the extensible abilities feature is on.
	 */
	public function test_added_line_reduces_stock_on_on_hold_order( bool $enabled ): void {
		$this->set_feature( $enabled );
		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::ON_HOLD );
		$order->save();
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);

		$this->execute(
			array(
				'id'         => $order->get_id(),
				'line_items' => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 3,
					),
				),
			)
		);

		$this->assertSame( 7, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$notes = wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' );
		$this->assertContains( 'Adjusted stock: ' . $product->get_name() . ' (10&rarr;7)', $notes );
	}

	/**
	 * Run order-update and check it succeeded.
	 *
	 * @param array $input Ability input.
	 * @return array
	 */
	private function execute( array $input ): array {
		$result = wp_get_ability( 'woocommerce/order-update' )->execute( $input );

		$this->assertIsArray( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );

		return $result;
	}

	/**
	 * Run order-update and check it was refused.
	 *
	 * @param array $input Ability input.
	 * @return \WP_Error
	 */
	private function execute_error( array $input ): \WP_Error {
		$result = wp_get_ability( 'woocommerce/order-update' )->execute( $input );

		$this->assertWPError( $result );

		return $result;
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
