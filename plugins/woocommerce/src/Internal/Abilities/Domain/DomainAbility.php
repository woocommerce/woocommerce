<?php
/**
 * Domain ability base class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helpers for WooCommerce domain ability definitions.
 */
abstract class DomainAbility {

	/**
	 * Get common domain metadata.
	 *
	 * @param string $operation   Operation group.
	 * @param bool   $is_readonly Whether the ability is readonly.
	 * @param bool   $idempotent  Whether the ability is idempotent.
	 * @param bool   $destructive Whether the ability can mutate data.
	 * @return array
	 */
	protected static function get_domain_meta( string $operation, bool $is_readonly, bool $idempotent, bool $destructive ): array {
		return array(
			'show_in_rest'                  => true,
			'mcp'                           => array(
				'public' => true,
				'type'   => 'tool',
			),
			'woocommerce_ability_source'    => 'domain-api',
			'woocommerce_ability_operation' => $operation,
			'annotations'                   => array(
				'readonly'    => $is_readonly,
				'idempotent'  => $idempotent,
				'destructive' => $destructive,
			),
		);
	}

	/**
	 * Get a collection output schema.
	 *
	 * @param string $collection_key Collection property key.
	 * @return array
	 */
	protected static function get_collection_output_schema( string $collection_key ): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				$collection_key => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'additionalProperties' => true,
					),
				),
				'total'         => array( 'type' => 'integer' ),
				'page'          => array( 'type' => 'integer' ),
				'per_page'      => array( 'type' => 'integer' ),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get an entity output schema.
	 *
	 * @param string $entity_key Entity property key.
	 * @return array
	 */
	protected static function get_entity_output_schema( string $entity_key ): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				$entity_key => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get a delete output schema.
	 *
	 * @return array
	 */
	protected static function get_delete_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'deleted' => array( 'type' => 'boolean' ),
				'id'      => array( 'type' => 'integer' ),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get an order note output schema.
	 *
	 * @return array
	 */
	protected static function get_order_note_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'note_id' => array( 'type' => 'integer' ),
				'order'   => array(
					'type'                 => 'object',
					'additionalProperties' => true,
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the shared product mutation input schema.
	 *
	 * @return array
	 */
	protected static function get_product_mutation_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'type'              => array( 'type' => 'string' ),
				'name'              => array( 'type' => 'string' ),
				'sku'               => array( 'type' => 'string' ),
				'regular_price'     => array( 'type' => 'string' ),
				'sale_price'        => array( 'type' => 'string' ),
				'description'       => array( 'type' => 'string' ),
				'short_description' => array( 'type' => 'string' ),
				'status'            => array( 'type' => 'string' ),
				'manage_stock'      => array( 'type' => 'boolean' ),
				'stock_quantity'    => array( 'type' => 'integer' ),
				'stock_status'      => array( 'type' => 'string' ),
				'virtual'           => array( 'type' => 'boolean' ),
				'downloadable'      => array( 'type' => 'boolean' ),
			),
			'required'             => array( 'name' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get a product from ability input.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Product|\WP_Error
	 */
	protected static function get_product_from_input( array $input ) {
		if ( empty( $input['id'] ) ) {
			return new \WP_Error(
				'woocommerce_product_id_required',
				__( 'Product ID is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$product = wc_get_product( absint( $input['id'] ) );

		if ( ! $product ) {
			return new \WP_Error(
				'woocommerce_product_not_found',
				__( 'Product not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		return $product;
	}

	/**
	 * Get an order from ability input.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order|\WP_Error
	 */
	protected static function get_order_from_input( array $input ) {
		if ( empty( $input['id'] ) ) {
			return new \WP_Error(
				'woocommerce_order_id_required',
				__( 'Order ID is required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$order = wc_get_order( absint( $input['id'] ) );

		if ( ! $order instanceof \WC_Order ) {
			return new \WP_Error(
				'woocommerce_order_not_found',
				__( 'Order not found.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}

		return $order;
	}

	/**
	 * Set supported product properties from ability input.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $input   Ability input.
	 */
	protected static function set_product_props( \WC_Product $product, array $input ): void {
		$setters = array(
			'name'              => 'set_name',
			'sku'               => 'set_sku',
			'regular_price'     => 'set_regular_price',
			'sale_price'        => 'set_sale_price',
			'description'       => 'set_description',
			'short_description' => 'set_short_description',
			'status'            => 'set_status',
			'manage_stock'      => 'set_manage_stock',
			'stock_quantity'    => 'set_stock_quantity',
			'stock_status'      => 'set_stock_status',
			'virtual'           => 'set_virtual',
			'downloadable'      => 'set_downloadable',
		);

		foreach ( $setters as $field => $setter ) {
			if ( array_key_exists( $field, $input ) && is_callable( array( $product, $setter ) ) ) {
				$value = $input[ $field ];

				if ( is_string( $value ) ) {
					$value = wc_clean( wp_unslash( $value ) );
				}

				$product->{$setter}( $value );
			}
		}
	}

	/**
	 * Format a product for ability output.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array
	 */
	protected static function format_product( \WC_Product $product ): array {
		return array(
			'id'             => $product->get_id(),
			'name'           => $product->get_name(),
			'slug'           => $product->get_slug(),
			'permalink'      => $product->get_permalink(),
			'type'           => $product->get_type(),
			'status'         => $product->get_status(),
			'sku'            => $product->get_sku(),
			'price'          => $product->get_price(),
			'regular_price'  => $product->get_regular_price(),
			'sale_price'     => $product->get_sale_price(),
			'stock_status'   => $product->get_stock_status(),
			'stock_quantity' => null === $product->get_stock_quantity() ? null : (int) $product->get_stock_quantity(),
			'manage_stock'   => (bool) $product->get_manage_stock(),
			'virtual'        => (bool) $product->get_virtual(),
			'downloadable'   => (bool) $product->get_downloadable(),
			'date_created'   => self::format_datetime( $product->get_date_created() ),
			'date_modified'  => self::format_datetime( $product->get_date_modified() ),
		);
	}

	/**
	 * Format an order for ability output.
	 *
	 * @param \WC_Order $order              Order object.
	 * @param bool      $include_line_items Whether to include line items.
	 * @return array
	 */
	protected static function format_order( \WC_Order $order, bool $include_line_items ): array {
		$data = array(
			'id'                   => $order->get_id(),
			'status'               => $order->get_status(),
			'currency'             => $order->get_currency(),
			'total'                => $order->get_total(),
			'customer_id'          => $order->get_customer_id(),
			'billing_email'        => $order->get_billing_email(),
			'payment_method'       => $order->get_payment_method(),
			'payment_method_title' => $order->get_payment_method_title(),
			'date_created'         => self::format_datetime( $order->get_date_created() ),
			'date_modified'        => self::format_datetime( $order->get_date_modified() ),
		);

		if ( $include_line_items ) {
			$data['line_items'] = array();

			foreach ( $order->get_items() as $item ) {
				if ( ! $item instanceof \WC_Order_Item_Product ) {
					continue;
				}

				$data['line_items'][] = array(
					'id'           => $item->get_id(),
					'name'         => $item->get_name(),
					'product_id'   => $item->get_product_id(),
					'variation_id' => $item->get_variation_id(),
					'quantity'     => $item->get_quantity(),
					'subtotal'     => $item->get_subtotal(),
					'total'        => $item->get_total(),
				);
			}
		}

		return $data;
	}

	/**
	 * Format a WooCommerce datetime.
	 *
	 * @param \WC_DateTime|null $datetime Date/time value.
	 * @return string|null
	 */
	protected static function format_datetime( ?\WC_DateTime $datetime ): ?string {
		return $datetime ? $datetime->date( DATE_ATOM ) : null;
	}

	/**
	 * Get an ID value from ability input.
	 *
	 * @param mixed $input Ability input.
	 * @return int
	 */
	protected static function get_input_id( $input ): int {
		return is_array( $input ) && ! empty( $input['id'] ) ? absint( $input['id'] ) : 0;
	}

	/**
	 * Sanitize a per-page value.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	protected static function sanitize_per_page( $value ): int {
		return min( 100, max( 1, absint( $value ) ) );
	}

	/**
	 * Sanitize a scalar string input value.
	 *
	 * @param mixed $value Raw input value.
	 * @return string
	 */
	protected static function sanitize_string( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$clean = wc_clean( wp_unslash( (string) $value ) );

		return is_string( $clean ) ? $clean : '';
	}

	/**
	 * Check whether an ability is already registered.
	 *
	 * @param string $ability_id Ability ID.
	 * @return bool
	 */
	protected static function has_ability( string $ability_id ): bool {
		return function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_id );
	}
}
