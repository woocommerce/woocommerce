<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Fixed plus Percents Shipping Method
 *
 * A simple shipping method that calulate sum of fixed tax plus percents of order
 *
 * @class     WC_Shipping_Fixed_Plus_Percents
 * @version   2.0.0
 * @package   WooCommerce/Classes/Shipping
 * @author    WooThemes
 */
class WC_Shipping_Fixed_Plus_Percents extends WC_Shipping_Method {

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    function __construct() {

        $this->id = 'fixed-plus-percents';
        $this->method_title = __( 'Fixed + Percentage', 'woocommerce' );
        $this->title = $this->method_title;
        $this->enabled = $this->settings['enabled'];

        $this->init_form_fields();
        $this->init_settings();

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param array $package (default: array())
     * @return void
     */
    function calculate_shipping($package) {
        $methods = explode( "\n", $this->settings['shipping_methods'] );
        $methods = array_map( 'trim', $methods );
        $methods = array_filter( $methods );
        foreach ($methods as $method) {
            $method = explode( '|', $method, 4 );
            $method = array_map( 'trim', $method );
            if ( !empty( $method[0] ) ) {

                // Prepare the percentage.
                if ( empty( $method[1] ) ) {
                    $method[1] = 0;
                }
                if ( !is_numeric( $p ) && preg_match( '#([0-9.]+)\%#', $method[1], $method1_val)) {
                  $method[1] = $method1_val[1];
                }
                if ( $method[1] ) {
                  $method[1] = $method[1] / 100;
                }

                // Prepare the fixed tax.
                if ( empty( $method[2] ) ) {
                  $method[2] = 0;
                }

                // Add the rate.
                $rate = array(
                    'id' => $this->id . '-' . md5($method[0]),
                    'label' => $method[0],
                    'taxes' => FALSE,
                    'cost' => round( $method[2] + ($package['contents_cost'] * $method[1]), 2),
                    'calc_tax' => 'per_order',
                );
                $this->add_rate($rate);
            }
        }
    }

    /**
     * is_available function.
     *
     * @access public
     * @param mixed $package
     * @return bool
     */
    function is_available($package) {
      if ( $this->enabled == 'no' ) {
          return FALSE;
      }
      $ship_to_countries = '';
      if ($this->availability == 'specific') {
          $ship_to_countries = $this->countries;
      }
      elseif ( get_option( 'woocommerce_allowed_countries' ) =='specific' ) {
          $ship_to_countries = get_option( 'woocommerce_specific_allowed_countries' );
      }
      if ( is_array( $ship_to_countries ) && !in_array( $package['destination']['country'], $ship_to_countries ) ) {
          return FALSE;
      }
      return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', TRUE );
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable shipping method.', 'woocommerce' ),
                'default' => 'no',
            ),
            'shipping_methods' => array(
                'title' => __( 'Additional Rates', 'woocommerce' ),
                'type' => 'textarea',
                'description' => __( 'Optional extra shipping Post services or Couriers (one per line): <br /> Post Name | Percents | Fixed cost to sum with <br /> Example: <br /> <code>AlienDelivery | 0.5% | 10</code>.', 'woocommerce' ),
                'default' => '',
                'desc_tip' => TRUE,
                'placeholder' => __( 'Post Name | Percents | Fixed cost to sum with'),
            ),
        );
    }

}
