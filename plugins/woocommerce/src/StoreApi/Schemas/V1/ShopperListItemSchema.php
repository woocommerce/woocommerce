<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;

/**
 * ShopperListItemSchema class.
 *
 * One row in a shopper list. Serves live product data when the product still
 * exists in the catalog, and at-save tombstone data when it doesn't, distinguishing
 * the two states via the `product_exists` boolean.
 */
class ShopperListItemSchema extends AbstractSchema {
	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'shopper_list_item';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'shopper-list-item';

	/**
	 * Image attachment schema instance.
	 *
	 * @var ImageAttachmentSchema
	 */
	protected $image_attachment_schema;

	/**
	 * Constructor.
	 *
	 * @param ExtendSchema     $extend Rest Extending instance.
	 * @param SchemaController $controller Schema Controller instance.
	 */
	public function __construct( ExtendSchema $extend, SchemaController $controller ) {
		parent::__construct( $extend, $controller );
		$schema = $this->controller->get( ImageAttachmentSchema::IDENTIFIER );
		if ( $schema instanceof ImageAttachmentSchema ) {
			$this->image_attachment_schema = $schema;
		}
	}

	/**
	 * Item schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return array(
			'key'                   => array(
				'description' => __( 'Stable identifier for the saved item within its list.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'id'                    => array(
				'description' => __( 'Variation ID if applicable, otherwise product ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'product_id'            => array(
				'description' => __( 'Product ID at the time the item was saved.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'variation_id'          => array(
				'description' => __( 'Variation ID at the time the item was saved, or 0 for non-variable products.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'quantity'              => array(
				'description' => __( 'Quantity of this saved item.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'product_exists'        => array(
				'description' => __( 'True when the underlying product still exists in the catalog. When false, the row is a tombstone served from at-save snapshot data.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'name'                  => array(
				'description' => __( 'Product name. Live when product_exists is true; falls back to product_title_at_save otherwise.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'permalink'             => array(
				'description' => __( 'Product URL. Empty when the product no longer exists.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'images'                => array(
				'description' => __( 'List of images for the live product. Empty when the product no longer exists.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->image_attachment_schema->get_properties(),
				),
			),
			'variation'             => array(
				'description' => __( 'Chosen variation attributes, if applicable.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'raw_attribute' => array(
							'description' => __( 'Variation system generated attribute name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'attribute'     => array(
							'description' => __( 'Variation attribute name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'value'         => array(
							'description' => __( 'Variation attribute value.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
			'prices'                => array(
				'description' => __( 'Live product prices. Omitted when the product no longer exists.', 'woocommerce' ),
				'type'        => array( 'object', 'null' ),
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => array_merge(
					$this->get_store_currency_properties(),
					array(
						'price'         => array(
							'description' => __( 'Current product price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'regular_price' => array(
							'description' => __( 'Regular product price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'sale_price'    => array(
							'description' => __( 'Sale product price, if applicable.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					)
				),
			),
			'item_data'             => array(
				'description' => __( 'Custom item data captured at save time (extension fields).', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type' => 'object',
				),
			),
			'date_added_gmt'        => array(
				'description' => __( 'The date the item was saved, as GMT.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'date-time',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'product_title_at_save' => array(
				'description' => __( 'Snapshot of the product title taken when the item was saved. Used as the rendered name when product_exists is false.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'price_at_save'         => array(
				'description' => __( 'Snapshot of the product price taken when the item was saved. Used as the rendered price when product_exists is false.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Convert a stored item record into the response shape.
	 *
	 * @param array $item Stored item record.
	 * @return array
	 */
	public function get_item_response( $item ) {
		$variation_id = $item['variation_id'] ?? 0;
		$product_id   = $variation_id > 0 ? $variation_id : ( $item['product_id'] ?? 0 );
		$product      = $product_id ? wc_get_product( $product_id ) : false;
		$has_product  = $product instanceof \WC_Product;

		$response = array(
			'key'                   => $item['key'] ?? '',
			'id'                    => $product_id,
			'product_id'            => $item['product_id'] ?? 0,
			'variation_id'          => $item['variation_id'] ?? 0,
			'quantity'              => $item['quantity'] ?? 1,
			'product_exists'        => $has_product,
			'item_data'             => isset( $item['item_data'] ) && is_array( $item['item_data'] ) ? $item['item_data'] : array(),
			'date_added_gmt'        => wc_rest_prepare_date_response( $item['date_added_gmt'] ?? current_time( 'mysql', true ) ),
			'product_title_at_save' => $item['product_title_at_save'] ?? '',
			'price_at_save'         => $item['price_at_save'] ?? '',
		);

		$variation_data = isset( $item['variation'] ) && is_array( $item['variation'] ) ? $item['variation'] : array();

		if ( $has_product ) {
			$response['name']      = $this->get_name( $product );
			$response['permalink'] = $product->get_permalink();
			$response['images']    = $this->get_images( $product );
			$response['variation'] = $this->format_variation_data( $variation_data, $product );
			$response['prices']    = (object) $this->get_prices( $product );
		} else {
			$response['name']      = $item['product_title_at_save'] ?? '';
			$response['permalink'] = '';
			$response['images']    = array();
			$response['variation'] = $this->format_variation_data( $variation_data, null );
			$response['prices']    = null;
		}//end if

		return $response;
	}

