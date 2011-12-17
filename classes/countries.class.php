<?php
/**
 * WooCommerce countries
 * 
 * The WooCommerce countries class stores country/state data.
 *
 * @class 		woocommerce_countries
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce_countries {
	
	var $countries;
	var $states;
	var $locale;
	var $address_formats;
	
	/**
	 * Constructor
	 */
	function __construct() {
	
		$this->countries = array(
			'AF' => __('Afghanistan', 'woothemes'),
			'AX' => __('Aland Islands', 'woothemes'),
			'AL' => __('Albania', 'woothemes'),
			'DZ' => __('Algeria', 'woothemes'),
			'AS' => __('American Samoa', 'woothemes'),
			'AD' => __('Andorra', 'woothemes'),
			'AO' => __('Angola', 'woothemes'),
			'AI' => __('Anguilla', 'woothemes'),
			'AQ' => __('Antarctica', 'woothemes'),
			'AG' => __('Antigua and Barbuda', 'woothemes'),
			'AR' => __('Argentina', 'woothemes'),
			'AM' => __('Armenia', 'woothemes'),
			'AW' => __('Aruba', 'woothemes'),
			'AU' => __('Australia', 'woothemes'),
			'AT' => __('Austria', 'woothemes'),
			'AZ' => __('Azerbaijan', 'woothemes'),
			'BS' => __('Bahamas', 'woothemes'),
			'BH' => __('Bahrain', 'woothemes'),
			'BD' => __('Bangladesh', 'woothemes'),
			'BB' => __('Barbados', 'woothemes'),
			'BY' => __('Belarus', 'woothemes'),
			'BE' => __('Belgium', 'woothemes'),
			'BZ' => __('Belize', 'woothemes'),
			'BJ' => __('Benin', 'woothemes'),
			'BM' => __('Bermuda', 'woothemes'),
			'BT' => __('Bhutan', 'woothemes'),
			'BO' => __('Bolivia', 'woothemes'),
			'BA' => __('Bosnia and Herzegovina', 'woothemes'),
			'BW' => __('Botswana', 'woothemes'),
			'BR' => __('Brazil', 'woothemes'),
			'IO' => __('British Indian Ocean Territory', 'woothemes'),
			'VG' => __('British Virgin Islands', 'woothemes'),
			'BN' => __('Brunei', 'woothemes'),
			'BG' => __('Bulgaria', 'woothemes'),
			'BF' => __('Burkina Faso', 'woothemes'),
			'BI' => __('Burundi', 'woothemes'),
			'KH' => __('Cambodia', 'woothemes'),
			'CM' => __('Cameroon', 'woothemes'),
			'CA' => __('Canada', 'woothemes'),
			'CV' => __('Cape Verde', 'woothemes'),
			'KY' => __('Cayman Islands', 'woothemes'),
			'CF' => __('Central African Republic', 'woothemes'),
			'TD' => __('Chad', 'woothemes'),
			'CL' => __('Chile', 'woothemes'),
			'CN' => __('China', 'woothemes'),
			'CX' => __('Christmas Island', 'woothemes'),
			'CC' => __('Cocos (Keeling) Islands', 'woothemes'),
			'CO' => __('Colombia', 'woothemes'),
			'KM' => __('Comoros', 'woothemes'),
			'CG' => __('Congo (Brazzaville)', 'woothemes'),
			'CD' => __('Congo (Kinshasa)', 'woothemes'),
			'CK' => __('Cook Islands', 'woothemes'),
			'CR' => __('Costa Rica', 'woothemes'),
			'HR' => __('Croatia', 'woothemes'),
			'CU' => __('Cuba', 'woothemes'),
			'CY' => __('Cyprus', 'woothemes'),
			'CZ' => __('Czech Republic', 'woothemes'),
			'DK' => __('Denmark', 'woothemes'),
			'DJ' => __('Djibouti', 'woothemes'),
			'DM' => __('Dominica', 'woothemes'),
			'DO' => __('Dominican Republic', 'woothemes'),
			'EC' => __('Ecuador', 'woothemes'),
			'EG' => __('Egypt', 'woothemes'),
			'SV' => __('El Salvador', 'woothemes'),
			'GQ' => __('Equatorial Guinea', 'woothemes'),
			'ER' => __('Eritrea', 'woothemes'),
			'EE' => __('Estonia', 'woothemes'),
			'ET' => __('Ethiopia', 'woothemes'),
			'FK' => __('Falkland Islands', 'woothemes'),
			'FO' => __('Faroe Islands', 'woothemes'),
			'FJ' => __('Fiji', 'woothemes'),
			'FI' => __('Finland', 'woothemes'),
			'FR' => __('France', 'woothemes'),
			'GF' => __('French Guiana', 'woothemes'),
			'PF' => __('French Polynesia', 'woothemes'),
			'TF' => __('French Southern Territories', 'woothemes'),
			'GA' => __('Gabon', 'woothemes'),
			'GM' => __('Gambia', 'woothemes'),
			'GE' => __('Georgia', 'woothemes'),
			'DE' => __('Germany', 'woothemes'),
			'GH' => __('Ghana', 'woothemes'),
			'GI' => __('Gibraltar', 'woothemes'),
			'GR' => __('Greece', 'woothemes'),
			'GL' => __('Greenland', 'woothemes'),
			'GD' => __('Grenada', 'woothemes'),
			'GP' => __('Guadeloupe', 'woothemes'),
			'GU' => __('Guam', 'woothemes'),
			'GT' => __('Guatemala', 'woothemes'),
			'GG' => __('Guernsey', 'woothemes'),
			'GN' => __('Guinea', 'woothemes'),
			'GW' => __('Guinea-Bissau', 'woothemes'),
			'GY' => __('Guyana', 'woothemes'),
			'HT' => __('Haiti', 'woothemes'),
			'HN' => __('Honduras', 'woothemes'),
			'HK' => __('Hong Kong', 'woothemes'),
			'HU' => __('Hungary', 'woothemes'),
			'IS' => __('Iceland', 'woothemes'),
			'IN' => __('India', 'woothemes'),
			'ID' => __('Indonesia', 'woothemes'),
			'IR' => __('Iran', 'woothemes'),
			'IQ' => __('Iraq', 'woothemes'),
			'IE' => __('Ireland', 'woothemes'),
			'IM' => __('Isle of Man', 'woothemes'),
			'IL' => __('Israel', 'woothemes'),
			'IT' => __('Italy', 'woothemes'),
			'CI' => __('Ivory Coast', 'woothemes'),
			'JM' => __('Jamaica', 'woothemes'),
			'JP' => __('Japan', 'woothemes'),
			'JE' => __('Jersey', 'woothemes'),
			'JO' => __('Jordan', 'woothemes'),
			'KZ' => __('Kazakhstan', 'woothemes'),
			'KE' => __('Kenya', 'woothemes'),
			'KI' => __('Kiribati', 'woothemes'),
			'KW' => __('Kuwait', 'woothemes'),
			'KG' => __('Kyrgyzstan', 'woothemes'),
			'LA' => __('Laos', 'woothemes'),
			'LV' => __('Latvia', 'woothemes'),
			'LB' => __('Lebanon', 'woothemes'),
			'LS' => __('Lesotho', 'woothemes'),
			'LR' => __('Liberia', 'woothemes'),
			'LY' => __('Libya', 'woothemes'),
			'LI' => __('Liechtenstein', 'woothemes'),
			'LT' => __('Lithuania', 'woothemes'),
			'LU' => __('Luxembourg', 'woothemes'),
			'MO' => __('Macao S.A.R., China', 'woothemes'),
			'MK' => __('Macedonia', 'woothemes'),
			'MG' => __('Madagascar', 'woothemes'),
			'MW' => __('Malawi', 'woothemes'),
			'MY' => __('Malaysia', 'woothemes'),
			'MV' => __('Maldives', 'woothemes'),
			'ML' => __('Mali', 'woothemes'),
			'MT' => __('Malta', 'woothemes'),
			'MH' => __('Marshall Islands', 'woothemes'),
			'MQ' => __('Martinique', 'woothemes'),
			'MR' => __('Mauritania', 'woothemes'),
			'MU' => __('Mauritius', 'woothemes'),
			'YT' => __('Mayotte', 'woothemes'),
			'MX' => __('Mexico', 'woothemes'),
			'FM' => __('Micronesia', 'woothemes'),
			'MD' => __('Moldova', 'woothemes'),
			'MC' => __('Monaco', 'woothemes'),
			'MN' => __('Mongolia', 'woothemes'),
			'ME' => __('Montenegro', 'woothemes'),
			'MS' => __('Montserrat', 'woothemes'),
			'MA' => __('Morocco', 'woothemes'),
			'MZ' => __('Mozambique', 'woothemes'),
			'MM' => __('Myanmar', 'woothemes'),
			'NA' => __('Namibia', 'woothemes'),
			'NR' => __('Nauru', 'woothemes'),
			'NP' => __('Nepal', 'woothemes'),
			'NL' => __('Netherlands', 'woothemes'),
			'AN' => __('Netherlands Antilles', 'woothemes'),
			'NC' => __('New Caledonia', 'woothemes'),
			'NZ' => __('New Zealand', 'woothemes'),
			'NI' => __('Nicaragua', 'woothemes'),
			'NE' => __('Niger', 'woothemes'),
			'NG' => __('Nigeria', 'woothemes'),
			'NU' => __('Niue', 'woothemes'),
			'NF' => __('Norfolk Island', 'woothemes'),
			'KP' => __('North Korea', 'woothemes'),
			'MP' => __('Northern Mariana Islands', 'woothemes'),
			'NO' => __('Norway', 'woothemes'),
			'OM' => __('Oman', 'woothemes'),
			'PK' => __('Pakistan', 'woothemes'),
			'PW' => __('Palau', 'woothemes'),
			'PS' => __('Palestinian Territory', 'woothemes'),
			'PA' => __('Panama', 'woothemes'),
			'PG' => __('Papua New Guinea', 'woothemes'),
			'PY' => __('Paraguay', 'woothemes'),
			'PE' => __('Peru', 'woothemes'),
			'PH' => __('Philippines', 'woothemes'),
			'PN' => __('Pitcairn', 'woothemes'),
			'PL' => __('Poland', 'woothemes'),
			'PT' => __('Portugal', 'woothemes'),
			'PR' => __('Puerto Rico', 'woothemes'),
			'QA' => __('Qatar', 'woothemes'),
			'RE' => __('Reunion', 'woothemes'),
			'RO' => __('Romania', 'woothemes'),
			'RU' => __('Russia', 'woothemes'),
			'RW' => __('Rwanda', 'woothemes'),
			'BL' => __('Saint Barthélemy', 'woothemes'),
			'SH' => __('Saint Helena', 'woothemes'),
			'KN' => __('Saint Kitts and Nevis', 'woothemes'),
			'LC' => __('Saint Lucia', 'woothemes'),
			'MF' => __('Saint Martin (French part)', 'woothemes'),
			'PM' => __('Saint Pierre and Miquelon', 'woothemes'),
			'VC' => __('Saint Vincent and the Grenadines', 'woothemes'),
			'WS' => __('Samoa', 'woothemes'),
			'SM' => __('San Marino', 'woothemes'),
			'ST' => __('Sao Tome and Principe', 'woothemes'),
			'SA' => __('Saudi Arabia', 'woothemes'),
			'SN' => __('Senegal', 'woothemes'),
			'RS' => __('Serbia', 'woothemes'),
			'SC' => __('Seychelles', 'woothemes'),
			'SL' => __('Sierra Leone', 'woothemes'),
			'SG' => __('Singapore', 'woothemes'),
			'SK' => __('Slovakia', 'woothemes'),
			'SI' => __('Slovenia', 'woothemes'),
			'SB' => __('Solomon Islands', 'woothemes'),
			'SO' => __('Somalia', 'woothemes'),
			'ZA' => __('South Africa', 'woothemes'),
			'GS' => __('South Georgia/Sandwich Islands', 'woothemes'),
			'KR' => __('South Korea', 'woothemes'),
			'ES' => __('Spain', 'woothemes'),
			'LK' => __('Sri Lanka', 'woothemes'),
			'SD' => __('Sudan', 'woothemes'),
			'SR' => __('Suriname', 'woothemes'),
			'SJ' => __('Svalbard and Jan Mayen', 'woothemes'),
			'SZ' => __('Swaziland', 'woothemes'),
			'SE' => __('Sweden', 'woothemes'),
			'CH' => __('Switzerland', 'woothemes'),
			'SY' => __('Syria', 'woothemes'),
			'TW' => __('Taiwan', 'woothemes'),
			'TJ' => __('Tajikistan', 'woothemes'),
			'TZ' => __('Tanzania', 'woothemes'),
			'TH' => __('Thailand', 'woothemes'),
			'TL' => __('Timor-Leste', 'woothemes'),
			'TG' => __('Togo', 'woothemes'),
			'TK' => __('Tokelau', 'woothemes'),
			'TO' => __('Tonga', 'woothemes'),
			'TT' => __('Trinidad and Tobago', 'woothemes'),
			'TN' => __('Tunisia', 'woothemes'),
			'TR' => __('Turkey', 'woothemes'),
			'TM' => __('Turkmenistan', 'woothemes'),
			'TC' => __('Turks and Caicos Islands', 'woothemes'),
			'TV' => __('Tuvalu', 'woothemes'),
			'VI' => __('U.S. Virgin Islands', 'woothemes'),
			'USAF' => __('US Armed Forces', 'woothemes'),
			'UM' => __('US Minor Outlying Islands', 'woothemes'),
			'UG' => __('Uganda', 'woothemes'),
			'UA' => __('Ukraine', 'woothemes'),
			'AE' => __('United Arab Emirates', 'woothemes'),
			'GB' => __('United Kingdom', 'woothemes'),
			'US' => __('United States', 'woothemes'),
			'UY' => __('Uruguay', 'woothemes'),
			'UZ' => __('Uzbekistan', 'woothemes'),
			'VU' => __('Vanuatu', 'woothemes'),
			'VA' => __('Vatican', 'woothemes'),
			'VE' => __('Venezuela', 'woothemes'),
			'VN' => __('Vietnam', 'woothemes'),
			'WF' => __('Wallis and Futuna', 'woothemes'),
			'EH' => __('Western Sahara', 'woothemes'),
			'YE' => __('Yemen', 'woothemes'),
			'ZM' => __('Zambia', 'woothemes'),
			'ZW' => __('Zimbabwe', 'woothemes')
		);
					
		$this->states = array(
			'AU' => array(
				'ACT' => __('Australian Capital Territory', 'woothemes') ,
				'NSW' => __('New South Wales', 'woothemes') ,
				'NT' => __('Northern Territory', 'woothemes') ,
				'QLD' => __('Queensland', 'woothemes') ,
				'SA' => __('South Australia', 'woothemes') ,
				'TAS' => __('Tasmania', 'woothemes') ,
				'VIC' => __('Victoria', 'woothemes') ,
				'WA' => __('Western Australia', 'woothemes') 
			),
			'AT' => array(),
			'BR' => array(
			    'AM' => __('Amazonas', 'woothemes'),
			    'AC' => __('Acre', 'woothemes'),
			    'AL' => __('Alagoas', 'woothemes'),
			    'AP' => __('Amap&aacute;', 'woothemes'),
			    'CE' => __('Cear&aacute;', 'woothemes'),
			    'DF' => __('Distrito federal', 'woothemes'),
			    'ES' => __('Espirito santo', 'woothemes'),
			    'MA' => __('Maranh&atilde;o', 'woothemes'),
			    'PR' => __('Paran&aacute;', 'woothemes'),
			    'PE' => __('Pernambuco', 'woothemes'),
			    'PI' => __('Piau&iacute;', 'woothemes'),
			    'RN' => __('Rio grande do norte', 'woothemes'),
			    'RS' => __('Rio grande do sul', 'woothemes'),
			    'RO' => __('Rond&ocirc;nia', 'woothemes'),
			    'RR' => __('Roraima', 'woothemes'),
			    'SC' => __('Santa catarina', 'woothemes'),
			    'SE' => __('Sergipe', 'woothemes'),
			    'TO' => __('Tocantins', 'woothemes'),
			    'PA' => __('Par&aacute;', 'woothemes'),
			    'BH' => __('Bahia', 'woothemes'),
			    'GO' => __('Goi&aacute;s', 'woothemes'),
			    'MT' => __('Mato grosso', 'woothemes'),
			    'MS' => __('Mato grosso do sul', 'woothemes'),
			    'RJ' => __('Rio de janeiro', 'woothemes'),
			    'SP' => __('S&atilde;o paulo', 'woothemes'),
			    'RS' => __('Rio grande do sul', 'woothemes'),
			    'MG' => __('Minas gerais', 'woothemes'),
			    'PB' => __('Paraiba', 'woothemes'),
			),
			'CA' => array(
				'AB' => __('Alberta', 'woothemes') ,
				'BC' => __('British Columbia', 'woothemes') ,
				'MB' => __('Manitoba', 'woothemes') ,
				'NB' => __('New Brunswick', 'woothemes') ,
				'NF' => __('Newfoundland', 'woothemes') ,
				'NT' => __('Northwest Territories', 'woothemes') ,
				'NS' => __('Nova Scotia', 'woothemes') ,
				'NU' => __('Nunavut', 'woothemes') ,
				'ON' => __('Ontario', 'woothemes') ,
				'PE' => __('Prince Edward Island', 'woothemes') ,
				'PQ' => __('Quebec', 'woothemes') ,
				'SK' => __('Saskatchewan', 'woothemes') ,
				'YT' => __('Yukon Territory', 'woothemes') 
			),
			'CZ' => array(),
			'DE' => array(),
			'DK' => array(),
			'FI' => array(),
			'FR' => array(),
			'HK' => array(
				'HONG KONG' => __('Hong Kong Island', 'woothemes'),
				'KOWLOONG' => __('Kowloong', 'woothemes'),
				'NEW TERRITORIES' => __('New Territories', 'woothemes')
			),
			'HU' => array(),
			'IS' => array(),
			'IL' => array(),
			'NL' => array(),
			'NZ' => array(),
			'NO' => array(),
			'PL' => array(),
			'SG' => array(),
			'SK' => array(),
			'SI' => array(),
			'LK' => array(),
			'SE' => array(),
			'US' => array(
				'AL' => __('Alabama', 'woothemes') ,
				'AK' => __('Alaska', 'woothemes') ,
				'AZ' => __('Arizona', 'woothemes') ,
				'AR' => __('Arkansas', 'woothemes') ,
				'CA' => __('California', 'woothemes') ,
				'CO' => __('Colorado', 'woothemes') ,
				'CT' => __('Connecticut', 'woothemes') ,
				'DE' => __('Delaware', 'woothemes') ,
				'DC' => __('District Of Columbia', 'woothemes') ,
				'FL' => __('Florida', 'woothemes') ,
				'GA' => __('Georgia', 'woothemes') ,
				'HI' => __('Hawaii', 'woothemes') ,
				'ID' => __('Idaho', 'woothemes') ,
				'IL' => __('Illinois', 'woothemes') ,
				'IN' => __('Indiana', 'woothemes') ,
				'IA' => __('Iowa', 'woothemes') ,
				'KS' => __('Kansas', 'woothemes') ,
				'KY' => __('Kentucky', 'woothemes') ,
				'LA' => __('Louisiana', 'woothemes') ,
				'ME' => __('Maine', 'woothemes') ,
				'MD' => __('Maryland', 'woothemes') ,
				'MA' => __('Massachusetts', 'woothemes') ,
				'MI' => __('Michigan', 'woothemes') ,
				'MN' => __('Minnesota', 'woothemes') ,
				'MS' => __('Mississippi', 'woothemes') ,
				'MO' => __('Missouri', 'woothemes') ,
				'MT' => __('Montana', 'woothemes') ,
				'NE' => __('Nebraska', 'woothemes') ,
				'NV' => __('Nevada', 'woothemes') ,
				'NH' => __('New Hampshire', 'woothemes') ,
				'NJ' => __('New Jersey', 'woothemes') ,
				'NM' => __('New Mexico', 'woothemes') ,
				'NY' => __('New York', 'woothemes') ,
				'NC' => __('North Carolina', 'woothemes') ,
				'ND' => __('North Dakota', 'woothemes') ,
				'OH' => __('Ohio', 'woothemes') ,
				'OK' => __('Oklahoma', 'woothemes') ,
				'OR' => __('Oregon', 'woothemes') ,
				'PA' => __('Pennsylvania', 'woothemes') ,
				'RI' => __('Rhode Island', 'woothemes') ,
				'SC' => __('South Carolina', 'woothemes') ,
				'SD' => __('South Dakota', 'woothemes') ,
				'TN' => __('Tennessee', 'woothemes') ,
				'TX' => __('Texas', 'woothemes') ,
				'UT' => __('Utah', 'woothemes') ,
				'VT' => __('Vermont', 'woothemes') ,
				'VA' => __('Virginia', 'woothemes') ,
				'WA' => __('Washington', 'woothemes') ,
				'WV' => __('West Virginia', 'woothemes') ,
				'WI' => __('Wisconsin', 'woothemes') ,
				'WY' => __('Wyoming', 'woothemes') 
			),
			'USAF' => array(
				'AA' => __('Americas', 'woothemes') ,
				'AE' => __('Europe', 'woothemes') ,
				'AP' => __('Pacific', 'woothemes') 
			)
		);
	}
	
	/** get base country */
	function get_base_country() {
		$default = get_option('woocommerce_default_country');
    	if (strstr($default, ':')) :
    		$country = current(explode(':', $default));
    		$state = end(explode(':', $default));
    	else :
    		$country = $default;
    		$state = '';
    	endif;
		
		return $country;	    	
	}
	
	/** get base state */
	function get_base_state() {
		$default = get_option('woocommerce_default_country');
    	if (strstr($default, ':')) :
    		$country = current(explode(':', $default));
    		$state = end(explode(':', $default));
    	else :
    		$country = $default;
    		$state = '';
    	endif;
		
		return $state;	    	
	}
	
	/** get countries we allow only */
	function get_allowed_countries() {
	
		$countries = $this->countries;
		
		if (get_option('woocommerce_allowed_countries')!=='specific') return $countries;

		$allowed_countries = array();
		
		$allowed_countries_raw = get_option('woocommerce_specific_allowed_countries');
		
		foreach ($allowed_countries_raw as $country) :
			
			$allowed_countries[$country] = $countries[$country];
			
		endforeach;
		
		asort($allowed_countries);
		
		return $allowed_countries;
	}
	
	/** Gets an array of countries in the EU */
	function get_european_union_countries() {
		return array('AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK');
	}
	
	/** Gets the correct string for shipping - ether 'to the' or 'to' */
	function shipping_to_prefix() {
		global $woocommerce;
		$return = '';
		if (in_array($woocommerce->customer->get_shipping_country(), array( 'GB', 'US', 'AE', 'CZ', 'DO', 'NL', 'PH', 'USAF' ))) $return = __('to the', 'woothemes');
		else $return = __('to', 'woothemes');
		return apply_filters('woocommerce_countries_shipping_to_prefix', $return, $woocommerce->customer->get_shipping_country());
	}
	
	/** Prefix certain countries with 'the' */
	function estimated_for_prefix() {
		global $woocommerce;
		$return = '';
		if (in_array($this->get_base_country(), array( 'GB', 'US', 'AE', 'CZ', 'DO', 'NL', 'PH', 'USAF' ))) $return = __('the', 'woothemes') . ' ';
		return apply_filters('woocommerce_countries_estimated_for_prefix', $return, $this->get_base_country());
	}
	
	/** Correctly name tax in some countries VAT on the frontend */
	function tax_or_vat() {
		global $woocommerce;
		
		$return = ( in_array($this->get_base_country(), $this->get_european_union_countries()) ) ? __('VAT', 'woothemes') : __('Tax', 'woothemes');
		
		return apply_filters('woocommerce_countries_tax_or_vat', $return);
	}
	
	function inc_tax_or_vat( $rate = false ) {
		global $woocommerce;
		
		if ( $rate > 0 || $rate === 0 ) :
			$rate = rtrim(rtrim($rate, '0'), '.');
			if (!$rate) $rate = 0;
			$return = ( in_array($this->get_base_country(), $this->get_european_union_countries()) ) ? sprintf(__('(inc. %s%% VAT)', 'woothemes'), $rate) : sprintf(__('(inc. %s%% tax)', 'woothemes'), $rate);
		else :
			$return = ( in_array($this->get_base_country(), $this->get_european_union_countries()) ) ? __('(inc. VAT)', 'woothemes') : __('(inc. tax)', 'woothemes');
		endif;
		
		return apply_filters('woocommerce_countries_inc_tax_or_vat', $return, $rate);
	}
	
	function ex_tax_or_vat() {
		global $woocommerce;
		
		$return = ( in_array($this->get_base_country(), $this->get_european_union_countries()) ) ? __('(ex. VAT)', 'woothemes') : __('(ex. tax)', 'woothemes');
		
		return apply_filters('woocommerce_countries_ex_tax_or_vat', $return);
	}
	
	/** get states */
	function get_states( $cc ) {
		if (isset( $this->states[$cc] )) return $this->states[$cc];
	}
	
	/** Outputs the list of countries and states for use in dropdown boxes */
	function country_dropdown_options( $selected_country = '', $selected_state = '', $escape=false ) {
		
		$countries = $this->countries;
		
		if ( $countries ) foreach ( $countries as $key=>$value) :
			if ( $states =  $this->get_states($key) ) :
				echo '<optgroup label="'.$value.'">';
    				foreach ($states as $state_key=>$state_value) :
    					echo '<option value="'.$key.':'.$state_key.'"';
    					
    					if ($selected_country==$key && $selected_state==$state_key) echo ' selected="selected"';
    					
    					echo '>'.$value.' &mdash; '. ($escape ? esc_js($state_value) : $state_value) .'</option>';
    				endforeach;
    			echo '</optgroup>';
			else :
    			echo '<option';
    			if ($selected_country==$key && $selected_state=='*') echo ' selected="selected"';
    			echo ' value="'.$key.'">'. ($escape ? esc_js( __($value, 'woothemes') ) : __($value, 'woothemes') ) .'</option>';
			endif;
		endforeach;
	}
	
	/** Outputs the list of countries and states for use in multiselect boxes */
	function country_multiselect_options( $selected_countries = '', $escape=false ) {
		
		$countries = $this->countries;
		
		if ( $countries ) foreach ( $countries as $key=>$value) :
			if ( $states =  $this->get_states($key) ) :
				echo '<optgroup label="'.$value.'">';
    				foreach ($states as $state_key=>$state_value) :
    					echo '<option value="'.$key.':'.$state_key.'"';
  
    					if (isset($selected_countries[$key]) && in_array($state_key, $selected_countries[$key])) echo ' selected="selected"';
    					
    					echo '>' . ($escape ? esc_js($state_value) : $state_value) .'</option>';
    				endforeach;
    			echo '</optgroup>';
			else :
    			echo '<option';
    			
    			if (isset($selected_countries[$key]) && in_array('*', $selected_countries[$key])) echo ' selected="selected"';
    			
    			echo ' value="'.$key.'">'. ($escape ? esc_js( __($value, 'woothemes') ) : __($value, 'woothemes') ) .'</option>';
			endif;
		endforeach;
	}
	
	/** Get country address formats */
	function get_address_format( $country = '' ) {
		
		if (!$this->address_formats) :
			
			// Common formats
			$postcode_before_city = "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}";
			
			// Define address formats
			$this->address_formats = apply_filters('woocommerce_localisation_address_formats', array(
				'default' => "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}",
				'AU' => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
				'AT' => $postcode_before_city,
				'CN' => "{country} {postcode}\n{state}, {city}, {address_2}, {address_1}\n{company}\n{name}",
				'CZ' => $postcode_before_city,
				'DE' => $postcode_before_city,
				'FI' => $postcode_before_city,
				'DK' => $postcode_before_city,
				'FR' => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city_upper}\n{country}",
				'HK' => "{company}\n{first_name} {last_name_upper}\n{address_1}\n{address_2}\n{city_upper}\n{state_upper}\n{country}",
				'HU' => "{name}\n{company}\n{city}\n{address_1}\n{address_2}\n{postcode}\n{country}",
				'IS' => $postcode_before_city,
				'IS' => $postcode_before_city,
				'NL' => $postcode_before_city,
				'NZ' => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {postcode}\n{country}",
				'NO' => $postcode_before_city,
				'PL' => $postcode_before_city,
				'SK' => $postcode_before_city,
				'SI' => $postcode_before_city,
				'ES' => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}",
				'SE' => $postcode_before_city,
				'TR' => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city} {state}\n{country}",
				'US' => "{name}\n{company}\n{address_1}\n{address_2}\n{city}, {state} {postcode}\n{country}",
			));
		endif;
	}
	
	/** Get country locale settings */
	function get_country_locale() {
		global $woocommerce;
		
		if (!$this->locale) :
		
			// Locale information used by the checkout
			$this->locale = apply_filters('woocommerce_localisation_address_fields', array(
				'AT' => array(
					'postcode_before_city' => true,
					'state'		=> array(
						'required' => false
					)
				),
				'CA' => array(
					'state'	=> array(
						'label'	=> __('Province', 'woothemes')
					)
				),
				'CL' => array(
					'city'		=> array(
						'required' 	=> false,
					),
					'state'		=> array(
						'label'		=> __('Municipality', 'woothemes')
					)
				),
				'CN' => array(
					'state'	=> array(
						'label'	=> __('Province', 'woothemes')
					)
				),
				'DE' => array(
					'postcode_before_city' => true,
					'state'		=> array(
						'required' => false
					)
				),
				'DK' => array(
					'postcode_before_city' => true,
					'state'		=> array(
						'required' => false
					)
				),
				'FI' => array(
					'postcode_before_city' => true,
					'state'		=> array(
						'required' => false
					)
				),
				'FR' => array(
					'postcode_before_city' => true,
					'state'		=> array(
						'required' => false
					)
				),
				'HK' => array(
					'postcode'	=> array(
						'required' => false
					),
					'city'	=> array(
						'label'	=> __('Town/District', 'woothemes')
					),
					'state'		=> array(
						'label' => __('Region', 'woothemes')
					)
				),
				'HU' => array(
					'state'		=> array(
						'required' => false
					)
				),
				'IS' => array(
					'postcode_before_city' => true,
					'state'		=> array(
						'required' => false
					)
				),
				'IL' => array(
					'postcode_before_city' => true,
					'state'		=> array(
						'required' => false
					)
				),
				'NL' => array(
					'postcode_before_city' => true,
					'state'		=> array(
						'required' => false
					)
				),
				'NZ' => array(
					'state'		=> array(
						'required' => false
					)
				),
				'NO' => array(
					'postcode_before_city' => true,
					'state'		=> array(
						'required' => false
					)
				),
				'PL' => array(
					'postcode_before_city' => true,
					'state'		=> array(
						'required' => false
					)
				),
				'RO' => array(
					'state'		=> array(
						'required' => false
					)
				),
				'SG' => array(
					'state'		=> array(
						'required' => false
					)
				),
				'SK' => array(
					'postcode_before_city' => true,
					'state'		=> array(
						'required' => false
					)
				),
				'SI' => array(
					'postcode_before_city' => true,
					'state'		=> array(
						'required' => false
					)
				),
				'ES' => array(
					'postcode_before_city' => true,
					'state'	=> array(
						'label'	=> __('Province', 'woothemes')
					)
				),
				'LK' => array(
					'state'	=> array(
						'required' => false
					)
				),
				'SE' => array(
					'postcode_before_city' => true,
					'state'	=> array(
						'required' => false
					)
				),
				'TR' => array(
					'postcode_before_city' => true,
					'state'	=> array(
						'label'	=> __('Province', 'woothemes')
					)
				),
				'US' => array(
					'postcode'	=> array(
						'label' => __('Zip', 'woothemes')
					),
					'state'		=> array(
						'label' => __('State', 'woothemes')
					)
				),
				'GB' => array(
					'postcode'	=> array(
						'label' => __('Postcode', 'woothemes')
					),
					'state'		=> array(
						'label' => __('County', 'woothemes')
					)
				),
						
			));
			
			$this->locale = array_intersect_key($this->locale, $this->get_allowed_countries());
			
			$this->locale['default'] = apply_filters('woocommerce_localisation_default_address_fields', array(
				'postcode'	=> array(
					'label' => __('Postcode/Zip', 'woothemes')
				),
				'city'	=> array(
					'label'	=> __('Town/City', 'woothemes')
				),
				'state'		=> array(
					'label' => __('State/County', 'woothemes')
				)
			));
			
		endif;
		
		return $this->locale;
		
	}
	
	/** Apply locale and get address fields */
	function get_address_fields( $country, $type = 'billing_' ) {
		global $woocommerce;
		
		$locale		= $this->get_country_locale();
		
		$fields = array(
			'first_name' => array( 
				'label' 		=> __('First Name', 'woothemes'), 
				'required' 		=> true, 
				'class'			=> array('form-row-first'),
				),
			'last_name' => array( 
				'label' 		=> __('Last Name', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-last'),
				'clear'			=> true
				),
			'company' 	=> array( 
				'label' 		=> __('Company Name', 'woothemes'), 
				'placeholder' 	=> __('Company (optional)', 'woothemes'),
				'clear'			=> true
				),
			'address_1' 	=> array( 
				'label' 		=> __('Address', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-first'),
				),
			'address_2' => array( 
				'label' 		=> __('Address 2', 'woothemes'), 
				'placeholder' 	=> __('Address 2 (optional)', 'woothemes'), 
				'class' 		=> array('form-row-last'), 
				'label_class' 	=> array('hidden'),
				'clear'			=> true
				),
			'city' 		=> array( 
				'label' 		=> __('Town/City', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-first'),
				),
			'postcode' 	=> array( 
				'label' 		=> __('Postcode/Zip', 'woothemes'), 
				'required' 		=> true, 
				'class'			=> array('form-row-last', 'update_totals_on_change'),
				'clear'			=> true
				),
			'country' 	=> array( 
				'type'			=> 'country', 
				'label' 		=> __('Country', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-first', 'update_totals_on_change', 'country_select'),
				),
			'state' 	=> array( 
				'type'			=> 'state', 
				'label' 		=> __('State/County', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-last', 'update_totals_on_change'),
				'clear'			=> true
				)
		);
		
		if (isset($locale[$country])) :
			
			$fields = woocommerce_array_overlay( $fields, $locale[$country] );
			
			if (isset($locale[$country]['postcode_before_city'])) :
				$fields['city']['class'] = array('form-row-last');
				$fields['postcode']['class'] = array('form-row-first', 'update_totals_on_change');
			endif;
			
		endif;
		
		// Prepend field keys
		$address_fields = array();
		
		foreach ($fields as $key => $value) :
			$address_fields[$type . $key] = $value;
		endforeach;
		
		// Billing/Shipping Specific
		if ($type=='billing_') :

			$address_fields['billing_email'] = array(
				'label' 		=> __('Email Address', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-first')
			);	
			$address_fields['billing_phone'] = array(
				'label' 		=> __('Phone', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-last'),
				'clear'			=> true
			);
			
			$address_fields = apply_filters('woocommerce_billing_fields', $address_fields);
		else :
			$address_fields = apply_filters('woocommerce_shipping_fields', $address_fields);
		endif;
		
		// Return
		return $address_fields;
	}
}