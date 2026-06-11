<?php
/**
 * LocationStockRestApiHooks class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Inventory;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks POS location stock into existing WooCommerce REST and POS catalog responses.
 *
 * @internal
 */
class LocationStockRestApiHooks {

	private const LOCATION_STOCK_REST_FIELD = 'location_stock';

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
	 * Initialize dependencies.
	 *
	 * @internal
	 */
	final public function init( FeaturesController $features_controller, LocationStockService $location_stock_service ): void {
		$this->features_controller   = $features_controller;
		$this->location_stock_service = $location_stock_service;
	}

	/**
	 * Register REST API hooks.
	 */
	public function register(): void {
		add_filter( 'woocommerce_rest_pre_insert_shop_order_object', array( $this, 'prepare_rest_order_location_stock' ), 10, 3 );
		add_action( 'rest_api_init', array( $this, 'register_product_location_stock_rest_fields' ) );
		add_filter( 'woocommerce_pos_catalog_map_product', array( $this, 'add_location_stock_to_pos_catalog_product' ), 10, 3 );

		if ( did_action( 'rest_api_init' ) ) {
			$this->register_product_location_stock_rest_fields();
		}
	}

	/**
	 * Persist and validate REST order POS stock routing before stock is reduced.
	 *
	 * @param \WC_Order        $order    Order object.
	 * @param \WP_REST_Request $request REST request.
	 * @param bool             $creating Whether the order is being created.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WC_Order|\WP_Error
	 */
	public function prepare_rest_order_location_stock( $order, $request, $creating ) {
		if ( ! $this->feature_is_enabled() ) {
			return $order;
		}

		if ( ! $creating || ! $order instanceof \WC_Order || ! $request instanceof \WP_REST_Request ) {
			return $order;
		}

		$location_slug = $this->get_rest_request_location_slug( $order, $request );
		if ( is_wp_error( $location_slug ) ) {
			return $location_slug;
		}

		if ( null === $location_slug ) {
			return $order;
		}

		$order->update_meta_data( InventoryController::ORDER_LOCATION_META, $location_slug );

		return $this->validate_order_has_location_stock( $order, $location_slug );
	}

	/**
	 * Register location stock REST fields on product responses.
	 */
	public function register_product_location_stock_rest_fields(): void {
		register_rest_field(
			array( 'product', 'product_variation' ),
			self::LOCATION_STOCK_REST_FIELD,
			array(
				'get_callback' => array( $this, 'get_product_location_stock_rest_field' ),
				'schema'       => $this->get_product_location_stock_rest_field_schema(),
			)
		);
	}

	/**
	 * Get the product location stock REST field value.
	 *
	 * @param array $object Prepared REST object data.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_product_location_stock_rest_field( array $object ): array {
		$product = wc_get_product( absint( $object['id'] ?? 0 ) );
		if ( ! $this->feature_is_enabled() || ! $product instanceof \WC_Product ) {
			return array();
		}

		$location_stock = $this->get_product_location_stock_response_item( $product, LocationStockService::LOCATION_POS );

		return empty( $location_stock ) ? array() : array( $location_stock );
	}

	/**
	 * Add POS location stock to POS catalog rows.
	 *
	 * @param array            $row     Mapped catalog product row.
	 * @param \WC_Product      $product Product object.
	 * @param \WP_REST_Request $request REST request used to prepare the mapped data.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return array
	 */
	public function add_location_stock_to_pos_catalog_product( array $row, \WC_Product $product, \WP_REST_Request $request ): array {
		if ( ! $this->can_manage_pos_location_stock() || ! isset( $row['data'] ) || ! is_array( $row['data'] ) || ! $this->catalog_request_includes_location_stock( $request ) ) {
			return $row;
		}

		if ( ! empty( $row['data'][ self::LOCATION_STOCK_REST_FIELD ] ) ) {
			return $row;
		}

		$location_stock = $this->get_product_location_stock_response_item( $product, LocationStockService::LOCATION_POS );

		$row['data'][ self::LOCATION_STOCK_REST_FIELD ] = empty( $location_stock ) ? array() : array( $location_stock );

		return $row;
	}

	/**
	 * Check whether the POS location stock feature flag is enabled.
	 */
	private function feature_is_enabled(): bool {
		return $this->features_controller->feature_is_enabled( InventoryController::FEATURE_ID );
	}

