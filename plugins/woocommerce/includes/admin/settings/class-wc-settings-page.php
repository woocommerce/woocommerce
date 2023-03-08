<?php
/**
 * WooCommerce Settings Page/Tab
 *
 * @package     WooCommerce\Admin
 * @version     2.1.0
 */

use Automattic\WooCommerce\Utilities\ArrayUtil;

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

			/**
			 * Filter to customize the list of settings handled by a given settings class for a given settings section.
			 *
			 * @param array $settings Associative array of settings (name => value).
			 * @param string $section_id The section the settings belong to.
			 *
			 * @since
			 */
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
			 * Filter to customize the list of sections belonging to a given settings class.
			 *
			 * @param array $settings Associative array of section names (id => name).
			 *
			 * @since
			 */
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
				/**
				 * Action triggered when the settings for a given settings class are saved.
				 *
				 * @since
				 */
				do_action( 'woocommerce_update_options_' . $this->id . '_' . $section_id );
			}
		}

		/**
		 * Get extra settings to be included in the generated settings export file
		 * when settings are exported. Derived classes must override this method
		 * unless all the settings handled by the class are standard options returned by "get_settings".
		 *
		 * @param bool $verbose True if verbose settings export is requested.
		 * @return array|null An associative array of extra settings for this class, an empty array or null if none is available.
		 */
		public function get_extra_settings_for_export( bool $verbose ) {
			return null;
		}

		/**
		 * Applies extra settings for the class read from an imported settings file. Derived classes must override this method
		 * if "get_extra_settings_for_export" is also overriden, and both methods must be symmetrical
		 * (that is, "apply_extra_exported_settings" must be able to apply settings generated by "get_extra_settings_for_export").
		 *
		 * The input comes from a user provided file and thus it must be checked for validity.
		 *
		 * @param array  $extra_settings The extra settings for the class as generated by "apply_extra_exported_settings".
		 * @param bool   $verbose True if the settings where exported with the "verbose" option.
		 * @param string $mode Settings import mode, one of: 'full', 'create_only', 'replace_only'.
		 * @return int|string On success, the count of settings that have been processed. On failure, an error message.
		 */
		public function apply_extra_exported_settings( array $extra_settings, bool $verbose, string $mode ) {
			return 0;
		}

		/**
		 * Auxiliary method to apply imported setting items via direct insert or update into the database.
		 *
		 * Items to be imported are compared against existing items and database inserts or updates are performed
		 * accordingly, depending on the passed mode. An error is thrown if one of the imported items has
		 * no data for any of the database table columns. Items data that don't match a database column are ignored.
		 *
		 * The returned array has two keys, 'inserted' and 'updated', each containing a list of item ids
		 * according to $id_column_name.
		 *
		 * @param string $item_name Item type name, used in error messages; e.g. "tax rate".
		 * @param string $table_name Database table name.
		 * @param string $id_column_name Name of the table column that uniquely identifies an item.
		 * @param array  $existing_items An array of objects or associative arrays, each representing an item already existing in the database.
		 * @param array  $imported_items An array of associative arrays, each representing an item to be imported.
		 * @param array  $allowed_columns The list of column names for the database table.
		 * @param string $mode One of 'full', 'replace_only', or 'create_only'.
		 * @return array[]|string An error message, or an array with information of inserted and updated items.
		 */
		protected function maybe_insert_or_update_imported_item( string $item_name, string $table_name, string $id_column_name, array $existing_items, array $imported_items, array $allowed_columns, string $mode ) {
			global $wpdb;

			$inserted = array();
			$updated  = array();

			foreach ( $imported_items as $imported_item ) {
				$key_diff = ArrayUtil::key_diff( $allowed_columns, $imported_item );
				if ( ! empty( $key_diff['missing'] ) ) {
					$missing = implode( ',', $key_diff['missing'] );
					/* translators: %1$s = type of the imported item, %2$s = error message */
					return sprintf( __( 'Missing keys for imported %1$s: %2$s', 'woocommerce' ), $item_name, $missing );
				}

				$whitelisted_imported_item = array_intersect_key( $imported_item, array_flip( $allowed_columns ) );

				$existing_item = current(
					array_filter(
						$existing_items,
						function( $item ) use ( $imported_item, $id_column_name ) {
							return ( (array) $item )[ $id_column_name ] === $imported_item[ $id_column_name ];
						}
					)
				);
				if ( empty( $existing_item ) && 'replace_only' !== $mode ) {
					$wpdb->insert( $table_name, $whitelisted_imported_item );
					$inserted[] = $imported_item;
				} elseif ( ! empty( $existing_item ) && 'create_only' !== $mode && ! empty( array_diff_assoc( (array) $existing_item, $imported_item ) ) ) {
					$wpdb->update( $table_name, $whitelisted_imported_item, array( $id_column_name => $imported_item[ $id_column_name ] ) );
					$updated[] = $imported_item;
				}
			}

			return array(
				'inserted' => $inserted,
				'updated'  => $updated,
			);
		}
	}

endif;
