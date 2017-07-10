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

if ( ! class_exists( 'WC_Settings_Tax', false ) ) :

/**
 * WC_Settings_Tax.
 */
class WC_Settings_Tax extends WC_Settings_Page {

	/**
	 * Setting page id.
	 *
	 * @var string
	 */
	protected $id = 'tax';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->label = __( 'Tax', 'woocommerce' );
		parent::__construct();
	}

	/**
	 * Add this page to settings.
	 *
	 * @param array $pages
	 *
	 * @return array|mixed
	 */
	public function add_settings_page( $pages ) {
		if ( wc_tax_enabled() ) {
			return parent::add_settings_page( $pages );
		} else {
			return $pages;
		}
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''         => __( 'Tax options', 'woocommerce' ),
			'standard' => __( 'Standard rates', 'woocommerce' ),
		);

		// Get tax classes and display as links
		$tax_classes = WC_Tax::get_tax_classes();

		foreach ( $tax_classes as $class ) {
			$sections[ sanitize_title( $class ) ] = sprintf( __( '%s rates', 'woocommerce' ), $class );
		}

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
	 		$settings = include( 'views/settings-tax.php' );
 		}
		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		$tax_classes = WC_Tax::get_tax_class_slugs();

		if ( 'standard' === $current_section || in_array( $current_section, $tax_classes ) ) {
			$this->output_tax_rates();
		} else {
			$settings = $this->get_settings();

			WC_Admin_Settings::output_fields( $settings );
		}
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		if ( ! $current_section ) {
			$settings = $this->get_settings();
			WC_Admin_Settings::save_fields( $settings );

		} elseif ( ! empty( $_POST['tax_rate_country'] ) ) {
			$this->save_tax_rates();
		}

		WC_Cache_Helper::incr_cache_prefix( 'taxes' );
	}

	/**
	 * Output tax rate tables.
	 */
	public function output_tax_rates() {
		global $current_section;

		$current_class = $this->get_current_tax_class();

		$countries = array();
		foreach ( WC()->countries->get_allowed_countries() as $value => $label ) {
			$countries[] = array(
				'value' => $value,
				'label' => esc_js( html_entity_decode( $label ) ),
			);
		}

		$states = array();
		foreach ( WC()->countries->get_allowed_country_states() as $label ) {
			foreach ( $label as $code => $state ) {
				$states[] = array(
					'value' => $code,
					'label' => esc_js( html_entity_decode( $state ) ),
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
			'rates'         => array_values( WC_Tax::get_rates_for_tax_class( $current_class ) ),
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
					__( 'Country code', 'woocommerce' ),
					__( 'State code', 'woocommerce' ),
					__( 'Postcode / ZIP', 'woocommerce' ),
					__( 'City', 'woocommerce' ),
					__( 'Rate %', 'woocommerce' ),
					__( 'Tax name', 'woocommerce' ),
					__( 'Priority', 'woocommerce' ),
					__( 'Compound', 'woocommerce' ),
					__( 'Shipping', 'woocommerce' ),
					__( 'Tax class', 'woocommerce' ),
				),
			),
		) );
		wp_enqueue_script( 'wc-settings-tax' );

		include( 'views/html-settings-tax.php' );
	}

	/**
	 * Get tax class being edited.
	 * @return string
	 */
	private static function get_current_tax_class() {
		global $current_section;

		$tax_classes   = WC_Tax::get_tax_classes();
		$current_class = '';

		foreach ( $tax_classes as $class ) {
			if ( sanitize_title( $class ) == $current_section ) {
				$current_class = $class;
			}
		}

		return $current_class;
	}

	/**
	 * Get a posted tax rate.
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
			'tax_rate_priority',
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
	 * Save tax rates.
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
			$mode        = ( 0 === strpos( $key, 'new-' ) ) ? 'insert' : 'update';
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
