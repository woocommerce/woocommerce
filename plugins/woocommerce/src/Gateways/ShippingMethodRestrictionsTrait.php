<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Gateways;

use Automattic\Jetpack\Constants;
use WC_Shipping_Zone;
use WC_Shipping_Zones;

defined( 'ABSPATH' ) || exit;

/**
 * Lets a payment gateway restrict its availability to selected shipping methods.
 *
 * Adds the "Enable for shipping methods" and "Accept for virtual orders" settings
 * and enforces them in `is_available()`. The gateway must call
 * `init_shipping_method_restrictions()` after `init_settings()` and merge
 * `get_shipping_method_restrictions_form_fields()` into its form fields.
 *
 * @since 11.2.0
 */
trait ShippingMethodRestrictionsTrait {

	/**
	 * Enable for shipping methods.
	 *
	 * @var array
	 */
	public $enable_for_methods;

	/**
	 * Enable for virtual products.
	 *
	 * @var bool
	 */
	public $enable_for_virtual;

	/**
	 * Load the shipping method restriction settings into the gateway properties.
	 *
	 * Gateways that have never saved these settings default to no restriction.
	 *
	 * @since 11.2.0
	 */
	protected function init_shipping_method_restrictions(): void {
		$enable_for_methods = $this->get_option( 'enable_for_methods', array() );

		$this->enable_for_methods = is_array( $enable_for_methods ) ? $enable_for_methods : array();
		$this->enable_for_virtual = 'yes' === $this->get_option( 'enable_for_virtual', 'yes' );
	}

	/**
	 * Get the form fields for the shipping method restriction settings.
	 *
	 * @since 11.2.0
	 *
	 * @return array Form fields keyed by setting id.
	 */
	protected function get_shipping_method_restrictions_form_fields() {
		$method_title = $this->get_method_title();

		return array(
			'enable_for_methods' => array(
				'title'             => __( 'Enable for shipping methods', 'woocommerce' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'default'           => '',
				/* translators: %s: payment method title. */
				'description'       => sprintf( __( 'If %s is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce' ), $method_title ),
				'options'           => $this->load_shipping_method_options(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select shipping methods', 'woocommerce' ),
				),
			),
			'enable_for_virtual' => array(
				'title'   => __( 'Accept for virtual orders', 'woocommerce' ),
				/* translators: %s: payment method title. */
				'label'   => sprintf( __( 'Accept %s if the order is virtual', 'woocommerce' ), $method_title ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
		);
	}

	/**
	 * Check If The Gateway Is Available For Use.
	 *
	 * @since 10.7.0 Added early return when gateway is disabled.
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		$is_virtual       = true;
		$shipping_methods = array();

		// Get shipping methods from the cart or order.
		if ( is_wc_endpoint_url( 'order-pay' ) ) {
			$order            = wc_get_order( absint( get_query_var( 'order-pay' ) ) );
			$shipping_methods = $order ? $order->get_shipping_methods() : array();
			$is_virtual       = ! count( $shipping_methods );
		} elseif ( WC()->cart && WC()->cart->needs_shipping() ) {
			$shipping_methods = WC()->cart->get_shipping_methods();
			$is_virtual       = false;
		}

		// If the gateway is not enabled for virtual orders and the order does not need shipping, return false.
		if ( ! $this->enable_for_virtual && $is_virtual ) {
			return false;
		}

		// Return early if:
		// - There are no shipping methods restrictions in place.
		// - The order is virtual so needs no shipping.
		// - Shipping methods are not set yet.
		if ( empty( $this->enable_for_methods ) || $is_virtual || ! $shipping_methods ) {
			return parent::is_available();
		}

		// Get the selected shipping method ids. This works on both WC_Shipping_Rate and WC_Order_Item_Shipping class instances.
		$canonical_rate_ids = array_unique(
			array_values(
				array_map(
					function ( $shipping_method ) {
						return is_object( $shipping_method ) && method_exists( $shipping_method, 'get_method_id' ) && method_exists( $shipping_method, 'get_instance_id' ) ? $shipping_method->get_method_id() . ':' . $shipping_method->get_instance_id() : null;
					},
					$shipping_methods
				)
			)
		);

		if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Checks to see whether or not the admin settings are being accessed by the current request.
	 *
	 * @return bool
	 */
	protected function is_accessing_settings() {
		if ( is_admin() ) {
			if ( ! is_wc_admin_settings_page() ) {
				return false;
			}
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['section'] ) || $this->id !== $_REQUEST['section'] ) {
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			return true;
		}

		if ( Constants::is_true( 'REST_REQUEST' ) ) {
			global $wp;
			if ( isset( $wp->query_vars['rest_route'] ) && false !== strpos( $wp->query_vars['rest_route'], '/payment_gateways' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Loads all of the shipping method options for the enable_for_methods field.
	 *
	 * @return array
	 */
	protected function load_shipping_method_options() {
		// Since this is expensive, we only want to do it if we're actually on the settings page.
		if ( ! $this->is_accessing_settings() ) {
			return array();
		}

		return $this->get_shipping_method_options();
	}

	/**
	 * Get all of the shipping method options for the enable_for_methods field, grouped by shipping method.
	 *
	 * Unlike `load_shipping_method_options()`, this always queries the shipping zones, so
	 * only call it when the options are actually needed.
	 *
	 * @since 11.2.0
	 *
	 * @return array Options keyed by shipping method title, each an array of rate id => option label.
	 */
	public function get_shipping_method_options() {
		$zones   = WC_Shipping_Zones::get_shipping_zones();
		$zones[] = new WC_Shipping_Zone( 0 );

		$options = array();
		foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

			$options[ $method->get_method_title() ] = array();

			// Translators: %1$s shipping method name.
			$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'woocommerce' ), $method->get_method_title() );

			foreach ( $zones as $zone ) {

				$shipping_method_instances = $zone->get_shipping_methods();

				foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

					if ( $shipping_method_instance->id !== $method->id ) {
						continue;
					}

					$option_id = $shipping_method_instance->get_rate_id();

					// Translators: %1$s shipping method title, %2$s shipping method id.
					$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'woocommerce' ), $shipping_method_instance->get_title(), $shipping_method_instance_id );

					// Translators: %1$s zone name, %2$s shipping method instance name.
					$option_title = sprintf( __( '%1$s &ndash; %2$s', 'woocommerce' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'woocommerce' ), $option_instance_title );

					$options[ $method->get_method_title() ][ $option_id ] = $option_title;
				}
			}
		}

		return $options;
	}

	/**
	 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
	 *
	 * @since  3.4.0
	 *
	 * @param array $rate_ids Rate ids to check.
	 * @return array
	 */
	protected function get_matching_rates( $rate_ids ) {
		// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
		return array_unique( array_merge( array_intersect( $this->enable_for_methods, $rate_ids ), array_intersect( $this->enable_for_methods, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
	}
}
