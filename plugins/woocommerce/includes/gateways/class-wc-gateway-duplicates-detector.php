<?php
/**
 * Class WC_Gateway_Duplicates_Service
 *
 * This class detects duplicate gateways registered by multiple plugins.
 */
class WC_Gateway_Duplicates_Service {

    /**
     * @var WC_Gateway_Duplicates_Finder
     */
    private $finder;
    
    /**
     * Constructor for the WC_Gateway_Duplicates_Service class.
     */
    public function __construct( WC_Gateway_Duplicates_Finder $duplicates_finder ) {
        $this->finder = $duplicates_finder;
    }

    /**
     * Method to detect duplicate gateways.
     *
     * @return array List of duplicate gateways.
     */
    public function detect_duplicates( $gateways ) {
        return $this->finder->find_duplicates( $gateways );
    }
}
