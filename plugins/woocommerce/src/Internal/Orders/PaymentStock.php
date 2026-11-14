<?php
/**
 * Stock checks for payments on existing orders.
 *
 * @package WooCommerce\Internal\Orders
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Checkout\Helpers\ReserveStock;
use Automattic\WooCommerce\Checkout\Helpers\ReserveStockException;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Utilities\DatabaseTransaction;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Checks and reserves stock before a payment starts.
 *
 * @internal
 * @since 11.4.0
 */
final class PaymentStock {
	/**
	 * Get the stock error without changing the order or its reservations.
	 *
	 * @since 11.4.0
	 * @param WC_Order      $order Order to check.
	 * @param callable|null $stock_filter Adapter for the documented stock filters.
	 * @return array{code: string, message: string}|null Stock error, or null when stock is available.
	 */
	public function get_stock_error( WC_Order $order, ?callable $stock_filter = null ): ?array {
		try {
			return $this->check_order_stock( $order, $stock_filter )['error'];
		} catch ( ReserveStockException $e ) {
			return array(
				'code'    => $e->getErrorCode(),
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Secure stock before handing the order to a payment integration.
	 *
	 * @since 11.4.0
	 * @param WC_Order $order Order to pay for.
	 * @throws ReserveStockException When stock cannot be secured.
	 * @throws \Throwable When an order save or integration fails.
	 */
	public function reserve_stock_for_payment( WC_Order $order ): void {
		$stock = $this->check_order_stock( $order );
		if ( $stock['error'] ) {
			throw new ReserveStockException( esc_attr( $stock['error']['code'] ), esc_html( $stock['error']['message'] ), 403 );
		}
		$quantities = $stock['quantities'];

		/**
		 * This filter is documented in includes/wc-stock-functions.php.
		 *
		 * @since 4.3.0
		 */
		if ( empty( $quantities ) || ! apply_filters( 'woocommerce_hold_stock_for_checkout', true ) ) {
			return;
		}

		try {
			DatabaseTransaction::run( fn() => $this->reserve_and_save_order( $order, $quantities ) );
		} catch ( \Throwable $e ) {
			$this->clear_order_caches_after_rollback( $order );
			throw $e;
		}
	}

	/**
	 * Save the stock holds and the unpaid order in the caller's transaction.
	 *
	 * @param WC_Order             $order Order to pay for.
	 * @param array<int,int|float> $quantities Checked quantities keyed by stock-managing product ID.
	 * @throws ReserveStockException When stock cannot be held or the order cannot be saved.
	 */
	private function reserve_and_save_order( WC_Order $order, array $quantities ): void {
		$minutes = (int) get_option( 'woocommerce_hold_stock_minutes', 60 );
		$reserve = wc_get_container()->get( ReserveStock::class );
		if ( ! $reserve->reserve_stock_for_payment( $order, $minutes, $quantities ) ) {
			return;
		}
		// Failed orders do not count in the stock-hold query, so a payment retry must become pending.
		if ( $order->has_status( OrderStatus::FAILED ) ) {
			$order->set_status( OrderStatus::PENDING );
		}
		$order->set_date_modified( time() );
		$order->save();
		if ( $order->get_last_save_error() ) {
			throw ReserveStockException::stock_check_failed();
		}
	}

	/**
	 * Discard cached values after DatabaseTransaction has rolled back payment preparation.
	 *
	 * @param WC_Order $order Order whose cached values must be read again.
	 */
	private function clear_order_caches_after_rollback( WC_Order $order ): void {
		clean_post_cache( $order->get_id() );
		wc_get_container()->get( OrderCache::class )->remove( $order->get_id() );
		$data_store = $order->get_data_store();
		if ( is_callable( array( $data_store, 'clear_cached_data' ) ) ) {
			$data_store->clear_cached_data( array( $order->get_id() ) );
		}
	}

	/**
	 * Check each product and return only the quantities that need a stock hold.
	 *
	 * @param WC_Order      $order Order to check.
	 * @param callable|null $stock_filter Adapter for the documented stock filters.
	 * @return array{error: array{code: string, message: string}|null, quantities: array<int,int|float>} Checked stock quantities or an error.
	 * @throws ReserveStockException When stock cannot be checked.
	 */
	private function check_order_stock( WC_Order $order, ?callable $stock_filter = null ): array {
		if ( $this->order_stock_was_reduced( $order ) ) {
			return array(
				'error'      => null,
				'quantities' => array(),
			);
		}
		$requirements          = $this->get_stock_requirements( $order );
		$reservations          = $requirements['quantities'] ? $this->get_valid_reservations( $order ) : array();
		$available_quantities  = array();
		$quantities_to_reserve = array();
		$stock_overrides       = array();

		foreach ( $requirements['products'] as $product ) {
			$stock_id            = $product->get_stock_managed_by_id();
			$required_quantity   = $requirements['quantities'][ $stock_id ] ?? 0;
			$has_own_reservation = $required_quantity > 0 && ( $reservations[ $stock_id ] ?? 0 ) >= $required_quantity;
			$error               = $this->get_product_status_error( $product, $order, $has_own_reservation, $stock_filter );
			if ( $error ) {
				return array(
					'error'      => $error,
					'quantities' => array(),
				);
			}
			if ( ! $has_own_reservation && ! $product->is_in_stock() ) {
				$stock_overrides[ $stock_id ] = true;
			}
			if ( ! $this->requires_stock_hold( $product ) ) {
				continue;
			}
			if ( ! isset( $available_quantities[ $stock_id ] ) ) {
				$available_quantities[ $stock_id ] = $this->get_available_quantity( $product, $order, $required_quantity, $has_own_reservation );
			}
			$available_quantity = $available_quantities[ $stock_id ];
			$error              = $this->get_product_quantity_error( $product, $order, $required_quantity, $available_quantity, $stock_filter );
			if ( $error ) {
				return array(
					'error'      => $error,
					'quantities' => array(),
				);
			}
			if ( $available_quantity < $required_quantity ) {
				$stock_overrides[ $stock_id ] = true;
			}
			$quantities_to_reserve[ $stock_id ] = $required_quantity;
		}
		// An explicit payment stock override must remain effective in the reservation writer.
		return array(
			'error'      => null,
			'quantities' => array_diff_key( $quantities_to_reserve, $stock_overrides ),
		);
	}

	/**
	 * Read the saved stock-reduction flag, since the order object can be stale.
	 *
	 * @param WC_Order $order Order to check.
	 * @return bool Whether this order's stock was already reduced.
	 */
	private function order_stock_was_reduced( WC_Order $order ): bool {
		$data_store = $order->get_data_store();
		return is_callable( array( $data_store, 'get_stock_reduced' ) ) && (bool) $data_store->get_stock_reduced( $order->get_id() );
	}

	/**
	 * Collect the products and combine quantities that use the same stock record.
	 *
	 * @param WC_Order $order Order to check.
	 * @return array{products: array<int,WC_Product>, quantities: array<int,int|float>} Stock requirements.
	 */
	private function get_stock_requirements( WC_Order $order ): array {
		$products   = array();
		$quantities = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$product = $item->get_product();
			if ( ! $product instanceof WC_Product || $item->get_quantity() <= 0 || $item->get_meta( '_reduced_stock', true ) ) {
				continue;
			}
			$products[ $product->get_id() ] = $product;
			if ( $this->requires_stock_hold( $product ) ) {
				$quantity                = $this->get_item_stock_quantity( $item, $order );
				$stock_id                = $product->get_stock_managed_by_id();
				$quantities[ $stock_id ] = ( $quantities[ $stock_id ] ?? 0 ) + $quantity;
			}
		}
		return array(
			'products'   => $products,
			'quantities' => $quantities,
		);
	}

	/**
	 * Apply quantity filters and reject invalid amounts before reserving stock.
	 *
	 * @param WC_Order_Item_Product $item Ordered item.
	 * @param WC_Order              $order Order containing the item.
	 * @return int|float Quantity to reserve.
	 * @throws ReserveStockException When a filter returns an invalid quantity.
	 */
	private function get_item_stock_quantity( WC_Order_Item_Product $item, WC_Order $order ) {
		/**
		 * This filter is documented in Checkout/Helpers/ReserveStock.php.
		 *
		 * @since 4.5.0
		 */
		$quantity = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );
		if ( ! $this->is_valid_quantity( $quantity ) ) {
			throw ReserveStockException::stock_check_failed();
		}
		$quantity = wc_stock_amount( (float) $quantity );
		if ( ! $this->is_valid_quantity( $quantity ) ) {
			throw ReserveStockException::stock_check_failed();
		}
		return $quantity;
	}

	/**
	 * Check a quantity supplied by a filter.
	 *
	 * @param mixed $quantity Filter result.
	 * @return bool Whether it is a finite, nonnegative number.
	 */
	private function is_valid_quantity( $quantity ): bool {
		return is_numeric( $quantity ) && is_finite( (float) $quantity ) && $quantity >= 0;
	}

	/**
	 * Check whether the product needs a limited stock allocation.
	 *
	 * @param WC_Product $product Product to check.
	 * @return bool Whether stock must be held.
	 */
	private function requires_stock_hold( WC_Product $product ): bool {
		return $product->managing_stock() && ! $product->backorders_allowed();
	}

	/**
	 * Check the product's stock status, including the payment stock filter.
	 *
	 * @param WC_Product    $product Product to check.
	 * @param WC_Order      $order Order to pay for.
	 * @param bool          $has_reservation Whether this order already holds the required units.
	 * @param callable|null $stock_filter Stock filter adapter.
	 * @return array{code: string, message: string}|null Stock status error.
	 */
	private function get_product_status_error( WC_Product $product, WC_Order $order, bool $has_reservation, ?callable $stock_filter ): ?array {
		$in_stock = $has_reservation || $product->is_in_stock();
		if ( $this->filter_stock( 'woocommerce_pay_order_product_in_stock', $in_stock, $product, $order, $stock_filter ) ) {
			return null;
		}
		return array(
			'code'    => 'woocommerce_rest_out_of_stock',
			/* translators: %s: product name */
			'message' => sprintf( __( 'Sorry, "%s" is no longer in stock so this order cannot be paid for. We apologize for any inconvenience caused.', 'woocommerce' ), $product->get_name() ),
		);
	}

	/**
	 * Count units this order can use, without taking another order's hold.
	 *
	 * @param WC_Product $product Product to check.
	 * @param WC_Order   $order Order to pay for.
	 * @param int|float  $required Required quantity.
	 * @param bool       $has_reservation Whether this order already holds the required units.
	 * @return int|float Available quantity.
	 */
	private function get_available_quantity( WC_Product $product, WC_Order $order, $required, bool $has_reservation ) {
		if ( $has_reservation ) {
			return $required;
		}
		$held = wc_get_held_stock_quantity( $product, $order->get_id() );
		return ( $product->get_stock_quantity() ?? 0 ) - $held;
	}

	/**
	 * Check the required quantity, including the payment stock filter.
	 *
	 * @param WC_Product    $product Product to check.
	 * @param WC_Order      $order Order to pay for.
	 * @param int|float     $required Required quantity.
	 * @param int|float     $available Available quantity.
	 * @param callable|null $stock_filter Stock filter adapter.
	 * @return array{code: string, message: string}|null Stock quantity error.
	 */
	private function get_product_quantity_error( WC_Product $product, WC_Order $order, $required, $available, ?callable $stock_filter ): ?array {
		if ( $this->filter_stock( 'woocommerce_pay_order_product_has_enough_stock', $available >= $required, $product, $order, $stock_filter ) ) {
			return null;
		}
		return array(
			'code'    => 'woocommerce_rest_out_of_stock',
			/* translators: 1: product name 2: quantity in stock */
			'message' => sprintf( __( 'Sorry, we do not have enough "%1$s" in stock to fulfill your order (%2$s available). We apologize for any inconvenience caused.', 'woocommerce' ), $product->get_name(), wc_format_stock_quantity_for_display( $available, $product ) ),
		);
	}

	/**
	 * Apply the documented payment stock filter at the caller's hook location.
	 *
	 * @param string        $hook Filter name.
	 * @param bool          $value Default result.
	 * @param WC_Product    $product Product to check.
	 * @param WC_Order      $order Order to check.
	 * @param callable|null $stock_filter Adapter for the documented stock filters.
	 * @return bool Filtered result.
	 */
	private function filter_stock( string $hook, bool $value, WC_Product $product, WC_Order $order, ?callable $stock_filter ): bool {
		if ( $stock_filter ) {
			return (bool) $stock_filter( $hook, $value, $product, $order );
		}
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Both filters are documented in StoreApi/Utilities/OrderController.php.
		return (bool) apply_filters( $hook, $value, $product, $order );
	}

	/**
	 * Read unexpired reservations that still belong to this pending order.
	 *
	 * @param WC_Order $order Order to check.
	 * @return array<int,int|float> Reserved quantities keyed by stock-managing product ID.
	 * @throws ReserveStockException When the reservation query fails.
	 */
	private function get_valid_reservations( WC_Order $order ): array {
		global $wpdb;
		if ( ! $order->has_status( array( OrderStatus::PENDING, OrderStatus::CHECKOUT_DRAFT ) ) || get_option( 'woocommerce_schema_version', 0 ) < 430 ) {
			return array();
		}
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT product_id, stock_quantity FROM {$wpdb->wc_reserved_stock} WHERE order_id = %d AND expires > NOW()", $order->get_id() ),
			ARRAY_A
		);
		if ( $wpdb->last_error ) {
			throw ReserveStockException::stock_check_failed();
		}
		$result = array();
		foreach ( $rows as $row ) {
			$result[ (int) $row['product_id'] ] = wc_stock_amount( (float) $row['stock_quantity'] );
		}
		return $result;
	}
}
