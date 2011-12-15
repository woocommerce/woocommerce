<?php
/**
 * WooCommerce Localisation
 * 
 * Contains country-specific rules
 *
 * @class 		woocommerce_localisation
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce_localisation {
	
	var $locales;
	
	/**
	 * Constructor
	 */
	function __construct() {
		
		$this->locales => array(
		
			'DE' 	=> 	array(
						'billing_fields'=> array(
							'billing_city' 		=> array( 
								'class' 		=> array('form-row-last'),
								'position'		=> 7,
								),
							'billing_postcode' 	=> array(
								'class'			=> array('form-row-first update_totals_on_change'),
								'position'		=> 6
								),
							'billing_state' 	=> array( 
								'required' 		=> false
								),
							)
						),
						'shipping_fields'=> array(
							'shipping_city' 	=> array( 
								'class' 		=> array('form-row-last'),
								'position'		=> 7,
								),
							'shipping_postcode' => array(
								'class'			=> array('form-row-first update_totals_on_change'),
								'position'		=> 6
								),
							'shipping_state' 	=> array( 
								'required' 		=> false
								),
							)
						)
			);
	}
	 
}