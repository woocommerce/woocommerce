<?php
/**
 * WooCommerce Admin Helper Plugin Info
 *
 * @package WooCommerce\Admin\Helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper_Plugin_Info Class
 *
 * Provides the "View Information" core modals with data for Woo.com
 * hosted extensions.
 */
class WC_Helper_Plugin_Info {

	/**
	 * Loads the class, runs on init.
	 */
	public static function load() {
		add_filter( 'plugins_api', array( __CLASS__, 'plugins_api' ), 20, 3 );
		add_filter( 'themes_api', array( __CLASS__, 'themes_api' ), 20, 3 );
	}

	/**
	 * Plugin information callback for Woo extensions.
	 *
	 * @param object $response The response core needs to display the modal.
	 * @param string $action The requested plugins_api() action.
	 * @param object $args Arguments passed to plugins_api().
	 *
	 * @return object An updated $response.
	 */
	public static function plugins_api( $response, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $response;
		}
		return self::maybe_override_products_api( $response, $action, $args );
	}

	/**
	 * Theme information callback for Woo themes.
	 *
	 * @param object $response The response core needs to display the modal.
	 * @param string $action The requested themes_api() action.
	 * @param object $args Arguments passed to themes_api().
	 */
	public static function themes_api( $response, $action, $args ) {
		if ( 'theme_information' !== $action ) {
			return $response;
		}
		return self::maybe_override_products_api( $response, $action, $args );
	}

	/**
	 * Override the products API to fetch data from the Helper API if it's a Woo product.
	 *
	 * @param object $response The response core needs to display the modal.
	 * @param string $action The requested action.
	 * @param object $args Arguments passed to the API.
	 */
	public static function maybe_override_products_api( $response, $action, $args ) {
		if ( empty( $args->slug ) ) {
			return $response;
		}

		// Only for slugs that start with woo-
		if ( 0 !== strpos( $args->slug, 'woocommerce-com-' ) ) {
			return $response;
		}

		$clean_slug = str_replace( 'woocommerce-com-', '', $args->slug );

		// Look through update data by slug.
		$update_data = WC_Helper_Updater::get_update_data();
		$products    = wp_list_filter( $update_data, array( 'slug' => $clean_slug ) );

		if ( empty( $products ) ) {
			return $response;
		}

		$product_id = array_keys( $products );
		$product_id = array_shift( $product_id );

		// Fetch the product information from the Helper API.
		$request = WC_Helper_API::get(
			add_query_arg(
				array(
					'product_id' => absint( $product_id ),
				),
				'info'
			),
			array( 'authenticated' => true )
		);

		$results = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( ! empty( $results ) ) {
			$response = (object) $results;

			$product = array_shift( $products );
			if ( isset( $product['package'] ) ) {
				$response->download_link = $product['package'];
			}
		}

		return $response;
	}
}

WC_Helper_Plugin_Info::load();
