<?php
/**
 * Convert a product variation object to the product variation schema format.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Controllers\Version4\Responses;

defined( 'ABSPATH' ) || exit;

/**
 * ProductVariationResponse class.
 */
class ProductVariationResponse extends ProductResponse {

	/**
	 * Convert object to match data in the schema.
	 *
	 * @param \WC_Product_Variation $object Variation data.
	 * @param string                $context Request context. Options: 'view' and 'edit'.
	 * @return array
	 */
	public function prepare_response( $object, $context ) {
		$data = array(
			'id'                    => $object->get_id(),
			'name'                  => $object->get_name( $context ),
			'type'                  => $object->get_type(),
			'parent_id'             => $object->get_parent_id( $context ),
			'date_created'          => wc_rest_prepare_date_response( $object->get_date_created(), false ),
			'date_created_gmt'      => wc_rest_prepare_date_response( $object->get_date_created() ),
			'date_modified'         => wc_rest_prepare_date_response( $object->get_date_modified(), false ),
			'date_modified_gmt'     => wc_rest_prepare_date_response( $object->get_date_modified() ),
			'description'           => wc_format_content( $object->get_description() ),
			'permalink'             => $object->get_permalink(),
			'sku'                   => $object->get_sku(),
			'price'                 => $object->get_price(),
			'regular_price'         => $object->get_regular_price(),
			'sale_price'            => $object->get_sale_price(),
			'date_on_sale_from'     => wc_rest_prepare_date_response( $object->get_date_on_sale_from(), false ),
			'date_on_sale_from_gmt' => wc_rest_prepare_date_response( $object->get_date_on_sale_from() ),
			'date_on_sale_to'       => wc_rest_prepare_date_response( $object->get_date_on_sale_to(), false ),
			'date_on_sale_to_gmt'   => wc_rest_prepare_date_response( $object->get_date_on_sale_to() ),
			'on_sale'               => $object->is_on_sale(),
			'status'                => $object->get_status(),
			'purchasable'           => $object->is_purchasable(),
			'virtual'               => $object->is_virtual(),
			'downloadable'          => $object->is_downloadable(),
			'downloads'             => $this->prepare_downloads( $object ),
			'download_limit'        => '' !== $object->get_download_limit() ? (int) $object->get_download_limit() : -1,
			'download_expiry'       => '' !== $object->get_download_expiry() ? (int) $object->get_download_expiry() : -1,
			'tax_status'            => $object->get_tax_status(),
			'tax_class'             => $object->get_tax_class(),
			'manage_stock'          => $object->managing_stock(),
			'stock_quantity'        => $object->get_stock_quantity(),
			'stock_status'          => $object->get_stock_status(),
			'backorders'            => $object->get_backorders(),
			'backorders_allowed'    => $object->backorders_allowed(),
			'backordered'           => $object->is_on_backorder(),
			'weight'                => $object->get_weight(),
			'dimensions'            => array(
				'length' => $object->get_length(),
				'width'  => $object->get_width(),
				'height' => $object->get_height(),
			),
			'shipping_class'        => $object->get_shipping_class(),
			'shipping_class_id'     => $object->get_shipping_class_id(),
			'image'                 => $this->prepare_image( $object ),
			'attributes'            => $this->prepare_attributes( $object ),
			'menu_order'            => $object->get_menu_order(),
			'meta_data'             => $object->get_meta_data(),
		);
		return $data;
	}

	/**
	 * Get the image for a product variation.
	 *
	 * @param \WC_Product_Variation $object Variation data.
	 * @return array
	 */
	protected static function prepare_image( $object ) {
		if ( ! $object->get_image_id() ) {
			return;
		}

		$attachment_id   = $object->get_image_id();
		$attachment_post = get_post( $attachment_id );
		if ( is_null( $attachment_post ) ) {
			return;
		}

		$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( ! is_array( $attachment ) ) {
			return;
		}

		if ( ! isset( $image ) ) {
			return array(
				'id'                => (int) $attachment_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, false ),
				'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
				'src'               => current( $attachment ),
				'name'              => get_the_title( $attachment_id ),
				'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
			);
		}
	}
}
