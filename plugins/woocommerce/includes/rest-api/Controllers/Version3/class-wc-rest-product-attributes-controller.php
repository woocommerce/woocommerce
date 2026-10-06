<?php
/**
 * REST API Product Attributes controller
 *
 * Handles requests to the products/attributes endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Attributes controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Attributes_V2_Controller
 */
class WC_REST_Product_Attributes_Controller extends WC_REST_Product_Attributes_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Generates a unique slug for a given attribute name. We do this so that we can 
	 * create more than one attribute with the same name.
	 *
	 * @param string $attribute_name The attribute name to generate a slug for.
	 * @return string The auto-generated slug
	 */
	private function generate_unique_slug( $attribute_name ) {
		global $wpdb;

		$root_slug = wc_sanitize_taxonomy_name( $attribute_name );

		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name LIKE %s ORDER BY attribute_id DESC LIMIT 1", $root_slug . '%' )
		);

		// The slug is already unique!
		if ( empty( $results ) ) {
			return $root_slug;
		}

		$last_created_slug = $results[0]->attribute_name;
		$suffix = intval( substr( $last_created_slug, strrpos( $last_created_slug, '-' ) + 1 ) );

		return $root_slug . '-' . ( $suffix + 1 );
	}

	/**
	 * Create a single attribute.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function create_item( $request ) {
		global $wpdb;

		$generate_slug = stripslashes( $request['generate_slug'] );
		$slug          = wc_sanitize_taxonomy_name( stripslashes( $request['slug'] ) );

		if ( ! empty( $generate_slug ) && 'true' === $generate_slug ) {
			$slug = $this->generate_unique_slug( $request['name'] );
		}

		$id = wc_create_attribute(
			array(
				'name'         => $request['name'],
				'slug'         => $slug,
				'type'         => ! empty( $request['type'] ) ? $request['type'] : 'select',
				'order_by'     => ! empty( $request['order_by'] ) ? $request['order_by'] : 'menu_order',
				'has_archives' => true === $request['has_archives'],
			)
		);

		// Checks for errors.
		if ( is_wp_error( $id ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', $id->get_error_message(), array( 'status' => 400 ) );
		}

		$attribute = $this->get_attribute( $id );

		if ( is_wp_error( $attribute ) ) {
			return $attribute;
		}

		$this->update_additional_fields_for_object( $attribute, $request );

		/**
		 * Fires after a single product attribute is created or updated via the REST API.
		 *
		 * @param stdObject       $attribute Inserted attribute object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating attribute, false when updating.
		 */
		do_action( 'woocommerce_rest_insert_product_attribute', $attribute, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $attribute, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( '/' . $this->namespace . '/' . $this->rest_base . '/' . $attribute->attribute_id ) );

		return $response;
	}
}
