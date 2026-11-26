<?php
/**
 * Order edit trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\Admin\Orders\ItemQuantityLimits;

defined( 'ABSPATH' ) || exit;

/**
 * Address and line rules of the abilities that write an order, as the order screen applies them. The address
 * and line changes stay in memory until the ability saves. The new product lines and the totals wait for the
 * save, which uses the order methods that save. A class that uses this trait also uses OrderAbilityTrait.
 */
trait OrderEditTrait {

	use AddressInputTrait;

	/**
	 * Product lines that change() added or gave a new quantity, for a stock change after the save.
	 *
	 * @var array<int, \WC_Order_Item_Product>
	 */
	private array $changed_product_lines = array();

	/**
	 * Whether recalculate_saved_order() recalculates with the methods that save, and which ones.
	 *
	 * @var string|null `coupons`, `totals` or null.
	 */
	private ?string $recalculate_after_save = null;

	/**
	 * Product lines for add_queued_products(), which adds them with WC_Order::add_product().
	 *
	 * @var array<int, array{0: \WC_Product, 1: int}>
	 */
	private array $products_to_add = array();

	/**
	 * Forget the line changes of an earlier call, since the ability object is reused.
	 */
	private function reset_line_changes(): void {
		$this->changed_product_lines  = array();
		$this->recalculate_after_save = null;
		$this->products_to_add        = array();
	}

	/**
	 * Leave the totals for recalculate_saved_order() after the lines changed. Coupons apply again after a
	 * product is added, because they were applied before the new line existed.
	 *
	 * @param \WC_Order $order         Order.
	 * @param bool      $product_added Whether a product line was added.
	 */
	private function recalculate_line_totals_after_save( \WC_Order $order, bool $product_added ): void {
		$this->recalculate_after_save = $product_added && ! empty( $order->get_items( 'coupon' ) ) ? 'coupons' : 'totals';
	}

	/**
	 * Add the product lines that add_product_line() left for the save.
	 * WC_Order::add_product() saves each line right away.
	 *
	 * @param \WC_Order $order Order.
	 * @return array<int, \WC_Order_Item_Product> The added lines, to delete when the order save fails.
	 */
	private function add_queued_products( \WC_Order $order ): array {
		$added = array();
		foreach ( $this->products_to_add as list( $product, $quantity ) ) {
			$item = $order->get_item( $order->add_product( $product, $quantity, array( 'order' => $order ) ), false );
			if ( $item instanceof \WC_Order_Item_Product ) {
				$added[] = $item;
			}
		}

		return $added;
	}

	/**
	 * Recalculate the saved order with the methods that save, when recalculate_line_totals_after_save() left it for later.
	 * Each step is checked on its own, because the next save clears the error of a failed one.
	 *
	 * @param \WC_Order $order Saved order.
	 * @return null|\WP_Error
	 */
	private function recalculate_saved_order( \WC_Order $order ): ?\WP_Error {
		$steps = array();
		if ( 'coupons' === $this->recalculate_after_save ) {
			if ( $order->get_prices_include_tax() ) {
				$steps[] = array( $order, 'calculate_taxes' );
			}
			$steps[] = array( $order, 'recalculate_coupons' );
		} elseif ( 'totals' === $this->recalculate_after_save ) {
			$steps[] = array( $order, 'calculate_totals' );
		}

		foreach ( $steps as $step ) {
			$step();
			$error = $order->get_last_save_error();
			if ( null !== $error ) {
				wc_get_logger()->error(
					sprintf( 'Ability "%s" failed to save the recalculated order: %s', $this->get_name(), $error->getMessage() ),
					array( 'source' => 'woocommerce-abilities' )
				);
				return new \WP_Error(
					'woocommerce_ability_save_failed',
					__( 'The change could not be saved.', 'woocommerce' ),
					array( 'status' => 500 )
				);
			}
		}

		return null;
	}

