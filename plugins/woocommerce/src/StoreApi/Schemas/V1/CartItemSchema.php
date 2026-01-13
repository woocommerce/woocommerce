<?php
namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

use Automattic\WooCommerce\StoreApi\Utilities\ProductItemTrait;
use Automattic\WooCommerce\StoreApi\Utilities\QuantityLimits;

/**
 * CartItemSchema class.
 */
class CartItemSchema extends ItemSchema {
	use ProductItemTrait;

	/**
	 * The schema item name.
	 *
	 * @var string
	 */
	protected $title = 'cart_item';

	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'cart-item';

	/**
	 * Convert a WooCommerce cart item to an object suitable for the response.
	 *
	 * @param array $cart_item Cart item array.
	 * @return array
	 */
	public function get_item_response( $cart_item ) {
		$product = $cart_item['data'] ?? false;

		if ( ! $product instanceof \WC_Product ) {
			return [];
		}

		/**
		 * Filter the product permalink.
		 *
		 * This is a hook taken from the legacy cart/mini-cart templates that allows the permalink to be changed for a
		 * product. This is specific to the cart endpoint.
		 *
		 * @since 9.9.0
		 *
		 * @param string $product_permalink Product permalink.
		 * @param array  $cart_item         Cart item array.
		 * @param string $cart_item_key     Cart item key.
		 */
		$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $product->get_permalink(), $cart_item, $cart_item['key'] );

