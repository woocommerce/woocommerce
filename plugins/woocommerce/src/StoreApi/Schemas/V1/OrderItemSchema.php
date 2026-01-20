<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\Utilities\ProductItemTrait;

/**
 * OrderItemSchema class.
 */
class OrderItemSchema extends ItemSchema {
	use ProductItemTrait;

	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'order_item';

	/**
	 * Cache for parent product attributes.
	 *
	 * @var array|null
	 */
	private $cached_parent_attributes = null;

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'order-item';

	/**
	 * Get order items data.
	 *
	 * @param \WC_Order_Item_Product $order_item Order item instance.
	 * @return array
	 */
	public function get_item_response( $order_item ) {
		$this->cached_parent_attributes = null;

		$order   = $order_item->get_order();
		$product = $order_item->get_product();

		$product_properties = [
			'short_description'  => '',
			'description'        => '',
			'sku'                => '',
			'permalink'          => '',
			'catalog_visibility' => 'hidden',
			'prices'             => [
				'price'                       => '',
				'regular_price'               => '',
				'sale_price'                  => '',
				'price_range'                 => null,
				'currency_code'               => '',
				'currency_symbol'             => '',
				'currency_minor_unit'         => 2,
				'currency_decimal_separator'  => '.',
				'currency_thousand_separator' => ',',
				'currency_prefix'             => '',
				'currency_suffix'             => '',
				'raw_prices'                  => [
					'precision'     => 6,
					'price'         => '',
					'regular_price' => '',
					'sale_price'    => '',
				],
			],
			'sold_individually'  => false,
			'images'             => [],
			'variation'          => [],
		];

		if ( is_a( $product, 'WC_Product' ) ) {
			$product_properties['short_description']  = $product->get_short_description();
			$product_properties['description']        = $product->get_description();
			$product_properties['sku']                = $product->get_sku();
			$product_properties['permalink']          = $product->get_permalink();
			$product_properties['catalog_visibility'] = $product->get_catalog_visibility();
			$product_properties['prices']             = $this->prepare_product_price_response( $product, get_option( 'woocommerce_tax_display_cart' ) );
			$product_properties['sold_individually']  = $product->is_sold_individually();
			$product_properties['images']             = $this->get_images( $product );

			// Only include variation data for product variations, not simple products.
			// This is consistent with the cart endpoint behavior.
			if ( $product instanceof \WC_Product_Variation ) {
				$product_properties['variation'] = $this->get_variation_data_from_order_item( $order_item, $product );
			}
		}

		return [
			'key'                  => $order->get_order_key(),
			'id'                   => $order_item->get_id(),
			'quantity'             => $order_item->get_quantity(),
			'quantity_limits'      => array(
				'minimum'     => $order_item->get_quantity(),
				'maximum'     => $order_item->get_quantity(),
				'multiple_of' => 1,
				'editable'    => false,
			),
			'name'                 => $order_item->get_name(),
			'short_description'    => $this->prepare_html_response( wc_format_content( wp_kses_post( $product_properties['short_description'] ) ) ),
			'description'          => $this->prepare_html_response( wc_format_content( wp_kses_post( $product_properties['description'] ) ) ),
			'sku'                  => $this->prepare_html_response( $product_properties['sku'] ),
			'low_stock_remaining'  => null,
			'backorders_allowed'   => false,
			'show_backorder_badge' => false,
			'sold_individually'    => $product_properties['sold_individually'] ?? false,
			'permalink'            => $product_properties['permalink'],
			'images'               => $product_properties['images'],
			'variation'            => $product_properties['variation'],
			'item_data'            => $order_item->get_all_formatted_meta_data(),
			'prices'               => (object) $product_properties['prices'],
			'totals'               => (object) $this->prepare_currency_response( $this->get_totals( $order_item ) ),
			'catalog_visibility'   => $product_properties['catalog_visibility'],
		];
	}

	/**
	 * Get totals data.
	 *
	 * @param \WC_Order_Item_Product $order_item Order item instance.
	 * @return array
	 */
	public function get_totals( $order_item ) {
		return [
			'line_subtotal'     => $this->prepare_money_response( $order_item->get_subtotal(), wc_get_price_decimals() ),
			'line_subtotal_tax' => $this->prepare_money_response( $order_item->get_subtotal_tax(), wc_get_price_decimals() ),
			'line_total'        => $this->prepare_money_response( $order_item->get_total(), wc_get_price_decimals() ),
			'line_total_tax'    => $this->prepare_money_response( $order_item->get_total_tax(), wc_get_price_decimals() ),
		];
	}

	/**
	 * Get variation data from order item metadata.
	 *
	 * Gets the customer's actual attribute choices from the order metadata.
	 * This fixes variations set to "Any" returning empty values.
	 *
	 * @param \WC_Order_Item_Product $order_item Order item instance.
	 * @param \WC_Product            $product Product instance.
	 * @return array Formatted variation data.
	 */
	protected function get_variation_data_from_order_item( $order_item, $product ) {
		$variation_data = array();
		$meta_data      = $order_item->get_meta_data();

		$parent_attributes = $this->get_parent_product_attributes( $product );

		foreach ( $meta_data as $meta ) {
			$meta_key   = $meta->key;
			$meta_value = $meta->value;

			if ( empty( $meta_key ) || ! is_scalar( $meta_value ) || '' === $meta_value || strpos( $meta_key, '_' ) === 0 ) {
				continue;
			}

			$is_variation_attribute = false;
			$normalized_key         = $meta_key;

			if ( strpos( $meta_key, 'attribute_' ) === 0 ) {
				$normalized_key = substr( $meta_key, strlen( 'attribute_' ) );
			}

			if ( strpos( $normalized_key, 'pa_' ) === 0 ) {
				$is_variation_attribute = true;
			} else {
				foreach ( $parent_attributes as $attribute ) {
					if ( strtolower( $attribute->get_name() ) === strtolower( $normalized_key ) ) {
						$is_variation_attribute = true;
						break;
					}
				}
			}

			if ( $is_variation_attribute ) {
				$variation_data[ wc_variation_attribute_name( $normalized_key ) ] = $meta_value;
			}
		}

		return $this->format_variation_data( $variation_data, $product );
	}

	/**
	 * Get parent product attributes.
	 *
	 * Cached to avoid multiple DB lookups when processing multiple meta items.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return array Array of WC_Product_Attribute objects.
	 *
	 * @since 10.5.0
	 */
	protected function get_parent_product_attributes( $product ) {
		if ( null !== $this->cached_parent_attributes ) {
			return $this->cached_parent_attributes;
		}

		$this->cached_parent_attributes = array();

		if ( ! $product->get_parent_id() ) {
			return $this->cached_parent_attributes;
		}

		$parent_product = wc_get_product( $product->get_parent_id() );
		if ( ! $parent_product instanceof \WC_Product ) {
			return $this->cached_parent_attributes;
		}

		$this->cached_parent_attributes = $parent_product->get_attributes();

		return $this->cached_parent_attributes;
	}
}
