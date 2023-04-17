<?php
/**
 * WCCOM Site Installer Error Class
 *
 * @package WooCommerce\WCCom\API
 * @since   7.7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCCOM Site Installer Error Class
 *
 */
class WC_REST_WCCOM_Site_Installer_Error extends Exception {

	public function __construct($error_code, $error_message = null, $http_code = null) {
		$this->error_code = $error_code;
		$this->error_message = $error_message ?? WC_REST_WCCOM_Site_Installer_Error_Codes::ERROR_MESSAGES[ $error_code ] ?? '';
		$this->http_code = $http_code ?? WC_REST_WCCOM_Site_Installer_Error_Codes::HTTP_CODES[ $error_code ] ?? 400;

        parent::__construct( $error_code );
	}

	public function get_error_code( ) {
		return $this->error_code;
	}

	public function get_error_message( ) {
		return $this->error_message;
	}

	public function get_http_code( ) {
		return $this->http_code;
	}
}