		return [
			'key'                  => $cart_item['key'],
			'id'                   => $product->get_id(),
			'type'                 => $product->get_type(),
			'quantity'             => wc_stock_amount( $cart_item['quantity'] ),
			'quantity_limits'      => (object) ( new QuantityLimits() )->get_cart_item_quantity_limits( $cart_item ),
			'name'                 => $this->prepare_html_response( $product->get_title() ),
			'short_description'    => $this->prepare_html_response( wc_format_content( wp_kses_post( $product->get_short_description() ) ) ),
			'description'          => $this->prepare_html_response( wc_format_content( wp_kses_post( $product->get_description() ) ) ),
			'sku'                  => $this->prepare_html_response( $product->get_sku() ),
			'low_stock_remaining'  => $this->get_low_stock_remaining( $product ),
			'backorders_allowed'   => (bool) $product->backorders_allowed(),
			'show_backorder_badge' => (bool) $product->backorders_require_notification() && $product->is_on_backorder( $cart_item['quantity'] ),
			'sold_individually'    => $product->is_sold_individually(),
			'permalink'            => $product_permalink,
			'images'               => $this->get_cart_images( $product, $cart_item, $cart_item['key'] ),
			'variation'            => $this->format_variation_data( $cart_item['variation'], $product ),
			'item_data'            => $this->get_item_data( $cart_item ),
			'prices'               => (object) $this->prepare_product_price_response( $product, get_option( 'woocommerce_tax_display_cart' ) ),
			'totals'               => (object) $this->prepare_currency_response(
				[
					'line_subtotal'     => $this->prepare_money_response( $cart_item['line_subtotal'], wc_get_price_decimals() ),
					'line_subtotal_tax' => $this->prepare_money_response( $cart_item['line_subtotal_tax'], wc_get_price_decimals() ),
					'line_total'        => $this->prepare_money_response( $cart_item['line_total'], wc_get_price_decimals() ),
					'line_total_tax'    => $this->prepare_money_response( $cart_item['line_tax'], wc_get_price_decimals() ),
				]
			),
			'catalog_visibility'   => $product->get_catalog_visibility(),
			self::EXTENDING_KEY    => $this->get_extended_data( self::IDENTIFIER, $cart_item ),
		];
	}

	/**
	 * Get list of product images for the cart item.
	 *
	 * @param \WC_Product $product       Product instance.
	 * @param array       $cart_item     Cart item array.
	 * @param string      $cart_item_key Cart item key.
	 * @return array
	 */
	protected function get_cart_images( \WC_Product $product, array $cart_item, string $cart_item_key ) {
		$product_images = $this->get_images( $product );

		/**
		 * Filter the cart product images.
		 *
		 * This hook allows the cart item images to be changed. This is specific to the cart endpoint.
		 *
		 * @param array  $product_images  Array of image objects, as defined in ImageAttachmentSchema.
		 * @param array  $cart_item      Cart item array.
		 * @param string $cart_item_key  Cart item key.
		 * @since 9.6.0
		 */
		$filtered_images = apply_filters( 'woocommerce_store_api_cart_item_images', $product_images, $cart_item, $cart_item_key );

		if ( ! is_array( $filtered_images ) || count( $filtered_images ) === 0 ) {
			return $product_images;
		}

		// Return the original images if the filtered image has no ID, or an invalid thumbnail or source URL.
		$valid_images = array();
		$logger       = wc_get_logger();

		foreach ( $filtered_images as $image ) {
			// If id is not set then something is wrong with the image, and further logging would break (it uses the ID).
			if ( ! isset( $image->id ) ) {
				$logger->warning( 'After passing through woocommerce_cart_item_images filter, one of the images did not have an id property.' );
				continue;
			}

			// Check if thumbnail is a valid url.
			if ( empty( $image->thumbnail ) || ! filter_var( $image->thumbnail, FILTER_VALIDATE_URL ) ) {
				$logger->warning( sprintf( 'After passing through woocommerce_cart_item_images filter, image with id %s did not have a valid thumbnail property.', $image->id ) );
				continue;
			}

			// Check if src is a valid url.
			if ( empty( $image->src ) || ! filter_var( $image->src, FILTER_VALIDATE_URL ) ) {
				$logger->warning( sprintf( 'After passing through woocommerce_cart_item_images filter, image with id %s did not have a valid src property.', $image->id ) );
				continue;
			}

			// Image is valid, add to resulting array.
			$valid_images[] = $image;
		}

		// If there are no valid images remaining, return original array.
		if ( count( $valid_images ) === 0 ) {
			return $product_images;
		}

		// Return the filtered images.
		return $valid_images;
	}

	/**
	 * Format cart item data removing any HTML tag.
	 *
	 * @param array $cart_item Cart item array.
	 * @return array
	 */
	protected function get_item_data( $cart_item ) {
		/**
		 * Filters cart item data.
		 *
		 * Filters the variation option name for custom option slugs.
		 *
		 * @since 4.3.0
		 *
		 * @internal Matches filter name in WooCommerce core.
		 *
		 * @param array $item_data Cart item data. Empty by default.
		 * @param array $cart_item Cart item array.
		 * @return array
		 */
		$item_data       = apply_filters( 'woocommerce_get_item_data', array(), $cart_item );
		$clean_item_data = [];
		foreach ( $item_data as $data ) {
			// We will check each piece of data in the item data element to ensure it is scalar. Extensions could add arrays
			// to this, which would cause a fatal in wp_strip_all_tags. If it is not scalar, we will return an empty array,
			// which will be filtered out in get_item_data (after this function has run).
			foreach ( $data as $data_value ) {
				if ( ! is_scalar( $data_value ) ) {
					continue 2;
				}
			}
			$clean_item_data[] = $this->format_item_data_element( $data );
		}
		return $clean_item_data;
	}

	/**
	 * Remove HTML tags from cart item data and set the `hidden` property to `__experimental_woocommerce_blocks_hidden`.
	 *
	 * @param array $item_data_element Individual element of a cart item data.
	 * @return array
	 */
	protected function format_item_data_element( $item_data_element ) {
		if ( array_key_exists( '__experimental_woocommerce_blocks_hidden', $item_data_element ) ) {
			$item_data_element['hidden'] = $item_data_element['__experimental_woocommerce_blocks_hidden'];
		}
		return array_map( 'wp_kses_post', $item_data_element );
	}
}
