<?php
/**
 * Product ability trait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Enums\ProductStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Shared product helpers for WooCommerce domain ability definitions.
 */
trait ProductAbilityTrait {

	/**
	 * Get the shared product mutation input schema.
	 *
	 * @return array
	 */
	protected static function get_product_mutation_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'type'              => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_product_types() ),
				),
				'name'              => array( 'type' => 'string' ),
				'sku'               => array( 'type' => 'string' ),
				'regular_price'     => array( 'type' => 'string' ),
				'sale_price'        => array( 'type' => 'string' ),
				'description'       => array( 'type' => 'string' ),
				'short_description' => array( 'type' => 'string' ),
				'status'            => array(
					'type' => 'string',
					'enum' => self::get_product_mutation_status_slugs(),
				),
				'manage_stock'      => array( 'type' => 'boolean' ),
				'stock_quantity'    => array( 'type' => self::get_product_stock_quantity_schema_type() ),
				'stock_status'      => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_product_stock_status_options() ),
				),
				'virtual'           => array( 'type' => 'boolean' ),
				'downloadable'      => array( 'type' => 'boolean' ),
			),
			'required'             => array( 'name' ),
			'additionalProperties' => false,
		);
	}

	/**
	 * Allowed product post-status slugs for mutation abilities.
	 *
	 * Mirrors the REST products controller mutation enum.
	 *
	 * @return array<int, string>
	 */
	protected static function get_product_mutation_status_slugs(): array {
		return array_merge(
			array_keys( get_post_statuses() ),
			array( ProductStatus::FUTURE, ProductStatus::AUTO_DRAFT, ProductStatus::TRASH )
		);
	}

	/**
	 * Allowed product post-status slugs for query abilities.
	 *
	 * @return array<int, string>
	 */
	protected static function get_product_query_status_slugs(): array {
		return array_merge(
			array( ProductStatus::FUTURE, ProductStatus::TRASH ),
			array_keys( get_post_statuses() )
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
	 * Set supported product properties from ability input.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $input   Ability input.
	 */
	protected static function set_product_props_from_input( \WC_Product $product, array $input ): void {
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
					$value = wc_clean( $value );
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
	protected static function format_product_for_response( \WC_Product $product ): array {
		$stock_quantity = $product->get_stock_quantity();

		return array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'slug'              => $product->get_slug(),
			'permalink'         => $product->get_permalink(),
			'type'              => $product->get_type(),
			'status'            => $product->get_status(),
			'sku'               => $product->get_sku(),
			'currency'          => get_woocommerce_currency(),
			'currency_symbol'   => html_entity_decode(
				get_woocommerce_currency_symbol(),
				ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
			),
			'price'             => $product->get_price(),
			'regular_price'     => $product->get_regular_price(),
			'sale_price'        => $product->get_sale_price(),
			'stock_status'      => $product->get_stock_status(),
			'stock_quantity'    => null === $stock_quantity ? null : wc_stock_amount( $stock_quantity ),
			'manage_stock'      => (bool) $product->get_manage_stock(),
			'virtual'           => (bool) $product->get_virtual(),
			'downloadable'      => (bool) $product->get_downloadable(),
			'date_created'      => wc_rest_prepare_date_response( $product->get_date_created(), false ),
			'date_created_gmt'  => wc_rest_prepare_date_response( $product->get_date_created() ),
			'date_modified'     => wc_rest_prepare_date_response( $product->get_date_modified(), false ),
			'date_modified_gmt' => wc_rest_prepare_date_response( $product->get_date_modified() ),
		);
	}

	/**
	 * Get the schema for a single product in a response.
	 *
	 * @return array
	 */
	protected static function get_product_output_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'                => array( 'type' => 'integer' ),
				'name'              => array( 'type' => 'string' ),
				'slug'              => array( 'type' => 'string' ),
				'permalink'         => array(
					'type'   => 'string',
					'format' => 'uri',
				),
				'type'              => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_product_types() ),
				),
				'status'            => array(
					'type' => 'string',
					'enum' => self::get_product_mutation_status_slugs(),
				),
				'sku'               => array( 'type' => 'string' ),
				'currency'          => array(
					'type' => 'string',
					'enum' => array_keys( get_woocommerce_currencies() ),
				),
				'currency_symbol'   => array( 'type' => 'string' ),
				'price'             => array( 'type' => 'string' ),
				'regular_price'     => array( 'type' => 'string' ),
				'sale_price'        => array( 'type' => 'string' ),
				'stock_status'      => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_product_stock_status_options() ),
				),
				'stock_quantity'    => array(
					'type' => array( self::get_product_stock_quantity_schema_type(), 'null' ),
				),
				'manage_stock'      => array( 'type' => 'boolean' ),
				'virtual'           => array( 'type' => 'boolean' ),
				'downloadable'      => array( 'type' => 'boolean' ),
				'date_created'      => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
				'date_created_gmt'  => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
				'date_modified'     => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
				'date_modified_gmt' => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Get the schema type for product stock quantities.
	 *
	 * WooCommerce stock quantities can support fractional values when the
	 * stock amount filter is configured to return non-integer amounts.
	 *
	 * @return string
	 */
	protected static function get_product_stock_quantity_schema_type(): string {
		return wc_is_stock_amount_integer() ? 'integer' : 'number';
	}
}
