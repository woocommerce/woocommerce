<?php
/**
 * WooCommerce Shipping Settings
 *
 * @package     WooCommerce/Admin
 * @version     2.6.0
 */

use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_Shipping', false ) ) {
	return new WC_Settings_Shipping();
}

/**
 * WC_Settings_Shipping.
 */
class WC_Settings_Shipping extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'shipping';
		$this->label = __( 'Shipping', 'woocommerce' );

		parent::__construct();
	}

	/**
	 * Add this page to settings.
	 *
	 * @param array $pages Current pages.
	 * @return array|mixed
	 */
	public function add_settings_page( $pages ) {
		return wc_shipping_enabled() ? parent::add_settings_page( $pages ) : $pages;
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''        => __( 'Shipping zones', 'woocommerce' ),
			'options' => __( 'Shipping options', 'woocommerce' ),
			'classes' => __( 'Shipping classes', 'woocommerce' ),
		);

		if ( ! Constants::is_defined( 'WC_INSTALLING' ) ) {
			// Load shipping methods so we can show any global options they may have.
			$shipping_methods = WC()->shipping()->load_shipping_methods();

			foreach ( $shipping_methods as $method ) {
				if ( ! $method->has_settings() ) {
					continue;
				}
				$title                                 = empty( $method->method_title ) ? ucfirst( $method->id ) : $method->method_title;
				$sections[ strtolower( $method->id ) ] = esc_html( $title );
			}
		}

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Current section.
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = apply_filters(
				'woocommerce_shipping_settings',
				array(
					array(
						'title' => __( 'Shipping options', 'woocommerce' ),
						'type'  => 'title',
						'id'    => 'shipping_options',
					),

					array(
						'title'         => __( 'Calculations', 'woocommerce' ),
						'desc'          => __( 'Enable the shipping calculator on the cart page', 'woocommerce' ),
						'id'            => 'woocommerce_enable_shipping_calc',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'checkboxgroup' => 'start',
						'autoload'      => false,
					),

					array(
						'desc'          => __( 'Hide shipping costs until an address is entered', 'woocommerce' ),
						'id'            => 'woocommerce_shipping_cost_requires_address',
						'default'       => 'no',
						'type'          => 'checkbox',
						'checkboxgroup' => 'end',
					),

					array(
						'title'           => __( 'Shipping destination', 'woocommerce' ),
						'desc'            => __( 'This controls which shipping address is used by default.', 'woocommerce' ),
						'id'              => 'woocommerce_ship_to_destination',
						'default'         => 'billing',
						'type'            => 'radio',
						'options'         => array(
							'shipping'     => __( 'Default to customer shipping address', 'woocommerce' ),
							'billing'      => __( 'Default to customer billing address', 'woocommerce' ),
							'billing_only' => __( 'Force shipping to the customer billing address', 'woocommerce' ),
						),
						'autoload'        => false,
						'desc_tip'        => true,
						'show_if_checked' => 'option',
					),

					array(
						'title'    => __( 'Debug mode', 'woocommerce' ),
						'desc'     => __( 'Enable debug mode', 'woocommerce' ),
						'desc_tip' => __( 'Enable shipping debug mode to show matching shipping zones and to bypass shipping rate cache.', 'woocommerce' ),
						'id'       => 'woocommerce_shipping_debug_mode',
						'default'  => 'no',
						'type'     => 'checkbox',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'shipping_options',
					),

				)
			);
		}

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section, $hide_save_button;

		// Load shipping methods so we can show any global options they may have.
		$shipping_methods = WC()->shipping()->load_shipping_methods();

		if ( '' === $current_section ) {
			$this->output_zones_screen();
		} elseif ( 'options' === $current_section ) {
			$settings = $this->get_settings();
			WC_Admin_Settings::output_fields( $settings );
		} elseif ( 'classes' === $current_section ) {
			$hide_save_button = true;
			$this->output_shipping_class_screen();
		} else {
			$is_shipping_method = false;
			foreach ( $shipping_methods as $method ) {
				if ( in_array( $current_section, array( $method->id, sanitize_title( get_class( $method ) ) ), true ) && $method->has_settings() ) {
					$is_shipping_method = true;
					$method->admin_options();
				}
			}
			if ( ! $is_shipping_method ) {
				$settings = $this->get_settings();
				$settings = apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
				WC_Admin_Settings::output_fields( $settings );
			}
		}
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		switch ( $current_section ) {
			case 'options':
				WC_Admin_Settings::save_fields( $this->get_settings() );
				do_action( 'woocommerce_update_options_' . $this->id . '_options' );
				break;
			case 'classes':
				do_action( 'woocommerce_update_options_' . $this->id . '_classes' );
				break;
			case '':
				break;
			default:
				$wc_shipping        = WC_Shipping::instance();
				$is_shipping_method = false;

				foreach ( $wc_shipping->get_shipping_methods() as $method_id => $method ) {
					if ( in_array( $current_section, array( $method->id, sanitize_title( get_class( $method ) ) ), true ) ) {
						$is_shipping_method = true;
						do_action( 'woocommerce_update_options_' . $this->id . '_' . $method->id );
					}
				}
				if ( ! $is_shipping_method ) {
					WC_Admin_Settings::save_fields( $this->get_settings( $current_section ) );
				}
				break;
		}

		// Increments the transient version to invalidate cache.
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}

	/**
	 * Handles output of the shipping zones page in admin.
	 */
	protected function output_zones_screen() {
		global $hide_save_button;

		if ( isset( $_REQUEST['zone_id'] ) ) { // WPCS: input var ok, CSRF ok.
			$hide_save_button = true;
			$this->zone_methods_screen( wc_clean( wp_unslash( $_REQUEST['zone_id'] ) ) ); // WPCS: input var ok, CSRF ok.
		} elseif ( isset( $_REQUEST['instance_id'] ) ) {
			$this->instance_settings_screen( absint( wp_unslash( $_REQUEST['instance_id'] ) ) ); // WPCS: input var ok, CSRF ok.
		} else {
			$hide_save_button = true;
			$this->zones_screen();
		}
	}

	/**
	 * Show method for a zone
	 *
	 * @param int $zone_id Zone ID.
	 */
	protected function zone_methods_screen( $zone_id ) {
		if ( 'new' === $zone_id ) {
			$zone = new WC_Shipping_Zone();
		} else {
			$zone = WC_Shipping_Zones::get_zone( absint( $zone_id ) );
		}

		if ( ! $zone ) {
			wp_die( esc_html__( 'Zone does not exist!', 'woocommerce' ) );
		}

		$allowed_countries   = WC()->countries->get_shipping_countries();
		$shipping_continents = WC()->countries->get_shipping_continents();

		// Prepare locations.
		$locations = array();
		$postcodes = array();

		foreach ( $zone->get_zone_locations() as $location ) {
			if ( 'postcode' === $location->type ) {
				$postcodes[] = $location->code;
			} else {
				$locations[] = $location->type . ':' . $location->code;
			}
		}

		wp_localize_script(
			'wc-shipping-zone-methods',
			'shippingZoneMethodsLocalizeScript',
			array(
				'methods'                 => $zone->get_shipping_methods( false, 'json' ),
				'zone_name'               => $zone->get_zone_name(),
				'zone_id'                 => $zone->get_id(),
				'wc_shipping_zones_nonce' => wp_create_nonce( 'wc_shipping_zones_nonce' ),
				'strings'                 => array(
					'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce' ),
					'save_changes_prompt'     => __( 'Do you wish to save your changes first? Your changed data will be discarded if you choose to cancel.', 'woocommerce' ),
					'save_failed'             => __( 'Your changes were not saved. Please retry.', 'woocommerce' ),
					'add_method_failed'       => __( 'Shipping method could not be added. Please retry.', 'woocommerce' ),
					'yes'                     => __( 'Yes', 'woocommerce' ),
					'no'                      => __( 'No', 'woocommerce' ),
					'default_zone_name'       => __( 'Zone', 'woocommerce' ),
				),
			)
		);
		wp_enqueue_script( 'wc-shipping-zone-methods' );

		include_once dirname( __FILE__ ) . '/views/html-admin-page-shipping-zone-methods.php';
	}

	/**
	 * Show zones
	 */
	protected function zones_screen() {
		$method_count = wc_get_shipping_method_count( false, true );

		wp_localize_script(
			'wc-shipping-zones',
			'shippingZonesLocalizeScript',
			array(
				'zones'                   => WC_Shipping_Zones::get_zones( 'json' ),
				'default_zone'            => array(
					'zone_id'    => 0,
					'zone_name'  => '',
					'zone_order' => null,
				),
				'wc_shipping_zones_nonce' => wp_create_nonce( 'wc_shipping_zones_nonce' ),
				'strings'                 => array(
					'unload_confirmation_msg'     => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce' ),
					'delete_confirmation_msg'     => __( 'Are you sure you want to delete this zone? This action cannot be undone.', 'woocommerce' ),
					'save_failed'                 => __( 'Your changes were not saved. Please retry.', 'woocommerce' ),
					'no_shipping_methods_offered' => __( 'No shipping methods offered to this zone.', 'woocommerce' ),
				),
			)
		);
		wp_enqueue_script( 'wc-shipping-zones' );

		include_once dirname( __FILE__ ) . '/views/html-admin-page-shipping-zones.php';
	}

	/**
	 * Show instance settings
	 *
	 * @param int $instance_id Shipping instance ID.
	 */
	protected function instance_settings_screen( $instance_id ) {
		$zone            = WC_Shipping_Zones::get_zone_by( 'instance_id', $instance_id );
		$shipping_method = WC_Shipping_Zones::get_shipping_method( $instance_id );

		if ( ! $shipping_method ) {
			wp_die( esc_html__( 'Invalid shipping method!', 'woocommerce' ) );
		}
		if ( ! $zone ) {
			wp_die( esc_html__( 'Zone does not exist!', 'woocommerce' ) );
		}
		if ( ! $shipping_method->has_settings() ) {
			wp_die( esc_html__( 'This shipping method does not have any settings to configure.', 'woocommerce' ) );
		}

		if ( ! empty( $_POST['save'] ) ) { // WPCS: input var ok, sanitization ok.

			if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'woocommerce-settings' ) ) { // WPCS: input var ok, sanitization ok.
				echo '<div class="updated error"><p>' . esc_html__( 'Edit failed. Please try again.', 'woocommerce' ) . '</p></div>';
			}

			$shipping_method->process_admin_options();
			$shipping_method->display_errors();
		}

		include_once dirname( __FILE__ ) . '/views/html-admin-page-shipping-zones-instance.php';
	}

	/**
	 * Handles output of the shipping class settings screen.
	 */
	protected function output_shipping_class_screen() {
		$wc_shipping = WC_Shipping::instance();
		wp_localize_script(
			'wc-shipping-classes',
			'shippingClassesLocalizeScript',
			array(
				'classes'                   => $wc_shipping->get_shipping_classes(),
				'default_shipping_class'    => array(
					'term_id'     => 0,
					'name'        => '',
					'description' => '',
				),
				'wc_shipping_classes_nonce' => wp_create_nonce( 'wc_shipping_classes_nonce' ),
				'strings'                   => array(
					'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce' ),
					'save_failed'             => __( 'Your changes were not saved. Please retry.', 'woocommerce' ),
				),
			)
		);
		wp_enqueue_script( 'wc-shipping-classes' );

		// Extendable columns to show on the shipping classes screen.
		$shipping_class_columns = apply_filters(
			'woocommerce_shipping_classes_columns',
			array(
				'wc-shipping-class-name'        => __( 'Shipping class', 'woocommerce' ),
				'wc-shipping-class-slug'        => __( 'Slug', 'woocommerce' ),
				'wc-shipping-class-description' => __( 'Description', 'woocommerce' ),
				'wc-shipping-class-count'       => __( 'Product count', 'woocommerce' ),
			)
		);

		include_once dirname( __FILE__ ) . '/views/html-admin-page-shipping-classes.php';
	}
}

return new WC_Settings_Shipping();
