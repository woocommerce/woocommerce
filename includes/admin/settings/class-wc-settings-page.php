<?php
/**
 * WooCommerce Settings Page/Tab
 *
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Settings_Page', false ) ) :

	/**
	 * WC_Settings_Page.
	 */
	abstract class WC_Settings_Page {

		/**
		 * Setting page id.
		 *
		 * @var string
		 */
		protected $id = '';

		/**
		 * Setting page label.
		 *
		 * @var string
		 */
		protected $label = '';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
			add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		}

		/**
		 * Get settings page ID.
		 *
		 * @since 3.0.0
		 * @return string
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Get settings page label.
		 *
		 * @since 3.0.0
		 * @return string
		 */
		public function get_label() {
			return $this->label;
		}

		/**
		 * Add this page to settings.
		 *
		 * @param array $pages The setings array where we'll add ourselves.
		 *
		 * @return mixed
		 */
		public function add_settings_page( $pages ) {
			$pages[ $this->id ] = $this->label;

			return $pages;
		}

		/**
		 * Get settings array.
		 *
		 * The strategy for getting the settings is as follows:
		 *
		 * - If a method named 'get_settings_for_{section_id}_section' exists in the class
		 *   it will be invoked (for the default '' section, the method name is 'get_settings_for_default_section').
		 *   Derived classes can implement these methods as required.
		 *
		 * - Otherwise, 'get_settings_for_section' will be invoked. Derived classes can override it
		 *   as an alternative to implementing 'get_settings_for_{section_id}_section' methods.
		 *
		 * @param string $current_section The id of the section to return settings for.
		 *
		 * @return array Settings array, each item being an associative array representing a setting.
		 */
		public function get_settings( $current_section = '' ) {
			if ( '' === $current_section ) {
				$method_name = 'get_settings_for_default_section';
			} else {
				$method_name = "get_settings_for_{$current_section}_section";
			}

			if ( method_exists( $this, $method_name ) ) {
				$settings = $this->$method_name();
			} else {
				$settings = $this->get_settings_for_section( $current_section );
			}

			return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
		}

		/**
		 * Get the settings for a given section.
		 * This method is invoked when no 'get_settings_for_{current_section}_section' exists in the class.
		 *
		 * @param string $current_section The section name to get the settings for.
		 *
		 * @return array Settings array, each item being an associative array representing a setting.
		 */
		protected function get_settings_for_section( $current_section ) {
			return array();
		}

		/**
		 * Get all sections for this page, both the own ones and the ones defined via filters.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = $this->get_own_sections();
			return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
		}

		/**
		 * Get own sections for this page.
		 * Derived classes should override this method if they define sections.
		 * There should always be one default section with an empty string as identifier.
		 *
		 * Example:
		 * return array(
		 *   ''        => __( 'General', 'woocommerce' ),
		 *   'foobars' => __( 'Foos & Bars', 'woocommerce' ),
		 * );
		 *
		 * @return array An associative array where keys are section identifiers and the values are translated section names.
		 */
		protected function get_own_sections() {
			return array( '' => __( 'General', 'woocommerce' ) );
		}

		/**
		 * Output sections.
		 */
		public function output_sections() {
			global $current_section;

			$sections = $this->get_sections();

			if ( empty( $sections ) || 1 === count( $sections ) ) {
				return;
			}

			echo '<ul class="subsubsub">';

			$array_keys = array_keys( $sections );

			foreach ( $sections as $id => $label ) {
				$url       = admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) );
				$class     = ( $current_section === $id ? 'current' : '' );
				$separator = ( end( $array_keys ) === $id ? '' : '|' );
				$text      = esc_html( $label );
				echo "<li><a href='$url' class='$class'>$text</a> $separator </li>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '</ul><br class="clear" />';
		}

		/**
		 * Output the HTML for the settings.
		 */
		public function output() {
			global $current_section;

			$settings = $this->get_settings( $current_section );

			$this->output_fields( $settings );
		}

		/**
		 * Output the HTML for the settings (core implementation).
		 * This method exists to ease unit testing.
		 *
		 * @param array $settings The settings to output.
		 */
		protected function output_fields( $settings ) {
			WC_Admin_Settings::output_fields( $settings );
		}

		/**
		 * Save settings and trigger the 'woocommerce_update_options_'.id action.
		 */
		public function save() {
			$this->save_settings_for_current_section();
			$this->do_update_options_action();
		}

		/**
		 * Save settings for current section.
		 */
		protected function save_settings_for_current_section() {
			global $current_section;

			$settings = $this->get_settings( $current_section );
			$this->save_fields( $settings );
		}

		/**
		 * Save settings for current section (core implementation).
		 * This method exists to ease unit testing.
		 *
		 * @param array $settings The settings to save.
		 */
		protected function save_fields( $settings ) {
			WC_Admin_Settings::save_fields( $settings );
		}

		/**
		 * Trigger the 'woocommerce_update_options_'.id action.
		 *
		 * @param string $section_id Section to trigger the action for, or null for current section.
		 */
		protected function do_update_options_action( $section_id = null ) {
			global $current_section;

			if ( is_null( $section_id ) ) {
				$section_id = $current_section;
			}

			if ( $section_id ) {
				do_action( 'woocommerce_update_options_' . $this->id . '_' . $section_id );
			}
		}
	}

endif;
