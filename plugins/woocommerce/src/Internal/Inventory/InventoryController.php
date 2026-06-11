<?php
/**
 * InventoryController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Inventory;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;

defined( 'ABSPATH' ) || exit;

/**
 * Controller for POS location stock.
 *
 * @internal
 */
class InventoryController {

	/**
	 * Feature ID used by FeaturesController.
	 */
	public const FEATURE_ID = 'pos_location_stock';

	/**
	 * Option key used as the feature flag.
	 */
	public const FEATURE_OPTION = 'woocommerce_pos_location_stock';

	public const ORDER_LOCATION_META = '_inventory_location';

	private const ORDER_LOCATION_STOCK_REDUCED_META = '_location_stock_reduced';

	private const ITEM_LOCATION_STOCK_REDUCED_META = '_reduced_location_stock';

	private const ITEM_LOCATION_STOCK_RESTOCKED_META = '_restock_refunded_location_items';

	/**
	 * Feature controller.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Location stock service.
	 *
	 * @var LocationStockService
	 */
	private LocationStockService $location_stock_service;

	/**
	 * Admin product editor controller.
	 *
	 * @var LocationStockAdminController
	 */
	private LocationStockAdminController $admin_controller;

	/**
	 * Location stock installer.
	 *
	 * @var LocationStockInstaller
	 */
	private LocationStockInstaller $location_stock_installer;

	/**
	 * REST API hooks.
	 *
	 * @var LocationStockRestApiHooks
	 */
	private LocationStockRestApiHooks $rest_api_hooks;

	/**
	 * Whether feature hooks have already been registered in this request.
	 *
	 * @var bool
	 */
	private bool $feature_hooks_registered = false;

	/**
	 * Order item snapshots captured before stock-adjustable item saves.
	 *
	 * @var array<int,array<int,array{name:string,quantity:int,tax_class:string,subtotal:string,total:string,taxes:array<string,mixed>}>>
	 */
	private array $line_item_stock_adjustment_snapshots = array();

	/**
	 * Initialize dependencies.
	 *
	 * @param FeaturesController           $features_controller Feature controller.
	 * @param LocationStockService         $location_stock_service Location stock service.
	 * @param LocationStockAdminController $admin_controller Admin product editor controller.
	 * @param LocationStockInstaller       $location_stock_installer Location stock installer.
	 * @param LocationStockRestApiHooks    $rest_api_hooks REST API hooks.
	 *
	 * @internal
	 */
	final public function init( FeaturesController $features_controller, LocationStockService $location_stock_service, LocationStockAdminController $admin_controller, LocationStockInstaller $location_stock_installer, LocationStockRestApiHooks $rest_api_hooks ): void {
		$this->features_controller      = $features_controller;
		$this->location_stock_service   = $location_stock_service;
		$this->admin_controller         = $admin_controller;
		$this->location_stock_installer = $location_stock_installer;
		$this->rest_api_hooks           = $rest_api_hooks;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		$this->location_stock_installer->register();
		add_action( 'init', array( $this, 'register_feature_hooks' ), 20 );
	}

	/**
	 * Check whether the POS location stock feature flag is enabled.
	 */
	public function feature_is_enabled(): bool {
		return $this->features_controller->feature_is_enabled( self::FEATURE_ID );
	}

