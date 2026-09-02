<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperListItem;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Utilities\ProductItemTrait;

/**
 * ShopperListItemSchema class.
 *
 * Serializes a {@see ShopperListItem}. Renders live product fields when the
 * item reports `is_live`, and falls back to at-save snapshot data otherwise.
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
			'is_live'        => array(
				'description' => __( 'True when the row serves live product data; false rows are at-save tombstones.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'is_purchasable' => array(
				'description' => __( 'True when the product can be added to the cart.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'name'           => array(
				'description' => __( 'Product name. Live when is_live is true; falls back to the at-save title snapshot otherwise.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'permalink'      => array(
				'description' => __( 'Product URL. Null when the row is a tombstone (so iAPI strips the anchor href).', 'woocommerce' ),
				'type'        => array( 'string', 'null' ),
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
			'price_html'     => array(
				'description' => __( 'Live product price as HTML, formatted via wc_price including sale/discount markup. Empty when the product no longer exists.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'image_html'     => array(
				'description' => __( 'Product thumbnail as a fully-formed <img> element with srcset, sizes, alt, and lazy-loading attributes. Falls back to the configured placeholder image when the product has no image or no longer exists.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
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
	 * Serialize the saved item.
	 *
	 * @param ShopperListItem $item Saved item.
	 * @return array
	 */
	public function get_item_response( $item ) {
		$variation_id = $item->get_variation_id();
		$product_id   = $variation_id > 0 ? $variation_id : $item->get_product_id();
		$product      = $item->get_product();
		$is_live      = $item->is_live();

		$response = array(
			'key'            => $item->get_key(),
			'id'             => $product_id,
			'product_id'     => $item->get_product_id(),
			'variation_id'   => $variation_id,
			'quantity'       => $item->get_quantity(),
			'is_live'        => $is_live,
			'is_purchasable' => $item->is_purchasable(),
			'date_added_gmt' => wc_rest_prepare_date_response( $item->get_date_added_gmt() ),
		);

		if ( $is_live && $product instanceof \WC_Product ) {
			$response['name']       = $this->get_name( $product );
			$response['permalink']  = $product->get_permalink();
			$response['images']     = $this->get_images( $product );
			$response['variation']  = $this->format_variation_data( $item->get_variation_attributes(), $product );
			$response['prices']     = (object) $this->get_prices( $product );
			$response['price_html'] = (string) $product->get_price_html();
			$response['image_html'] = $this->get_image_html( $product );
		} else {
			$response['name']       = $this->prepare_html_response( $item->get_product_title_at_save() );
			$response['permalink']  = null;
			$response['images']     = array();
			$response['variation']  = array();
			$response['prices']     = null;
			$response['price_html'] = '';
			$response['image_html'] = $this->get_image_html( null );
		}

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
	 * Get the thumbnail image HTML for a shopper list item, falling back to the
	 * WooCommerce placeholder when the product has no image or has been deleted.
	 *
	 * Pre-formatting on the server lets renderers (PHP SSR + JS hydration)
	 * consume one canonical string instead of each side composing the markup
	 * from the structured `images` array. Mirrors the pattern WC uses in
	 * `ProductSchema::price_html` / `ProductImage::render`.
	 *
	 * @param \WC_Product|null $product Live product instance, or null for tombstones.
	 * @return string
	 */
	private function get_image_html( ?\WC_Product $product ): string {
		$image_id = $product instanceof \WC_Product ? (int) $product->get_image_id() : 0;
		if ( $image_id > 0 ) {
			return (string) wp_get_attachment_image( $image_id, 'woocommerce_thumbnail' );
		}
		return (string) wc_placeholder_img( 'woocommerce_thumbnail' );
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
