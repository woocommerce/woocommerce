<?php
/**
 * Product resource class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Resources;

use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Enums\ProductStatus;

defined( 'ABSPATH' ) || exit;

/**
 * The `product` resource and the fields that abilities return for a product.
 *
 * @since 11.4.0
 */
class ProductResource {

	public const NAME = 'product';

	/**
	 * Register the product and its fields, in output order.
	 *
	 * @since 11.4.0
	 */
	public static function register(): void {
		$price_schema = array(
			'type'        => 'string',
			'description' => __( 'Decimal price as a string, without a currency symbol.', 'woocommerce' ),
		);
		$string       = array( 'type' => 'string' );
		$boolean      = array( 'type' => 'boolean' );

		AbilityResources::register_resource( self::NAME );
		AbilityResources::register_field(
			self::NAME,
			'id',
			array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_id(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'name',
			array(
				'schema'       => $string,
				'get_callback' => static fn( \WC_Product $product ) => $product->get_name(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'slug',
			array(
				'schema'       => $string,
				'get_callback' => static fn( \WC_Product $product ) => $product->get_slug(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'permalink',
			array(
				'schema'       => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Product permalink, or null when no public permalink is available.', 'woocommerce' ),
					'format'      => 'uri',
				),
				'get_callback' => static fn( \WC_Product $product ) => false === $product->get_permalink() ? null : $product->get_permalink(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'type',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Internal product type slug, such as simple, external, grouped, or variable.', 'woocommerce' ),
					'enum'        => array_keys( wc_get_product_types() ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_type(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'status',
			array(
				'schema'       => array(
					'type' => 'string',
					'enum' => self::get_output_status_slugs(),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_status(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'sku',
			array(
				'schema'       => $string,
				'get_callback' => static fn( \WC_Product $product ) => $product->get_sku(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'currency',
			array(
				'schema'       => array(
					'type' => 'string',
					'enum' => array_keys( get_woocommerce_currencies() ),
				),
				'get_callback' => static fn() => get_woocommerce_currency(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'currency_symbol',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => html_entity_decode( get_woocommerce_currency_symbol( get_woocommerce_currency() ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'price',
			array(
				'schema'       => $price_schema,
				'get_callback' => static fn( \WC_Product $product ) => $product->get_price(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'regular_price',
			array(
				'schema'       => $price_schema,
				'get_callback' => static fn( \WC_Product $product ) => $product->get_regular_price(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'sale_price',
			array(
				'schema'       => $price_schema,
				'get_callback' => static fn( \WC_Product $product ) => $product->get_sale_price(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'stock_status',
			array(
				'schema'       => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_product_stock_status_options() ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_stock_status(),
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'stock_quantity',
			array(
				'schema'       => array(
					'type'        => array( self::get_stock_quantity_schema_type(), 'null' ),
					'description' => __( 'Current stock quantity, or null when no stock quantity is set.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => null === $product->get_stock_quantity() ? null : wc_stock_amount( $product->get_stock_quantity() ),
			)
		);
		foreach ( array( 'manage_stock', 'virtual', 'downloadable' ) as $prop ) {
			AbilityResources::register_field(
				self::NAME,
				$prop,
				array(
					'schema'       => $boolean,
					'get_callback' => static fn( \WC_Product $product ) => (bool) $product->{'get_' . $prop}(),
				)
			);
		}
		AbilityResources::register_field(
			self::NAME,
			'external_url',
			array(
				'schema'       => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'External product URL for external products.', 'woocommerce' ),
					'format'      => 'uri',
				),
				'get_callback' => static fn( \WC_Product $product ) => $product instanceof \WC_Product_External ? $product->get_product_url() : null,
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'button_text',
			array(
				'schema'       => array(
					'type'        => array( 'string', 'null' ),
					'description' => __( 'Button text for external products.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product instanceof \WC_Product_External ? $product->get_button_text() : null,
			)
		);
		AbilityResources::register_field(
			self::NAME,
			'grouped_products',
			array(
				'schema'       => array(
					'type'        => 'array',
					'description' => __( 'Product IDs included as children of a grouped product.', 'woocommerce' ),
					'items'       => array( 'type' => 'integer' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product instanceof \WC_Product_Grouped ? array_map( 'absint', $product->get_children( 'edit' ) ) : array(),
			)
		);
		$date_schema = array(
			'type'   => array( 'string', 'null' ),
			'format' => 'date-time',
		);
		foreach ( array( 'date_created', 'date_modified' ) as $prop ) {
			AbilityResources::register_field(
				self::NAME,
				$prop,
				array(
					'schema'       => $date_schema,
					'get_callback' => static fn( \WC_Product $product ) => wc_rest_prepare_date_response( $product->{'get_' . $prop}(), false ),
				)
			);
			AbilityResources::register_field(
				self::NAME,
				$prop . '_gmt',
				array(
					'schema'       => $date_schema,
					'get_callback' => static fn( \WC_Product $product ) => wc_rest_prepare_date_response( $product->{'get_' . $prop}() ),
				)
			);
		}
	}

	/**
	 * Post status slugs that a product can be set to.
	 *
	 * @since 11.4.0
	 *
	 * @return array<int, string>
	 */
	public static function get_mutation_status_slugs(): array {
		return array_merge(
			array_keys( get_post_statuses() ),
			array( ProductStatus::FUTURE )
		);
	}

	/**
	 * Every post status slug a product in the output can have.
	 *
	 * @since 11.4.0
	 *
	 * @return array<int, string>
	 */
	public static function get_output_status_slugs(): array {
		return array_values(
			array_unique(
				array_merge(
					self::get_mutation_status_slugs(),
					array( ProductStatus::AUTO_DRAFT, ProductStatus::TRASH )
				)
			)
		);
	}

	/**
	 * Schema type of a stock quantity, which is fractional when the stock amount filter allows it.
	 *
	 * @since 11.4.0
	 *
	 * @return string
	 */
	public static function get_stock_quantity_schema_type(): string {
		return wc_is_stock_amount_integer() ? 'integer' : 'number';
	}
}
