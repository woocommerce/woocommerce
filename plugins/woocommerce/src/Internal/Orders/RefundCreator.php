<?php
/**
 * RefundCreator class file.
 */

// phpcs:ignore Generic.PHP.RequireStrictTypes.Disabled -- Code moved from wc_create_refund() keeps its loose types, so existing callers see no change.
declare( strict_types = 0 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CostOfGoodsSoldController;
use Exception;
use WC_Data_Store;
use WC_Order;
use WC_Order_Item_Product;
use WC_Order_Refund;

/**
 * Creates one refund of an order in the steps of wc_create_refund(): build() builds the refund in memory,
 * calculate() or stage() adds its taxes and totals, and complete() saves it, refunds the payment and updates
 * the order. wc_create_refund() runs calculate(), which saves as it always did. A caller that checks the refund
 * before anything is saved runs stage(), saves the refund, and then runs complete().
 *
 * Every step throws an exception when it fails. The caller deletes the refund from get_refund() then, as
 * wc_create_refund() does.
 *
 * @internal
 * @since 11.5.0
 */
class RefundCreator {

	/**
	 * Arguments of wc_create_refund(), with their defaults.
	 *
	 * @var array
	 */
	private $args = array();

	/**
	 * The order to refund.
	 *
	 * @var WC_Order|null
	 */
	private $order = null;

	/**
	 * The refund being created.
	 *
	 * @var WC_Order_Refund|null
	 */
	private $refund = null;

	/**
	 * Amount of the order that was left to refund before this refund.
	 *
	 * @var string
	 */
	private $remaining_refund_amount = '0';

	/**
	 * Number of items of the order that were left to refund before this refund.
	 *
	 * @var int
	 */
	private $remaining_refund_items = 0;

	/**
	 * Number of items this refund refunds.
	 *
	 * @var int
	 */
	private $refund_item_count = 0;

	/**
	 * Order and product IDs of the refunded product lines, for revoking their downloads.
	 *
	 * @var array
	 */
	private $refunded_order_and_products = array();

	/**
	 * Build the refund in memory: its order, amount, reason and the refunded lines with their taxes. Nothing is saved.
	 *
	 * @since 11.5.0
	 *
	 * @param array|string $args Arguments of wc_create_refund(), in any form that wp_parse_args() takes.
	 * @return WC_Order_Refund
	 * @throws Exception When the order or the amount is not valid.
	 */
	public function build( $args ): WC_Order_Refund {
		$default_args = array(
			'amount'         => 0,
			'reason'         => null,
			'order_id'       => 0,
			'refund_id'      => 0,
			'line_items'     => array(),
			'refund_payment' => false,
			'restock_items'  => false,
		);

		$args       = wp_parse_args( $args, $default_args );
		$this->args = $args;
		$order      = wc_get_order( $args['order_id'] );

		if ( ! $order instanceof WC_Order ) {
			throw new Exception( __( 'Invalid order ID.', 'woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- wc_create_refund() returns the message in a WP_Error, unescaped as before.
		}

		$this->order                   = $order;
		$this->remaining_refund_amount = $order->get_remaining_refund_amount();
		$this->remaining_refund_items  = $order->get_remaining_refund_items();
		$refund                        = new WC_Order_Refund( $args['refund_id'] );
		$this->refund                  = $refund;

		if ( 0 > $args['amount'] || $args['amount'] > $this->remaining_refund_amount ) {
			throw new Exception( __( 'Invalid refund amount.', 'woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- wc_create_refund() returns the message in a WP_Error, unescaped as before.
		}

		$refund->set_currency( $order->get_currency() );
		$refund->set_amount( $args['amount'] );
		$refund->set_parent_id( absint( $args['order_id'] ) );
		// A refund can be created with nobody logged in (gateway webhook, REST, WP-CLI, cron). Record no
		// user in that case; falling back to user 1 attributes the refund to whoever that happens to be.
		$refund->set_refunded_by( get_current_user_id() );
		$refund->set_prices_include_tax( $order->get_prices_include_tax() );

		if ( ! is_null( $args['reason'] ) ) {
			$refund->set_reason( $args['reason'] );
		}

		// Negative line items.
		if ( is_array( $args['line_items'] ) && count( $args['line_items'] ) > 0 ) {
			$items = $order->get_items( array( 'line_item', 'fee', 'shipping' ) );

			foreach ( $items as $item_id => $item ) {
				if ( ! isset( $args['line_items'][ $item_id ] ) ) {
					continue;
				}

				$qty          = isset( $args['line_items'][ $item_id ]['qty'] ) ? $args['line_items'][ $item_id ]['qty'] : 0;
				$refund_total = $args['line_items'][ $item_id ]['refund_total'];

				// Keep every numeric tax entry, including a 0% amount that a callback-less array_filter would drop as falsy. 0% is a valid rate, not "no tax". See #27118.
				$refund_tax = isset( $args['line_items'][ $item_id ]['refund_tax'] ) ? array_map( 'floatval', array_filter( (array) $args['line_items'][ $item_id ]['refund_tax'], 'is_numeric' ) ) : array();

				// The admin refund form posts a 0 total and 0 tax amounts for every untouched row, so an
				// all-zero refund_tax alone must not create a refund line item (a genuine 0% tax line
				// still survives, because its item is refunded via qty or refund_total).
				$refund_tax_sum = array_sum( array_map( 'abs', $refund_tax ) );

				if ( empty( $qty ) && empty( $refund_total ) && empty( $refund_tax_sum ) ) {
					continue;
				}

				// array of order id and product id which were refunded.
				// later to be used for revoking download permission.
				// checking if the item is a product, as we only need to revoke download permission for products.
				if ( $item->is_type( 'line_item' ) && method_exists( $item, 'get_product_id' ) ) {
					$product_id = $item->get_product_id();
					if ( $item instanceof WC_Order_Item_Product && $item->get_variation_id() ) {
						$product_id = $item->get_variation_id();
					}
					$this->refunded_order_and_products[ $item_id ] = array(
						'order_id'   => $order->get_id(),
						'product_id' => $product_id,
					);
				}

				$class         = get_class( $item );
				$refunded_item = new $class( $item );
				if ( ! method_exists( $refunded_item, 'set_total' ) || ! method_exists( $refunded_item, 'set_taxes' ) ) {
					continue;
				}
				$refunded_item->set_id( 0 );

				// Copied metadata needs new IDs so refund changes cannot update or delete the original item's metadata.
				foreach ( $refunded_item->get_meta_data() as $meta ) {
					$meta->id = 0;
				}

				$refunded_item->add_meta_data( '_refunded_item_id', $item_id, true );
				$refunded_item->set_total( wc_format_refund_total( $refund_total ) );
				$refunded_item->set_taxes(
					array(
						'total'    => array_map( 'wc_format_refund_total', $refund_tax ),
						'subtotal' => array_map( 'wc_format_refund_total', $refund_tax ),
					)
				);

				if ( is_callable( array( $refunded_item, 'set_subtotal' ) ) ) {
					$refunded_item->set_subtotal( wc_format_refund_total( $refund_total ) );
				}

				if ( is_callable( array( $refunded_item, 'set_quantity' ) ) ) {
					$refunded_item->set_quantity( $qty * -1 );
				}

				$refund->add_item( $refunded_item );
				$this->refund_item_count += $qty;
			}
		}

		return $refund;
	}

	/**
	 * Add the tax lines and totals to the built refund, and save it after each, as wc_create_refund() always did.
	 *
	 * @since 11.5.0
	 */
	public function calculate(): void {
		list( , $refund ) = $this->built();
		$refund->update_taxes();
		$refund->calculate_totals( false );
		$this->set_total_and_date( $refund );
	}

	/**
	 * Add the tax lines and totals to the built refund in memory, with the stage methods. Nothing is saved, and
	 * woocommerce_order_after_calculate_totals fires at the next save.
	 *
	 * @since 11.5.0
	 *
	 * @throws \WC_Data_Exception When the refund class overrides a saving method without its stage method.
	 */
	public function stage(): void {
		list( , $refund ) = $this->built();
		$refund->stage_tax_items();
		$refund->stage_totals( false );
		$this->set_total_and_date( $refund );
	}

	/**
	 * Save the refund, refund the payment when asked, restock, revoke the refunded downloads and update the order,
	 * with the hooks of wc_create_refund().
	 *
	 * @since 11.5.0
	 *
	 * @return WC_Order_Refund|\WP_Error The refund, or the error of the payment gateway after the refund was deleted.
	 * @throws Exception When a step fails.
	 */
	public function complete() {
		$args                   = $this->args;
		list( $order, $refund ) = $this->built();

		/**
		 * Action hook to adjust refund before save.
		 *
		 * @since 3.0.0
		 */
		do_action( 'woocommerce_create_refund', $refund, $args );

		if ( $refund->save() ) {
			if ( $args['refund_payment'] ) {
				$result = wc_refund_payment( $order, $refund->get_amount(), $refund->get_reason() );

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

			// delete downloads that were refunded using order and product id, if present.
			if ( ! empty( $this->refunded_order_and_products ) ) {
				$download_data_store = WC_Data_Store::load( 'customer-download' );
				foreach ( $this->refunded_order_and_products as $refunded_order_and_product ) {
					$downloads = $download_data_store->get_downloads( $refunded_order_and_product ); // @phpstan-ignore method.notFound (WC_Data_Store forwards it to the customer download data store.)
					if ( ! empty( $downloads ) ) {
						foreach ( $downloads as $download ) {
							$download_data_store->delete_by_id( $download->get_id() ); // @phpstan-ignore method.notFound (WC_Data_Store forwards it to the customer download data store.)
						}
					}
				}
			}

			/**
			 * Trigger notification emails.
			 *
			 * Filter hook to modify the partially-refunded status conditions.
			 *
			 * @since 6.7.0
			 *
			 * @param bool $is_partially_refunded Whether the order is partially refunded.
			 * @param int  $order_id The order id.
			 * @param int  $refund_id The refund id.
			 */
			if ( (bool) apply_filters(
				'woocommerce_order_is_partially_refunded',
				( $this->remaining_refund_amount - $args['amount'] ) > 0 ||
				( ! empty( $args['line_items'] ) && $order->has_free_item() && ( $this->remaining_refund_items - $this->refund_item_count ) > 0 ),
				$order->get_id(),
				$refund->get_id()
			) ) {
				/**
				 * Fires after a refund that leaves part of the order to refund.
				 *
				 * @param int $order_id  The order ID.
				 * @param int $refund_id The refund ID.
				 */
				do_action( 'woocommerce_order_partially_refunded', $order->get_id(), $refund->get_id() ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment -- Moved unchanged from wc_create_refund(), which did not record its version.
			} else {
				/**
				 * Fires after a refund that leaves nothing of the order to refund.
				 *
				 * @param int $order_id  The order ID.
				 * @param int $refund_id The refund ID.
				 */
				do_action( 'woocommerce_order_fully_refunded', $order->get_id(), $refund->get_id() ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment -- Moved unchanged from wc_create_refund(), which did not record its version.

				/**
				 * Filter the status to set the order to when fully refunded.
				 *
				 * @since 2.7.0
				 *
				 * @param string $parent_status The status to set the order to when fully refunded.
				 * @param int    $order_id      The order ID.
				 * @param int    $refund_id     The refund ID.
				 */
				$parent_status = apply_filters( 'woocommerce_order_fully_refunded_status', OrderStatus::REFUNDED, $order->get_id(), $refund->get_id() );

				if ( $parent_status ) {
					$order->update_status( $parent_status );
				}
			}
		}

		$order->set_date_modified( time() );
		if ( wc_get_container()->get( CostOfGoodsSoldController::class )->feature_is_enabled() && $order->has_cogs() ) {
			$order->calculate_cogs_total_value();
		}
		$order->save();

		/**
		 * Fires after a refund is created and the order is saved.
		 *
		 * @param int   $refund_id The refund ID.
		 * @param array $args      The arguments of wc_create_refund().
		 */
		do_action( 'woocommerce_refund_created', $refund->get_id(), $args ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment -- Moved unchanged from wc_create_refund(), which did not record its version.

		/**
		 * Fires after an order is refunded.
		 *
		 * @param int $order_id  The order ID.
		 * @param int $refund_id The refund ID.
		 */
		do_action( 'woocommerce_order_refunded', $order->get_id(), $refund->get_id() ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment -- Moved unchanged from wc_create_refund(), which did not record its version.

		return $refund;
	}

	/**
	 * The refund that build() made, also when a later check of build() failed, or null.
	 *
	 * @since 11.5.0
	 *
	 * @return WC_Order_Refund|null
	 */
	public function get_refund(): ?WC_Order_Refund {
		return $this->refund;
	}

	/**
	 * The order and the refund that build() made.
	 *
	 * @return array{0: WC_Order, 1: WC_Order_Refund}
	 * @throws Exception When build() did not build the refund.
	 */
	private function built(): array {
		if ( null === $this->order || null === $this->refund ) {
			throw new Exception( 'The refund is not built.' );
		}

		return array( $this->order, $this->refund );
	}

	/**
	 * Set the total from the amount, and the creation date when the arguments have one. The date comes after the
	 * taxes, because the first save writes the current date.
	 *
	 * @param WC_Order_Refund $refund The refund.
	 */
	private function set_total_and_date( WC_Order_Refund $refund ): void {
		$refund->set_total( $this->args['amount'] * -1 ); // @phpstan-ignore argument.type (wc_create_refund() has always passed the negated amount as a number, and set_total() formats it.)

		if ( isset( $this->args['date_created'] ) ) {
			$refund->set_date_created( $this->args['date_created'] );
		}
	}
}
