<?php
/**
 * ProductResource class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Resources;

use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductTaxStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Fields returned by the resource abilities.
 *
 * @since 11.4.0
 */
class ProductResource {

	use DomainAbilityHelpers;

	public const NAME = 'product';

	/**
	 * Register product fields.
	 */
	public static function register(): void {
		AbilityResources::register_resource( self::NAME );

		AbilityResources::register_field(
			'product',
			'id',
			array(
				'schema'       => array( 'type' => 'integer' ),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_id(),
			)
		);

		AbilityResources::register_field(
			'product',
			'name',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_name(),
			)
		);

		AbilityResources::register_field(
			'product',
			'slug',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_slug(),
			)
		);

		AbilityResources::register_field(
			'product',
			'permalink',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Product permalink. Omitted when no public permalink is available.', 'woocommerce' ),
					'format'      => 'uri',
				),
				'get_callback' => static fn( \WC_Product $product ) => false === $product->get_permalink() ? null : $product->get_permalink(),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'product',
			'type',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Internal product type slug, such as simple, external, grouped, variable or variation.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_type(),
			)
		);

		AbilityResources::register_field(
			'product',
			'status',
			array(
				'schema'       => array(
					'type' => 'string',
					'enum' => self::get_product_output_status_slugs(),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_status(),
			)
		);

		AbilityResources::register_field(
			'product',
			'sku',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_sku(),
			)
		);

		AbilityResources::register_field(
			'product',
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
			'product',
			'currency_symbol',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn() => html_entity_decode(
					get_woocommerce_currency_symbol(),
					ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
				),
			)
		);

		AbilityResources::register_field(
			'product',
			'price',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Decimal price as a string, without a currency symbol.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => self::format_amount_for_response( $product->get_price() ),
			)
		);

		AbilityResources::register_field(
			'product',
			'regular_price',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Decimal price as a string, without a currency symbol.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => self::format_amount_for_response( $product->get_regular_price() ),
			)
		);

		AbilityResources::register_field(
			'product',
			'sale_price',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Decimal price as a string, without a currency symbol.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => self::format_amount_for_response( $product->get_sale_price() ),
			)
		);

		AbilityResources::register_field(
			'product',
			'on_sale',
			array(
				'schema'       => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the sale price applies now, taking the sale dates into account.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => (bool) $product->is_on_sale(),
			)
		);

		AbilityResources::register_field(
			'product',
			'date_on_sale_from',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Start of the sale in the store timezone. Omitted when the sale has no start date.', 'woocommerce' ),
					'format'      => 'date-time',
				),
				'get_callback' => static fn( \WC_Product $product ) => self::format_date_for_response( $product->get_date_on_sale_from(), false ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'product',
			'date_on_sale_to',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'End of the sale in the store timezone. Omitted when the sale has no end date.', 'woocommerce' ),
					'format'      => 'date-time',
				),
				'get_callback' => static fn( \WC_Product $product ) => self::format_date_for_response( $product->get_date_on_sale_to(), false ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'product',
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
			'product',
			'stock_quantity',
			array(
				'schema'       => array(
					'type'        => self::get_product_stock_quantity_schema_type(),
					'description' => __( 'Current stock quantity. Omitted when no stock quantity is set.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => null === $product->get_stock_quantity() ? null : wc_stock_amount( $product->get_stock_quantity() ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'product',
			'manage_stock',
			array(
				'schema'       => array( 'type' => 'boolean' ),
				'get_callback' => static fn( \WC_Product $product ) => (bool) $product->get_manage_stock(),
			)
		);

		AbilityResources::register_field(
			'product',
			'sold_individually',
			array(
				'schema'       => array( 'type' => 'boolean' ),
				'get_callback' => static fn( \WC_Product $product ) => (bool) $product->get_sold_individually(),
			)
		);

		AbilityResources::register_field(
			'product',
			'virtual',
			array(
				'schema'       => array( 'type' => 'boolean' ),
				'get_callback' => static fn( \WC_Product $product ) => (bool) $product->get_virtual(),
			)
		);

		AbilityResources::register_field(
			'product',
			'downloadable',
			array(
				'schema'       => array( 'type' => 'boolean' ),
				'get_callback' => static fn( \WC_Product $product ) => (bool) $product->get_downloadable(),
			)
		);

		AbilityResources::register_field(
			'product',
			'external_url',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'External product URL. Omitted when the product is not external or no URL is set.', 'woocommerce' ),
					'format'      => 'uri',
				),
				'get_callback' => static fn( \WC_Product $product ) => $product instanceof \WC_Product_External && '' !== $product->get_product_url() ? $product->get_product_url() : null,
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'product',
			'button_text',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Button text. Omitted when the product is not external or no button text is set.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product instanceof \WC_Product_External && '' !== $product->get_button_text() ? $product->get_button_text() : null,
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'product',
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

		AbilityResources::register_field(
			'product',
			'parent_id',
			array(
				'schema'       => array(
					'type'        => 'integer',
					'description' => __( 'ID of the variable product of a variation, or 0.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => (int) $product->get_parent_id(),
			)
		);

		AbilityResources::register_field(
			'product',
			'attributes',
			array(
				'schema'       => array(
					'type'        => 'array',
					'description' => __( 'Attributes with their options on a product, or the option each attribute picks on a variation. For a global attribute the options are term slugs.', 'woocommerce' ),
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'id'        => array(
								'type'        => 'integer',
								'description' => __( 'ID of the global attribute, or 0 for an attribute of this product only.', 'woocommerce' ),
							),
							'name'      => array( 'type' => 'string' ),
							'options'   => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'option'    => array(
								'type'        => 'string',
								'description' => __( 'The option a variation picks. Omitted when the variation matches any option.', 'woocommerce' ),
							),
							'visible'   => array( 'type' => 'boolean' ),
							'variation' => array(
								'type'        => 'boolean',
								'description' => __( 'Whether the variations pick an option of this attribute.', 'woocommerce' ),
							),
						),
						'required'             => array( 'name' ),
						'additionalProperties' => false,
					),
				),
				'get_callback' => static fn( \WC_Product $product ) => self::format_product_attributes( $product ),
			)
		);

		AbilityResources::register_field(
			'product',
			'categories',
			array(
				'schema'       => self::get_product_terms_output_schema(),
				'get_callback' => static fn( \WC_Product $product ) => self::format_product_terms_for_response( $product->get_category_ids( 'edit' ), 'product_cat' ),
			)
		);

		AbilityResources::register_field(
			'product',
			'tags',
			array(
				'schema'       => self::get_product_terms_output_schema(),
				'get_callback' => static fn( \WC_Product $product ) => self::format_product_terms_for_response( $product->get_tag_ids( 'edit' ), 'product_tag' ),
			)
		);

		AbilityResources::register_field(
			'product',
			'brands',
			array(
				'schema'       => self::get_product_terms_output_schema(),
				'get_callback' => static fn( \WC_Product $product ) => self::format_product_terms_for_response( $product->get_brand_ids( 'edit' ), 'product_brand' ),
			)
		);

		AbilityResources::register_field(
			'product',
			'catalog_visibility',
			array(
				'schema'       => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_product_visibility_options() ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_catalog_visibility(),
			)
		);

		AbilityResources::register_field(
			'product',
			'featured',
			array(
				'schema'       => array( 'type' => 'boolean' ),
				'get_callback' => static fn( \WC_Product $product ) => (bool) $product->get_featured(),
			)
		);

		AbilityResources::register_field(
			'product',
			'description',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_description(),
			)
		);

		AbilityResources::register_field(
			'product',
			'short_description',
			array(
				'schema'       => array( 'type' => 'string' ),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_short_description(),
			)
		);

		AbilityResources::register_field(
			'product',
			'images',
			array(
				'schema'       => array(
					'type'        => 'array',
					'description' => __( 'Featured image first, then the gallery.', 'woocommerce' ),
					'items'       => array(
						'type'                 => 'object',
						'properties'           => array(
							'id'   => array( 'type' => 'integer' ),
							'src'  => array(
								'type'   => 'string',
								'format' => 'uri',
							),
							'name' => array( 'type' => 'string' ),
							'alt'  => array( 'type' => 'string' ),
						),
						'additionalProperties' => false,
					),
				),
				'get_callback' => static fn( \WC_Product $product ) => self::format_product_images_for_response( $product ),
			)
		);

		AbilityResources::register_field(
			'product',
			'weight',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => sprintf(
						/* translators: %s: weight unit, such as kg. */
						__( 'Decimal string in %s, or empty when not set.', 'woocommerce' ),
						get_option( 'woocommerce_weight_unit' )
					),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_weight(),
			)
		);

		AbilityResources::register_field(
			'product',
			'dimensions',
			array(
				'schema'       => array(
					'type'                 => 'object',
					'description'          => sprintf(
						/* translators: %s: dimension unit, such as cm. */
						__( 'Decimal strings in %s, each empty when not set.', 'woocommerce' ),
						get_option( 'woocommerce_dimension_unit' )
					),
					'properties'           => array(
						'length' => array( 'type' => 'string' ),
						'width'  => array( 'type' => 'string' ),
						'height' => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
				'get_callback' => static fn( \WC_Product $product ) => array(
					'length' => $product->get_length(),
					'width'  => $product->get_width(),
					'height' => $product->get_height(),
				),
			)
		);

		AbilityResources::register_field(
			'product',
			'shipping_required',
			array(
				'schema'       => array( 'type' => 'boolean' ),
				'get_callback' => static fn( \WC_Product $product ) => (bool) $product->needs_shipping(),
			)
		);

		AbilityResources::register_field(
			'product',
			'shipping_class',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Shipping class slug, or empty when the product has none.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_shipping_class(),
			)
		);

		AbilityResources::register_field(
			'product',
			'shipping_class_id',
			array(
				'schema'       => array(
					'type'        => 'integer',
					'description' => __( 'Shipping class term ID, or 0 when the product has none.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_shipping_class_id(),
			)
		);

		AbilityResources::register_field(
			'product',
			'tax_status',
			array(
				'schema'       => array(
					'type' => 'string',
					'enum' => array( ProductTaxStatus::TAXABLE, ProductTaxStatus::SHIPPING, ProductTaxStatus::NONE ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_tax_status(),
			)
		);

		AbilityResources::register_field(
			'product',
			'tax_class',
			array(
				'schema'       => array(
					'type'        => 'string',
					'description' => __( 'Tax class slug, or empty for the standard rate.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_tax_class(),
			)
		);

		AbilityResources::register_field(
			'product',
			'backorders',
			array(
				'schema'       => array(
					'type' => 'string',
					'enum' => array_keys( wc_get_product_backorder_options() ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product->get_backorders(),
			)
		);

		AbilityResources::register_field(
			'product',
			'low_stock_amount',
			array(
				'schema'       => array(
					'type'        => self::get_product_stock_quantity_schema_type(),
					'description' => __( 'Low stock threshold of the product. Omitted when the store-wide threshold applies.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => is_numeric( $product->get_low_stock_amount() ) ? wc_stock_amount( (float) $product->get_low_stock_amount() ) : null,
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'product',
			'variation_ids',
			array(
				'schema'       => array(
					'type'        => 'array',
					'description' => __( 'IDs of the variations of a variable product.', 'woocommerce' ),
					'items'       => array( 'type' => 'integer' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => $product instanceof \WC_Product_Variable ? self::read_variation_children( $product, false )['all'] : array(),
			)
		);

		AbilityResources::register_field(
			'product',
			'date_created',
			array(
				'schema'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Product creation date in the store timezone. Omitted when no creation date is available.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => self::format_date_for_response( $product->get_date_created(), false ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'product',
			'date_created_gmt',
			array(
				'schema'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Product creation date in UTC. Omitted when no creation date is available.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => self::format_date_for_response( $product->get_date_created() ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'product',
			'date_modified',
			array(
				'schema'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Product modification date in the store timezone. Omitted when no modification date is available.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => self::format_date_for_response( $product->get_date_modified(), false ),
				'omit_if_null' => true,
			)
		);

		AbilityResources::register_field(
			'product',
			'date_modified_gmt',
			array(
				'schema'       => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Product modification date in UTC. Omitted when no modification date is available.', 'woocommerce' ),
				),
				'get_callback' => static fn( \WC_Product $product ) => self::format_date_for_response( $product->get_date_modified() ),
				'omit_if_null' => true,
			)
		);
	}

	/**
	 * Allowed product post-status slugs for product output.
	 *
	 * @return array<int, string>
	 */
	protected static function get_product_output_status_slugs(): array {
		return array_values(
			array_unique(
				array_merge(
					self::get_product_mutation_status_slugs(),
					array( ProductStatus::AUTO_DRAFT, ProductStatus::TRASH )
				)
			)
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


	/**
	 * Schema of a product's terms in one taxonomy.
	 *
	 * @return array
	 */
	private static function get_product_terms_output_schema(): array {
		return array(
			'type'  => 'array',
			'items' => array(
				'type'                 => 'object',
				'properties'           => array(
					'id'   => array( 'type' => 'integer' ),
					'name' => array( 'type' => 'string' ),
					'slug' => array( 'type' => 'string' ),
				),
				'additionalProperties' => false,
			),
		);
	}


	/**
	 * Terms of a product in one taxonomy. Loading a product caches its terms, so this adds no query per product.
	 *
	 * @param array<int, int|string> $term_ids Term IDs of the product.
	 * @param string                 $taxonomy Taxonomy, such as `product_cat`.
	 * @return array<int, array{id: int, name: string, slug: string}>
	 */
	private static function format_product_terms_for_response( array $term_ids, string $taxonomy ): array {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = array();

		foreach ( $term_ids as $term_id ) {
			$term = get_term( (int) $term_id, $taxonomy );

			if ( $term instanceof \WP_Term ) {
				$terms[] = array(
					'id'   => $term->term_id,
					'name' => html_entity_decode( $term->name, ENT_QUOTES | ENT_HTML5 ),
					'slug' => $term->slug,
				);
			}
		}

		return $terms;
	}


	/**
	 * Featured image first, then the gallery. Unlike the REST API, a product without images gets no placeholder.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array<int, array{id: int, src: string, name: string, alt: string}>
	 */
	private static function format_product_images_for_response( \WC_Product $product ): array {
		$images = array();

		foreach ( self::get_product_image_ids( $product ) as $attachment_id ) {
			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );

			if ( ! is_array( $attachment ) ) {
				continue;
			}

			$images[] = array(
				'id'   => $attachment_id,
				'src'  => (string) $attachment[0],
				'name' => get_post_field( 'post_title', $attachment_id ),
				'alt'  => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			);
		}

		return $images;
	}


	/**
	 * Format the attributes of a product for output, in the shape of the
	 * `attributes` input: options on a parent, one option on a variation.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array<int, array<string, mixed>>
	 */
	private static function format_product_attributes( \WC_Product $product ): array {
		$output = array();

		foreach ( $product->get_attributes() as $slug => $attribute ) {
			if ( $attribute instanceof \WC_Product_Attribute ) {
				$output[] = array(
					'id'        => $attribute->get_id(),
					'name'      => wc_attribute_label( $attribute->get_name(), $product ),
					'options'   => self::get_attribute_option_slugs( $attribute ),
					'visible'   => (bool) $attribute->get_visible(),
					'variation' => (bool) $attribute->get_variation(),
				);
			} else {
				$picked = array(
					'id'   => taxonomy_is_product_attribute( (string) $slug ) ? wc_attribute_taxonomy_id_by_name( (string) $slug ) : 0,
					'name' => wc_attribute_label( (string) $slug, $product ),
				);
				if ( '' !== (string) $attribute ) {
					$picked['option'] = (string) $attribute;
				}
				$output[] = $picked;
			}
		}

		return $output;
	}


	/**
	 * The values a variation can pick for an attribute: the term slugs of a
	 * global attribute, or the options of a local one. An option of a global
	 * attribute that has no term yet gives the slug that the save creates.
	 *
	 * @param \WC_Product_Attribute $attribute Attribute of the parent.
	 * @return array<int, string>
	 */
	private static function get_attribute_option_slugs( \WC_Product_Attribute $attribute ): array {
		if ( ! $attribute->is_taxonomy() ) {
			return array_map( 'strval', $attribute->get_options() );
		}

		$slugs = array();
		foreach ( $attribute->get_options() as $option ) {
			$term    = is_int( $option ) ? get_term( $option, $attribute->get_name() ) : null;
			$slugs[] = $term instanceof \WP_Term ? $term->slug : sanitize_title( (string) $option );
		}

		return $slugs;
	}


	/**
	 * The variations of a parent, from the data store. `WC_Product_Variable::get_children()`
	 * runs the `woocommerce_get_children` filter, which a plugin can use to hide
	 * variations from a user, so checks and output read the data store instead.
	 *
	 * @param \WC_Product_Variable $parent_product Parent product.
	 * @param bool                 $force_read     Whether to skip the children cache.
	 * @return array{all: array<int, int>, visible: array<int, int>}
	 */
	private static function read_variation_children( \WC_Product_Variable $parent_product, bool $force_read ): array {
		return $parent_product->get_data_store()->read_children( $parent_product, $force_read ); // @phpstan-ignore method.notFound (WC_Data_Store forwards the call to the variable product data store)
	}


	/**
	 * Attachment IDs of a product's featured image and gallery, without duplicates.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array<int, int>
	 */
	protected static function get_product_image_ids( \WC_Product $product ): array {
		$image_ids = array_merge( array( (int) $product->get_image_id() ), array_map( 'intval', $product->get_gallery_image_ids() ) );

		return array_values( array_unique( array_filter( $image_ids ) ) );
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
			array( ProductStatus::FUTURE )
		);
	}
}
