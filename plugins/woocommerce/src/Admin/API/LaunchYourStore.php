<?php
/**
 * REST API Launch Your Store Controller
 *
 * Handles requests to /launch-your-store/*
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\WCAdminHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Launch Your Store controller.
 *
 * @internal
 */
class LaunchYourStore {

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
	protected $rest_base = 'launch-your-store';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/initialize-coming-soon',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'initialize_coming_soon' ),
					'permission_callback' => array( $this, 'must_be_shop_manager_or_admin' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/update-survey-status',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_survey_status' ),
					'permission_callback' => array( $this, 'must_be_shop_manager_or_admin' ),
					'args'                => array(
						'status' => array(
							'type' => 'string',
							'enum' => array( 'yes', 'no' ),
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/survey-completed',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'has_survey_completed' ),
					'permission_callback' => array( $this, 'must_be_shop_manager_or_admin' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/woopayments/test-orders/count',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_woopay_test_orders_count' ),
					'permission_callback' => array( $this, 'must_be_shop_manager_or_admin' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/woopayments/test-orders',
			array(
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_woopay_test_orders' ),
					'permission_callback' => array( $this, 'must_be_shop_manager_or_admin' ),
				),
			)
		);
	}

	/**
	 * User must be either shop_manager or administrator.
	 *
	 * @return bool
	 */
	public function must_be_shop_manager_or_admin() {
		// phpcs:ignore
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'administrator' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Initializes options for coming soon. Overwrites existing coming soon status but keeps the private link and share key.
	 *
	 * @return bool|void
	 */
	public function initialize_coming_soon() {
		$current_user_id = get_current_user_id();
		// Abort if we don't have a user id for some reason.
		if ( ! $current_user_id ) {
			return;
		}

		$coming_soon      = 'yes';
		$store_pages_only = WCAdminHelper::is_site_fresh() ? 'no' : 'yes';
		$private_link     = 'no';
		$share_key        = wp_generate_password( 32, false );

		update_option( 'woocommerce_coming_soon', $coming_soon );
		update_option( 'woocommerce_store_pages_only', $store_pages_only );
		add_option( 'woocommerce_private_link', $private_link );
		add_option( 'woocommerce_share_key', $share_key );

		wc_admin_record_tracks_event(
			'launch_your_store_initialize_coming_soon',
			array(
				'coming_soon'      => $coming_soon,
				'store_pages_only' => $store_pages_only,
				'private_link'     => $private_link,
			)
		);

		return true;
	}

	/**
	 * Count the test orders created during Woo Payments test mode.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_woopay_test_orders_count() {
		$return = function ( $count ) {
			return new \WP_REST_Response( array( 'count' => $count ) );
		};

		$orders = wc_get_orders(
			array(
				// phpcs:ignore
				'meta_key'   => '_wcpay_mode',
				// phpcs:ignore
				'meta_value' => 'test',
				'return'     => 'ids',
			)
		);

		return $return( count( $orders ) );
	}

	/**
	 * Delete WooPayments test orders.
	 *
	 * @return \WP_REST_Response
	 */
	public function delete_woopay_test_orders() {
		$return = function ( $status = 204 ) {
			return new \WP_REST_Response( null, $status );
		};

		$orders = wc_get_orders(
			array(
				// phpcs:ignore
				'meta_key'   => '_wcpay_mode',
				// phpcs:ignore
				'meta_value' => 'test',
			)
		);

		foreach ( $orders as $order ) {
			$order->delete();
		}

		return $return();
	}

	/**
	 * Update woocommerce_admin_launch_your_store_survey_completed to yes or no
	 *
	 * @param \WP_REST_Request $request WP_REST_Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function update_survey_status( \WP_REST_Request $request ) {
		update_option( 'woocommerce_admin_launch_your_store_survey_completed', $request->get_param( 'status' ) );
		return new \WP_REST_Response();
	}

	/**
	 * Return woocommerce_admin_launch_your_store_survey_completed option.
	 *
	 * @return \WP_REST_Response
	 */
	public function has_survey_completed() {
		return new \WP_REST_Response( get_option( 'woocommerce_admin_launch_your_store_survey_completed', 'no' ) );
	}
}