	/**
	 * Change a shipping line in memory, or add a new one when the line has no ID.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $line  Shipping line input.
	 * @return null|\WP_Error
	 */
	private static function set_shipping_line( \WC_Order $order, array $line ): ?\WP_Error {
		// wc_format_decimal() drops the exponent of a number such as 1e3, which changes its value.
		if ( isset( $line['total'] ) && ( ! is_numeric( $line['total'] ) || false !== stripos( (string) $line['total'], 'e' ) || (float) $line['total'] < 0 ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_shipping_line_total_invalid',
				/* translators: %s: shipping total sent by the client. */
				sprintf( __( '"%s" is not a valid shipping total. Send a decimal number that is not negative, such as 9.99.', 'woocommerce' ), $line['total'] ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $line['id'] ) ) {
			if ( empty( $line['method_title'] ) ) {
				return new \WP_Error(
					'woocommerce_ability_order_shipping_line_method_title_required',
					__( 'A new shipping line needs a method_title. Send method_title, or send the id of the shipping line to change.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$item = new \WC_Order_Item_Shipping();
		} else {
			$item = $order->get_item( $line['id'], false );

			if ( ! $item instanceof \WC_Order_Item_Shipping ) {
				return self::line_not_found( $line['id'] );
			}
		}

		if ( isset( $line['method_id'] ) ) {
			$item->set_method_id( sanitize_text_field( $line['method_id'] ) );
		}

		if ( isset( $line['method_title'] ) ) {
			$item->set_method_title( sanitize_text_field( $line['method_title'] ) );
		}

		if ( isset( $line['total'] ) ) {
			$item->set_total( wc_format_decimal( $line['total'] ) );
		}

		if ( empty( $line['id'] ) ) {
			$order->add_item( $item );
		}

		return null;
	}

	/**
	 * Check a new product line. The line waits for add_queued_products(), which adds it on save.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $line  Product line input.
	 * @return null|\WP_Error
	 */
	private function add_product_line( \WC_Order $order, array $line ): ?\WP_Error {
		$product  = wc_get_product( $line['product_id'] );
		$quantity = $line['quantity'] ?? 1;

		if ( ! $product instanceof \WC_Product ) {
			return new \WP_Error(
				'woocommerce_ability_order_product_not_found',
				/* translators: %d: product ID. */
				sprintf( __( 'Product %d does not exist. Use the woocommerce/products-query ability to find the product ID.', 'woocommerce' ), $line['product_id'] ),
				array( 'status' => 400 )
			);
		}

		if ( $product->is_type( ProductType::VARIABLE ) ) {
			return new \WP_Error(
				'woocommerce_ability_order_product_variable',
				/* translators: %d: product ID. */
				sprintf( __( 'Product %d is a variable product and cannot be added. Send the ID of one of its variations as product_id.', 'woocommerce' ), $line['product_id'] ),
				array( 'status' => 400 )
			);
		}

		try {
			wc_get_container()->get( ItemQuantityLimits::class )->validate_new_item_quantity( $quantity, $product );
		} catch ( \Exception $exception ) {
			return self::quantity_error( $exception );
		}

		$this->products_to_add[] = array( $product, $quantity );

		return null;
	}

	/**
	 * Build the error for a line that the order does not have.
	 *
	 * @param int $item_id Order item ID sent by the client.
	 * @return \WP_Error
	 */
	private static function line_not_found( int $item_id ): \WP_Error {
		return new \WP_Error(
			'woocommerce_ability_order_line_not_found',
			/* translators: %d: order item ID. */
			sprintf( __( 'Item %d is not a line of this order that this ability can change. Use the woocommerce/orders-query ability with include_line_items and include_lines to read the IDs of the product and shipping lines.', 'woocommerce' ), $item_id ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Build the error for a quantity below the minimum of a product.
	 *
	 * @param \Exception $exception The refusal of ItemQuantityLimits, which names the product and the minimum.
	 * @return \WP_Error
	 */
	private static function quantity_error( \Exception $exception ): \WP_Error {
		return new \WP_Error( 'woocommerce_ability_order_line_item_quantity_invalid', $exception->getMessage(), array( 'status' => 400 ) );
	}
}
