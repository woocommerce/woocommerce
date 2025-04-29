<?php

namespace Automattic\WooCommerce\Blocks\AIContent;

use WP_Error;

/**
 * This class is used to create dummy products for the Customize Your Store flow.
 * Even if it is in the AI Content namespace, it is not used for AI content generation.
 *
 * @internal
 */
class UpdateProducts {

	/**
	 * The dummy products.
	 */
	const DUMMY_PRODUCTS = [
		[
			'title'       => 'Vintage Typewriter',
			'image'       => 'assets/images/pattern-placeholders/writing-typing-keyboard-technology-white-vintage.jpg',
			'description' => 'A hit spy novel or a love letter? Anything you type using this vintage typewriter from the 20s is bound to make a mark.',
			'price'       => 90,
		],
		[
			'title'       => 'Leather-Clad Leisure Chair',
			'image'       => 'assets/images/pattern-placeholders/table-wood-house-chair-floor-window.jpg',
			'description' => 'Sit back and relax in this comfy designer chair. High-grain leather and steel frame add luxury to your your leisure.',
			'price'       => 249,
		],
		[
			'title'       => 'Black and White',
			'image'       => 'assets/images/pattern-placeholders/white-black-black-and-white-photograph-monochrome-photography.jpg',
			'description' => 'This 24" x 30" high-quality print just exudes summer. Hang it on the wall and forget about the world outside.',
			'price'       => 115,
		],
		[
			'title'       => '3-Speed Bike',
			'image'       => 'assets/images/pattern-placeholders/road-sport-vintage-wheel-retro-old.jpg',
			'description' => 'Zoom through the streets on this premium 3-speed bike. Manufactured and assembled in Germany in the 80s.',
			'price'       => 115,
		],
		[
			'title'       => 'Hi-Fi Headphones',
			'image'       => 'assets/images/pattern-placeholders/man-person-music-black-and-white-white-photography.jpg',
			'description' => 'Experience your favorite songs in a new way with these premium hi-fi headphones.',
			'price'       => 125,
		],
		[
			'title'       => 'Retro Glass Jug (330 ml)',
			'image'       => 'assets/images/pattern-placeholders/drinkware-liquid-tableware-dishware-bottle-fluid.jpg',
			'description' => 'Thick glass and a classic silhouette make this jug a must-have for any retro-inspired kitchen.',
			'price'       => 115,
		],
	];


	/**
	 * Return all dummy products that were not modified by the store owner.
	 *
	 * @return array|WP_Error An array with the dummy products that need to have their content updated by AI.
	 */
	public function fetch_dummy_products_to_update() {
		$real_products       = $this->fetch_product_ids();
		$real_products_count = count( $real_products );

		if ( is_array( $real_products ) && $real_products_count > 6 ) {
			return array(
				'product_content' => array(),
			);
		}

		$dummy_products       = $this->fetch_product_ids( 'dummy' );
		$dummy_products_count = count( $dummy_products );
		$products_to_create   = max( 0, 6 - $real_products_count - $dummy_products_count );
		while ( $products_to_create > 0 ) {
			$this->create_new_product( self::DUMMY_PRODUCTS[ $products_to_create - 1 ] );
			--$products_to_create;
		}

		// Identify dummy products that need to have their content updated.
		$dummy_products_ids = $this->fetch_product_ids( 'dummy' );
		if ( ! is_array( $dummy_products_ids ) ) {
			return new \WP_Error( 'failed_to_fetch_dummy_products', __( 'Failed to fetch dummy products.', 'woocommerce' ) );
		}

		$dummy_products = array_map(
			function ( $product ) {
				return wc_get_product( $product->ID );
			},
			$dummy_products_ids
		);

		$dummy_products_to_update = [];
		foreach ( $dummy_products as $dummy_product ) {
			if ( ! $dummy_product instanceof \WC_Product ) {
				continue;
			}

			$should_update_dummy_product = $this->should_update_dummy_product( $dummy_product );

			if ( $should_update_dummy_product ) {
				$dummy_products_to_update[] = $dummy_product;
			}
		}

		return $dummy_products_to_update;
	}

