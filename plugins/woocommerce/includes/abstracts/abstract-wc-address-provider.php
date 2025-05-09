<?php
declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Address Provider Class.
 *
 * Extended by address providers to handle address provision, for autocomplete, maps, etc.
 *
 * @class       WC_Address_Provider
 * @version     9.9.0
 * @package     WooCommerce\Abstracts
 */
abstract class WC_Address_Provider {

	/**
	 * Unique ID for the address provider.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Provider name.
	 *
	 * @var string
	 */
	public $name;
}
