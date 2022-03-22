<?php
/**
 * REST API Notices Controller
 *
 * Handles requests to get and dismiss notices.
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\Features\TransientNotices;

defined( 'ABSPATH' ) || exit;

/**
 * Notices Controller.
 *
 * @extends WC_REST_Data_Controller
 */
class Notices extends \WC_REST_Data_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'notices';

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
					'callback'            => array( $this, 'get_notices' ),
					'permission_callback' => false,
					'args'                => array(
						'user_id' => array(
							'required'          => false,
							'validate_callback' => function( $param, $request, $key ) {
								return is_numeric( $param );
							},
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[a-z0-9_\-]+)/dismiss',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'dismiss_notice' ),
					'permission_callback' => false,
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Get the notices.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_notices( $request ) {
		$user_id = $request->get_param( 'user_id' );
		$notices = $user_id ? TransientNotices::get_queue_by_user( intval( $user_id ) ) : TransientNotices::get_queue();

		return rest_ensure_response( apply_filters( 'woocommerce_admin_notices', $notices ) );
	}

	/**
	 * Dismiss a single notice.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function dismiss_notice( $request ) {
		$id      = $request->get_param( 'id' );
		$removal = TransientNotices::remove( $id );

		return rest_ensure_response( $removal );
	}

}
