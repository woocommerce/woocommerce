<?php
/**
 * REST API Layout Templates controller
 *
 * Handles requests to /layout-templates.
 *
 * @package WooCommerce\RestApi
 * @since 8.5.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry\BlockTemplateRegistry;

class WC_REST_Layout_Templates_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'layout-templates';

	/**
	 * Register the routes for layouts.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => array(),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\w[\w\s\-]*)',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(),
				),
			)
		);
	}

	public function get_items_permissions_check( $request ) {
		return true;
	}

	public function get_item_permissions_check( $request ) {
		return true;
	}

	public function get_items( $request ) {
		$layout_templates = array();

		$template_registry           = wc_get_container()->get( BlockTemplateRegistry::class );
		$registered_layout_templates = $template_registry->get_all_registered();

		foreach ( $registered_layout_templates as $layout_template ) {
			$layout_templates[] = $layout_template->get_formatted_template();
		}

		$response = rest_ensure_response( $layout_templates );

		return $response;
	}

	public function get_item( $request ) {
		$id = $request['id'];

		$template_registry = wc_get_container()->get( BlockTemplateRegistry::class );
		$layout_template   = $template_registry->get_registered( $id );

		if ( ! $layout_template ) {
			return new WP_Error( 'woocommerce_rest_layout_template_invalid_id', __( 'Invalid layout template ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$response = rest_ensure_response( $layout_template->get_formatted_template() );

		return $response;
	}
}
