<?php
/**
 * ManifestAPI class file
 *
 * @package  WooCommerceDocs
 */

namespace WooCommerceDocs\API;

/**
 * A class to register the manifest API endpoints.
 */
class ManifestAPI {

	/**
	 * Register the routes for the objects of the controller.
	 */
	public static function register_routes() {
		register_rest_route(
			'woocommerce-docs/v1',
			'/manifests',
			array(
				'methods'             => 'GET',
				'callback'            => array( '\WooCommerceDocs\API\ManifestAPI', 'get_manifests' ),
				'permission_callback' => array( '\WooCommerceDocs\API\ManifestAPI', 'permission_check' ),
			)
		);

		// bootstrap some fake data.
		if ( ! count( \WooCommerceDocs\Data\ManifestStore::get_manifest_list() ) ) {
			\WooCommerceDocs\Data\ManifestStore::add_manifest_url( 'https://example.com/manifest.json' );
		}
	}

	/**
	 * Get a list of manifest urls
	 */
	public static function get_manifests() {
		$manifests = \WooCommerceDocs\Data\ManifestStore::get_manifest_list();

		return new \WP_REST_Response( $manifests, 200 );
	}

	/**
	 * Check if user is allowed to use this endpoint.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public static function permission_check( $request ) {
		return current_user_can( 'edit_posts' );
	}
}