	/**
	 * Verify if the dummy product should have its content generated and managed by AI.
	 *
	 * @param \WC_Product $dummy_product The dummy product.
	 *
	 * @return bool
	 */
	public function should_update_dummy_product( $dummy_product ): bool {
		$date_created  = $dummy_product->get_date_created();
		$date_modified = $dummy_product->get_date_modified();

		if ( ! $date_created instanceof \WC_DateTime || ! $date_modified instanceof \WC_DateTime ) {
			return false;
		}

		$formatted_date_created  = $dummy_product->get_date_created()->date( 'Y-m-d H:i:s' );
		$formatted_date_modified = $dummy_product->get_date_modified()->date( 'Y-m-d H:i:s' );

		$timestamp_created  = strtotime( $formatted_date_created );
		$timestamp_modified = strtotime( $formatted_date_modified );
		$timestamp_current  = time();

		$dummy_product_recently_modified = abs( $timestamp_current - $timestamp_modified ) < 10;
		$dummy_product_not_modified      = abs( $timestamp_modified - $timestamp_created ) < 60;

		if ( $dummy_product_not_modified || $dummy_product_recently_modified ) {
			return true;
		}

		return false;
	}

	/**
	 * Creates a new product and assigns the _headstart_post meta to it.
	 *
	 * @param array $product_data The product data.
	 *
	 * @return bool|int|\WP_Error
	 */
	public function create_new_product( $product_data ) {
		$product          = new \WC_Product();
		$image_src        = plugins_url( $product_data['image'], dirname( __DIR__, 2 ) );
		$image_alt        = $product_data['title'];
		$product_image_id = $this->product_image_upload( $product->get_id(), $image_src, $image_alt );

		$saved_product = $this->product_update( $product, $product_image_id, $product_data['title'], $product_data['description'], $product_data['price'] );

		if ( is_wp_error( $saved_product ) ) {
			return $saved_product;
		}

		return update_post_meta( $saved_product, '_headstart_post', true );
	}

	/**
	 * Return all existing products that have the _headstart_post meta assigned to them.
	 *
	 * @param string $type The type of products to fetch.
	 *
	 * @return array|null
	 */
	public function fetch_product_ids( string $type = 'user_created' ) {
		global $wpdb;

		if ( 'user_created' === $type ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE ID NOT IN ( SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = 'product' AND p.post_status = 'publish' ) AND post_type = 'product' AND post_status = 'publish' LIMIT 6", '_headstart_post' ) );
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = 'product' AND p.post_status = 'publish'", '_headstart_post' ) );
	}

	/**
	 * Upload the image for the product.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $image_src The image source.
	 * @param string $image_alt The image alt.
	 *
	 * @return int|string|WP_Error
	 */
	private function product_image_upload( $product_id, $image_src, $image_alt ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Since the media_sideload_image function is expensive and can take longer to complete
		// the process of downloading the external image and uploading it to the media library,
		// here we are increasing the time limit to avoid any issues.
		set_time_limit( 150 );
		wp_raise_memory_limit( 'image' );

		return media_sideload_image( $image_src, $product_id, $image_alt, 'id' );
	}

	/**
	 * Update the product with the new content.
	 *
	 * @param \WC_Product         $product The product.
	 * @param int|string|WP_Error $product_image_id The product image ID.
	 * @param string              $product_title The product title.
	 * @param string              $product_description The product description.
	 * @param int                 $product_price The product price.
	 *
	 * @return int|\WP_Error
	 */
	private function product_update( $product, $product_image_id, $product_title, $product_description, $product_price ) {
		if ( ! $product instanceof \WC_Product ) {
			return new WP_Error( 'invalid_product', __( 'Invalid product.', 'woocommerce' ) );
		}

		if ( ! is_wp_error( $product_image_id ) ) {
			$product->set_image_id( $product_image_id );
		} else {
			wc_get_logger()->warning(
				sprintf(
					// translators: %s is a generated error message.
					__( 'The image upload failed: "%s", creating the product without image', 'woocommerce' ),
					$product_image_id->get_error_message()
				),
			);
		}
		$product->set_name( $product_title );
		$product->set_description( $product_description );
		$product->set_price( $product_price );
		$product->set_regular_price( $product_price );
		$product->set_slug( sanitize_title( $product_title ) );
		$product->save();

		return $product->get_id();
	}
}
