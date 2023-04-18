<?php
/**
 * Get product info step.
 *
 * @package WooCommerce\WCCOM\Installation\Installation_Steps
 */

use WC_REST_WCCOM_Site_Installer_Error_Codes as Installer_Error_Codes;
use WC_REST_WCCOM_Site_Installer_Error as Installer_Error;

defined( 'ABSPATH' ) || exit;

class WC_WCCOM_Site_Installation_Step_Unpack_Product implements WC_WCCOM_Site_Installation_Step {
	public function __construct( $state ) {
		$this->state = $state;
	}

	public function run() {
		$upgrader      = WC_WCCOM_Site_Installer::get_wp_upgrader();
		$unpacked_path = $upgrader->unpack_package( $this->state->get_download_path(), true );

		if ( empty( $unpacked_path ) ) {
			return new Installer_Error( Installer_Error_Codes::MISSING_UNPACKED_PATH );
		}

		$this->state->set_unpacked_path( $unpacked_path );

		return $this->state;
	}
}
