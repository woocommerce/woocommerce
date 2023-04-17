<?php

use WC_REST_WCCOM_Site_Installer_Error_Codes as Installer_Error_Codes;
use WC_REST_WCCOM_Site_Installer_Error as Installer_Error;

defined( 'ABSPATH' ) || exit;

class WC_WCCOM_Site_Installation_Step_Download_Product implements WC_WCCOM_Site_Installation_Step {
	public function __construct($state) {
		$this->state = $state;
	}

	public function run(  ) {
		$upgrader = WC_WCCOM_Site_Installer::get_wp_upgrader();

		$download_path = $upgrader->download_package( $this->state->get_download_url() );

		if (empty($download_path)) {
			throw new Installer_Error( Installer_Error_Codes::MISSING_DOWNLOAD_PATH);
		}

		$this->state->set_download_path($download_path);

		return $this->state;
	}
}