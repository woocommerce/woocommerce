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
	
	var $locale;
	var $address_formats;
	
	/**
	 * Constructor
	 */
	function __construct() {
		
		// Address formats when it's different to the default locale
		$this->address_formats = apply_filters('woocommerce_localisation_address_formats', array(
			'default' => "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}",
			'AU' => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
			'AT' => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
			'CN' => "{country} {postcode}\n{state}, {city}, {address_2}, {address_1}\n{company}\n{name}",
			'CZ' => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
			'DE' => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
			'FI' => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
			'DK' => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
			'FR' => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city_upper}\n{country}",
			'HK' => "{company}\n{first_name} {last_name_upper}\n{address_1}\n{address_2}\n{city_upper}\n{state_upper}\n{country}"
		));
		
		
		// Actions
		add_filter('woocommerce_address_fields', array(&$this, 'apply_locale'), 0);
	}

	/**
	 * Returns locale data for enabled countries
	 */
	function get_checkout_locale( $country = '' ) {
		global $woocommerce;
		
		$countries = $woocommerce->countries->get_allowed_countries();
		
		return array_intersect_key($this->locale, $countries);
	}

	function apply_locale( $fields ) {
		global $woocommerce;
		
		$country = $woocommerce->customer->get_country();
		
		if (isset($this->locale[$country])) :
			
			$locale = $this->locale[$country];
			
			$fields = $this->array_overlay( $fields, $locale );
			
		endif;
		
		return $fields;
	}

    function array_overlay($a1,$a2) {
        foreach($a1 as $k => $v) {
            if(!array_key_exists($k,$a2)) continue;
            if(is_array($v) && is_array($a2[$k])){
                $a1[$k] = $this->array_overlay($v,$a2[$k]);
            }else{
                $a1[$k] = $a2[$k];
            }
        }
        return $a1;
    }

}