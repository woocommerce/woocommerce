<?php
/**
 * WCCOM Site Installer Errors Class
 *
 * @package WooCommerce\WooCommerce_Site\Rest_Api
 * @since   3.9.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCCOM Site Installer Errors Class
 *
 * Stores data for errors, returned by installer API.
 */
class WC_REST_WCCOM_Site_Installer_Errors {

	/**
	 * No permissions error
	 */
	const NO_PERMISSION_CODE      = 'woocommerce_rest_cannot_install_product';
	const NO_PERMISSION_MESSAGE   = 'You do not have permission to install plugin or theme';
	const NO_PERMISSION_HTTP_CODE = 401;
}