	/**
	 * Register behavior hooks only when the feature flag is enabled.
	 */
	public function register_feature_hooks(): void {
		if ( $this->feature_hooks_registered || ! $this->feature_is_enabled() ) {
			return;
		}

		$this->feature_hooks_registered = true;
		$this->location_stock_installer->maybe_create_db_tables();

		$this->admin_controller->register();
		add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'allow_core_stock_adjustment_for_location_order' ), 10, 2 );
		add_filter( 'woocommerce_can_restore_order_stock', array( $this, 'allow_core_stock_adjustment_for_location_order' ), 10, 2 );
		add_filter( 'woocommerce_can_restock_refunded_items', array( $this, 'handle_location_refunded_items_restock' ), 10, 3 );
		add_action( 'woocommerce_before_save_order_items', array( $this, 'snapshot_location_order_items_before_admin_save' ), 10, 2 );
		add_action( 'woocommerce_saved_order_items', array( $this, 'clear_location_order_item_snapshots' ), 10, 2 );
		add_action( 'woocommerce_rest_set_order_item', array( $this, 'snapshot_location_order_item_before_rest_save' ), 10, 2 );
		add_filter( 'woocommerce_prevent_adjust_line_item_product_stock', array( $this, 'prevent_core_line_item_product_stock_adjustment' ), 10, 3 );
		$this->rest_api_hooks->register();

		add_action( 'woocommerce_payment_complete', array( $this, 'maybe_reduce_location_stock_levels' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_reduce_location_stock_levels' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_reduce_location_stock_levels' ) );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'maybe_reduce_location_stock_levels' ) );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'maybe_restore_location_stock_levels' ) );
		add_action( 'woocommerce_order_status_pending', array( $this, 'maybe_restore_location_stock_levels' ) );
	}

	/**
	 * Keep Core from adjusting _stock for POS-backed orders.
	 *
	 * @param bool      $can_adjust Whether Core can adjust stock.
	 * @param \WC_Order $order      Order object.
	 */
	public function allow_core_stock_adjustment_for_location_order( $can_adjust, $order ): bool {
		if ( ! $can_adjust || ! $order instanceof \WC_Order || ! $this->get_configured_order_location_slug( $order ) ) {
			return (bool) $can_adjust;
		}

		return false;
	}

	/**
	 * Reduce POS stock for POS-backed orders.
	 *
	 * @param int $order_id Order ID.
	 */
	public function maybe_reduce_location_stock_levels( $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order || 'yes' !== get_option( 'woocommerce_manage_stock' ) || 'yes' === $order->get_meta( self::ORDER_LOCATION_STOCK_REDUCED_META, true ) ) {
			return;
		}

		$location_slug = $this->get_configured_order_location_slug( $order );
		if ( ! $location_slug ) {
			return;
		}

		$changes = array();
		$this->location_stock_service->with_deferred_product_modified_date_updates(
			function () use ( $order, $location_slug, &$changes ): void {
				foreach ( $order->get_items() as $item ) {
					if ( ! $item instanceof \WC_Order_Item_Product ) {
						continue;
					}

					$change = $this->reduce_location_stock_for_order_item( $order, $item, $location_slug );
					if ( is_wp_error( $change ) ) {
						$order->add_order_note( $change->get_error_message(), 0, false, array( 'note_group' => OrderNoteGroup::ERROR ) );
						continue;
					}

					if ( $change ) {
						$changes[] = $change;
					}
				}
			}
		);

		if ( empty( $changes ) ) {
			return;
		}

		$this->mark_order_location_stock_reduced( $order, $location_slug );
		$this->add_location_stock_order_note( $order, __( 'POS stock levels reduced:', 'woocommerce' ), $changes );
		$order->save();
	}

	/**
	 * Restore POS stock for POS-backed orders.
	 *
	 * @param int $order_id Order ID.
	 */
	public function maybe_restore_location_stock_levels( $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order || 'yes' !== get_option( 'woocommerce_manage_stock' ) || 'yes' !== $order->get_meta( self::ORDER_LOCATION_STOCK_REDUCED_META, true ) ) {
			return;
		}

		$location_slug = $this->get_configured_order_location_slug( $order );
		if ( ! $location_slug ) {
			return;
		}

		$changes = array();
		$this->location_stock_service->with_deferred_product_modified_date_updates(
			function () use ( $order, $location_slug, &$changes ): void {
				$changes = $this->restore_location_stock_for_order_items( $order, $order->get_items(), $location_slug );
			}
		);
		$this->clear_order_reduced_meta_if_done( $order );

		if ( ! empty( $changes ) ) {
			$this->add_location_stock_order_note( $order, __( 'POS stock levels increased:', 'woocommerce' ), $changes );
		}

		$order->save();
	}

	/**
	 * Restock POS-backed refunded items and keep Core from touching _stock.
	 *
	 * @param bool      $can_restock         Whether Core can restock refunded items.
	 * @param \WC_Order $order               Order object.
	 * @param array     $refunded_line_items Refunded line item data.
	 */
	public function handle_location_refunded_items_restock( $can_restock, $order, $refunded_line_items ): bool {
		if ( ! $can_restock || ! $order instanceof \WC_Order ) {
			return (bool) $can_restock;
		}

		$location_slug = $this->get_configured_order_location_slug( $order );
		if ( ! $location_slug ) {
			return (bool) $can_restock;
		}

		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
			$changes = array();
			$this->location_stock_service->with_deferred_product_modified_date_updates(
				function () use ( $order, $location_slug, $refunded_line_items, &$changes ): void {
					$changes = $this->restore_location_stock_for_order_items( $order, $order->get_items(), $location_slug, $refunded_line_items );
				}
			);
			$this->clear_order_reduced_meta_if_done( $order );

			if ( ! empty( $changes ) ) {
				$this->add_location_stock_order_note( $order, __( 'POS stock levels increased:', 'woocommerce' ), $changes );
			}

			$order->save();
		}

		return false;
	}

	/**
	 * Adjust POS stock in place of Core's line-item stock delta handling.
	 *
	 * @param bool           $prevent       Whether Core line item stock adjustment is already prevented.
	 * @param \WC_Order_Item $item          Order item.
	 * @param int|float      $item_quantity Optional quantity to check against.
	 */
	public function prevent_core_line_item_product_stock_adjustment( $prevent, $item, $item_quantity ): bool {
		if ( $prevent || ! $item instanceof \WC_Order_Item_Product ) {
			return (bool) $prevent;
		}

		$order = $item->get_order();
		if ( ! $order instanceof \WC_Order ) {
			return false;
		}

		$location_slug = $this->get_configured_order_location_slug( $order );
		if ( ! $location_slug ) {
			return false;
		}

		if ( 'yes' !== get_option( 'woocommerce_manage_stock' ) ) {
			$this->delete_line_item_stock_adjustment_snapshot( $item );
			return true;
		}

		$change = $this->adjust_location_stock_for_line_item_quantity( $item, $location_slug, $item_quantity );
		if ( is_wp_error( $change ) ) {
			$order->add_order_note( $change->get_error_message(), 0, false, array( 'note_group' => OrderNoteGroup::ERROR ) );
			$order->save();
			$this->delete_line_item_stock_adjustment_snapshot( $item );
			return true;
		}

		if ( $change ) {
			$this->add_location_stock_order_note( $order, __( 'POS stock levels adjusted:', 'woocommerce' ), array( $change ) );
			$order->save();
		}

		$this->delete_line_item_stock_adjustment_snapshot( $item );

		return true;
	}

	/**
	 * Snapshot POS order items before classic admin saves mutate them.
	 *
	 * @param int   $order_id Order ID.
	 * @param array $items    Posted order item data.
	 */
	public function snapshot_location_order_items_before_admin_save( $order_id, $items ): void {
		unset( $items );

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order || ! $this->get_configured_order_location_slug( $order ) ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof \WC_Order_Item_Product ) {
				$this->snapshot_line_item_stock_adjustment_state( $item );
			}
		}
	}

	/**
	 * Clear request-local line item snapshots after classic admin saves finish.
	 *
	 * @param int   $order_id Order ID.
	 * @param array $items    Posted order item data.
	 */
	public function clear_location_order_item_snapshots( $order_id, $items ): void {
		unset( $items );

		unset( $this->line_item_stock_adjustment_snapshots[ absint( $order_id ) ] );
	}

	/**
	 * Snapshot a REST-updated line item before it is saved.
	 *
	 * @param \WC_Order_Item $item Order item.
	 * @param array          $data REST order item data.
	 */
	public function snapshot_location_order_item_before_rest_save( $item, $data ): void {
		unset( $data );

		if ( ! $item instanceof \WC_Order_Item_Product || ! $item->get_id() ) {
			return;
		}

		$stored_item = new \WC_Order_Item_Product( $item->get_id() );
		if ( $stored_item->get_id() ) {
			$this->snapshot_line_item_stock_adjustment_state( $stored_item );
		}
	}

	/**
	 * Get a configured inventory location for an order.
	 *
	 * @param \WC_Order $order Order object.
	 */
	private function get_configured_order_location_slug( \WC_Order $order ): ?string {
		$location_slug = $this->get_order_location_slug( $order );
		if ( ! $this->feature_is_enabled() || null === $location_slug || ! $this->location_is_configured( $location_slug ) ) {
			return null;
		}

		return $location_slug;
	}

	/**
	 * Get the inventory location for an order, if it routes to location stock.
	 *
	 * @param \WC_Order $order Order object.
	 */
	private function get_order_location_slug( \WC_Order $order ): ?string {
		$explicit_location = sanitize_title( (string) $order->get_meta( self::ORDER_LOCATION_META, true ) );
		if ( '' !== $explicit_location ) {
			return LocationStockService::LOCATION_POS === $explicit_location ? LocationStockService::LOCATION_POS : null;
		}

		return null;
	}

	/**
	 * Check whether a stock location has been configured.
	 *
	 * @param string $location_slug Location slug.
	 */
	private function location_is_configured( string $location_slug ): bool {
		return $this->location_stock_service->is_known_location_slug( $location_slug );
	}

	/**
	 * Reduce item-level location stock for an order item.
	 *
	 * @param \WC_Order              $order Order object.
	 * @param \WC_Order_Item_Product $item  Order item.
	 * @param string                 $location_slug Location slug.
	 * @return array{product:\WC_Product,from:float,to:float}|\WP_Error|null
	 */
	private function reduce_location_stock_for_order_item( \WC_Order $order, \WC_Order_Item_Product $item, string $location_slug ) {
		$product = $item->get_product();
		if ( ! $product instanceof \WC_Product || ! $product->managing_stock() || $this->item_location_stock_reduction_is_recorded( $item ) ) {
			return null;
		}

		/**
		 * Filter order item quantity.
		 *
		 * @since 4.5.0
		 *
		 * @param int|float              $quantity Quantity.
		 * @param \WC_Order              $order    Order data.
		 * @param \WC_Order_Item_Product $item     Order item data.
		 */
		$qty = wc_stock_amount( apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item ) );
		if ( $qty <= 0 ) {
			return null;
		}

		$change = $this->decrease_product_location_stock( $product, $location_slug, $qty );
		if ( is_wp_error( $change ) ) {
			return $change;
		}

		$item->add_meta_data( self::ITEM_LOCATION_STOCK_REDUCED_META, (string) $qty, true );
		$item->save();

		return $change;
	}

	/**
	 * Adjust item-level location stock after order item quantity changes.
	 *
	 * @param \WC_Order_Item_Product $item          Order item.
	 * @param string                 $location_slug Location slug.
	 * @param int|float              $item_quantity Optional quantity to check against.
	 * @return array{product:\WC_Product,from:float,to:float}|\WP_Error|false
	 */
	private function adjust_location_stock_for_line_item_quantity( \WC_Order_Item_Product $item, string $location_slug, $item_quantity = -1 ) {
		$order = $item->get_order();
		if ( ! $order instanceof \WC_Order ) {
			return false;
		}

		$product = $item->get_product();
		if ( ! $product instanceof \WC_Product || ! $product->managing_stock() ) {
			return false;
		}

		$item_quantity          = wc_stock_amount( $item_quantity >= 0 ? $item_quantity : $item->get_quantity() );
		$already_reduced_stock  = wc_stock_amount( $item->get_meta( self::ITEM_LOCATION_STOCK_REDUCED_META, true ) );
		$restock_refunded_items = wc_stock_amount( $item->get_meta( self::ITEM_LOCATION_STOCK_RESTOCKED_META, true ) );
		$diff                   = $item_quantity - $restock_refunded_items - $already_reduced_stock;

		if ( 0.0 === (float) $item_quantity ) {
			$diff = $already_reduced_stock * -1;
		}

		if ( 0.0 === (float) $diff ) {
			return false;
		}

		$change = $diff < 0
			? $this->increase_product_location_stock( $product, $location_slug, $diff * -1 )
			: $this->decrease_product_location_stock( $product, $location_slug, $diff );

		if ( is_wp_error( $change ) ) {
			if ( $diff > 0 ) {
				$this->restore_line_item_after_failed_location_stock_adjustment( $item );
			}

			return $change;
		}

		$reduced_stock_qty = $item_quantity - $restock_refunded_items;
		$item->update_meta_data( self::ITEM_LOCATION_STOCK_REDUCED_META, (string) $reduced_stock_qty );
		$item->save();

		if ( $reduced_stock_qty > 0 ) {
			$this->mark_order_location_stock_reduced( $order, $location_slug );
		} else {
			$this->clear_order_reduced_meta_if_done( $order );
		}

		return $change;
	}

	/**
	 * Restore item-level location stock reductions.
	 *
	 * @param \WC_Order $order               Order object.
	 * @param array     $line_items          Line item objects.
	 * @param string    $location_slug        Location slug.
	 * @param array     $refunded_line_items  Optional refunded quantities keyed by item ID.
	 * @return array<int,array{product:\WC_Product,from:float,to:float}>
	 */
	private function restore_location_stock_for_order_items( \WC_Order $order, array $line_items, string $location_slug, array $refunded_line_items = array() ): array {
		$changes = array();

		foreach ( $line_items as $item_id => $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product instanceof \WC_Product || ! $product->managing_stock() ) {
				continue;
			}

			$qty_to_restore = $this->get_item_location_stock_reduced_qty( $item );
			if ( isset( $refunded_line_items[ $item_id ], $refunded_line_items[ $item_id ]['qty'] ) ) {
				$qty_to_restore = min( $qty_to_restore, wc_stock_amount( $refunded_line_items[ $item_id ]['qty'] ) );
			}

			if ( $qty_to_restore <= 0 ) {
				continue;
			}

			$changes[] = $this->increase_product_location_stock( $product, $location_slug, $qty_to_restore );
			$this->update_item_reduced_location_stock_after_restore( $item, $qty_to_restore, ! empty( $refunded_line_items ) );
		}

		return $changes;
	}

	/**
	 * Decrease one product's location stock.
	 *
	 * @param \WC_Product $product       Product object.
	 * @param string      $location_slug Location slug.
	 * @param int|float   $qty           Quantity to reduce.
	 * @return array{product:\WC_Product,from:float,to:float}|\WP_Error
	 */
	private function decrease_product_location_stock( \WC_Product $product, string $location_slug, $qty ) {
		$qty       = wc_stock_amount( $qty );
		$new_stock = $this->location_stock_service->decrease_location_stock( $product, $location_slug, $qty );
		if ( null === $new_stock ) {
			return $this->get_insufficient_location_stock_error(
				$location_slug,
				$product->get_name(),
				$qty,
				$this->location_stock_service->get_location_stock( $product, $location_slug )
			);
		}

		return array(
			'product' => $product,
			'from'    => $new_stock + $qty,
			'to'      => $new_stock,
		);
	}

	/**
	 * Increase one product's location stock.
	 *
	 * @param \WC_Product $product       Product object.
	 * @param string      $location_slug Location slug.
	 * @param int|float   $qty           Quantity to increase.
	 * @return array{product:\WC_Product,from:float,to:float}
	 */
	private function increase_product_location_stock( \WC_Product $product, string $location_slug, $qty ): array {
		$qty       = wc_stock_amount( $qty );
		$new_stock = $this->location_stock_service->increase_location_stock( $product, $location_slug, $qty );

		return array(
			'product' => $product,
			'from'    => $new_stock - $qty,
			'to'      => $new_stock,
		);
	}

	/**
	 * Get an insufficient location stock error.
	 *
	 * @param string    $location_slug Location slug.
	 * @param string    $item_name     Name to display.
	 * @param int|float $requested     Requested quantity.
	 * @param int|float $available     Available quantity.
	 */
	private function get_insufficient_location_stock_error( string $location_slug, string $item_name, $requested, $available ): \WP_Error {
		return new \WP_Error(
			'woocommerce_location_stock_insufficient',
			sprintf(
				/* translators: 1: location name 2: item name 3: requested quantity 4: available quantity */
				__( 'Not enough stock at %1$s for %2$s. Requested %3$s, available %4$s.', 'woocommerce' ),
				$this->location_stock_service->get_location_name( $location_slug ),
				$item_name,
				wc_stock_amount( $requested ),
				wc_stock_amount( $available )
			),
			array()
		);
	}

	/**
	 * Mark an order as having location stock reduced.
	 *
	 * @param \WC_Order $order         Order object.
	 * @param string    $location_slug Location slug.
	 */
	private function mark_order_location_stock_reduced( \WC_Order $order, string $location_slug ): void {
		$order->update_meta_data( self::ORDER_LOCATION_META, $location_slug );
		$order->update_meta_data( self::ORDER_LOCATION_STOCK_REDUCED_META, 'yes' );
	}

	/**
	 * Delete order-level reduced meta when no line item location reductions remain.
	 *
	 * @param \WC_Order $order Order object.
	 */
	private function clear_order_reduced_meta_if_done( \WC_Order $order ): void {
		if ( ! $this->order_has_reduced_location_stock_items( $order ) ) {
			$order->delete_meta_data( self::ORDER_LOCATION_STOCK_REDUCED_META );
		}
	}

	/**
	 * Get an item's reduced location stock quantity.
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 */
	private function get_item_location_stock_reduced_qty( \WC_Order_Item_Product $item ): float {
		return wc_stock_amount( $item->get_meta( self::ITEM_LOCATION_STOCK_REDUCED_META, true ) );
	}

	/**
	 * Check whether location stock reduction meta has been written for an item.
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 */
	private function item_location_stock_reduction_is_recorded( \WC_Order_Item_Product $item ): bool {
		return '' !== $item->get_meta( self::ITEM_LOCATION_STOCK_REDUCED_META, true );
	}

	/**
	 * Snapshot one line item's saved state before stock-adjustable changes.
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 */
	private function snapshot_line_item_stock_adjustment_state( \WC_Order_Item_Product $item ): void {
		$order = $item->get_order();
		if ( ! $order instanceof \WC_Order || ! $this->get_configured_order_location_slug( $order ) || ! $item->get_id() ) {
			return;
		}

		$this->line_item_stock_adjustment_snapshots[ $order->get_id() ][ $item->get_id() ] = $this->get_line_item_stock_adjustment_snapshot( $item );
	}

	/**
	 * Get one line item's stock-adjustable state.
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 * @return array{name:string,quantity:int,tax_class:string,subtotal:string,total:string,taxes:array<string,mixed>}
	 */
	private function get_line_item_stock_adjustment_snapshot( \WC_Order_Item_Product $item ): array {
		return array(
			'name'      => $item->get_name( 'edit' ),
			'quantity'  => $item->get_quantity( 'edit' ),
			'tax_class' => $item->get_tax_class( 'edit' ),
			'subtotal'  => $item->get_subtotal( 'edit' ),
			'total'     => $item->get_total( 'edit' ),
			'taxes'     => $item->get_taxes( 'edit' ),
		);
	}

	/**
	 * Restore a line item to the state captured before a failed location stock adjustment.
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 */
	private function restore_line_item_after_failed_location_stock_adjustment( \WC_Order_Item_Product $item ): void {
		$snapshot = $this->get_line_item_stock_adjustment_snapshot_for_item( $item );
		if ( ! $snapshot ) {
			return;
		}

		$item->set_name( $snapshot['name'] );
		$item->set_quantity( $snapshot['quantity'] );
		$item->set_tax_class( $snapshot['tax_class'] );
		$item->set_subtotal( $snapshot['subtotal'] );
		$item->set_total( $snapshot['total'] );
		$item->set_taxes( $snapshot['taxes'] );
		$item->save();
	}

	/**
	 * Get a captured line item state.
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 * @return array{name:string,quantity:int,tax_class:string,subtotal:string,total:string,taxes:array<string,mixed>}|null
	 */
	private function get_line_item_stock_adjustment_snapshot_for_item( \WC_Order_Item_Product $item ): ?array {
		return $this->line_item_stock_adjustment_snapshots[ $item->get_order_id() ][ $item->get_id() ] ?? null;
	}

	/**
	 * Delete a captured line item state.
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 */
	private function delete_line_item_stock_adjustment_snapshot( \WC_Order_Item_Product $item ): void {
		unset( $this->line_item_stock_adjustment_snapshots[ $item->get_order_id() ][ $item->get_id() ] );
	}

	/**
	 * Update item meta after location stock is restored.
	 *
	 * @param \WC_Order_Item_Product $item            Order item.
	 * @param int|float              $qty_restored    Restored quantity.
	 * @param bool                   $restored_refund Whether this restore came from refund restocking.
	 */
	private function update_item_reduced_location_stock_after_restore( \WC_Order_Item_Product $item, $qty_restored, bool $restored_refund ): void {
		$remaining_reduced_stock = $this->get_item_location_stock_reduced_qty( $item ) - wc_stock_amount( $qty_restored );

		if ( $restored_refund || $remaining_reduced_stock > 0 ) {
			$item->update_meta_data( self::ITEM_LOCATION_STOCK_REDUCED_META, (string) $remaining_reduced_stock );
		} else {
			$item->delete_meta_data( self::ITEM_LOCATION_STOCK_REDUCED_META );
		}

		if ( $restored_refund ) {
			$restocked_refunds = wc_stock_amount( $item->get_meta( self::ITEM_LOCATION_STOCK_RESTOCKED_META, true ) );
			$item->update_meta_data( self::ITEM_LOCATION_STOCK_RESTOCKED_META, (string) ( $restocked_refunds + wc_stock_amount( $qty_restored ) ) );
		}

		$item->save();
	}

	/**
	 * Check whether the order still has item-level location stock reductions.
	 *
	 * @param \WC_Order $order Order object.
	 */
	private function order_has_reduced_location_stock_items( \WC_Order $order ): bool {
		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof \WC_Order_Item_Product && $this->get_item_location_stock_reduced_qty( $item ) > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add a location stock order note.
	 *
	 * @param \WC_Order $order   Order object.
	 * @param string    $prefix  Note prefix.
	 * @param array     $changes Stock changes.
	 * @phpstan-param array<int,array{product:\WC_Product,from:float,to:float}> $changes
	 */
	private function add_location_stock_order_note( \WC_Order $order, string $prefix, array $changes ): void {
		$order->add_order_note(
			$prefix . ' ' . implode( ', ', array_map( array( $this, 'format_stock_change' ), $changes ) ),
			0,
			false,
			array( 'note_group' => OrderNoteGroup::PRODUCT_STOCK )
		);
	}

	/**
	 * Format one stock change for order notes.
	 *
	 * @param array $change Stock change.
	 * @phpstan-param array{product:\WC_Product,from:float,to:float} $change
	 */
	private function format_stock_change( array $change ): string {
		return sprintf(
			'%1$s (%2$s&rarr;%3$s)',
			$change['product']->get_name(),
			$change['from'],
			$change['to']
		);
	}
}
