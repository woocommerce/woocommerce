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
		$tax_classes = $this->get_tax_classes();

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
		$tax_classes     = $this->get_tax_classes();
		$classes_options = array();

		foreach ( $tax_classes as $class ) {
			$classes_options[ sanitize_title( $class ) ] = esc_html( $class );
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, include( 'settings-tax.php' ) );
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		$tax_classes = $this->get_tax_classes();

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

		} else {
			$this->save_tax_rates();
		}

		$wpdb->query( "DELETE FROM `$wpdb->options` WHERE `option_name` LIKE ('_transient_wc_tax_rates_%') OR `option_name` LIKE ('_transient_timeout_wc_tax_rates_%')" );
	}

	/**
	 * Output tax rate tables
	 */
	public function output_tax_rates() {
		global $wpdb;

		$page          = ! empty( $_GET['p'] ) ? absint( $_GET['p'] ) : 1;
		$limit         = 100;
		$current_class = $this->get_current_tax_class();

		include( 'html-settings-tax.php' );
	}

	/**
	 * Get tax classes
	 * @return array
	 */
	private function get_tax_classes() {
		return array_filter( array_map( 'trim', explode( "\n", get_option('woocommerce_tax_classes' ) ) ) );
	}

	/**
	 * Get tax class being edited
	 * @return string
	 */
	private function get_current_tax_class() {
		global $current_section;

		$tax_classes   = $this->get_tax_classes();
		$current_class = '';

		foreach( $tax_classes as $class ) {
			if ( sanitize_title( $class ) == $current_section ) {
				$current_class = $class;
			}
		}

		return $current_class;
	}

	/**
	 * Insert a tax rate
	 * @param  array $_tax_rate
	 * @return  int tax rate id
	 */
	private function insert_tax_rate( $_tax_rate ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'woocommerce_tax_rates', $_tax_rate );

		$tax_rate_id = $wpdb->insert_id;

		do_action( 'woocommerce_tax_rate_added', $tax_rate_id, $_tax_rate );

		return $tax_rate_id;
	}

	/**
	 * Update a tax rate
	 * @param  int $tax_rate_id
	 * @param  array $_tax_rate
	 * @return  int tax rate id
	 */
	private function update_tax_rate( $tax_rate_id, $_tax_rate ) {
		global $wpdb;

		$tax_rate_id = absint( $tax_rate_id );

		$wpdb->update(
			$wpdb->prefix . "woocommerce_tax_rates",
			$_tax_rate,
			array(
				'tax_rate_id' => $tax_rate_id
			)
		);

		do_action( 'woocommerce_tax_rate_updated', $tax_rate_id, $_tax_rate );

		return $tax_rate_id;
	}

	/**
	 * Delete a tax rate from the database
	 * @param  int $tax_rate_id
	 */
	private function delete_tax_rate( $tax_rate_id ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = %d;", $tax_rate_id ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %d;", $tax_rate_id ) );

		do_action( 'woocommerce_tax_rate_deleted', $tax_rate_id );
	}

	/**
	 * format the state
	 * @param  string $state
	 * @return string
	 */
	private function format_tax_rate_state( $state ) {
		$state = strtoupper( $state );
		return $state === '*' ? '' : $state;
	}

	/**
	 * format the country
	 * @param  string $state
	 * @return string
	 */
	private function format_tax_rate_country( $country ) {
		$country = strtoupper( $country );
		return $country === '*' ? '' : $country;
	}

	/**
	 * format the tax rate name
	 * @param  string $state
	 * @return string
	 */
	private function format_tax_rate_name( $name ) {
		return $name ? $name : __( 'Tax', 'woocommerce' );
	}

	/**
	 * format the rate
	 * @param  string $state
	 * @return float
	 */
	private function format_tax_rate( $rate ) {
		return number_format( (double) $rate, 4, '.', '' );
	}

	/**
	 * format the city
	 * @param  string $state
	 * @return string
	 */
	private function format_tax_rate_city( $city ) {
		return strtoupper( $city );
	}

	/**
	 * format the postcodes
	 * @param  string $state
	 * @return string
	 */
	private function format_tax_rate_postcode( $postcode ) {
		return strtoupper( $postcode );
	}

	/**
	 * format the priority
	 * @param  string $priority
	 * @return int
	 */
	private function format_tax_rate_priority( $priority ) {
		return absint( $priority );
	}

	/**
	 * Update postcodes for a tax rate in the DB
	 * @param  int $tax_rate_id
	 * @param  string $postcodes
	 * @return string
	 */
	private function update_tax_rate_postcodes( $tax_rate_id, $postcodes ) {
		global $wpdb;

		// Delete old
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = %d AND location_type = 'postcode';", $tax_rate_id ) );

		// Add changed
		$postcodes      = array_filter( explode( ';', $postcodes ) );
		$postcode_query = array();

		foreach( $postcodes as $postcode ) {
			if ( strstr( $postcode, '-' ) ) {
				$postcode_parts = explode( '-', $postcode );

				if ( is_numeric( $postcode_parts[0] ) && is_numeric( $postcode_parts[1] ) && $postcode_parts[1] > $postcode_parts[0] ) {
					for ( $i = $postcode_parts[0]; $i <= $postcode_parts[1]; $i ++ ) {
						if ( ! $i ) {
							continue;
						}

						if ( strlen( $i ) < strlen( $postcode_parts[0] ) ) {
							$i = str_pad( $i, strlen( $postcode_parts[0] ), "0", STR_PAD_LEFT );
						}

						$postcode_query[] = "( '" . esc_sql( $i ) . "', $tax_rate_id, 'postcode' )";
					}
				}
			} elseif ( $postcode ) {
				$postcode_query[] = "( '" . esc_sql( $postcode ) . "', $tax_rate_id, 'postcode' )";
			}
		}

		if ( ! empty( $postcode_query ) ) {
			$wpdb->query( "INSERT INTO {$wpdb->prefix}woocommerce_tax_rate_locations ( location_code, tax_rate_id, location_type ) VALUES " . implode( ',', $postcode_query ) );
		}
	}

	/**
	 * Update cities for a tax rate in the DB
	 * @param  int $tax_rate_id
	 * @param  string $cities
	 * @return string
	 */
	private function update_tax_rate_cities( $tax_rate_id, $cities ) {
		global $wpdb;

		// Delete old
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = %d AND location_type = 'city';", $tax_rate_id ) );

		// Add changed
		$cities     = array_filter( explode( ';', $cities ) );
		$city_query = array();

		foreach( $cities as $city ) {
			$city_query[] = "( '" . esc_sql( $city ) . "', $tax_rate_id, 'city' )";
		}

		if ( ! empty( $city_query ) ) {
			$wpdb->query( "INSERT INTO {$wpdb->prefix}woocommerce_tax_rate_locations ( location_code, tax_rate_id, location_type ) VALUES " . implode( ',', $city_query ) );
		}
	}

	/**
	 * Get a posted tax rate
	 * @param  string $key   Key of tax rate in the post data array
	 * @param  int $order Position/order of rate
	 * @param  string $class Tax class for rate
	 * @return array
	 */
	private function get_posted_tax_rate( $key, $order, $class ) {
		$_tax_rate     = array();
		$tax_rate_keys = array(
			'tax_rate_country',
			'tax_rate_state',
			'tax_rate',
			'tax_rate_name',
			'tax_rate_priority'
		);

		foreach ( $tax_rate_keys as $tax_rate_key ) {
			if ( isset( $_POST[ $tax_rate_key ] ) && isset( $_POST[ $tax_rate_key ][ $key ] ) ) {
				$_tax_rate[ $tax_rate_key ] = wc_clean( $_POST[ $tax_rate_key ][ $key ] );
				$_tax_rate[ $tax_rate_key ] = call_user_func( array( $this, 'format_' . $tax_rate_key ), $_tax_rate[ $tax_rate_key ] );
			}
		}

		$_tax_rate['tax_rate_compound'] = isset( $_POST['tax_rate_compound'][ $key ] ) ? 1 : 0;
		$_tax_rate['tax_rate_shipping'] = isset( $_POST['tax_rate_shipping'][ $key ] ) ? 1 : 0;
		$_tax_rate['tax_rate_order']    = $order;
		$_tax_rate['tax_rate_class']    = $class;

		return $_tax_rate;
	}

	/**
	 * Save tax rates
	 */
	public function save_tax_rates() {
		if ( empty( $_POST['tax_rate_country'] ) ) {
			return;
		}

		$current_class = sanitize_title( $this->get_current_tax_class() );
		$index         = 0;

		// Loop posted fields
		foreach ( $_POST['tax_rate_country'] as $key => $value ) {
			$mode          = 0 === strpos( $key, 'new-' ) ? 'insert' : 'update';
			$_tax_rate     = $this->get_posted_tax_rate( $key, $index ++, $current_class );

			if ( 'insert' === $mode ) {
				$tax_rate_id = $this->insert_tax_rate( $_tax_rate );
			} else {
				// Remove rates
				if ( 1 == $_POST['remove_tax_rate'][ $key ] ) {
					$this->delete_tax_rate( $key );
					continue;
				}

				$tax_rate_id = $this->update_tax_rate( $key, $_tax_rate );
			}

			if ( isset( $_POST['tax_rate_postcode'][ $key ] ) ) {
				$this->update_tax_rate_postcodes( $tax_rate_id, $this->format_tax_rate_postcode( $_POST['tax_rate_postcode'][ $key ] ) );
			}
			if ( isset( $_POST['tax_rate_city'][ $key ] ) ) {
				$this->update_tax_rate_cities( $tax_rate_id, $this->format_tax_rate_city( $_POST['tax_rate_city'][ $key ] ) );
			}
		}
	}
}

endif;

return new WC_Settings_Tax();