	/**
	 * Check whether POS location stock can be managed.
	 */
	private function can_manage_pos_location_stock(): bool {
		return $this->feature_is_enabled() && $this->location_is_configured( LocationStockService::LOCATION_POS );
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
	 * Get the REST-requested inventory location slug.
	 *
	 * @param \WC_Order        $order   Order object.
	 * @param \WP_REST_Request $request Request object.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return string|\WP_Error|null
	 */
	private function get_rest_request_location_slug( \WC_Order $order, \WP_REST_Request $request ) {
		$requested_location = $request->get_param( 'inventory_location' );
		if ( null !== $requested_location ) {
			return $this->validate_rest_location_slug( $requested_location );
		}

		if ( $this->location_is_configured( LocationStockService::LOCATION_POS ) && $this->is_pos_created_via( $request->get_param( 'created_via' ) ?: $order->get_created_via() ) ) {
			return LocationStockService::LOCATION_POS;
		}

		return null;
	}

	/**
	 * Validate an explicit REST inventory location request value.
	 *
	 * @param mixed $location Location request value.
	 * @return string|\WP_Error
	 */
	private function validate_rest_location_slug( $location ) {
		$location_slug = is_scalar( $location ) ? sanitize_title( wp_unslash( (string) $location ) ) : '';
		if ( LocationStockService::LOCATION_POS === $location_slug && $this->location_is_configured( $location_slug ) ) {
			return LocationStockService::LOCATION_POS;
		}

		return new \WP_Error(
			'woocommerce_rest_invalid_inventory_location',
			sprintf(
				/* translators: %s inventory location slug. */
				__( 'Inventory location "%s" is not available.', 'woocommerce' ),
				$location_slug
			),
			array( 'status' => 400 )
		);
	}

	/**
	 * Check whether a created_via value identifies POS.
	 *
	 * @param mixed $created_via Order created_via value.
	 */
	private function is_pos_created_via( $created_via ): bool {
		if ( ! is_scalar( $created_via ) ) {
			return false;
		}

		return in_array( (string) $created_via, array( 'point-of-sale', 'pos-rest-api' ), true );
	}

	/**
	 * Validate all managed-stock order items against location stock.
	 *
	 * @param \WC_Order $order         Order object.
	 * @param string    $location_slug Location slug.
	 * @return \WC_Order|\WP_Error
	 */
	private function validate_order_has_location_stock( \WC_Order $order, string $location_slug ) {
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product instanceof \WC_Product || ! $product->managing_stock() ) {
				continue;
			}

			$requested = wc_stock_amount( $item->get_quantity() );
			$available = $this->location_stock_service->get_location_stock( $product, $location_slug );
			if ( $requested > $available ) {
				return $this->get_insufficient_location_stock_error( $location_slug, $product->get_name(), $requested, $available );
			}
		}

		return $order;
	}

	/**
	 * Get an insufficient location stock REST error.
	 *
	 * @param string    $location_slug Location slug.
	 * @param string    $item_name     Name to display.
	 * @param int|float $requested     Requested quantity.
	 * @param int|float $available     Available quantity.
	 */
	private function get_insufficient_location_stock_error( string $location_slug, string $item_name, $requested, $available ): \WP_Error {
		return new \WP_Error(
			'woocommerce_rest_location_stock_insufficient',
			sprintf(
				/* translators: 1: location name 2: item name 3: requested quantity 4: available quantity */
				__( 'Not enough stock at %1$s for %2$s. Requested %3$s, available %4$s.', 'woocommerce' ),
				$this->location_stock_service->get_location_name( $location_slug ),
				$item_name,
				wc_stock_amount( $requested ),
				wc_stock_amount( $available )
			),
			array( 'status' => 400 )
		);
	}

	/**
	 * Get one location stock REST response item for a product.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array<string,mixed>
	 */
	private function get_product_location_stock_response_item( \WC_Product $product, string $location_slug ): array {
		if ( ! $product->managing_stock() ) {
			return array();
		}

		$location = $this->location_stock_service->get_location( $location_slug );
		if ( ! $location ) {
			return array();
		}

		$quantity = $this->location_stock_service->get_location_stock( $product, $location_slug );

		return array(
			'slug'         => $location['slug'],
			'name'         => $location['name'],
			'quantity'     => $quantity,
			'stock_status' => $this->get_location_stock_status( $quantity ),
		);
	}

	/**
	 * Get the stock status for a location stock quantity.
	 *
	 * @param int|float $quantity Location stock quantity.
	 */
	private function get_location_stock_status( $quantity ): string {
		return (float) wc_stock_amount( $quantity ) > 0.0 ? ProductStockStatus::IN_STOCK : ProductStockStatus::OUT_OF_STOCK;
	}

	/**
	 * Determine whether a POS catalog request includes location_stock.
	 *
	 * @param \WP_REST_Request $request REST request used to prepare the mapped data.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	private function catalog_request_includes_location_stock( \WP_REST_Request $request ): bool {
		$fields = $request->get_param( '_fields' );
		if ( null === $fields || array() === $fields || '' === $fields ) {
			return true;
		}

		foreach ( wp_parse_list( $fields ) as $field ) {
			$field = trim( (string) $field );
			if ( self::LOCATION_STOCK_REST_FIELD === $field || 0 === strpos( $field, self::LOCATION_STOCK_REST_FIELD . '.' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the location stock REST field schema.
	 *
	 * @return array<string,mixed>
	 */
	private function get_product_location_stock_rest_field_schema(): array {
		$stock_amount_type = wc_is_stock_amount_integer() ? 'integer' : 'number';

		return array(
			'description' => __( 'Stock data grouped by inventory location.', 'woocommerce' ),
			'type'        => 'array',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'slug'         => array(
						'description' => __( 'Inventory location slug.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'name'         => array(
						'description' => __( 'Inventory location name.', 'woocommerce' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'quantity'     => array(
						'description' => __( 'Stock quantity at this inventory location.', 'woocommerce' ),
						'type'        => $stock_amount_type,
						'context'     => array( 'view', 'edit' ),
					),
					'stock_status' => array(
						'description' => __( 'Stock status at this inventory location.', 'woocommerce' ),
						'type'        => 'string',
						'enum'        => array(
							ProductStockStatus::IN_STOCK,
							ProductStockStatus::OUT_OF_STOCK,
						),
						'context'     => array( 'view', 'edit' ),
					),
				),
			),
		);
	}
}
