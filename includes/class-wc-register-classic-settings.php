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
class WC_Register_Classic_Settings {

	/** @var class Current settings class. Used to pull settings. */
	protected $page;

	/**
	 * Hooks into the settings API and starts registering our classic settings.
	 * @since 2.7.0
	 */
	public function __construct( $page ) {
		$this->page = $page;
		add_filter( 'woocommerce_settings_groups', array( $this, 'register_classic_group' ) );
		add_filter( 'woocommerce_settings-' . $this->page->get_id(),  array( $this, 'register_classic_settings' ) );
	}

	/**
	* Registers a setting group.
	* @since  2.7.0
	* @param  array $group
	* @return array
	*/
	public function register_classic_group( $groups ) {
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
	public function register_classic_settings( $settings ) {
		$classic_sections = $this->page->get_sections();
		if ( empty( $classic_sections ) ) {
			$classic_sections = array( '' );
		}

		foreach ( $classic_sections as $classic_section => $classic_section_label ) {
			$classic_settings = $this->page->get_settings( $classic_section );
			foreach ( $classic_settings as $classic_setting ) {
				$new_setting = array(
					'id'          => $classic_setting['id'],
					'label'       => ( ! empty( $classic_setting['title'] ) ? $classic_setting['title'] : '' ),
					'description' => ( ! empty( $classic_setting['desc'] ) ? $classic_setting['desc'] : '' ),
					'type'        => $classic_setting['type'],
				);
				if ( isset( $classic_setting['default'] ) ) {
					$new_setting['default'] = $classic_setting['default'];
				}
				if ( isset( $classic_setting['options'] ) ) {
					$new_setting['options'] = $classic_setting['options'];
				}
				if ( isset( $classic_setting['desc_tip'] ) ) {
					if ( true === $classic_setting['desc_tip'] ) {
						$new_setting['tip'] = $classic_setting['desc'];
					} else if ( ! empty( $classic_setting['desc_tip'] ) ) {
						$new_setting['tip'] = $classic_setting['desc_tip'];
					}
				}
				$settings[] = $new_setting;
			}
		}
		return $settings;
	}
}

/**
 * Register full classic settings to the REST API.
 * @since  2.7.0
 */
 function wc_settings_api_register_classic() {
	 $pages = WC_Admin_Settings::get_settings_pages();
	 foreach ( $pages as $page ) {
		 new WC_Register_Classic_Settings( $page );
	 }
 }

add_action( 'rest_api_init', 'wc_settings_api_register_classic' );
