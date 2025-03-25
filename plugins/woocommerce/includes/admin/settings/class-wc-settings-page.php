<?php
/**
 * WooCommerce Settings Page/Tab
 *
 * @package     WooCommerce\Admin
 * @version     2.1.0
 */

declare( strict_types = 1);

use Automattic\WooCommerce\Admin\Features\Features;


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
		 * Setting page icon.
		 *
		 * @var string
		 */
		public $icon = 'settings';

		/**
		 * Setting field types.
		 *
		 * @var string
		 */
		const TYPE_TITLE                          = 'title';
		const TYPE_INFO                           = 'info';
		const TYPE_SECTIONEND                     = 'sectionend';
		const TYPE_TEXT                           = 'text';
		const TYPE_PASSWORD                       = 'password';
		const TYPE_DATETIME                       = 'datetime';
		const TYPE_DATETIME_LOCAL                 = 'datetime-local';
		const TYPE_DATE                           = 'date';
		const TYPE_MONTH                          = 'month';
		const TYPE_TIME                           = 'time';
		const TYPE_WEEK                           = 'week';
		const TYPE_NUMBER                         = 'number';
		const TYPE_EMAIL                          = 'email';
		const TYPE_URL                            = 'url';
		const TYPE_TEL                            = 'tel';
		const TYPE_COLOR                          = 'color';
		const TYPE_TEXTAREA                       = 'textarea';
		const TYPE_SELECT                         = 'select';
		const TYPE_MULTISELECT                    = 'multiselect';
		const TYPE_RADIO                          = 'radio';
		const TYPE_CHECKBOX                       = 'checkbox';
		const TYPE_IMAGE_WIDTH                    = 'image_width';
		const TYPE_SINGLE_SELECT_PAGE             = 'single_select_page';
		const TYPE_SINGLE_SELECT_PAGE_WITH_SEARCH = 'single_select_page_with_search';
		const TYPE_SINGLE_SELECT_COUNTRY          = 'single_select_country';
		const TYPE_MULTI_SELECT_COUNTRIES         = 'multi_select_countries';
		const TYPE_RELATIVE_DATE_SELECTOR         = 'relative_date_selector';
		const TYPE_SLOTFILL_PLACEHOLDER           = 'slotfill_placeholder';

		/**
		 * Settings field types which are known.
		 *
		 * @var string[]
		 */
		protected $types = array(
			self::TYPE_TITLE,
			self::TYPE_INFO,
			self::TYPE_SECTIONEND,
			self::TYPE_TEXT,
			self::TYPE_PASSWORD,
			self::TYPE_DATETIME,
			self::TYPE_DATETIME_LOCAL,
			self::TYPE_DATE,
			self::TYPE_MONTH,
			self::TYPE_TIME,
			self::TYPE_WEEK,
			self::TYPE_NUMBER,
			self::TYPE_EMAIL,
			self::TYPE_URL,
			self::TYPE_TEL,
			self::TYPE_COLOR,
			self::TYPE_TEXTAREA,
			self::TYPE_SELECT,
			self::TYPE_MULTISELECT,
			self::TYPE_RADIO,
			self::TYPE_CHECKBOX,
			self::TYPE_IMAGE_WIDTH,
			self::TYPE_SINGLE_SELECT_PAGE,
			self::TYPE_SINGLE_SELECT_PAGE_WITH_SEARCH,
			self::TYPE_SINGLE_SELECT_COUNTRY,
			self::TYPE_MULTI_SELECT_COUNTRIES,
			self::TYPE_RELATIVE_DATE_SELECTOR,
			self::TYPE_SLOTFILL_PLACEHOLDER,
		);

		/**
		 * Setting page label.
		 *
		 * @var string
		 */
		protected $label = '';

		/**
		 * Setting page is modern.
		 *
		 * @var bool
		 */
		protected $is_modern = false;

		/**
		 * Whether the output method has been called.
		 *
		 * @var bool
		 */
		private $output_called = false;

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
			add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'woocommerce_admin_field_add_settings_slot', array( $this, 'add_settings_slot' ) );
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
		 * Creates the React mount point for settings slot.
		 */
		public function add_settings_slot() {
			?>
			<div id="wc_settings_slotfill"> </div>
			<?php
		}

		/**
		 * Add this page to settings.
		 *
		 * @param array $pages The settings array where we'll add ourselves.
		 *
		 * @return mixed
		 */
		public function add_settings_page( $pages ) {
			$pages[ $this->id ] = $this->label;

			return $pages;
		}

		/**
		 * Get page settings data to populate the settings editor.
		 *
		 * @param array $pages The settings array where we'll add data.
		 *
		 * @return array
		 */
		public function add_settings_page_data( $pages ) {
			global $current_section;

			$saved_current_section = $current_section;
			$sections              = $this->get_sections();
			$sections_data         = array();

			// Loop through each section and get the settings for that section.
			foreach ( $sections as $section_id => $section_label ) {
				$current_section       = $section_id;
				$section_settings_data = $this->get_section_settings_data( $section_id, $sections );

				// Replace empty string section ids with 'default'.
				$normalized_section_id                   = '' === $section_id ? 'default' : $section_id;
				$sections_data[ $normalized_section_id ] = array(
					'label'    => html_entity_decode( esc_html( $section_label ) ),
					'settings' => $section_settings_data,
				);
			}

			// Reset the current section to the saved current section.
			$current_section = $saved_current_section;

			$pages[ $this->id ] = array(
				'label'     => html_entity_decode( $this->label ),
				'slug'      => $this->id,
				'icon'      => $this->icon,
				'sections'  => $sections_data,
				'is_modern' => $this->is_modern,
			);

			$pages[ $this->id ]['start'] = $this->get_custom_view( 'woocommerce_before_settings_' . $this->id );
			$pages[ $this->id ]['end']   = $this->get_custom_view( 'woocommerce_after_settings_' . $this->id );

			return $pages;
		}

		/**
		 * Get settings data for a specific section.
		 *
		 * @param string $section_id The ID of the section.
		 * @param array  $sections   All sections available.
		 * @return array Settings data for the section.
		 */
		protected function get_section_settings_data( $section_id, $sections ) {
			$section_settings_data = array();

			$custom_view = $this->get_custom_view( 'woocommerce_settings_' . $this->id, $section_id );
			// We only want to loop through the settings object if the parent class's output method is being rendered during the get_custom_view call.
			if ( $this->output_called ) {
				$section_settings = count( $sections ) > 1
					? $this->get_settings_for_section( $section_id )
					: $this->get_settings();

				// Loop through each setting in the section and add the value to the settings data.
				foreach ( $section_settings as $section_setting ) {
					// Add custom views for sectionend.
					if ( 'sectionend' === $section_setting['type'] && ! empty( $section_setting['id'] ) ) {
						$section_settings_data[] = $this->get_custom_view( 'woocommerce_settings_' . $section_setting['id'] . '_end' );
						$section_settings_data[] = $this->get_custom_view( 'woocommerce_settings_' . $section_setting['id'] . '_after' );
					}

					$section_settings_data[] = $this->populate_setting_value( $section_setting );

					// Add custom views for title.
					if ( 'title' === $section_setting['type'] && ! empty( $section_setting['id'] ) ) {
						$section_settings_data[] = $this->get_custom_view( 'woocommerce_settings_' . $section_setting['id'] );
					}
				}
			}

			// If the custom view has output, add it to the settings data.
			if ( ! empty( $custom_view ) ) {
				$section_settings_data[] = $custom_view;
			}

			// Reset the output_called property.
			$this->output_called = false;

			return $section_settings_data;
		}

		/**
		 * Populate the value for a given section setting.
		 *
		 * @param array $section_setting The setting array to populate.
		 * @return array The setting array with populated value.
		 */
		protected function populate_setting_value( $section_setting ) {
			if ( isset( $section_setting['id'] ) ) {
				$section_setting['value'] = isset( $section_setting['default'] )
					// Fallback to the default value if it exists.
					? get_option( $section_setting['id'], $section_setting['default'] )
					// Otherwise, fallback to false.
					: get_option( $section_setting['id'] );
			}

			$type = $section_setting['type'];
			if ( ! in_array( $type, $this->types, true ) ) {
				$section_setting = $this->get_custom_type_field( 'woocommerce_admin_field_' . $type, $section_setting );
			}

			return $section_setting;
		}

		/**
		 * Get the custom view given the current tab and section.
		 *
		 * @param string $action The action to call.
		 * @param string $section_id The section id.
		 * @return string The custom view. HTML output.
		 */
		public function get_custom_view( $action, $section_id = false ) {
			global $current_section;

			if ( $section_id ) {
				// Make sure the current section is set to the sectionid here. Reset it at the end of the function.
				$saved_current_section = $current_section;
				// set global current_section to the section_id.
				$current_section = $section_id;
			}

			ob_start();
			/**
			 * Output the custom view given the current tab and section by calling the action.
			 *
			 * @since 2.1.0
			 */
			do_action( $action );
			$html = ob_get_contents();
			ob_end_clean();

			// Reset the global variable.
			if ( $section_id ) {
				$current_section = $saved_current_section;
			}

			$content = trim( $html );

			if ( empty( $content ) ) {
				return null;
			}

			return array(
				'id'      => wp_unique_prefixed_id( 'settings_custom_view' ),
				'type'    => 'custom',
				'content' => $content,
			);
		}

		/**
		 * Get the custom type field by calling the action and returning the setting with the content, id, and type.
		 *
		 * @param string $action  The action to call.
		 * @param array  $setting The setting to pass to the action.
		 * @return array The setting with the content, id, and type.
		 */
		public function get_custom_type_field( $action, $setting ) {
			ob_start();
			/**
			 * Output the custom type field by calling the action.
			 *
			 * @since 3.3.0
			 */
			do_action( $action, $setting );
			$html = ob_get_contents();
			ob_end_clean();
			$setting['content'] = trim( $html );
			$setting['id']      = isset( $setting['id'] ) ? $setting['id'] : wp_unique_prefixed_id( 'settings_custom_view' );
			$setting['type']    = 'custom';

			return $setting;
		}

		/**
		 * Get settings array for the default section.
		 *
		 * External settings classes (registered via 'woocommerce_get_settings_pages' filter)
		 * might have redefined this method as "get_settings($section_id='')", thus we need
		 * to use this method internally instead of 'get_settings_for_section' to register settings
		 * and render settings pages.
		 *
		 * *But* we can't just redefine the method as "get_settings($section_id='')" here, since this
		 * will break on PHP 8 if any external setting class have it as 'get_settings()'.
		 *
		 * Thus we leave the method signature as is and use 'func_get_arg' to get the setting id
		 * if it's supplied, and we use this method internally; but it's deprecated and should
		 * otherwise never be used.
		 *
		 * @deprecated 5.4.0 Use 'get_settings_for_section' (passing an empty string for default section)
		 *
		 * @return array Settings array, each item being an associative array representing a setting.
		 */
		public function get_settings() {
			$section_id = 0 === func_num_args() ? '' : func_get_arg( 0 );
			return $this->get_settings_for_section( $section_id );
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
		 * - Otherwise, 'get_settings_for_section_core' will be invoked. Derived classes can override it
		 *   as an alternative to implementing 'get_settings_for_{section_id}_section' methods.
		 *
		 * @param string $section_id The id of the section to return settings for, an empty string for the default section.
		 *
		 * @return array Settings array, each item being an associative array representing a setting.
		 */
		final public function get_settings_for_section( $section_id ) {
			if ( '' === $section_id ) {
				$method_name = 'get_settings_for_default_section';
			} else {
				$method_name = "get_settings_for_{$section_id}_section";
			}

			if ( method_exists( $this, $method_name ) ) {
				$settings = $this->$method_name();
			} else {
				$settings = $this->get_settings_for_section_core( $section_id );
			}

			return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $section_id );
		}

		/**
		 * Get the settings for a given section.
		 * This method is invoked from 'get_settings_for_section' when no 'get_settings_for_{current_section}_section'
		 * method exists in the class.
		 *
		 * When overriding, note that the 'woocommerce_get_settings_' filter must NOT be triggered,
		 * as this is already done by 'get_settings_for_section'.
		 *
		 * @param string $section_id The section name to get the settings for.
		 *
		 * @return array Settings array, each item being an associative array representing a setting.
		 */
		protected function get_settings_for_section_core( $section_id ) {
			return array();
		}

		/**
		 * Get all sections for this page, both the own ones and the ones defined via filters.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections = $this->get_own_sections();
			/**
			 * Filters the sections for this settings page.
			 *
			 * @since 2.2.0
			 * @param array $sections The sections for this settings page.
			 */
			return (array) apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
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
			$this->output_called = true;

			if ( Features::is_enabled( 'settings' ) ) {
				return;
			}

			global $current_section;

			// We can't use "get_settings_for_section" here
			// for compatibility with derived classes overriding "get_settings".
			$settings = $this->get_settings( $current_section );

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

			// We can't use "get_settings_for_section" here
			// for compatibility with derived classes overriding "get_settings".
			$settings = $this->get_settings( $current_section );
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
