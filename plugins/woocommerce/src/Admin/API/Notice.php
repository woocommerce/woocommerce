<?php
/**
 * REST API Notice controller
 *
 * Handles requests to /notice/
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\PluginsHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Notice Controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class Notice extends \WC_REST_Data_Controller {

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
	protected $rest_base = 'notice';

	/**
	 * Register the routes for admin notes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/dismiss',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'dissmiss_notice' ),
					'permission_callback' => array( $this, 'get_permission' ),
				),
			)
		);
	}

	/**
	 * Save notice dismiss information in user meta.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function dissmiss_notice( $request ) {
		if ( ! isset( $request['dismiss_notice_nonce'] )
			|| ! wp_verify_nonce( $request['dismiss_notice_nonce'], 'dismiss_notice' ) ) {
			return new WP_Error( 'unauthorized', 'Invalid nonce.', array( 'status' => 401 ) );
		}
		$notice_id = isset( $request['notice_id'] ) ? sanitize_text_field( wp_unslash( $request['notice_id'] ) ) : '';
		$dismissed = false;
		switch ( $notice_id ) {
			case 'woo-subscription-expired-notice':
				update_user_meta( get_current_user_id(), PluginsHelper::DISMISS_EXPIRED_SUBS_NOTICE, time() );
				$dismissed = true;
				break;
			case 'woo-subscription-expiring-notice':
				update_user_meta( get_current_user_id(), PluginsHelper::DISMISS_EXPIRING_SUBS_NOTICE, time() );
				$dismissed = true;
				break;
		}

		return rest_ensure_response(
			array(
				'success' => $dismissed,
			)
		);
	}

	/**
	 * Check user has the necessary permissions to perform this action.
	 *
	 * @return bool
	 */
	public function get_permission(): bool {
		return current_user_can( 'manage_woocommerce' );
	}
}
