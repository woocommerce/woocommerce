<?php
/**
 * REST API Product Attribute Terms controller
 *
 * Handles requests to the products/attributes/<attribute_id>/terms endpoint.
 *
 * @package WooCommerce/API
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Attribute Terms controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Product_Attribute_Terms_V3_Controller
 */
class WC_REST_Product_Attribute_Terms_Controller extends WC_REST_Product_Attribute_Terms_V3_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Prepare a single product attribute term output for response.
	 *
	 * @param WP_Term $item Term object.
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response $response
	 */
	public function prepare_item_for_response( $item, $request ) {
		// Get term order.
		$menu_order = get_woocommerce_term_meta( $item->term_id, 'order_' . $this->taxonomy );

		// Get the attribute slug.
		$attribute_id = absint( $request->get_param( 'attribute_id' ) );
		$attribute    = wc_get_attribute( $attribute_id );

		$data = array(
			'id'          => (int) $item->term_id,
			'name'        => $item->name,
			'slug'        => $item->slug,
			'description' => $item->description,
			'menu_order'  => (int) $menu_order,
			'count'       => (int) $item->count,
			'attr_name'   => $attribute->name,
			'attr_slug'   => $attribute->slug,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item, $request ) );

		/**
		 * Filter a term item returned from the API.
		 *
		 * Allows modification of the term data right before it is returned.
		 *
		 * @param WP_REST_Response  $response  The response object.
		 * @param object            $item      The original term object.
		 * @param WP_REST_Request   $request   Request used to generate the response.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->taxonomy}", $response, $item, $request );
	}

	/**
	 * Get the Attribute Term's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$raw_schema = parent::get_item_schema();
		$schema     = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'product_block_category',
			'type'       => 'object',
			'properties' => array(),
		);
		$schema['properties']['id']          = $raw_schema['properties']['id'];
		$schema['properties']['name']        = $raw_schema['properties']['name'];
		$schema['properties']['slug']        = $raw_schema['properties']['slug'];
		$schema['properties']['description'] = $raw_schema['properties']['description'];
		$schema['properties']['menu_order']  = $raw_schema['properties']['menu_order'];
		$schema['properties']['count']       = $raw_schema['properties']['count'];
		$schema['properties']['attr_name']   = array(
			'description' => __( 'Attribute group name.', 'woocommerce' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'arg_options' => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
		$schema['properties']['attr_slug']   = array(
			'description' => __( 'An alphanumeric identifier for the resource unique to its type.', 'woocommerce' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'arg_options' => array(
				'sanitize_callback' => 'sanitize_title',
			),
		);
		return $this->add_additional_fields_schema( $schema );
	}
}
