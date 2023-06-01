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

		register_rest_route(
			'woocommerce-docs/v1',
			'/manifests',
			array(
				'methods'             => 'POST',
				'callback'            => array( '\WooCommerceDocs\API\ManifestAPI', 'add_manifest' ),
				'permission_callback' => array( '\WooCommerceDocs\API\ManifestAPI', 'permission_check' ),
			)
		);

		register_rest_route(
			'woocommerce-docs/v1',
			'/manifests',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( '\WooCommerceDocs\API\ManifestAPI', 'delete_manifest' ),
				'permission_callback' => array( '\WooCommerceDocs\API\ManifestAPI', 'permission_check' ),
				'args'                => array(
					'manifest' => array(
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Get a list of manifest urls
	 */
	public static function get_manifests() {
		$manifests = \WooCommerceDocs\Data\ManifestStore::get_manifest_list();

		return new \WP_REST_Response( $manifests, 200 );
	}

	/**
	 * Add a manifest url to the list
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 */
	public static function add_manifest( $request ) {
		$manifest_url = $request->get_param( 'manifest' );
		if ( ! $manifest_url ) {
			return new \WP_Error( 'invalid_manifest_url', __( 'Invalid manifest url', 'woocommerce-docs' ), array( 'status' => 400 ) );
		}

		\WooCommerceDocs\Data\ManifestStore::add_manifest_url( $manifest_url );

		return new \WP_REST_Response( \WooCommerceDocs\Data\ManifestStore::get_manifest_list(), 200 );
	}

	/**
	 * Remove a manifest url from the list.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 */
	public static function delete_manifest( $request ) {
		$manifest_url = $request->get_param( 'manifest' );
		if ( ! $manifest_url ) {
			return new \WP_Error( 'no_manifest_url', __( 'Manifest url not passed', 'woocommerce-docs' ), array( 'status' => 400 ) );
		}

		$manifests = \WooCommerceDocs\Data\ManifestStore::get_manifest_list();

		$index = array_search( $manifest_url, $manifests, true );
		if ( false === $index ) {
			return new \WP_Error( 'invalid_manifest_url', __( 'Invalid manifest url', 'woocommerce-docs' ), array( 'status' => 400 ) );
		}

		\WooCommerceDocs\Data\ManifestStore::remove_manifest_url( $manifest_url );

		return new \WP_REST_Response( \WooCommerceDocs\Data\ManifestStore::get_manifest_list(), 200 );
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
