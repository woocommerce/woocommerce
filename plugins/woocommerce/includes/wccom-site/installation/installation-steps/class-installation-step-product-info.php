<?php

use WC_REST_WCCOM_Site_Installer_Error_Codes as Installer_Error_Codes;
use WC_REST_WCCOM_Site_Installer_Error as Installer_Error;

defined( 'ABSPATH' ) || exit;

class WC_WCCOM_Site_Installation_Step_Get_Product_Info implements WC_WCCOM_Site_Installation_Step {
	public function __construct($state) {
		$this->state = $state;
	}
	public function run() {

		$product_id = $this->state->get_product_id();

		// Get product info from woocommerce.com.
		$request = WC_Helper_API::get(
			add_query_arg(
				[ 'product_id' => $product_id ],
				'info'
			),
			[
				'authenticated' => true,
			]
		);

		if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
			throw new Installer_Error( Installer_Error_Codes::FAILED_GETTING_PRODUCT_INFO  );
		}

		$result = json_decode( wp_remote_retrieve_body( $request ), true );

		if (!isset($result['_product_type'], $result['name'])) {
			throw new Installer_Error( Installer_Error_Codes::INVALID_PRODUCT_INFO_RESPONSE );
		}


		if ( ! empty( $result['_wporg_product'] )) {
			$download_url = $this->get_wporg_download_url( $result );
		} else {
			$download_url = $this->get_wccom_download_url( $product_id );
		}

		$this->state->set_product_type( $result['_product_type'] );
		$this->state->set_product_name ($result['name'] );
		$this->state->set_download_url ($download_url );

		return $this->state;
	}


	protected function get_wporg_download_url ( $data ) {
		if ( empty( $data['_wporg_product'] ) ) {
			return null;
		}

		if( empty( $data['download_link'] ) ) {
			throw new Installer_Error( Installer_Error_Codes::WPORG_PRODUCT_MISSING_DOWNLOAD_LINK );
		}

		return $data['download_link'];
	}

	protected function get_wccom_download_url( $product_id ) {
		WC_Helper::_flush_subscriptions_cache();

		if ( ! WC_Helper::has_product_subscription( $product_id ) ) {
			throw new Installer_Error( Installer_Error_Codes::WCCOM_PRODUCT_MISSING_SUBSCRIPTION );
		}

		// Retrieve download URL for non-wporg product.
		WC_Helper_Updater::flush_updates_cache();
		$updates = WC_Helper_Updater::get_update_data();

		if ( empty( $updates[ $product_id ]['package'] ) ) {
			return new Installer_Error( Installer_Error_Codes::WCCOM_PRODUCT_MISSING_PACKAGE );
		}

		return $updates[ $product_id ]['package'];
	}
}