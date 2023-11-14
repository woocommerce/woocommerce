<?php

namespace Automattic\WooCommerce\Blocks\Patterns;

use Automattic\WooCommerce\Blocks\AI\Connection;
use WP_Error;
/**
 * Pattern Images class.
 */
class ProductUpdater {

	/**
	 * Generate AI content and assign AI-managed images to Products.
	 *
	 * @param Connection $ai_connection The AI connection.
	 * @param string     $token The JWT token.
	 * @param array      $images The array of images.
	 * @param string     $business_description The business description.
	 *
	 * @return array|WP_Error The generated content for the products. An error if the content could not be generated.
	 */
	public function generate_content( $ai_connection, $token, $images, $business_description ) {
		if ( empty( $business_description ) ) {
			return new \WP_Error( 'missing_business_description', __( 'No business description provided for generating AI content.', 'woo-gutenberg-products-block' ) );
		}

		$last_business_description = get_option( 'last_business_description_with_ai_content_generated' );

		if ( $last_business_description === $business_description ) {
			if ( is_string( $business_description ) && is_string( $last_business_description ) ) {
				return array(
					'product_content' => array(),
				);
			} else {
				return new \WP_Error( 'business_description_not_found', __( 'No business description provided for generating AI content.', 'woo-gutenberg-products-block' ) );
			}
		}

		$ai_selected_products_images = $this->get_images_information( $images );
		$products_information_list   = $this->assign_ai_selected_images_to_dummy_products_information_list( $ai_selected_products_images );

		$response = $this->generate_product_content( $ai_connection, $token, $products_information_list );

		if ( is_wp_error( $response ) ) {
			$error_msg = $response;
		} elseif ( empty( $response ) || ! isset( $response['completion'] ) ) {
			$error_msg = new \WP_Error( 'missing_completion_key', __( 'The response from the AI service is empty or missing the completion key.', 'woo-gutenberg-products-block' ) );
		}

		if ( isset( $error_msg ) ) {
			return $error_msg;
		}

		$product_content = json_decode( $response['completion'], true );

		return array(
			'product_content' => $product_content,
		);
	}

