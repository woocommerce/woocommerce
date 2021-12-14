<?php
/**
 * SettingsImportExport class file.
 */

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * Class to import and export the WooCommerce settings.
 *
 * @package Automattic\WooCommerce\Internal
 */
class SettingsImportExport {

	/**
	 * Id of the ajax action for settings export.
	 */
	const AJAX_EXPORT_ACTION = 'wc_export_settings';

	/**
	 * The injected instance of DownloadUtil.
	 *
	 * @var DownloadUtil
	 */
	private $download_util;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param DownloadUtil $download_util The instance of DownloadUtil to use.
	 */
	final public function init( DownloadUtil $download_util ) {
		$this->download_util = $download_util;
	}

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action(
			'wp_ajax_' . static::AJAX_EXPORT_ACTION,
			function() {
				$this->export_settings();
				die();
			}
		);
	}

	/**
	 * Generate a JSON file with all the existing settings and send it as a file.
	 */
	private function export_settings() {
		$this->verify_nonce( static::AJAX_EXPORT_ACTION );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$verbose       = 'on' === ArrayUtil::get_value_or_default( $_GET, 'export_settings_verbose' );
		$settings_data = $verbose ? $this->get_settings_verbose() : $this->get_settings_simple();

		$json_options  =
			'on' === ArrayUtil::get_value_or_default( $_GET, 'export_settings_pretty_printed' ) ?
			JSON_PRETTY_PRINT : 0;
		$settings_json = wp_json_encode( $settings_data, $json_options );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$filename = sprintf( 'woocommerce-settings-%s.json', gmdate( 'Ymdgi' ) );
		$this->download_util->download_as_attachment( $filename, 'application/json', $settings_json );
	}

	/**
	 * Verify the nonce received in the request.
	 *
	 * @param string $action_name Action name for the nonce verification.
	 */
	private function verify_nonce( string $action_name ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), $action_name ) ) {
			header( 'HTTP/1.1 403 Forbidden' );
			exit;
		}
	}

	/**
	 * Generate a verbose representation of all the existing WooCommerce settings
	 * (includes pages, sections, and settings titles, descriptions and default values).
	 *
	 * @return array An array containing verbose information about all the WooCommerce settings.
	 */
	private function get_settings_verbose() {
		$pages_data = array();

		$setting_pages = \WC_Admin_Settings::get_settings_pages();
		foreach ( $setting_pages as $settings_page ) {
			$page_data = array(
				'id'       => $settings_page->get_id(),
				'label'    => $settings_page->get_label(),
				'sections' => array(),
			);

			$page_sections = $settings_page->get_sections();

			foreach ( $page_sections as $section_id => $section_title ) {
				$section_data = array(
					'id'    => $section_id,
					'title' => $section_title,
				);

				$settings_data    = array();
				$section_settings = $settings_page->get_settings( $section_id );
				foreach ( $section_settings as $setting ) {
					if ( ! $setting['id'] || 'sectionend' === $setting['type'] || 'title' === $setting['type'] ) {
						continue;
					}

					$setting_data = array( 'id' => $setting['id'] );

					$setting_info_keys = array( 'title', 'desc', 'desc_hint', 'type', 'default' );
					foreach ( $setting_info_keys as $key ) {
						$value = ArrayUtil::get_value_or_default( $setting, $key );
						if ( null !== $value ) {
							$key                  = str_replace( 'desc', 'description', $key );
							$setting_data[ $key ] = $value;
						}
					}

					$setting_data['value'] = get_option( $setting['id'] );

					if ( ! empty( $setting_data ) ) {
						$settings_data[] = $setting_data;
					}
				}
				$section_data['settings'] = $settings_data;

				if ( ! empty( $section_data['settings'] ) ) {
					$page_data['sections'][] = $section_data;
				}
			}

			if ( ! empty( $page_data['sections'] ) ) {
				$pages_data[] = $page_data;
			}
		}

		return array( 'woocommerce_settings_pages' => $pages_data );
	}

	/**
	 * Generate a simplified representation of all the existing WooCommerce settings
	 * (includes just settings keys and values).
	 *
	 * @return array An array containing simplified information about all the WooCommerce settings.
	 */
	private function get_settings_simple() {
		$settings_data = array();

		$setting_pages = \WC_Admin_Settings::get_settings_pages();
		foreach ( $setting_pages as $settings_page ) {
			$page_sections = $settings_page->get_sections();

			foreach ( $page_sections as $section_id => $section_title ) {
				$section_settings = $settings_page->get_settings( $section_id );

				foreach ( $section_settings as $setting ) {
					if ( ! $setting['id'] || 'sectionend' === $setting['type'] || 'title' === $setting['type'] ) {
						continue;
					}

					$setting_id    = $setting['id'];
					$setting_value = get_option( $setting_id );

					$settings_data[ $setting_id ] = $setting_value;
				}
			}
		}

		return array( 'woocommerce_settings' => $settings_data );
	}
}
