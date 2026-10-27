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
	 * @testdox Quantity and amount previews preserve a fractional remainder and its cap.
	 * @param array $line Requested quantity or amount.
	 * @param bool  $valid Whether the request fits the remainder.
	 * @testWith [{"quantity": 1}, true]
	 *           [{"refund_total": 0.25}, true]
	 *           [{"refund_total": 0.26}, false]
	 */
	public function test_preview_preserves_fractional_remainder( array $line, bool $valid ): void {
		list( $order, $item ) = $this->create_order( 10.25 );
		$this->add_refund( $order, $item, 10.0, 0 );
		$order                = new WC_Order( $order->get_id() );
		$line['line_item_id'] = $item->get_id();
		$result               = $this->sut->compute_refund_preview_or_error( $order, array( $line ), 'currency-test' );

		if ( $valid ) {
			$this->assertIsArray( $result );
			$this->assertSame( '0.25', $result['total'] );
			$this->assertSame( '0.25', $result['max_refundable'] );
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
	 *           [10.25, null, null]
	 *           [20.25, null, null]
	 *           [20.26, "refund_exceeds_remaining", "Refund amount (20.26) exceeds the remaining refundable amount (20.25)."]
	 *
	 * @param float       $amount Requested override.
	 * @param string|null $code Expected error code, or null on success.
	 * @param string|null $message Expected error message.
	 */
	public function test_amount_override_guards_use_order_precision( float $amount, ?string $code, ?string $message ): void {
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

		if ( null === $code ) {
			$this->assertIsArray( $result );
			$this->assertSame( $amount, $result['amount'] );
		} else {
			$this->assertWPError( $result );
			$this->assertSame( $code, $result->get_error_code() );
			$this->assertSame( $message, $result->get_error_message() );
		}
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
	 * @testdox Public overrides affect every workflow while standalone calls retain store precision.
	 * @param string $store Store currency.
	 * @param int    $decimals Store decimals.
	 * @param float  $normalized Standalone normalized total.
	 * @param float  $calculated Standalone calculated amount.
	 * @testWith ["JPY", 0, 11.0, 12.0]
	 *           ["USD", 2, 11.25, 12.25]
	 */
	public function test_public_helper_overrides( string $store, int $decimals, float $normalized, float $calculated ): void {
		update_option( 'woocommerce_currency', $store );
		list( $order, $item ) = $this->create_order( 20.25 );
		update_option( 'woocommerce_price_num_decimals', $decimals );
		$sut   = new class() extends DataUtils {
			/**
			 * Add a line adjustment after normalization.
			 * @param array $line_items Lines.
			 * @return array
			 */
			public function normalize_refund_totals( array $line_items ): array {
				$line_items                     = parent::normalize_refund_totals( $line_items );
				$line_items[0]['refund_total'] += 1;
				return $line_items;
			}

			/**
			 * Add goodwill to the calculated amount.
			 * @param array $line_items Lines.
			 * @return float|null
			 */
			public function calculate_refund_amount( array $line_items ): ?float {
				return parent::calculate_refund_amount( $line_items ) + 2;
			}
		};
		$lines = array(
			array(
				'line_item_id' => $item->get_id(),
				'refund_total' => 10.25,
			),
		);

		$this->assertSame( 11.25, $sut->fill_missing_refund_totals( $lines, $order )[0]['refund_total'] );
		$preview = $sut->compute_refund_preview_or_error( $order, $lines, 'currency-test' );
		$this->assertIsArray( $preview );
		$this->assertSame( '11.25', $preview['total'] );
		$prepared = $sut->prepare_refund_creation_or_error( $order, $lines, false, null, 'currency-test' );
		$this->assertIsArray( $prepared );
		$this->assertSame( 11.25, $prepared['schema_line_items'][0]['refund_total'] );
		$this->assertSame( 13.25, $prepared['amount'] );
		$this->assertSame( $normalized, $sut->normalize_refund_totals( $lines )[0]['refund_total'] );
		$this->assertSame( $calculated, $sut->calculate_refund_amount( $lines ) );
	}

	/**
	 * @testdox Nested helper calls and exceptions restore the previous precision on a reused instance.
	 * @param string $helper Override triggering the nested call or exception.
	 * @param bool   $throws Whether the override throws.
	 * @testWith ["normalize", false]
	 *           ["calculate", false]
	 *           ["normalize", true]
	 *           ["calculate", true]
	 */
	public function test_helper_precision_scope( string $helper, bool $throws ): void {
		list( $order, $item )               = $this->create_order( 20 );
		list( $nested_order, $nested_item ) = $this->create_order( 20 );
		$nested_order->set_currency( 'KWD' );
		$nested_order->save();
		$lines         = array(
			array(
				'line_item_id' => $item->get_id(),
				'refund_total' => 10.251,
			),
		);
		$sut           = new class() extends DataUtils {
			/** @var callable|null Operation performed by an override. */
			public $on_call;

			/**
			 * Run an extension operation before delegating to core.
			 * @param array $line_items Lines.
			 * @return array
			 */
			public function normalize_refund_totals( array $line_items ): array {
				if ( $this->on_call ) {
					( $this->on_call )( 'normalize' );
				}
				return parent::normalize_refund_totals( $line_items );
			}

			/**
			 * Run an extension operation before delegating to core.
			 * @param array $line_items Lines.
			 * @return float|null
			 */
			public function calculate_refund_amount( array $line_items ): ?float {
				if ( $this->on_call ) {
					( $this->on_call )( 'calculate' );
				}
				return parent::calculate_refund_amount( $line_items );
			}
		};
		$nested_amount = null;
		$sut->on_call  = function ( $called ) use ( $sut, $helper, $throws, $nested_order, $nested_item, &$nested_amount ) {
			if ( $called !== $helper ) {
				return;
			}
			$sut->on_call = null;
			if ( $throws ) {
				throw new \RuntimeException( 'Extension failure' );
			}
			$nested_lines = array(
				array(
					'line_item_id' => $nested_item->get_id(),
					'refund_total' => 10.251,
				),
			);
			$result       = $sut->prepare_refund_creation_or_error( $nested_order, $nested_lines, false, null, 'nested-currency-test' );
			$this->assertIsArray( $result );
			$nested_amount = $result['amount'];
		};

		$error_message = null;
		try {
			$prepared = $sut->prepare_refund_creation_or_error( $order, $lines, false, null, 'currency-test' );
		} catch ( \RuntimeException $error ) {
			$error_message = $error->getMessage();
		}
		$this->assertSame( $throws ? 'Extension failure' : null, $error_message );
		if ( ! $throws ) {
			$this->assertIsArray( $prepared );
			$this->assertSame( 10.25, $prepared['amount'] );
		}
		$this->assertSame( $throws ? null : 10.251, $nested_amount );
		$this->assertSame( 10.0, $sut->normalize_refund_totals( $lines )[0]['refund_total'] );
		$this->assertSame( 10.0, $sut->calculate_refund_amount( $lines ) );
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

	/**
	 * @testdox The preview explains a fractional order-level cap after an amount-only refund.
	 */
	public function test_preview_order_cap_message_preserves_cents(): void {
		list( $order, $item ) = $this->create_order( 20.25 );
		$refund               = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 5,
			)
		);
		$this->assertInstanceOf( WC_Order_Refund::class, $refund );
		$order  = new WC_Order( $order->get_id() );
		$result = $this->sut->compute_refund_preview_or_error(
			$order,
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => 16.25,
				),
			),
			'currency-test'
		);
		$this->assertWPError( $result );
		$this->assertSame( 'preview_exceeds_max_refundable', $result->get_error_code() );
		$this->assertSame( 'Requested refund preview (16.25) exceeds the remaining refundable amount (15.25).', $result->get_error_message() );
	}

	/**
	 * @testdox Foreign-currency tax history keeps the configured per-rate rounding policy.
	 * @param string $store Store currency.
	 * @param int    $decimals Store decimals.
	 * @param string $at_subtotal Whether to round at subtotal.
	 * @param float  $expected Expected gross amount.
	 * @testWith ["JPY", 0, "no", 20.99]
	 *           ["USD", 2, "no", 20.99]
	 *           ["JPY", 0, "yes", 21.0]
	 *           ["USD", 2, "yes", 21.0]
	 */
	public function test_subcent_tax_buckets_keep_rounding_policy( string $store, int $decimals, string $at_subtotal, float $expected ): void {
		update_option( 'woocommerce_currency', $store );
		update_option( 'woocommerce_tax_round_at_subtotal', $at_subtotal );
		list( $order, $item ) = $this->create_order( 20 );
		update_option( 'woocommerce_price_num_decimals', $decimals );
		$taxes = array(
			123 => 0.333,
			124 => 0.333,
			125 => 0.333,
		);
		$item->set_taxes(
			array(
				'total'    => $taxes,
				'subtotal' => $taxes,
			)
		);
		$item->save();
		$order->set_total( $expected );
		$order->save();
		$order = new WC_Order( $order->get_id() );

		$result = $this->sut->prepare_refund_creation_or_error(
			$order,
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			),
			false,
			null,
			'currency-test'
		);

		$this->assertIsArray( $result );
		$this->assertSame( $expected, $result['amount'] );
	}
}
