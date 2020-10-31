<?php
/**
 * REST API Product Attribute Terms controller
 *
 * Handles requests to the products/attributes/<attribute_id>/terms endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Attribute Terms controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Attribute_Terms_V2_Controller
 */
class WC_REST_Product_Attribute_Terms_Controller extends WC_REST_Product_Attribute_Terms_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Update term meta fields.
	 *
	 * @param WP_Term         $term The term to update.
	 * @param WP_REST_Request         $request Request data.
	 * @return bool|WP_Error
	 */
	protected function update_term_meta_fields( $term, $request ) {
		$id = (int) $term->term_id;

		update_term_meta( $id, 'order', $request['menu_order'] );

		return true;
	}
}
