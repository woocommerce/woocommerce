<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Refunds;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\RestApi\Refunds\DataUtils;
use WC_Order;
use WC_Order_Item_Product;
use WC_Order_Refund;
use WC_Unit_Test_Case;

/**
 * Refund calculations for orders whose currency needs more decimals than the store.
 */
class DataUtilsCurrencyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var DataUtils
	 */
	private $sut;

	/**
	 * Set up a zero-decimal JPY store.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_currency', 'JPY' );
		update_option( 'woocommerce_price_num_decimals', 0 );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
		$this->sut = new DataUtils();
	}

	/**
	 * @testdox A quantity preview accepts the fractional amount remaining on a USD line.
	 */
	public function test_quantity_preview_accepts_fractional_remaining_amount(): void {
		list( $order, $item ) = $this->create_order( 10.25 );
		$this->add_refund( $order, $item, 10.0, 0 );
		$order = new WC_Order( $order->get_id() );
		$lines = array(
			array(
				'line_item_id' => $item->get_id(),
				'quantity'     => 1,
			),
		);

		$this->assertTrue( $this->sut->validate_preview_line_items( $lines, $order ) );
		$preview = $this->sut->compute_refund_preview_or_error( $order, $lines, 'currency-test' );
		$this->assertIsArray( $preview );
		$this->assertSame( '0.25', $preview['total'] );
		$this->assertSame( '0.25', $preview['max_refundable'] );
	}

	/**
	 * @testdox Explicit USD line refunds are capped at the remaining cents.
	 * @testWith [0.25, true]
	 *           [0.26, false]
	 *
	 * @param float $amount Requested amount.
	 * @param bool  $valid Whether the amount fits the remainder.
	 */
	public function test_explicit_preview_preserves_fractional_remaining_cap( float $amount, bool $valid ): void {
		list( $order, $item ) = $this->create_order( 10.25 );
		$this->add_refund( $order, $item, 10.0, 0 );
		$order  = new WC_Order( $order->get_id() );
		$result = $this->sut->validate_preview_line_items(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => $amount,
				),
			),
			$order
		);

		if ( $valid ) {
			$this->assertTrue( $result );
		} else {
			$this->assertWPError( $result );
			$this->assertSame( 'refund_total_exceeds_remaining', $result->get_error_code() );
			$this->assertStringContainsString( '(0.25)', $result->get_error_message() );
		}
	}

	/**
	 * @testdox Explicit tax refunds are capped at the remaining cents for their rate.
	 * @testWith [0.25, true]
	 *           [0.26, false]
	 *
	 * @param float $tax Requested tax.
	 * @param bool  $valid Whether the tax fits the remainder.
	 */
	public function test_explicit_tax_cap_uses_order_precision( float $tax, bool $valid ): void {
		list( $order, $item ) = $this->create_order( 100.0, 1, 0.75 );
		$this->add_refund( $order, $item, 0.0, 0, 0.50 );
		$order  = new WC_Order( $order->get_id() );
		$result = $this->sut->validate_line_items(
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => 1.0,
					'refund_tax'   => array(
						array(
							'id'           => 123,
							'refund_total' => $tax,
						),
					),
				),
			),
			$order
		);

		if ( $valid ) {
			$this->assertTrue( $result );
		} else {
			$this->assertWPError( $result );
			$this->assertSame( 'invalid_refund_amount', $result->get_error_code() );
			$this->assertSame( 'Refund tax total cannot be greater than the remaining refundable tax for this line item (0.25).', $result->get_error_message() );
		}
	}

	/**
	 * @testdox Amount override guards compare and describe USD cents.
	 * @testWith [10.24, "invalid_refund_amount", "Refund amount (10.24) cannot be less than the total of line items (10.25)."]
	 *           [20.26, "refund_exceeds_remaining", "Refund amount (20.26) exceeds the remaining refundable amount (20.25)."]
	 *
	 * @param float  $amount Requested override.
	 * @param string $code Expected error code.
	 * @param string $message Expected error message.
	 */
	public function test_amount_override_guards_use_order_precision( float $amount, string $code, string $message ): void {
		list( $order, $item ) = $this->create_order( 20.25 );
		$result               = $this->sut->prepare_refund_creation_or_error(
			$order,
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => 10.25,
				),
			),
			true,
			$amount,
			'currency-test'
		);

		$this->assertWPError( $result );
		$this->assertSame( $code, $result->get_error_code() );
		$this->assertSame( $message, $result->get_error_message() );
	}

	/**
	 * @testdox Quantity refunds preserve USD precision and reconcile only genuine rounding drift.
	 * @testWith [10.0, 3, [], 2, 6.67]
	 *           [10.0, 3, [3.33, 3.33], 1, 3.34]
	 *           [10.0, 3, [3.30, 3.33], 1, 3.33]
	 *           [10.25, 2, [5.20], 1, 5.05]
	 *
	 * @param float $total Original line total.
	 * @param int   $quantity Original line quantity.
	 * @param array $history Prior one-unit refund amounts.
	 * @param int   $requested Quantity to refund.
	 * @param float $expected Expected refund amount.
	 */
	public function test_quantity_refunds_reconcile_at_order_precision( float $total, int $quantity, array $history, int $requested, float $expected ): void {
		list( $order, $item ) = $this->create_order( $total, $quantity );
		foreach ( $history as $amount ) {
			$this->add_refund( $order, $item, $amount, 1 );
		}
		$order = new WC_Order( $order->get_id() );
		$lines = array(
			array(
				'line_item_id' => $item->get_id(),
				'quantity'     => $requested,
			),
		);

		$prepared = $this->sut->prepare_refund_creation_or_error( $order, $lines, false, null, 'currency-test' );

		$this->assertIsArray( $prepared );
		$this->assertSame( $expected, $prepared['amount'] );
		$this->assertSame( $expected, $prepared['line_items'][ $item->get_id() ]['refund_total'] );
	}

	/**
	 * @testdox Inclusive USD amounts split into cents consistently for preview and creation.
	 */
	public function test_inclusive_tax_split_preserves_order_precision(): void {
		list( $order, $item ) = $this->create_order( 100.0, 1, 10.0 );
		$lines                = array(
			array(
				'line_item_id' => $item->get_id(),
				'refund_total' => 55.25,
			),
		);

		$preview  = $this->sut->compute_refund_preview_or_error( $order, $lines, 'currency-test' );
		$prepared = $this->sut->prepare_refund_creation_or_error( $order, $lines, false, null, 'currency-test' );

		$this->assertIsArray( $preview );
		$this->assertSame( '55.25', $preview['total'] );
		$this->assertSame( '50.23', $preview['subtotal'] );
		$this->assertSame( '5.02', $preview['tax'] );
		$this->assertIsArray( $prepared );
		$this->assertSame( 55.25, $prepared['amount'] );
		$this->assertSame( 50.23, $prepared['line_items'][ $item->get_id() ]['refund_total'] );
		$this->assertSame( 5.02, $prepared['line_items'][ $item->get_id() ]['refund_tax'][123] );
	}

	/**
	 * @testdox Fee and shipping refund histories retain fractional taxes despite rounded tax aggregates.
	 * @testWith ["WC_Order_Item_Fee"]
	 *           ["WC_Order_Item_Shipping"]
	 *
	 * @param string $item_class Order item class.
	 */
	public function test_non_product_gross_and_refund_history_preserve_tax_cents( string $item_class ): void {
		list( $order, $item ) = $this->create_order( 10.0, 1, 0.75, $item_class );
		$this->add_refund( $order, $item, 10.0, 1, 0.50 );
		$order = new WC_Order( $order->get_id() );
		$item  = $order->get_item( $item->get_id() );

		$this->assertSame( 10.75, $this->sut->compute_line_item_refund_total( $item, 1 ) );
		$history = $this->sut->compute_refunded_quantities_and_totals( $order );
		$this->assertSame( 10.50, $history['totals'][ $item->get_id() ] );
		$this->assertSame( 0.50, $history['tax_totals'][ $item->get_id() ][123] );
		$preview = $this->sut->compute_refund_preview_or_error(
			$order,
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			),
			'currency-test'
		);
		$this->assertIsArray( $preview );
		$this->assertSame( '0.25', $preview['total'] );
	}

	/**
	 * @testdox Array-only helpers keep store precision and remain overridable with their original signatures.
	 */
	public function test_array_only_helpers_keep_store_precision_and_signatures(): void {
		$subclass = new class() extends DataUtils {
			/**
			 * Calls received by the public overrides.
			 *
			 * @var array<string, int>
			 */
			public $calls = array(
				'normalize' => 0,
				'calculate' => 0,
			);

			/**
			 * Normalize through the original array-only signature.
			 *
			 * @param array $line_items Refund lines.
			 * @return array
			 */
			public function normalize_refund_totals( array $line_items ): array {
				++$this->calls['normalize'];
				return parent::normalize_refund_totals( $line_items );
			}

			/**
			 * Sum through the original array-only signature.
			 *
			 * @param array $line_items Refund lines.
			 * @return float|null
			 */
			public function calculate_refund_amount( array $line_items ): ?float {
				++$this->calls['calculate'];
				return parent::calculate_refund_amount( $line_items );
			}
		};
		$lines    = array( array( 'refund_total' => 10.25 ) );

		$this->assertSame( 10.0, $subclass->normalize_refund_totals( $lines )[0]['refund_total'] );
		$this->assertSame( 10.0, $subclass->calculate_refund_amount( $lines ) );
		$this->assertSame( 1, $subclass->calls['normalize'] );
		$this->assertSame( 1, $subclass->calls['calculate'] );
	}

	/**
	 * Create a USD order with one stored line, preserving its original total.
	 *
	 * @param float  $net Net line amount.
	 * @param int    $quantity Line quantity.
	 * @param float  $tax Stored tax amount.
	 * @param string $item_class Order item class.
	 * @return array
	 */
	private function create_order( float $net, int $quantity = 1, float $tax = 0.0, string $item_class = WC_Order_Item_Product::class ): array {
		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_status( OrderStatus::PROCESSING );
		$item = new $item_class();
		$item->set_name( 'Currency precision fixture' );
		$item->set_total( $net );
		if ( $item instanceof WC_Order_Item_Product ) {
			$item->set_quantity( $quantity );
			$item->set_subtotal( $net );
		}
		$item->set_taxes(
			array(
				'total'    => array( 123 => $tax ),
				'subtotal' => array( 123 => $tax ),
			)
		);
		$order->add_item( $item );
		// Seed the historical total independently of the current store's precision.
		update_option( 'woocommerce_price_num_decimals', 2 );
		$order->set_total( $net + $tax );
		$order->save();
		update_option( 'woocommerce_price_num_decimals', 0 );

		return array( $order, $item );
	}

	/**
	 * Store refund history without invoking the refund creation pipeline under test elsewhere.
	 *
	 * @param WC_Order       $order Original order.
	 * @param \WC_Order_Item $item Original item.
	 * @param float          $net Refunded net amount.
	 * @param int            $quantity Refunded quantity.
	 * @param float          $tax Refunded tax amount.
	 */
	private function add_refund( WC_Order $order, $item, float $net, int $quantity, float $tax = 0.0 ): void {
		$item_class    = get_class( $item );
		$refunded_item = new $item_class();
		$refunded_item->set_total( -$net );
		if ( $refunded_item instanceof WC_Order_Item_Product ) {
			$refunded_item->set_quantity( -$quantity );
			$refunded_item->set_subtotal( -$net );
		}
		$refunded_item->set_taxes(
			array(
				'total'    => array( 123 => -$tax ),
				'subtotal' => array( 123 => -$tax ),
			)
		);
		$refunded_item->add_meta_data( '_refunded_item_id', $item->get_id() );
		$refund = new WC_Order_Refund();
		$refund->set_parent_id( $order->get_id() );
		$refund->set_currency( 'USD' );
		$refund->set_amount( $net + $tax );
		$refund->set_total( -( $net + $tax ) );
		$refund->add_item( $refunded_item );
		$refund->save();
	}
}
