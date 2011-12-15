<?php
/**
 * WooCommerce Localisation
 * 
 * Contains country-specific rules. Spotted an error? Tell us on GitHub.
 *
 * @class 		woocommerce_localisation
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce_localisation {
	
	var $default_locale;
	var $locale;
	
	/**
	 * Constructor
	 */
	function __construct() {
		
		$this->default_locale = array(
			'required_fields' => array(
				'first_name', 'last_name', 'address_1', 'city', 'postcode', 'country', 'state', 'email', 'phone'
			),
			'address_format' => "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}"
		);
		
		$this->locale = array(
			
			// Austrialia
			'AU' => array(
				'labels'	=> array(
					'city'	=> __('Town/City', 'woothemes')
				),
				'address_format' => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}"
			),
			
			// Austria
			'AT' => array(
				'field_postition' => array(
					'city'		=> 7,
					'postcode' 	=> 6
				),
				'field_classes' => array(
					'city' 		=> array('form-row-last'),
					'postcode'	=> array('form-row-first update_totals_on_change')
				),
				'required_fields' => array(
					'first_name', 'last_name', 'address_1', 'city', 'postcode', 'country', 'email', 'phone'
				),
				'address_format' => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}"
			),
			
			// Canada
			'CA' => array(
				'labels'	=> array(
					'state'	=> __('Province', 'woothemes')
				)
			),
			
			// Chile
			'CL' => array(
				'labels'	=> array(
					'state'	=> __('Municipality', 'woothemes')
				),
				'required_fields' => array(
					'first_name', 'last_name', 'address_1', 'country', 'state', 'email', 'phone'
				)
			),
			
			// China
			'CN' => array(
				'labels'	=> array(
					'state'	=> __('Province', 'woothemes')
				),
				'address_format' => "{country} {postcode}\n{state}, {city}, {address_2}, {address_1}\n{company}\n{name}",
			),
			
			// Czech Republic
			'CZ' => array(
				'labels'	=> array(
					'city'	=> __('Town', 'woothemes')
				),
				'address_format' => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}"
			),
			
			// Germany
			'DE' => array(
				'field_postition' => array(
					'city'		=> 7,
					'postcode' 	=> 6
				),
				'field_classes' => array(
					'city' 		=> array('form-row-last'),
					'postcode'	=> array('form-row-first update_totals_on_change')
				),
				'required_fields' => array(
					'first_name', 'last_name', 'address_1', 'city', 'postcode', 'country', 'email', 'phone'
				),
				'address_format' => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}"
			),
			
			// Denmark
			'DK' => array(
				'labels'	=> array(
					'city'	=> __('Town', 'woothemes')
				),
				'address_format' => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}"
			),
			
			// Germany
			'GB' => array(
				'field_postition' => array(
					'city'		=> 7,
					'postcode' 	=> 6
				),
				'field_classes' => array(
					'city' 		=> array('form-row-last'),
					'postcode'	=> array('form-row-first update_totals_on_change')
				),
				'required_fields' => array(
					'first_name', 'last_name', 'address_1', 'city', 'postcode', 'country', 'email', 'phone'
				),
				'address_format' => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}"
			),
			
						
		);
		
		// Actions
		add_filter('woocommerce_billing_fields', array(&$this, 'apply_locale'), 0);
	}
	
	function apply_locale( $fields, $type = "billing_" ) {
		global $woocommerce;
		
		$country = $woocommerce->customer->get_country();
		
		if (isset($this->locale[$country])) :
			
			$locale = $this->locale[$country];
			
			if (isset($locale['field_postition'])) :
				
				foreach ($locale['field_postition'] as $field => $value) :
					
					$fields[$type . $field]['position'] = $value;
					
				endforeach;
				
			endif;
			
			if (isset($locale['field_classes'])) :
				
				foreach ($locale['field_classes'] as $field => $value) :
					
					$fields[$type . $field]['class'] = $value;
					
				endforeach;
				
			endif;
			
		endif;
		
		return $fields;
	}

}