<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Utilities\ProductItemTrait;

/**
 * ShopperListItemSchema class.
 *
 * One row in a shopper list. Serves live product data when the product still
 * exists in the catalog, and at-save tombstone data when it doesn't, distinguishing
 * the two states via the `product_exists` boolean.
 */
class ShopperListItemSchema extends AbstractSchema {
	// We only call format_variation_data(); see phpstan.neon for the related suppressions.
	use ProductItemTrait;

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
	 * @throws \RuntimeException When the ImageAttachmentSchema is not registered.
	 *
	 * @param ExtendSchema     $extend Rest Extending instance.
	 * @param SchemaController $controller Schema Controller instance.
	 */
	public function __construct( ExtendSchema $extend, SchemaController $controller ) {
		parent::__construct( $extend, $controller );
		$schema = $this->controller->get( ImageAttachmentSchema::IDENTIFIER );
		if ( ! $schema instanceof ImageAttachmentSchema ) {
			throw new \RuntimeException( 'ImageAttachmentSchema is not registered in SchemaController.' );
		}
		$this->image_attachment_schema = $schema;
	}

	/**
	 * Item schema properties.
	 *
	 * @return array
	 */
	public function get_properties() {
		return array(
			'key'            => array(
				'description' => __( 'Stable identifier for the saved item within its list.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'id'             => array(
				'description' => __( 'Variation ID if applicable, otherwise product ID.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'product_id'     => array(
				'description' => __( 'Product ID at the time the item was saved.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'variation_id'   => array(
				'description' => __( 'Variation ID at the time the item was saved, or 0 for non-variable products.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'quantity'       => array(
				'description' => __( 'Quantity of this saved item.', 'woocommerce' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'product_exists' => array(
				'description' => __( 'True when the underlying product still exists in the catalog. When false, the row is a tombstone served from at-save snapshot data.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'name'           => array(
				'description' => __( 'Product name. Live when product_exists is true; falls back to the at-save title snapshot otherwise.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'permalink'      => array(
				'description' => __( 'Product URL. Empty when the product no longer exists.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'images'         => array(
				'description' => __( 'List of images for the live product. Empty when the product no longer exists.', 'woocommerce' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'items'       => array(
					'type'       => 'object',
					'properties' => $this->image_attachment_schema->get_properties(),
				),
			),
			'variation'      => array(
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
			'prices'         => array(
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
			'date_added_gmt' => array(
				'description' => __( 'The date the item was saved, as GMT.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'date-time',
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
			'key'            => $item['key'] ?? '',
			'id'             => $product_id,
			'product_id'     => $item['product_id'] ?? 0,
			'variation_id'   => $item['variation_id'] ?? 0,
			'quantity'       => $item['quantity'] ?? 1,
			'product_exists' => $has_product,
			'date_added_gmt' => wc_rest_prepare_date_response( $item['date_added_gmt'] ?? current_time( 'mysql', true ) ),
		);

		$variation_data = isset( $item['variation'] ) && is_array( $item['variation'] ) ? $item['variation'] : array();

		if ( $has_product ) {
			$response['name']      = $this->get_name( $product );
			$response['permalink'] = $product->get_permalink();
			$response['images']    = $this->get_images( $product );
			$response['variation'] = $this->format_variation_data( $variation_data, $product );
			$response['prices']    = (object) $this->get_prices( $product );
		} else {
			$response['name']      = $this->prepare_html_response( (string) ( $item['product_title_at_save'] ?? '' ) );
			$response['permalink'] = '';
			$response['images']    = array();
			$response['variation'] = array();
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
	 * We don't extend ProductSchema because saved items aren't products. The shape
	 * here is a thin subset of cart-item prices.
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
}
