<?php
/**
 * RefundCreator class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CostOfGoodsSoldController;

defined( 'ABSPATH' ) || exit;

/**
 * Creates one refund of an order in the steps of wc_create_refund(), so the caller can check the refund before it
 * is saved: stage() changes the refund in memory, before_save() fires the hook that precedes the save, and finish()
 * does what follows it. It mirrors wc_create_refund(), so a change to one belongs in the other.
 *
 * @since 11.4.0
 */
class RefundCreator {

	/**
	 * Order to refund.
	 *
	 * @var \WC_Order
	 */
	private \WC_Order $order;

	/**
	 * Arguments in the format of wc_create_refund(): amount, reason, line_items, refund_payment, restock_items.
	 *
	 * @var array
	 */
	private array $args;

	/**
	 * What the order could still refund when stage() ran, before this refund.
	 *
	 * @var float
	 */
	private float $remaining_refund_amount = 0.0;

	/**
	 * What the order could still refund in item quantities when stage() ran.
	 *
	 * @var int
	 */
	private int $remaining_refund_items = 0;

	/**
	 * Quantity that the refund lines refund.
	 *
	 * @var int|float
	 */
	private $refund_item_count = 0;

	/**
	 * Order and product IDs of the refunded product lines, to revoke their downloads.
	 *
	 * @var array<int, array{order_id: int, product_id: int}>
	 */
	private array $refunded_order_and_products = array();

	/**
	 * Constructor.
	 *
	 * @param \WC_Order $order Order to refund.
	 * @param array     $args  Arguments in the format of wc_create_refund(). The order_id argument is ignored.
	 *
	 * @since 11.4.0
	 */
	public function __construct( \WC_Order $order, array $args ) {
		$this->order = $order;
		$this->args  = wp_parse_args(
			$args,
			array(
				'amount'         => 0,
				'reason'         => null,
				'line_items'     => array(),
				'refund_payment' => false,
				'restock_items'  => false,
			)
		);
	}