	/**
	 * Get the displayable name for the live product.
	 *
	 * @param \WC_Product $product Live product instance.
	 * @return string
	 */
	private function get_name( \WC_Product $product ): string {
		$prepared = $this->prepare_html_response( $product->get_title() );
		return is_string( $prepared ) ? $prepared : (string) $product->get_title();
	}

	/**
	 * Get the main image for a shopper list item.
	 *
	 * Returns the product's main image only — shopper list rows are compact and
	 * the gallery isn't needed at the row level.
	 *
	 * @param \WC_Product $product Live product instance.
	 * @return array
	 */
	private function get_images( \WC_Product $product ): array {
		$image_id = (int) $product->get_image_id();
		if ( $image_id <= 0 ) {
			return array();
		}

		$image = $this->image_attachment_schema->get_item_response( $image_id );
		return $image ? array( $image ) : array();
	}

	/**
	 * Compute live prices for the saved item.
	 *
	 * @param \WC_Product $product Live product instance.
	 * @return array
	 */
	private function get_prices( \WC_Product $product ): array {
		$decimals      = wc_get_price_decimals();
		$regular_price = $product->get_regular_price();
		$sale_price    = $product->get_sale_price();
		$current_price = $product->get_price();

		return $this->prepare_currency_response(
			array(
				'price'         => $this->prepare_money_response( $current_price, $decimals ),
				'regular_price' => $this->prepare_money_response( '' === $regular_price ? $current_price : $regular_price, $decimals ),
				'sale_price'    => '' === $sale_price ? '' : $this->prepare_money_response( $sale_price, $decimals ),
			)
		);
	}

	/**
	 * Format variation attribute data into [{ raw_attribute, attribute, value }] objects.
	 *
	 * Mirrors ProductItemTrait::format_variation_data but works without requiring
	 * a ProductSchema parent class. Tolerates a null product (tombstone path).
	 *
	 * @param array            $variation_data Variation attributes keyed by attribute_*.
	 * @param \WC_Product|null $product        Live product, or null if missing.
	 * @return array
	 */
	private function format_variation_data( array $variation_data, $product ): array {
		$return = array();

		foreach ( $variation_data as $key => $value ) {
			$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( (string) $key ) ) );

			if ( taxonomy_exists( $taxonomy ) ) {
				$term = get_term_by( 'slug', $value, $taxonomy );
				if ( ! is_wp_error( $term ) && $term && $term->name ) {
					/**
					 * Filters the variation option name.
					 *
					 * This filter is documented in src/StoreApi/Utilities/ProductItemTrait.php.
					 *
					 * @since 10.8.0
					 */
					$value = apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $taxonomy, $product );
				}
				$label = wc_attribute_label( $taxonomy );
			} else {
				/**
				 * Filters the variation option name.
				 *
				 * This filter is documented in src/StoreApi/Utilities/ProductItemTrait.php.
				 *
				 * @since 10.8.0
				 */
				$value = apply_filters( 'woocommerce_variation_option_name', $value, null, $taxonomy, $product );
				$label = $product instanceof \WC_Product
					? wc_attribute_label( str_replace( 'attribute_', '', (string) $key ), $product )
					: str_replace( 'attribute_', '', (string) $key );
			}//end if

			$return[] = array(
				'raw_attribute' => $this->prepare_html_response( (string) $key ),
				'attribute'     => $this->prepare_html_response( (string) $label ),
				'value'         => $this->prepare_html_response( (string) $value ),
			);
		}//end foreach

		return $return;
	}
}
