<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper_Plugin_Info Class
 *
 * Provides the "View Information" core modals with data for WooCommerce.com
 * hosted extensions.
 */
class WC_Helper_Plugin_Info {

	/**
	 * Loads the class, runs on init.
	 */
	public static function load() {
		add_filter( 'plugins_api', array( __CLASS__, 'plugins_api' ), 20, 3 );
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

		if ( empty( $args->slug ) ) {
			return $response;
		}

		$found_plugin = null;

		// Look through local Woo plugins by slugs.
		foreach ( WC_Helper::get_local_woo_plugins() as $plugin ) {
			$slug = dirname( $plugin['_filename'] );
			if ( dirname( $plugin['_filename'] ) === $args->slug ) {
				$plugin['_slug'] = $args->slug;
				$found_plugin = $plugin;
				break;
			}
		}

		if ( ! $found_plugin ) {
			return $response;
		}

		// Fetch the product information from the Helper API.
		$request = WC_Helper_API::get( add_query_arg( array(
			'product_id' => absint( $plugin['_product_id'] ),
			'product_slug' => rawurlencode( $plugin['_slug'] ),
		), 'info' ), array( 'authenticated' => true ) );

		$results = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( ! empty( $results ) ) {
			$response = (object) $results;
		}

		return $response;
	}
}

WC_Helper_Plugin_Info::load();
