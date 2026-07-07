<?php
/**
 * WooCommerce Settings Page/Tab
 *
 * @package     WooCommerce\Admin
 * @version     2.1.0
 */

declare( strict_types = 1);

use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;
use Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface;
use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIRequestContext;

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
			add_action( 'woocommerce_admin_field_add_settings_slot', array( $this, 'add_settings_slot' ) );
			add_filter( 'admin_body_class', array( $this, 'add_settings_ui_body_class' ) );
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
		 * Get the settings UI page adapter for this settings page.
		 *
		 * Settings pages can override this to opt in to the settings UI renderer
		 * while retaining the classic WooCommerce settings page route and save flow.
		 *
		 * @since 10.9.0
		 * @return SettingsUIPageInterface|null
		 */
		public function get_settings_ui_page(): ?SettingsUIPageInterface {
			return null;
		}

		/**
		 * Add a body class for settings pages rendered through the settings UI.
		 *
		 * @since 10.9.0
		 *
		 * @param string $classes The existing body classes for the admin area.
		 * @return string The modified body classes for the admin area.
		 */
		public function add_settings_ui_body_class( $classes ) {
			global $current_section, $current_tab;

			if ( ! is_string( $classes ) || $this->id !== $current_tab ) {
				return $classes;
			}

			$section = is_string( $current_section ) ? $current_section : '';
			$context = $this->get_settings_ui_request_context( $section );

			if ( ! $context || ! $context->is_rendering_enabled() ) {
				return $classes;
			}

			if ( str_contains( $classes, 'woocommerce-settings-ui-page' ) ) {
				return $classes;
			}

			return "$classes woocommerce-settings-ui-page";
		}

		/**
		 * Log a developer-facing notice when settings UI rendering falls back to the legacy renderer.
		 *
		 * @since 10.9.0
		 *
		 * @param SettingsUIPageInterface $settings_ui_page Settings UI page adapter.
		 * @param string                  $section_id Section id.
		 * @param string                  $reason Fallback reason.
		 */
		private function log_settings_ui_fallback( SettingsUIPageInterface $settings_ui_page, string $section_id, string $reason ): void {
			wc_doing_it_wrong(
				'WC_Settings_Page::output',
				sprintf(
					/* translators: 1: settings page id, 2: settings section id, 3: fallback reason. */
					__( 'Settings UI rendering for page "%1$s" section "%2$s" fell back to the legacy settings renderer. Reason: %3$s', 'woocommerce' ),
					$settings_ui_page->get_page_id(),
					'' === $section_id ? 'default' : $section_id,
					$reason
				),
				'10.9.0'
			);
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
		 * Settings section registry instance, or null when the modern settings SDK is unavailable.
		 *
		 * The class can be missing mid-update: a 10.9 copy of this file may load before the autoloader
		 * class map can safely resolve the new registry, so a direct call would fatal.
		 *
		 * @return SettingsSectionRegistry|null
		 */
		protected function get_settings_section_registry() {
			try {
				if ( ! class_exists( SettingsSectionRegistry::class ) ) {
					return null;
				}

				return SettingsSectionRegistry::get_instance();
			} catch ( \Throwable $e ) {
				return null;
			}
		}

		/**
		 * Settings UI request context, or null when the modern settings SDK is unavailable.
		 *
		 * @param string $section Section id.
		 * @return SettingsUIRequestContext|null
		 */
		protected function get_settings_ui_request_context( $section ) {
			try {
				if ( ! class_exists( SettingsUIRequestContext::class ) ) {
					return null;
				}

				return SettingsUIRequestContext::for_settings_page( $this, $section );
			} catch ( \Throwable $e ) {
				return null;
			}
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
			$registry           = $this->get_settings_section_registry();
			$registered_section = $registry ? $registry->get_registered( $this->id, (string) $section_id ) : null;

			return $registered_section ? $registered_section->get_settings( $this ) : array();
		}

		/**
		 * Get all sections for this page, both the own ones and the ones defined via filters.
		 *
		 * @return array
		 */
		public function get_sections() {
			$sections            = $this->get_own_sections();
			$registry            = $this->get_settings_section_registry();
			$registered_sections = $registry ? $registry->get_sections_for_page( $this->id ) : array();

			foreach ( $registered_sections as $section_id => $section_label ) {
				// Preserve sections declared by the settings page when a registered section uses the same id.
				if ( array_key_exists( $section_id, $sections ) ) {
					continue;
				}

				$sections[ $section_id ] = $section_label;
			}

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
			global $current_section;

			$section = is_string( $current_section ) ? $current_section : '';
			$context = $this->get_settings_ui_request_context( $section );

			if ( $context && $context->is_rendering_enabled() ) {
				$settings_ui_page = $context->get_settings_ui_page();
				assert( $settings_ui_page instanceof SettingsUIPageInterface );

				if ( $context->has_schema_failed() ) {
					$this->log_settings_ui_fallback(
						$settings_ui_page,
						$section,
						__( 'Settings UI schema generation failed.', 'woocommerce' )
					);
				} else {
					$script_handles = $context->get_script_handles();

					if ( $context->has_script_handles_failed() ) {
						$this->log_settings_ui_fallback( $settings_ui_page, $section, $context->get_script_handles_failure_reason() );
					} else {
						foreach ( $script_handles as $script_handle ) {
							wp_enqueue_script( $script_handle );
						}

						$GLOBALS['hide_save_button'] = true;

						printf(
							'<div id="%1$s" data-wc-settings-ui="1" data-wc-settings-page="%2$s" data-wc-settings-section="%3$s"></div>',
							esc_attr( 'wc_settings_ui_' . sanitize_html_class( $this->id ) . '_' . sanitize_html_class( '' === $section ? 'default' : $section ) ),
							esc_attr( $context->get_page_id() ),
							esc_attr( $section )
						);
						return;
					}
				}
			}

			// We can't use "get_settings_for_section" here
			// for compatibility with derived classes overriding "get_settings".
			$settings = $this->get_settings( $section );

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
