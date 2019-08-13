<?php
/**
 * REST API Onboarding Levels Controller
 *
 * Handles requests to /onboarding/levels
 *
 * @package WooCommerce Admin/API
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

/**
 * Onboarding Levels controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Data_Controller
 */
class OnboardingLevels extends \WC_REST_Data_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'onboarding/levels';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Get an array of all levels and child tasks.
	 *
	 * @todo Status values below should pull from the database task status once implemented.
	 */
	public function get_levels() {
		$levels = array(
			'account'    => array(
				'tasks' => array(
					'create_account' => array(
						'label'        => __( 'Create an account', 'woocommerce-admin' ),
						'description'  => __( 'Speed up & secure your store', 'woocommerce-admin' ),
						'illustration' => '',
						'status'       => 'visible',
						'is_required'  => false,
					),
				),
			),
			'storefront' => array(
				'tasks' => array(
					'add_products'         => array(
						'label'        => __( 'Add your products', 'woocommerce-admin' ),
						'description'  => __( 'Bring your store to life', 'woocommerce-admin' ),
						'illustration' => '',
						'status'       => 'visible',
						'is_required'  => true,
					),
					'customize_appearance' => array(
						'label'        => __( 'Customize Appearance', 'woocommerce-admin' ),
						'description'  => __( 'Ensure your store is on-brand', 'woocommerce-admin' ),
						'illustration' => '',
						'status'       => 'visible',
						'is_required'  => false,
					),
				),
			),
			'checkout'   => array(
				'id'    => 'checkout',
				'tasks' => array(
					'configure_shipping' => array(
						'label'        => __( 'Configure shipping', 'woocommerce-admin' ),
						'description'  => __( 'Set up prices and destinations', 'woocommerce-admin' ),
						'illustration' => '',
						'status'       => 'visible',
						'is_required'  => true,
					),
					'configure_taxes'    => array(
						'label'        => __( 'Configure taxes', 'woocommerce-admin' ),
						'description'  => __( 'Set up sales tax rates', 'woocommerce-admin' ),
						'illustration' => '',
						'status'       => 'visible',
						'is_required'  => false,
					),
					'configure_payments' => array(
						'label'        => __( 'Configure payments', 'woocommerce-admin' ),
						'description'  => __( 'Choose payment providers', 'woocommerce-admin' ),
						'illustration' => '',
						'status'       => 'visible',
						'is_required'  => true,
					),
				),
			),
		);

		return apply_filters( 'woocommerce_onboarding_levels', $levels );
	}

	/**
	 * Return all level items and child tasks.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		global $wpdb;

		$levels = $this->get_levels();
		$data   = array();

		if ( ! empty( $levels ) ) {
			foreach ( $levels as $id => $level ) {
				$level    = $this->convert_to_non_associative( $level, $id );
				$response = $this->prepare_item_for_response( $level, $request );
				$data[]   = $this->prepare_response_for_collection( $response );
			}
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Prepare the data object for response.
	 *
	 * @param object          $item Data object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data     = $this->add_additional_fields_to_object( $item, $request );
		$data     = $this->filter_response_by_context( $data, 'view' );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item ) );

		/**
		 * Filter the list returned from the API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array            $item     The original item.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_onboarding_level', $response, $item, $request );
	}

	/**
	 * Convert the associative levels and tasks to non-associative for JSON use.
	 *
	 * @param array  $item Level.
	 * @param string $id Level ID.
	 * @return array
	 */
	public function convert_to_non_associative( $item, $id ) {
		$item = array( 'id' => $id ) + $item;

		$tasks = array();
		foreach ( $item['tasks'] as $key => $task ) {
			$tasks[] = array( 'id' => $key ) + $task;
		}
		$item['tasks'] = $tasks;

		return $item;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param object $item Data object.
	 * @return array Links for the given object.
	 * @todo Check to make sure this generates a valid URL after #1897.
	 */
	protected function prepare_links( $item ) {
		$links = array(
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/onboarding/tasks?level=%s', $this->namespace, $item['id'] ) ),
			),
		);
		return $links;
	}

	/**
	 * Get the schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'onboarding_level',
			'type'       => 'object',
			'properties' => array(
				'id'    => array(
					'type'        => 'string',
					'description' => __( 'Level ID.', 'woocommerce-admin' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'tasks' => array(
					'type'        => 'array',
					'description' => __( 'Array of tasks under the level.', 'woocommerce-admin' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array(
								'description' => __( 'Task ID.', 'woocommerce-admin' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'label'        => array(
								'description' => __( 'Task label.', 'woocommerce-admin' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'description'  => array(
								'description' => __( 'Task description.', 'woocommerce-admin' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'illustration' => array(
								'description' => __( 'URL for illustration used.', 'woocommerce-admin' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'status'       => array(
								'description' => __( 'Task status.', 'woocommerce-admin' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
								'enum'        => array( 'visible', 'hidden', 'in-progress', 'skipped', 'completed' ),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
