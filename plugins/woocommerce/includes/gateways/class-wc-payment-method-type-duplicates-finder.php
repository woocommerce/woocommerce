<?php

namespace WooCommerce\Gateways;

use WC_Gateway_Duplicates_Finder;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_Importer_Interface', false ) ) {
	include_once WC_ABSPATH . 'includes/interfaces/class-wc-gateway-duplicates-finder.php';
}

/**
 * Class WC_Gateway_Duplicates_Finder
 *
 * Description of the class goes here.
 */
class WC_Payment_Method_Type_Duplicates_Finder implements WC_Gateway_Duplicates_Finder {

    public function find_duplicates( $gateways ) {
        $types = array_map( function( $gateway ) {
            return isset( $gateway->payment_method ) ? $gateway->payment_method->get_type() : null;
        }, $gateways );

        $duplicates = array_keys( array_filter( array_count_values( $types ), function( $value ) {
            return $value > 1;
        } ) );

        return $duplicates;
    }

}
