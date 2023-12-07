<?php

namespace Automattic\WooCommerce\Blocks\Patterns;

use Automattic\WooCommerce\Blocks\AI\Connection;
use WP_Error;
/**
 * Pattern Images class.
 */
class ProductUpdater {
	const DUMMY_PRODUCTS = [
		[
			'title'       => 'Vintage Typewriter',
			'image'       => 'images/pattern-placeholders/writing-typing-keyboard-technology-white-vintage.jpg',
			'description' => 'A hit spy novel or a love letter? Anything you type using this vintage typewriter from the 20s is bound to make a mark.',
			'price'       => 90,
		],
		[
			'title'       => 'Leather-Clad Leisure Chair',
			'image'       => 'images/pattern-placeholders/table-wood-house-chair-floor-window.jpg',
			'description' => 'Sit back and relax in this comfy designer chair. High-grain leather and steel frame add luxury to your your leisure.',
			'price'       => 249,
		],
		[
			'title'       => 'Black and White Summer Portrait',
			'image'       => 'images/pattern-placeholders/white-black-black-and-white-photograph-monochrome-photography.jpg',
			'description' => 'This 24" x 30" high-quality print just exudes summer. Hang it on the wall and forget about the world outside.',
			'price'       => 115,
		],
		[
			'title'       => '3-Speed Bike',
			'image'       => 'images/pattern-placeholders/road-sport-vintage-wheel-retro-old.jpg',
			'description' => 'Zoom through the streets on this premium 3-speed bike. Manufactured and assembled in Germany in the 80s.',
			'price'       => 115,
		],
		[
			'title'       => 'Hi-Fi Headphones',
			'image'       => 'images/pattern-placeholders/man-person-music-black-and-white-white-photography.jpg',
			'description' => 'Experience your favorite songs in a new way with these premium hi-fi headphones.',
			'price'       => 125,
		],
		[
			'title'       => 'Retro Glass Jug (330 ml)',
			'image'       => 'images/pattern-placeholders/drinkware-liquid-tableware-dishware-bottle-fluid.jpg',
			'description' => 'Thick glass and a classic silhouette make this jug a must-have for any retro-inspired kitchen.',
			'price'       => 115,
		],
	];

	/**
	 * Generate AI content and assign AI-managed images to Products.
	 *
	 * @param Connection      $ai_connection The AI connection.
	 * @param string|WP_Error $token The JWT token.
	 * @param array|WP_Error  $images The array of images.
	 * @param string          $business_description The business description.
	 *
	 * @return array|WP_Error The generated content for the products. An error if the content could not be generated.
	 */
	public function generate_content( $ai_connection, $token, $images, $business_description ) {
		if ( is_wp_error( $images ) ) {
			return $images;
		}

		if ( is_wp_error( $token ) ) {
			return $token;
		}

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

		$dummy_products_to_update = $this->fetch_dummy_products_to_update();

		if ( is_wp_error( $dummy_products_to_update ) ) {
			return $dummy_products_to_update;
		}

		if ( empty( $dummy_products_to_update ) ) {
			return array(
				'product_content' => array(),
			);
		}

		$products_information_list = $this->assign_ai_selected_images_to_dummy_products( $dummy_products_to_update, $images );

		return $this->assign_ai_generated_content_to_dummy_products( $ai_connection, $token, $products_information_list, $business_description );
	}

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
		$timestamp_current  = time();

		$dummy_product_recently_modified = abs( $timestamp_current - $timestamp_modified ) < 10;
		$dummy_product_not_modified      = abs( $timestamp_modified - $timestamp_created ) < 60;

