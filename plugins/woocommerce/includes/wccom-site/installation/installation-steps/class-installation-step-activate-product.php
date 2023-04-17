<?php

use WC_REST_WCCOM_Site_Installer_Error_Codes as Installer_Error_Codes;
use WC_REST_WCCOM_Site_Installer_Error as Installer_Error;

defined( 'ABSPATH' ) || exit;

class WC_WCCOM_Site_Installation_Step_Activate_Product implements WC_WCCOM_Site_Installation_Step {
	public function __construct($state) {
		$this->state = $state;
	}

	public function run(  ) {
		$product_id = $this->state->get_product_id();

		if ( 'plugin' === $this->state->get_product_type() ) {
			$this->activate_plugin( $product_id );
		} else {
			$this->activate_theme( $product_id );
		}

		return $this->state;
	}

	private function activate_plugin( $product_id ) {
		// Clear plugins cache used in `WC_Helper::get_local_woo_plugins`.
		wp_clean_plugins_cache();
		$filename = false;

		// If product is WP.org one, find out its filename.
		$dir_name = $this->get_wporg_product_dir_name( );
		if ( false !== $dir_name ) {
			$filename = \WC_WCCOM_Site_Installer::get_wporg_plugin_main_file( $dir_name );
		}

		if ( false === $filename ) {
			$plugins = wp_list_filter(
				WC_Helper::get_local_woo_plugins(),
				[
					'_product_id' => $product_id,
				]
			);

			$filename = is_array( $plugins ) && ! empty( $plugins ) ? key( $plugins ) : '';
		}

		if ( empty( $filename ) ) {
			return new Installer_Error( Installer_Error_Codes::UNKNOWN_FILENAME );
		}

		$result = activate_plugin( $filename );

		if (is_wp_error($result)) {
			return new Installer_Error( Installer_Error_Codes::PLUGIN_ACTIVATION_ERROR, $result->get_error_message() );
		}
	}

	private function activate_theme( $product_id ) {
		// Clear plugins cache used in `WC_Helper::get_local_woo_themes`.
		wp_clean_themes_cache();
		$theme_slug = false;

		// If product is WP.org theme, find out its slug.
		$dir_name = $this->get_wporg_product_dir_name( );
		if ( false !== $dir_name ) {
			$theme_slug = basename( $dir_name );
		}

		if ( false === $theme_slug ) {
			$themes = wp_list_filter(
				WC_Helper::get_local_woo_themes(),
				[
					'_product_id' => $product_id,
				]
			);

			$theme_slug = is_array( $themes ) && ! empty( $themes ) ? dirname( key( $themes ) ) : '';
		}

		if ( empty( $theme_slug ) ) {
			return new Installer_Error( Installer_Error_Codes::UNKNOWN_FILENAME );
		}

		switch_theme( $theme_slug );
	}

	private function get_wporg_product_dir_name( ) {
		if ( empty($this->state->get_installed_path() ) ) {
			return false;
		}

		// Check whether product was downloaded from WordPress.org.
		$download_url = $this->state->get_download_url();
		$parsed_url = wp_parse_url( $download_url );
		if ( ! empty( $parsed_url['host'] ) && 'downloads.wordpress.org' !== $parsed_url['host'] ) {
			return false;
		}

		return basename( $this->state->get_installed_path() );
	}
}