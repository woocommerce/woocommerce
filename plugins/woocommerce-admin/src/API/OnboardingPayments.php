<?php
/**
 * REST API Onboarding Payments Controller
 *
 * Handles requests to /onboarding/payments
 */

namespace Automattic\WooCommerce\Admin\API;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\Init as PaymentGatewaySuggestions;

/**
 * Onboarding Payments Controller.
 *
 * @extends WC_REST_Data_Controller
 */
class OnboardingPayments extends \WC_REST_Data_Controller {

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
	protected $rest_base = 'onboarding/payments';

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
					'callback'            => array( $this, 'get_suggestions' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check whether a given request has permission to read onboarding profile data.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce-admin' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Return available payment gateway suggestions.
	 *
	 * @param \WP_REST_Request $request Request data.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_suggestions( $request ) {
		return PaymentGatewaySuggestions::get_suggestions();
	}

}
