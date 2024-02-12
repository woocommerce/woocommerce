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
class WC_Gateway_Duplicates_Standardized_Finder implements WC_Gateway_Duplicates_Finder {

    public function find_duplicates($gateways) {
        $occurrences_array = array_count_values(array_column($gateways, 'standardized_gateway_id'));
        $duplicates = array_keys(array_filter($occurrences_array, function($value) {
            return $value > 1;
        }));
        return $duplicates;
    }

}
