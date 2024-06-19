<?php
/**
 * WCCOM Site Base REST API Controller
 *
 * Handles requests to /ssr.
 *
 * @package WooCommerce\WCCom\API
 * @since 8.6.0
 */

use WC_REST_WCCOM_Site_Installer_Error_Codes as Installer_Error_Codes;
use WC_REST_WCCOM_Site_Installer_Error as Installer_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST API WCCOM Site Base REST API Controller Astract Class.
 *
 * @extends WC_REST_Controller
 */
abstract class WC_REST_WCCOM_Site_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wccom-site/v3';

	/**
	 * Check whether user has permission to access controller's endpoints.
	 *
	 * @since 8.6.0
	 * @param WP_USER $user User object.
	 * @return bool
	 */
	abstract protected function user_has_permission( $user ) : bool;

	/**
	 * Check permissions.
	 *
	 * Please note that access to this endpoint is also governed by the WC_WCCOM_Site::authenticate_wccom() method.
	 *
	 * @since  7.8.0
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		$current_user = wp_get_current_user();

		if ( empty( $current_user ) || ( $current_user instanceof WP_User && ! $current_user->exists() ) ) {
			/**
			 * This filter allows to provide a custom error message when the user is not authenticated.
			 *
			 * @since 3.7.0
			 */
			$error = apply_filters(
				WC_WCCOM_Site::AUTH_ERROR_FILTER_NAME,
				new Installer_Error( Installer_Error_Codes::NOT_AUTHENTICATED )
			);
			return new WP_Error(
				$error->get_error_code(),
				$error->get_error_message(),
				array( 'status' => $error->get_http_code() )
			);
		}

		if ( ! $this->user_has_permission( $current_user ) ) {
			$error = new Installer_Error( Installer_Error_Codes::NO_PERMISSION );
			return new WP_Error(
				$error->get_error_code(),
				$error->get_error_message(),
				array( 'status' => $error->get_http_code() )
			);
		}

		return true;
	}
}
