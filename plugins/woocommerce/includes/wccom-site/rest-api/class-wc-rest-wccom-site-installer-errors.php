<?php
/**
 * WCCOM Site Installer Errors Class
 *
 * @package WooCommerce\WCCom\API
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
	 * Not unauthenticated generic error
	 */
	const NOT_AUTHENTICATED_CODE      = 'not_authenticated';
	const NOT_AUTHENTICATED_MESSAGE   = 'Authentication required';
	const NOT_AUTHENTICATED_HTTP_CODE = 401;

	/**
	 * No access token provided
	 */
	const NO_ACCESS_TOKEN_CODE      = 'no_access_token';
	const NO_ACCESS_TOKEN_MESSAGE   = 'No access token provided';
	const NO_ACCESS_TOKEN_HTTP_CODE = 400;

	/**
	 * No signature provided
	 */
	const NO_SIGNATURE_CODE      = 'no_signature';
	const NO_SIGNATURE_MESSAGE   = 'No signature provided';
	const NO_SIGNATURE_HTTP_CODE = 400;

	/**
	 * Site not connected to WooCommerce.com
	 */
	const SITE_NOT_CONNECTED_CODE      = 'site_not_connnected';
	const SITE_NOT_CONNECTED_MESSAGE   = 'Site not connected to WooCommerce.com';
	const SITE_NOT_CONNECTED_HTTP_CODE = 401;

	/**
	* Provided access token is not valid
	*/
	const INVALID_TOKEN_CODE      = 'invalid_token';
	const INVALID_TOKEN_MESSAGE   = 'Invalid access token provided';
	const INVALID_TOKEN_HTTP_CODE = 401;

	/**
	 * Request verification by provided signature failed
	 */
	const REQUEST_VERIFICATION_FAILED_CODE      = 'request_verification_failed';
	const REQUEST_VERIFICATION_FAILED_MESSAGE   = 'Request verification by signature failed';
	const REQUEST_VERIFICATION_FAILED_HTTP_CODE = 400;

	/**
	 * User doesn't exist
	 */
	const USER_NOT_FOUND_CODE      = 'user_not_found';
	const USER_NOT_FOUND_MESSAGE   = 'Token owning user not found';
	const USER_NOT_FOUND_HTTP_CODE = 401;

	/**
	 * No permissions error
	 */
	const NO_PERMISSION_CODE      = 'forbidden';
	const NO_PERMISSION_MESSAGE   = 'You do not have permission to install plugin or theme';
	const NO_PERMISSION_HTTP_CODE = 403;
}