		if ( $current_product_hash === $ai_modified_product_hash || $dummy_product_not_modified || $dummy_product_recently_modified ) {
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
		$product = new \WC_Product();

		$product->set_name( $product_data['title'] );
		$product->set_status( 'publish' );
		$product->set_description( $product_data['description'] );
		$product->set_price( $product_data['price'] );
		$product->set_regular_price( $product_data['price'] );

		$saved_product = $product->save();

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$product_image_id = media_sideload_image( plugins_url( $product_data['image'], dirname( __DIR__ ) ), $product->get_id(), $product_data['title'], 'id' );
		if ( is_wp_error( $product_image_id ) ) {
			return new \WP_Error( 'error_uploading_image', $product_image_id->get_error_message() );
		}

		$product->set_image_id( $product_image_id );
		$product->save();

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
	 * @param array $ai_generated_product_content The AI-generated product content.
	 *
	 * @return string|void
	 */
	public function update_product_content( $ai_generated_product_content ) {
		if ( ! isset( $ai_generated_product_content['product_id'] ) ) {
			return;
		}

		$product = wc_get_product( $ai_generated_product_content['product_id'] );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( ! isset( $ai_generated_product_content['image']['src'] ) || ! isset( $ai_generated_product_content['image']['alt'] ) || ! isset( $ai_generated_product_content['title'] ) || ! isset( $ai_generated_product_content['description'] ) ) {
			return;
		}

		$product->set_name( $ai_generated_product_content['title'] );
		$product->set_description( $ai_generated_product_content['description'] );
		$product->set_regular_price( $ai_generated_product_content['price'] );
		$product->set_slug( sanitize_title( $ai_generated_product_content['title'] ) );
		$product->save();

		$update_product_image = $this->update_product_image( $product, $ai_generated_product_content );

		if ( is_wp_error( $update_product_image ) ) {
			return $update_product_image;
		}

		$this->create_hash_for_ai_modified_product( $product );
	}

	/**
	 * Update the product images with the AI-generated image.
	 *
	 * @param \WC_Product $product The product.
	 * @param array       $ai_generated_product_content The AI-generated product content.
	 *
	 * @return string|true
	 */
	public function update_product_image( $product, $ai_generated_product_content ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Since the media_sideload_image function is expensive and can take longer to complete
		// the process of downloading the external image and uploading it to the media library,
		// here we are increasing the time limit to avoid any issues.
		set_time_limit( 150 );
		wp_raise_memory_limit( 'image' );

		$product_image_id = media_sideload_image( $ai_generated_product_content['image']['src'], $product->get_id(), $ai_generated_product_content['image']['alt'], 'id' );

		if ( is_wp_error( $product_image_id ) ) {
			return $product_image_id->get_error_message();
		}

		$product->set_image_id( $product_image_id );
		$product->save();

		return true;
	}

	/**
	 * Assigns the default content for the products.
	 *
	 * @param array $dummy_products_to_update The dummy products to update.
	 * @param array $ai_selected_images The images' information.
	 *
	 * @return array[]
	 */
	public function assign_ai_selected_images_to_dummy_products( $dummy_products_to_update, $ai_selected_images ) {
		$products_information_list = [];
		$dummy_products_count      = count( $dummy_products_to_update );
		for ( $i = 0; $i < $dummy_products_count; $i ++ ) {
			$image_src = $ai_selected_images[ $i ]['URL'] ?? '';
			$image_alt = $ai_selected_images[ $i ]['title'] ?? '';

			$products_information_list[] = [
				'title'       => 'A product title',
				'description' => 'A product description',
				'price'       => 'The product price',
				'image'       => [
					'src' => esc_url( $image_src ),
					'alt' => esc_attr( $image_alt ),
				],
				'product_id'  => $dummy_products_to_update[ $i ]->get_id(),
			];
		}

		return $products_information_list;
	}

	/**
	 * Generate the product content.
	 *
	 * @param Connection $ai_connection The AI connection.
	 * @param string     $token The JWT token.
	 * @param array      $products_information_list The products information list.
	 * @param string     $business_description The business description.
	 *
	 * @return array|int|string|\WP_Error
	 */
	public function assign_ai_generated_content_to_dummy_products( $ai_connection, $token, $products_information_list, $business_description ) {
		if ( empty( $business_description ) ) {
			return new \WP_Error( 'missing_store_description', __( 'The store description is required to generate content for your site.', 'woo-gutenberg-products-block' ) );
		}

		$prompts = [];
		foreach ( $products_information_list as $product_information ) {
			if ( ! empty( $product_information['image']['alt'] ) ) {
				$prompts[] = sprintf( 'Generate a product name for a product that could be sold in a store and could be associated with the following image description: "%s" and also is related to the following business description: "%s". Do not include any adjectives or descriptions of the qualities of the product and always refer to objects or services, not humans. The returned result should not refer to people, only objects.', $product_information['image']['alt'], $business_description );
			} else {
				$prompts[] = sprintf( 'Generate a product name for a product that could be sold in a store and matches the following business description: "%s". Do not include any adjectives or descriptions of the qualities of the product and always refer to objects or services, not humans.', $business_description );
			}
		}

		$expected_results_format = [];
		foreach ( $products_information_list as $index => $product ) {
			$expected_results_format[ $index ] = [
				'title' => '',
				'price' => '',
			];
		}

		$formatted_prompt = sprintf(
			"Generate two-words titles and price for products using the following prompts for each one of them: '%s'. Ensure each entry is unique and does not repeat the given examples. It should be a number and it's not too low or too high for the corresponding product title being advertised. Convert the price to this currency: '%s'. Do not include backticks or the word json in the response. Here's an example format: '%s'.",
			wp_json_encode( $prompts ),
			get_woocommerce_currency(),
			wp_json_encode( $expected_results_format )
		);

		$ai_request_retries = 0;
		$success            = false;
		while ( $ai_request_retries < 5 && ! $success ) {
			$ai_request_retries ++;
			$ai_response = $ai_connection->fetch_ai_response( $token, $formatted_prompt, 30 );
			if ( is_wp_error( $ai_response ) ) {
				continue;
			}

			if ( empty( $ai_response ) ) {
				continue;
			}

			if ( ! isset( $ai_response['completion'] ) ) {
				continue;
			}

			$completion = json_decode( $ai_response['completion'], true );

			if ( ! is_array( $completion ) ) {
				continue;
			}

			$diff = array_diff_key( $expected_results_format, $completion );

			if ( ! empty( $diff ) ) {
				continue;
			}

			$empty_results = false;
			foreach ( $completion as $completion_item ) {
				if ( empty( $completion_item ) ) {
					$empty_results = true;
					break;
				}
			}

			if ( $empty_results ) {
				continue;
			}

			foreach ( $products_information_list as $index => $product_information ) {
				$products_information_list[ $index ]['title'] = str_replace( '"', '', $completion[ $index ]['title'] );
				$products_information_list[ $index ]['price'] = $completion[ $index ]['price'];
			}

			$success = true;
		}

		if ( ! $success ) {
			return new WP_Error( 'failed_to_fetch_ai_responses', __( 'Failed to fetch AI responses for products.', 'woo-gutenberg-products-block' ) );
		}

		return array(
			'product_content' => $products_information_list,
		);
	}

	/**
	 * Reset the products content.
	 */
	public function reset_products_content() {
		$dummy_products_to_update = $this->fetch_dummy_products_to_update();
		$i                        = 0;
		foreach ( $dummy_products_to_update as $product ) {
			$product->set_name( self::DUMMY_PRODUCTS[ $i ]['title'] );
			$product->set_description( self::DUMMY_PRODUCTS[ $i ]['description'] );
			$product->set_regular_price( self::DUMMY_PRODUCTS[ $i ]['price'] );
			$product->set_slug( sanitize_title( self::DUMMY_PRODUCTS[ $i ]['title'] ) );
			$product->save();

			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$product_image_id = media_sideload_image( plugins_url( self::DUMMY_PRODUCTS[ $i ]['image'], dirname( __DIR__ ) ), $product->get_id(), self::DUMMY_PRODUCTS[ $i ]['title'], 'id' );
			$product_image_id = $product->set_image_id( $product_image_id );

			$product->save();

			$this->create_hash_for_ai_modified_product( $product );

			$i++;
		}
	}
}