	/**
	 * Return all dummy products that were not modified by the store owner.
	 *
	 * @return array|WP_Error An array with the dummy products that need to have their content updated by AI.
	 */
	public function fetch_dummy_products_to_update() {
		$real_products = $this->fetch_product_ids();

		if ( is_array( $real_products ) && count( $real_products ) > 0 ) {
			return array(
				'product_content' => array(),
			);
		}

		$dummy_products = $this->fetch_product_ids( 'dummy' );

		if ( ! is_array( $dummy_products ) ) {
			return new \WP_Error( 'failed_to_fetch_dummy_products', __( 'Failed to fetch dummy products.', 'woo-gutenberg-products-block' ) );
		}

		$dummy_products_count          = count( $dummy_products );
		$expected_dummy_products_count = 6;
		$products_to_create            = max( 0, $expected_dummy_products_count - $dummy_products_count );

		while ( $products_to_create > 0 ) {
			$this->create_new_product();
			$products_to_create--;
		}

		// Identify dummy products that need to have their content updated.
		$dummy_products_ids = $this->fetch_product_ids( 'dummy' );

		if ( ! is_array( $dummy_products_ids ) ) {
			return new \WP_Error( 'failed_to_fetch_dummy_products', __( 'Failed to fetch dummy products.', 'woo-gutenberg-products-block' ) );
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
		$current_product_hash     = $this->get_hash_for_product( $dummy_product );
		$ai_modified_product_hash = $this->get_hash_for_ai_modified_product( $dummy_product );

		$date_created  = $dummy_product->get_date_created();
		$date_modified = $dummy_product->get_date_modified();

		if ( ! $date_created instanceof \WC_DateTime || ! $date_modified instanceof \WC_DateTime ) {
			return false;
		}

		$formatted_date_created  = $dummy_product->get_date_created()->date( 'Y-m-d H:i:s' );
		$formatted_date_modified = $dummy_product->get_date_modified()->date( 'Y-m-d H:i:s' );

		$timestamp_created  = strtotime( $formatted_date_created );
		$timestamp_modified = strtotime( $formatted_date_modified );

		$dummy_product_not_modified = abs( $timestamp_modified - $timestamp_created ) < 60;

		if ( $current_product_hash === $ai_modified_product_hash || $dummy_product_not_modified ) {
			return true;
		}

		return false;
	}

	/**
	 * Creates a new product and assigns the _headstart_post meta to it.
	 *
	 * @return bool|int
	 */
	public function create_new_product() {
		$product      = new \WC_Product();
		$random_price = wp_rand( 5, 50 );

		$product->set_name( 'My Awesome Product' );
		$product->set_status( 'publish' );
		$product->set_description( 'Product description' );
		$product->set_price( $random_price );
		$product->set_regular_price( $random_price );

		$saved_product = $product->save();

		return update_post_meta( $saved_product, '_headstart_post', true );
	}

	/**
	 * Return all existing products that have the _headstart_post meta assigned to them.
	 *
	 * @param string $type The type of products to fetch.
	 *
	 * @return array
	 */
	public function fetch_product_ids( $type = 'user_created' ) {
		global $wpdb;

		if ( 'user_created' === $type ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE ID NOT IN ( SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = 'product' AND p.post_status = 'publish' ) AND post_type = 'product' AND post_status = 'publish' LIMIT 6", '_headstart_post' ) );
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = 'product' AND p.post_status = 'publish'", '_headstart_post' ) );
	}

	/**
	 * Return the hash for a product based on its name, description and image_id.
	 *
	 * @param \WC_Product $product The product.
	 *
	 * @return false|string
	 */
	public function get_hash_for_product( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		return md5( $product->get_name() . $product->get_description() . $product->get_image_id() );
	}

	/**
	 * Return the hash for a product that had its content AI-generated.
	 *
	 * @param \WC_Product $product The product.
	 *
	 * @return false|mixed
	 */
	public function get_hash_for_ai_modified_product( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		return get_post_meta( $product->get_id(), '_ai_generated_content', true );
	}

	/**
	 * Create a hash with the AI-generated content and save it as a meta for the product.
	 *
	 * @param \WC_Product $product The product.
	 *
	 * @return bool|int
	 */
	public function create_hash_for_ai_modified_product( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		$content = $this->get_hash_for_product( $product );

		return update_post_meta( $product->get_id(), '_ai_generated_content', $content );
	}

	/**
	 * Update the product content with the AI-generated content.
	 *
	 * @param \WC_Product $product The product.
	 * @param array       $ai_generated_product_content The AI-generated content.
	 *
	 * @return string|void
	 */
	public function update_product_content( $product, $ai_generated_product_content ) {
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( ! isset( $ai_generated_product_content['image']['src'] ) || ! isset( $ai_generated_product_content['image']['alt'] ) || ! isset( $ai_generated_product_content['title'] ) || ! isset( $ai_generated_product_content['description'] ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Since the media_sideload_image function is expensive and can take longer to complete
		// the process of downloading the external image and uploading it to the media library,
		// here we are increasing the time limit to avoid any issues.
		set_time_limit( 60 );

		$product_image_id = media_sideload_image( $ai_generated_product_content['image']['src'], $product->get_id(), $ai_generated_product_content['image']['alt'], 'id' );

		if ( is_wp_error( $product_image_id ) ) {
			return $product_image_id->get_error_message();
		}

		$product->set_name( $ai_generated_product_content['title'] );
		$product->set_description( $ai_generated_product_content['description'] );
		$product->set_image_id( $product_image_id );

		$product->save();

		$this->create_hash_for_ai_modified_product( $product );
	}

	/**
	 * Assigns the default content for the products.
	 *
	 * @param array $ai_selected_products_images The images information.
	 *
	 * @return array[]
	 */
	public function assign_ai_selected_images_to_dummy_products_information_list( $ai_selected_products_images ) {
		$default_image = [
			'src' => esc_url( plugins_url( 'woocommerce-blocks/images/block-placeholders/product-image-gallery.svg' ) ),
			'alt' => 'The placeholder for a product image.',
		];

		return [
			[
				'title'       => 'A product title',
				'description' => 'A product description',
				'image'       => $ai_selected_products_images[0] ?? $default_image,
			],
			[
				'title'       => 'A product title',
				'description' => 'A product description',
				'image'       => $ai_selected_products_images[1] ?? $default_image,
			],
			[
				'title'       => 'A product title',
				'description' => 'A product description',
				'image'       => $ai_selected_products_images[2] ?? $default_image,
			],
			[
				'title'       => 'A product title',
				'description' => 'A product description',
				'image'       => $ai_selected_products_images[3] ?? $default_image,
			],
			[
				'title'       => 'A product title',
				'description' => 'A product description',
				'image'       => $ai_selected_products_images[4] ?? $default_image,
			],
			[
				'title'       => 'A product title',
				'description' => 'A product description',
				'image'       => $ai_selected_products_images[5] ?? $default_image,
			],
		];
	}

	/**
	 * Get the images information.
	 *
	 * @param array $images The array of images.
	 *
	 * @return array
	 */
	public function get_images_information( $images ) {
		if ( is_wp_error( $images ) ) {
			return [
				'src' => 'images/block-placeholders/product-image-gallery.svg',
				'alt' => 'The placeholder for a product image.',
			];
		}

		$count              = 0;
		$placeholder_images = [];
		foreach ( $images as $image ) {
			if ( $count >= 6 ) {
				break;
			}

			if ( ! isset( $image['title'] ) || ! isset( $image['thumbnails']['medium'] ) ) {
				continue;
			}

			$placeholder_images[] = [
				'src' => esc_url( $image['thumbnails']['medium'] ),
				'alt' => esc_attr( $image['title'] ),
			];

			++ $count;
		}

		return $placeholder_images;
	}

	/**
	 * Generate the product content.
	 *
	 * @param Connection $ai_connection The AI connection.
	 * @param string     $token The JWT token.
	 * @param array      $products_default_content The default content for the products.
	 *
	 * @return array|int|string|\WP_Error
	 */
	public function generate_product_content( $ai_connection, $token, $products_default_content ) {
		$store_description = get_option( 'woo_ai_describe_store_description' );

		if ( ! $store_description ) {
			return new \WP_Error( 'missing_store_description', __( 'The store description is required to generate the content for your site.', 'woo-gutenberg-products-block' ) );
		}

		$prompt = sprintf( 'Given the following business description: "%1s" and the assigned value for the alt property in the JSON below, generate new titles and descriptions for each one of the products listed below and assign them as the new values for the JSON: %2s. Each one of the titles should be unique and must be limited to 29 characters. The response should be only a JSON string, with no intro or explanations.', $store_description, wp_json_encode( $products_default_content ) );

		return $ai_connection->fetch_ai_response( $token, $prompt, 60 );
	}
}
