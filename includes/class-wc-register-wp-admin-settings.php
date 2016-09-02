<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Take settings registered for WP-Admin and hooks them up to the REST API.
 *
 * @version  2.7.0
 * @since    2.7.0
 * @package  WooCommerce/Classes
 * @category Class
 */
class WC_Register_WP_Admin_Settings {

	/** @var class Current settings class. Used to pull settings. */
	protected $page;

	/**
	 * Hooks into the settings API and starts registering our settings.
	 *
	 * @since 2.7.0
	 */
	public function __construct( $page ) {
		$this->page = $page;
		add_filter( 'woocommerce_settings_groups', array( $this, 'register_group' ) );
		add_filter( 'woocommerce_settings-' . $this->page->get_id(),  array( $this, 'register_settings' ) );
	}

	/**
	 * Registers a setting group, based on admin page ID & label as parent group.
	 *
	 * @since  2.7.0
	 * @param  array $groups Array of previously registered groups.
	 * @return array
	 */
	public function register_group( $groups ) {
		$groups[] = array(
			'id'    => $this->page->get_id(),
			'label' => $this->page->get_label(),
		);
		return $groups;
	}

	/**
	 * Registers settings to a specific group.
	 *
	 * @since  2.7.0
	 * @param  array $settings Existing registered settings
	 * @return array
	 */
	public function register_settings( $settings ) {
		/**
		 * wp-admin settings can be broken down into separate sections from
		 * a UI standpoint. This will grab all the sections associated with
		 * a particular setting group (like 'products') and register them
		 * to the REST API.
		 */
		$sections = $this->page->get_sections();
		if ( empty( $sections ) ) {
			// Default section is just an empty string, per admin page classes
			$sections = array( '' );
		}

		foreach ( $sections as $section => $section_label ) {
			$settings_from_section = $this->page->get_settings( $section );
			foreach ( $settings_from_section as $setting ) {
				$new_setting = $this->register_setting( $setting );
				if ( $new_setting ) {
					$settings[] = $new_setting;
				}
			}
		}
		return $settings;
	}

	/**
	 * Register's a specific setting (from WC_Settings_Page::get_settings() )
	 * into the format expected for the REST API Settings Controller.
	 *
	 * @since 2.7.0
	 * @param  array $setting Settings array, as produced by a subclass of WC_Settings_Page.
	 * @return array|bool boolean False if setting has no ID or converted array.
	 */
	public function register_setting( $setting ) {
		if ( ! isset( $setting['id'] ) ) {
			return false;
		}
		$new_setting = array(
			'id'          => $setting['id'],
			'label'       => ( ! empty( $setting['title'] ) ? $setting['title'] : '' ),
			'description' => ( ! empty( $setting['desc'] ) ? $setting['desc'] : '' ),
			'type'        => $setting['type'],
		);
		if ( isset( $setting['default'] ) ) {
			$new_setting['default'] = $setting['default'];
		}
		if ( isset( $setting['options'] ) ) {
			$new_setting['options'] = $setting['options'];
		}
		if ( isset( $setting['desc_tip'] ) ) {
			if ( true === $setting['desc_tip'] ) {
				$new_setting['tip'] = $setting['desc'];
			} elseif ( ! empty( $setting['desc_tip'] ) ) {
				$new_setting['tip'] = $setting['desc_tip'];
			}
		}

		return $new_setting;
	}
}
