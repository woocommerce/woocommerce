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
	 * Not unauthenticated generic error
	 */
	const NOT_AUTHENTICATED_CODE      = 'not_authenticated';
	const NOT_AUTHENTICATED_MESSAGE   = 'Authentication required';
	const NOT_AUTHENTICATED_HTTP_CODE = 401;

	/**
	 * No Authorization header
	 */
	const NO_AUTH_HEADER_CODE      = 'no_auth_header';
	const NO_AUTH_HEADER_MESSAGE   = 'No header "Authorization" present';
	const NO_AUTH_HEADER_HTTP_CODE = 400;

	/**
	 * Authorization header invalid
	 */
	const INVALID_AUTH_HEADER_CODE      = 'no_auth_header';
	const INVALID_AUTH_HEADER_MESSAGE   = 'Header "Authorization" is invalid';
	const INVALID_AUTH_HEADER_HTTP_CODE = 400;

	/**
	 * No Signature header
	 */
	const NO_SIGNATURE_HEADER_CODE      = 'no_signature_header';
	const NO_SIGNATURE_HEADER_MESSAGE   = 'No header "X-Woo-Signature" present';
	const NO_SIGNATURE_HEADER_HTTP_CODE = 400;

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