	/**
	 * Save the refund, then fire woocommerce_create_refund. wc_create_refund() saves the refund before the hook too,
	 * and callbacks use its ID, so the caller must run this inside a transaction it rolls back when this fails.
	 * Callbacks can move money or take locks there, so a dry run or a refused refund must not reach it.
	 *
	 * @param \WC_Order_Refund $refund Staged refund, not saved.
	 * @return null|\WP_Error A WP_Error when the refund was not saved, or a callback declined it by throwing.
	 *
	 * @since 11.4.0
	 */
	public function before_save( \WC_Order_Refund $refund ): ?\WP_Error {
		$args = $this->args;

		$refund->save();
		if ( $refund->get_id() <= 0 ) {
			return new \WP_Error( 'refund_not_saved', __( 'The refund could not be saved.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		try {
			/**
			 * Fires before a new refund is saved, so a callback can change it.
			 *
			 * @since 3.0.0
			 *
			 * @param \WC_Order_Refund $refund Refund, saved once so it has an ID, before its final save.
			 * @param array            $args   Arguments in the format of wc_create_refund().
			 *
			 * @throws \Exception To decline the refund, with the message to show.
			 */
			do_action( 'woocommerce_create_refund', $refund, $args );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'refund_declined', $e->getMessage(), array( 'status' => 400 ) );
		}

		return null;
	}

	/**
	 * Set the amount, lines and totals of the refund without saving it, and without firing any hook.
	 *
	 * @param \WC_Order_Refund $refund New refund, not saved.
	 * @return null|\WP_Error
	 *
	 * @since 11.4.0
	 */
	public function stage( \WC_Order_Refund $refund ): ?\WP_Error {
		$order                             = $this->order;
		$args                              = $this->args;
		$this->remaining_refund_amount     = (float) $order->get_remaining_refund_amount();
		$this->remaining_refund_items      = (int) $order->get_remaining_refund_items();
		$this->refund_item_count           = 0;
		$this->refunded_order_and_products = array();

		if ( 0 > $args['amount'] || $args['amount'] > $this->remaining_refund_amount ) {
			return new \WP_Error( 'invalid_refund_amount', __( 'Invalid refund amount.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$refund->set_currency( $order->get_currency() );
		$refund->set_amount( $args['amount'] );
		$refund->set_parent_id( $order->get_id() );
		$refund->set_refunded_by( get_current_user_id() );
		$refund->set_prices_include_tax( $order->get_prices_include_tax() );

		if ( ! is_null( $args['reason'] ) ) {
			$refund->set_reason( $args['reason'] );
		}

		foreach ( is_array( $args['line_items'] ) ? $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) : array() as $item_id => $item ) {
			if ( isset( $args['line_items'][ $item_id ] ) && ( $item instanceof \WC_Order_Item_Product || $item instanceof \WC_Order_Item_Fee || $item instanceof \WC_Order_Item_Shipping ) ) {
				$this->stage_refund_line( $refund, $item_id, $item, $args['line_items'][ $item_id ] );
			}
		}

		$refund->stage_tax_items();
		$refund->stage_totals( false );
		$refund->set_total( wc_format_decimal( $args['amount'] * -1 ) );

		return null;
	}

	/**
	 * Do what follows the save of the refund: refund the payment, restock, revoke downloads, mark the order
	 * partially or fully refunded, and fire the refund hooks. When the gateway refuses, the refund is deleted
	 * and nothing else runs.
	 *
	 * @param \WC_Order_Refund $refund Saved refund.
	 * @return null|\WP_Error The error of the gateway.
	 *
	 * @since 11.4.0
	 */
	public function finish( \WC_Order_Refund $refund ): ?\WP_Error {
		$order = $this->order;
		$args  = $this->args;

		if ( $args['refund_payment'] ) {
			$result = wc_refund_payment( $order, wc_format_decimal( $refund->get_amount() ), $refund->get_reason() );

			if ( is_wp_error( $result ) ) {
				$refund->delete();
				return $result;
			}

			$refund->set_refunded_payment( true );
			$refund->save();
		}

		if ( $args['restock_items'] ) {
			wc_restock_refunded_items( $order, $args['line_items'] );
		}

		$download_data_store = empty( $this->refunded_order_and_products ) ? null : \WC_Data_Store::load( 'customer-download' );
		if ( is_callable( array( $download_data_store, 'get_downloads' ) ) && is_callable( array( $download_data_store, 'delete_by_id' ) ) ) {
			foreach ( $this->refunded_order_and_products as $refunded_order_and_product ) {
				foreach ( $download_data_store->get_downloads( $refunded_order_and_product ) as $download ) {
					$download_data_store->delete_by_id( $download->get_id() );
				}
			}
		}

		/**
		 * Filters whether the refund leaves the order partially refunded rather than fully refunded.
		 *
		 * @since 6.7.0
		 *
		 * @param bool $is_partially_refunded Whether the order is partially refunded.
		 * @param int  $order_id              Order ID.
		 * @param int  $refund_id             Refund ID.
		 */
		$is_partially_refunded = (bool) apply_filters(
			'woocommerce_order_is_partially_refunded',
			( $this->remaining_refund_amount - $args['amount'] ) > 0 ||
			( ! empty( $args['line_items'] ) && $order->has_free_item() && ( $this->remaining_refund_items - $this->refund_item_count ) > 0 ),
			$order->get_id(),
			$refund->get_id()
		);

		if ( $is_partially_refunded ) {
			/**
			 * Fires after a refund that leaves part of the order unrefunded.
			 *
			 * @since 2.2.0
			 *
			 * @param int $order_id  Order ID.
			 * @param int $refund_id Refund ID.
			 */
			do_action( 'woocommerce_order_partially_refunded', $order->get_id(), $refund->get_id() );
		} else {
			/**
			 * Fires after a refund that refunds the rest of the order.
			 *
			 * @since 2.2.0
			 *
			 * @param int $order_id  Order ID.
			 * @param int $refund_id Refund ID.
			 */
			do_action( 'woocommerce_order_fully_refunded', $order->get_id(), $refund->get_id() );

			/**
			 * Filters the status of a fully refunded order. A falsy value keeps the status.
			 *
			 * @since 2.7.0
			 *
			 * @param string $parent_status Status to set.
			 * @param int    $order_id      Order ID.
			 * @param int    $refund_id     Refund ID.
			 */
			$parent_status = apply_filters( 'woocommerce_order_fully_refunded_status', OrderStatus::REFUNDED, $order->get_id(), $refund->get_id() );

			if ( $parent_status ) {
				$order->update_status( $parent_status );
			}
		}

		$order->set_date_modified( time() );
		if ( wc_get_container()->get( CostOfGoodsSoldController::class )->feature_is_enabled() && $order->has_cogs() ) {
			$order->calculate_cogs_total_value();
		}
		$order->save();

		/**
		 * Fires after a refund is created and the order is updated.
		 *
		 * @since 2.2.0
		 *
		 * @param int   $refund_id Refund ID.
		 * @param array $args      Arguments in the format of wc_create_refund().
		 */
		do_action( 'woocommerce_refund_created', $refund->get_id(), $args );
		/**
		 * Fires after an order is refunded.
		 *
		 * @since 2.2.0
		 *
		 * @param int $order_id  Order ID.
		 * @param int $refund_id Refund ID.
		 */
		do_action( 'woocommerce_order_refunded', $order->get_id(), $refund->get_id() );

		return null;
	}

	/**
	 * Add the negative copy of one order line to the refund, as wc_create_refund() does.
	 *
	 * @param \WC_Order_Refund                                                  $refund  Refund.
	 * @param int                                                               $item_id ID of the order line.
	 * @param \WC_Order_Item_Product|\WC_Order_Item_Fee|\WC_Order_Item_Shipping $item    Order line.
	 * @param array                                                             $line    Refund of the line: qty, refund_total, refund_tax.
	 */
	private function stage_refund_line( \WC_Order_Refund $refund, int $item_id, \WC_Order_Item $item, array $line ): void {
		$qty          = $line['qty'] ?? 0;
		$refund_total = $line['refund_total'] ?? 0;

		// Keep a 0% tax amount, which a callback-less array_filter() would drop as falsy.
		$refund_tax = isset( $line['refund_tax'] ) ? array_map( 'floatval', array_filter( (array) $line['refund_tax'], 'is_numeric' ) ) : array();

		// An all-zero row from the admin refund form is not a refund of the line.
		if ( empty( $qty ) && empty( $refund_total ) && empty( array_sum( array_map( 'abs', $refund_tax ) ) ) ) {
			return;
		}

		if ( $item instanceof \WC_Order_Item_Product ) {
			$this->refunded_order_and_products[ $item_id ] = array(
				'order_id'   => $this->order->get_id(),
				'product_id' => $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(),
			);
		}

		$class         = get_class( $item );
		$refunded_item = new $class( $item );
		$refunded_item->set_id( 0 );

		// New meta IDs, so a change to the refund line cannot write to the meta of the order line.
		foreach ( $refunded_item->get_meta_data() as $meta ) {
			$meta->id = 0;
		}

		$refunded_item->add_meta_data( '_refunded_item_id', (string) $item_id, true );
		$refunded_item->set_total( wc_format_decimal( wc_format_refund_total( $refund_total ) ) );
		$refunded_item->set_taxes(
			array(
				'total'    => array_map( 'wc_format_refund_total', $refund_tax ),
				'subtotal' => array_map( 'wc_format_refund_total', $refund_tax ),
			)
		);

		if ( is_callable( array( $refunded_item, 'set_subtotal' ) ) ) {
			$refunded_item->set_subtotal( wc_format_decimal( wc_format_refund_total( $refund_total ) ) );
		}

		if ( is_callable( array( $refunded_item, 'set_quantity' ) ) ) {
			$refunded_item->set_quantity( $qty * -1 );
		}

		$refund->add_item( $refunded_item );
		$this->refund_item_count += $qty;
	}
}
