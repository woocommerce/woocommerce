<?php
/**
 * Gateway Duplicates Finder Interface
 *
 * @version 3.0.0
 * @package WooCommerce\Interfaces
 */

/**
 * WC Gateway Duplicates Finder
 *
 * Functions that must be defined by duplicates finder classes.
 *
 * @version  3.0.0
 */

interface WC_Gateway_Duplicates_Finder {
    public function find_duplicates($gateways);
}
