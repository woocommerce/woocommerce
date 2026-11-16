<?php
/**
 * Product REST image upload cleanup.
 *
 * @package WooCommerce\Internal\RestApi
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\RestApi;

use WC_Product;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Track new product images and remove unused uploads after a failed update.
 */
trait ProductImageUploadCleanupTrait {

	/**
	 * Stores attachment IDs processed during the current request for potential cleanup.
	 *
	 * @var int[]
	 */
	private $processed_attachment_ids_for_request = array();

	/**
	 * Update a product and remove new images that the saved product does not use on failure.
	 *
	 * @since 11.4.0
	 *
	 * @param WP_REST_Request<array> $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {
		$this->processed_attachment_ids_for_request = array();

		try {
			$response = parent::update_item( $request );

			if ( is_wp_error( $response ) ) {
				$this->delete_unused_uploaded_images( (int) $request['id'] );
			}

			return $response;
		} finally {
			$this->processed_attachment_ids_for_request = array();
		}
	}

	/**
	 * Remove newly uploaded images that the persisted product does not use.
	 *
	 * @param int $product_id Product ID.
	 */
	private function delete_unused_uploaded_images( int $product_id ): void {
		if ( ! $this->processed_attachment_ids_for_request ) {
			return;
		}

		// An error can occur after saving, so read the persisted image references.
		$product        = $this->get_object( $product_id );
		$used_image_ids = $product instanceof WC_Product ? array_merge( array( $product->get_image_id( 'edit' ) ), $product->get_gallery_image_ids( 'edit' ) ) : array();

		foreach ( array_diff( $this->processed_attachment_ids_for_request, $used_image_ids ) as $attachment_id ) {
			wp_delete_attachment( (int) $attachment_id, true );
		}
	}
}
