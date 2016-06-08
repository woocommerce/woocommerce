<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Takes settings registered for WP-Admin and hooks them up to the new
 * WC settings API so they can be accessed via REST.
 *
 * @version  2.7.0
 * @since    2.7.0
 * @package	 WooCommerce/Classes
 * @category Class
 */
class WC_Register_Legacy_Settings {

	/** @var class Current settings class. Used to pull settings. */
	protected $page;

	/**
	 * Hooks into the settings API and starts registering our settings registered via legacy hooks/filters.
	 * @since 2.7.0
	 */
	public function __construct( $page ) {
		$this->page = $page;
		add_filter( 'woocommerce_settings_groups', array( $this, 'register_legacy_group' ) );
		add_filter( 'woocommerce_settings-' . $this->page->get_id(),  array( $this, 'register_legacy_settings' ) );
	}

	/**
	* Registers a setting group.
	* @since  2.7.0
	* @param  array $group
	* @return array
	*/
	public function register_legacy_group( $groups ) {
		$groups[] = array(
			'id'    => $this->page->get_id(),
			'label' => $this->page->get_label(),
		);
		return $groups;
	}

	/**
	* Registers the actual settings to the group they came from.
	* @since  2.7.0
	* @param  array $settings Existing registered settings
	* @return array
	*/
	public function register_legacy_settings( $settings ) {
		$legacy_sections = $this->page->get_sections();
		if ( empty( $legacy_sections ) ) {
			$legacy_sections = array( '' );
		}

		foreach ( $legacy_sections as $legacy_section => $legacy_section_label ) {
			$legacy_settings = $this->page->get_settings( $legacy_section );
			foreach ( $legacy_settings as $legacy_setting ) {
				if ( ! isset( $legacy_setting['id'] ) ) {
					continue;
				}
				$new_setting = array(
					'id'          => $legacy_setting['id'],
					'label'       => ( ! empty( $legacy_setting['title'] ) ? $legacy_setting['title'] : '' ),
					'description' => ( ! empty( $legacy_setting['desc'] ) ? $legacy_setting['desc'] : '' ),
					'type'        => $legacy_setting['type'],
				);
				if ( isset( $legacy_setting['default'] ) ) {
					$new_setting['default'] = $legacy_setting['default'];
				}
				if ( isset( $legacy_setting['options'] ) ) {
					$new_setting['options'] = $legacy_setting['options'];
				}
				if ( isset( $legacy_setting['desc_tip'] ) ) {
					if ( true === $legacy_setting['desc_tip'] ) {
						$new_setting['tip'] = $legacy_setting['desc'];
					} else if ( ! empty( $legacy_setting['desc_tip'] ) ) {
						$new_setting['tip'] = $legacy_setting['desc_tip'];
					}
				}
				$settings[] = $new_setting;
			}
		}
		return $settings;
	}
}

/**
 * Register legacy settings to the REST API.
 * @since  2.7.0
 */
 function wc_settings_api_register_legacy() {
	 $pages = WC_Admin_Settings::get_settings_pages();
	 foreach ( $pages as $page ) {
		 new WC_Register_Legacy_Settings( $page );
	 }
 }

add_action( 'rest_api_init', 'wc_settings_api_register_legacy' );
