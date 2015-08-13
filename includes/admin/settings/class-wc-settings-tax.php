<?php
/**
 * WooCommerce Tax Settings
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Settings_Tax' ) ) :

/**
 * WC_Settings_Tax
 */
class WC_Settings_Tax extends WC_Settings_Page {

	protected $id = 'tax';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wc_tax_rates_save_changes', array( __CLASS__, 'wp_ajax_wc_tax_rates_save_changes' ) );

		$this->label = __( 'Tax', 'woocommerce' );
		parent::__construct();
	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''         => __( 'Tax Options', 'woocommerce' ),
			'standard' => __( 'Standard Rates', 'woocommerce' )
		);

		// Get tax classes and display as links
		$tax_classes = WC_Tax::get_tax_classes();

		foreach ( $tax_classes as $class ) {
			$sections[ sanitize_title( $class ) ] = sprintf( __( '%s Rates', 'woocommerce' ), $class );
		}

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		$tax_classes     = WC_Tax::get_tax_classes();
		$classes_options = array();

		foreach ( $tax_classes as $class ) {
			$classes_options[ sanitize_title( $class ) ] = esc_html( $class );
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, include( 'views/settings-tax.php' ) );
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		$tax_classes = WC_Tax::get_tax_classes();

		if ( $current_section == 'standard' || in_array( $current_section, array_map( 'sanitize_title', $tax_classes ) ) ) {
			$this->output_tax_rates();
		} else {
			$settings = $this->get_settings();

			WC_Admin_Settings::output_fields( $settings );
		}
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section, $wpdb;

		if ( ! $current_section ) {
			$settings = $this->get_settings();
			WC_Admin_Settings::save_fields( $settings );

		} elseif ( ! empty( $_POST['tax_rate_country'] ) ) {
			$this->save_tax_rates();
		}

		$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_tax_rates_%') OR `option_name` LIKE ('_transient_timeout_wc_tax_rates_%')" );
	}

	public static function get_rates_for_tax_class( $tax_class ) {
		global $wpdb;

		// Get all the rates and locations. Snagging all at once should significantly cut down on the number of queries.
		$rates     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}woocommerce_tax_rates` WHERE `tax_rate_class` = %s ORDER BY `tax_rate_order`;", sanitize_title( $tax_class ) ) );
		$locations = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}woocommerce_tax_rate_locations`" );

		// Set the rates keys equal to their ids.
		$rates = array_combine( wp_list_pluck( $rates, 'tax_rate_id' ), $rates );

		// Drop the locations into the rates array.
		foreach ( $locations as $location ) {
			// Don't set them for unexistent rates.
			if ( ! isset( $rates[ $location->tax_rate_id ] ) ) {
				continue;
			}
			// If the rate exists, initialize the array before appending to it.
			if ( ! isset( $rates[ $location->tax_rate_id ]->{$location->location_type} ) ) {
				$rates[ $location->tax_rate_id ]->{$location->location_type} = array();
			}
			$rates[ $location->tax_rate_id ]->{$location->location_type}[] = $location->location_code;
		}

		return $rates;
	}

	/**
	 * Output tax rate tables
	 */
	public function output_tax_rates() {
		global $wpdb,
				$current_section;

		$current_class = $this->get_current_tax_class();

		$countries = array();
		foreach ( WC()->countries->get_allowed_countries() as $value => $label ) {
			$countries[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		$states = array();
		foreach ( WC()->countries->get_allowed_country_states() as $label ) {
			foreach ( $label as $code => $state ) {
				$states[] = array(
					'label' => $state,
					'value' => $code,
				);
			}
		}

		$base_url = admin_url( add_query_arg( array(
			'page'    => 'wc-settings',
			'tab'     => 'tax',
			'section' => $current_section,
		), 'admin.php' ) );

		// Localize and enqueue our js.
		wp_localize_script( 'wc-settings-tax', 'htmlSettingsTaxLocalizeScript', array(
			'current_class' => $current_class,
			'wc_tax_nonce'  => wp_create_nonce( 'wc_tax_nonce-class:' . $current_class ),
			'base_url'      => $base_url,
			'rates'         => array_values( self::get_rates_for_tax_class( $current_class ) ),
			'page'          => ! empty( $_GET['p'] ) ? absint( $_GET['p'] ) : 1,
			'limit'         => 100,
			'countries'     => $countries,
			'states'        => $states,
			'default_rate'  => array(
				'tax_rate_id'       => 0,
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '',
				'tax_rate_name'     => '',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => null,
				'tax_rate_class'    => $current_class,
			),
			'strings'       => array(
				'no_rows_selected' => __( 'No row(s) selected', 'woocommerce' ),
				'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce' ),
				'csv_data_cols' => array(
					__( 'Country Code', 'woocommerce' ),
					__( 'State Code', 'woocommerce' ),
					__( 'ZIP/Postcode', 'woocommerce' ),
					__( 'City', 'woocommerce' ),
					__( 'Rate %', 'woocommerce' ),
					__( 'Tax Name', 'woocommerce' ),
					__( 'Priority', 'woocommerce' ),
					__( 'Compound', 'woocommerce' ),
					__( 'Shipping', 'woocommerce' ),
					__( 'Tax Class', 'woocommerce' ),
				),
			),
		) );
		wp_enqueue_script( 'wc-settings-tax' );

		include( 'views/html-settings-tax.php' );
	}

	/**
	 * Handle ajax tax rate submissions.
	 */
	private static function wp_ajax_wc_tax_rates_save_changes() {
		global $wpdb;

		if ( ! isset( $_POST['current_class'], $_POST['wc_tax_nonce'], $_POST['changes'] ) ) {
			wp_send_json_error( 'missing_fields' );
			exit;
		}

		$current_class = $_POST['current_class']; // This is sanitized seven lines later.

		if ( ! wp_verify_nonce( $_POST['wc_tax_nonce'], 'wc_tax_nonce-class:' . $current_class ) ) {
			wp_send_json_error( 'bad_nonce' );
			exit;
		}

		$current_class = WC_Tax::format_tax_rate_class( $current_class );

		// Check User Caps
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'missing_capabilities' );
			exit;
		}

		$changes = $_POST['changes'];
		foreach ( $changes as $tax_rate_id => $data ) {
			if ( isset( $data['deleted'] ) ) {
				if ( isset( $data['newRow'] ) ) {
					// So the user added and deleted a new row.
					// That's fine, it's not in the database anyways. NEXT!
					continue;
				}
				WC_Tax::_delete_tax_rate( $tax_rate_id );
			}

			$tax_rate = array_intersect_key( $data, array(
				'tax_rate_country'  => 1,
				'tax_rate_state'    => 1,
				'tax_rate'          => 1,
				'tax_rate_name'     => 1,
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 1,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 1,
			) );

			if ( isset( $data['newRow'] ) ) {
				// Hurrah, shiny and new!
				$tax_rate['tax_rate_class'] = $current_class;
				$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
			} else {
				// Updating an existing rate ...
				WC_Tax::_update_tax_rate( $tax_rate_id, $tax_rate );
			}

			if ( isset( $data['postcode'] ) ) {
				WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, wc_clean( $data['postcode'] ) );
			}
			if ( isset( $data['city'] ) ) {
				WC_Tax::_update_tax_rate_cities( $tax_rate_id, wc_clean( $data['postcode'] ) );
			}
		}

		wp_send_json_success( array(
			'rates' => self::get_rates_for_tax_class( $current_class ),
		) );
	}

	/**
	 * Get tax class being edited
	 * @return string
	 */
	private static function get_current_tax_class() {
		global $current_section;

		$tax_classes   = WC_Tax::get_tax_classes();
		$current_class = '';

		foreach( $tax_classes as $class ) {
			if ( sanitize_title( $class ) == $current_section ) {
				$current_class = $class;
			}
		}

		return $current_class;
	}

	/**
	 * Get a posted tax rate
	 * @param  string $key   Key of tax rate in the post data array
	 * @param  int $order Position/order of rate
	 * @param  string $class Tax class for rate
	 * @return array
	 */
	private function get_posted_tax_rate( $key, $order, $class ) {
		$tax_rate     = array();
		$tax_rate_keys = array(
			'tax_rate_country',
			'tax_rate_state',
			'tax_rate',
			'tax_rate_name',
			'tax_rate_priority'
		);

		foreach ( $tax_rate_keys as $tax_rate_key ) {
			if ( isset( $_POST[ $tax_rate_key ] ) && isset( $_POST[ $tax_rate_key ][ $key ] ) ) {
				$tax_rate[ $tax_rate_key ] = wc_clean( $_POST[ $tax_rate_key ][ $key ] );
			}
		}

		$tax_rate['tax_rate_compound'] = isset( $_POST['tax_rate_compound'][ $key ] ) ? 1 : 0;
		$tax_rate['tax_rate_shipping'] = isset( $_POST['tax_rate_shipping'][ $key ] ) ? 1 : 0;
		$tax_rate['tax_rate_order']    = $order;
		$tax_rate['tax_rate_class']    = $class;

		return $tax_rate;
	}

	/**
	 * Save tax rates
	 */
	public function save_tax_rates() {
		global $wpdb;

		$current_class = sanitize_title( $this->get_current_tax_class() );

		// get the tax rate id of the first submited row
		$first_tax_rate_id = key( $_POST['tax_rate_country'] );

		// get the order position of the first tax rate id
		$tax_rate_order = absint( $wpdb->get_var( $wpdb->prepare( "SELECT tax_rate_order FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %s", $first_tax_rate_id ) ) );

		$index = isset( $tax_rate_order ) ? $tax_rate_order : 0;

		// Loop posted fields
		foreach ( $_POST['tax_rate_country'] as $key => $value ) {
			$mode        = 0 === strpos( $key, 'new-' ) ? 'insert' : 'update';
			$tax_rate    = $this->get_posted_tax_rate( $key, $index ++, $current_class );

			if ( 'insert' === $mode ) {
				$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
			} elseif ( 1 == $_POST['remove_tax_rate'][ $key ] ) {
				$tax_rate_id = absint( $key );
				WC_Tax::_delete_tax_rate( $tax_rate_id );
				continue;
			} else {
				$tax_rate_id = absint( $key );
				WC_Tax::_update_tax_rate( $tax_rate_id, $tax_rate );
			}

			if ( isset( $_POST['tax_rate_postcode'][ $key ] ) ) {
				WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, wc_clean( $_POST['tax_rate_postcode'][ $key ] ) );
			}
			if ( isset( $_POST['tax_rate_city'][ $key ] ) ) {
				WC_Tax::_update_tax_rate_cities( $tax_rate_id, wc_clean( $_POST['tax_rate_city'][ $key ] ) );
			}
		}
	}
}

endif;

return new WC_Settings_Tax();
