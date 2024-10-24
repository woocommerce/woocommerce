<?php


if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Include dependencies.
 */
if ( ! class_exists( 'WC_Importer_Interface', false ) ) {
	include_once WC_ABSPATH . 'includes/interfaces/class-wc-gateway-duplicates-finder.php';
}

class WC_Gateway_Duplicates_Finder_Static_List implements WC_Gateway_Duplicates_Finder {

    /**
     * The list of most common gateway ids which is used to be compared against when finding duplicates.
     */
    private $gateway_ids  = [
        'apple_pay', 'applepay', 'google_pay', 'googlepay', 'affirm',
        'afterpay', 'clearpay', 'klarna', 'credit_card', 'credicard', 'cc', 'bancontact', 'ideal'
        // TODO add more gateways after analysing all options currently possible
    ];

    public function find_duplicates( $gateways ) {
		// Use associative array for counting occurrences
		$counter = [];
		$duplicated_payment_methods = [];

		// Only loop through gateways once
		foreach ($gateways as $gateway) {
			foreach ($this->gateway_ids as $keyword) {
				if (strpos($gateway->id, $keyword) !== false) {
					// Increment counter or initialize if not exists
					if (isset($counter[$keyword])) {
						$counter[$keyword]++;
					} else {
						$counter[$keyword] = 1;
					}
	
					// If more than one occurrence, add to duplicates
					if ($counter[$keyword] > 1) {
						$duplicated_payment_methods[] = $gateway->title; // Use keys to prevent duplicates
					}
					break; // Stop searching once a match is found for this gateway
				}
			}
		}

        // Return duplicated gateway titles
        return array_values($duplicated_payment_methods); 
    }
}