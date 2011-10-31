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
	
	var $countries = array(
		'AD' => 'Andorra',
    	'AE' => 'United Arab Emirates',
		'AF' => 'Afghanistan',
		'AG' => 'Antigua and Barbuda',
		'AI' => 'Anguilla',
		'AL' => 'Albania',
		'AM' => 'Armenia',
		'AN' => 'Netherlands Antilles',
		'AO' => 'Angola',
		'AQ' => 'Antarctica',
		'AR' => 'Argentina',
		'AS' => 'American Samoa',
		'AT' => 'Austria',
		'AU' => 'Australia',
		'AW' => 'Aruba',
		'AX' => 'Aland Islands',
		'AZ' => 'Azerbaijan',
		'BA' => 'Bosnia and Herzegovina',
		'BB' => 'Barbados',
		'BD' => 'Bangladesh',
		'BE' => 'Belgium',
		'BF' => 'Burkina Faso',
		'BG' => 'Bulgaria',
		'BH' => 'Bahrain',
		'BI' => 'Burundi',
		'BJ' => 'Benin',
		'BL' => 'Saint Barthélemy',
		'BM' => 'Bermuda',
		'BN' => 'Brunei',
		'BO' => 'Bolivia',
		'BR' => 'Brazil',
		'BS' => 'Bahamas',
		'BT' => 'Bhutan',
		'BV' => 'Bouvet Island',
		'BW' => 'Botswana',
		'BY' => 'Belarus',
		'BZ' => 'Belize',
		'CA' => 'Canada',
		'CC' => 'Cocos (Keeling) Islands',
		'CD' => 'Congo (Kinshasa)',
		'CF' => 'Central African Republic',
		'CG' => 'Congo (Brazzaville)',
		'CH' => 'Switzerland',
		'CI' => 'Ivory Coast',
		'CK' => 'Cook Islands',
		'CL' => 'Chile',
		'CM' => 'Cameroon',
		'CN' => 'China',
		'CO' => 'Colombia',
		'CR' => 'Costa Rica',
		'CU' => 'Cuba',
		'CV' => 'Cape Verde',
		'CX' => 'Christmas Island',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DE' => 'Germany',
		'DJ' => 'Djibouti',
		'DK' => 'Denmark',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'DZ' => 'Algeria',
		'EC' => 'Ecuador',
		'EE' => 'Estonia',
		'EG' => 'Egypt',
		'EH' => 'Western Sahara',
		'ER' => 'Eritrea',
		'ES' => 'Spain',
		'ET' => 'Ethiopia',
		'FI' => 'Finland',
		'FJ' => 'Fiji',
		'FK' => 'Falkland Islands',
		'FM' => 'Micronesia',
		'FO' => 'Faroe Islands',
		'FR' => 'France',
		'GA' => 'Gabon',
		'GB' => 'United Kingdom',
		'GD' => 'Grenada',
		'GE' => 'Georgia',
		'GF' => 'French Guiana',
		'GG' => 'Guernsey',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GL' => 'Greenland',
		'GM' => 'Gambia',
		'GN' => 'Guinea',
		'GP' => 'Guadeloupe',
		'GQ' => 'Equatorial Guinea',
		'GR' => 'Greece',
		'GS' => 'South Georgia and the South Sandwich Islands',
		'GT' => 'Guatemala',
		'GU' => 'Guam',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HK' => 'Hong Kong S.A.R., China',
		'HM' => 'Heard Island and McDonald Islands',
		'HN' => 'Honduras',
		'HR' => 'Croatia',
		'HT' => 'Haiti',
		'HU' => 'Hungary',
		'ID' => 'Indonesia',
		'IE' => 'Ireland',
		'IL' => 'Israel',
		'IM' => 'Isle of Man',
		'IN' => 'India',
		'IO' => 'British Indian Ocean Territory',
		'IQ' => 'Iraq',
		'IR' => 'Iran',
		'IS' => 'Iceland',
		'IT' => 'Italy',
		'JE' => 'Jersey',
		'JM' => 'Jamaica',
		'JO' => 'Jordan',
		'JP' => 'Japan',
		'KE' => 'Kenya',
		'KG' => 'Kyrgyzstan',
		'KH' => 'Cambodia',
		'KI' => 'Kiribati',
		'KM' => 'Comoros',
		'KN' => 'Saint Kitts and Nevis',
		'KP' => 'North Korea',
		'KR' => 'South Korea',
		'KW' => 'Kuwait',
		'KY' => 'Cayman Islands',
		'KZ' => 'Kazakhstan',
		'LA' => 'Laos',
		'LB' => 'Lebanon',
		'LC' => 'Saint Lucia',
		'LI' => 'Liechtenstein',
		'LK' => 'Sri Lanka',
		'LR' => 'Liberia',
		'LS' => 'Lesotho',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'LV' => 'Latvia',
		'LY' => 'Libya',
		'MA' => 'Morocco',
		'MC' => 'Monaco',
		'MD' => 'Moldova',
		'ME' => 'Montenegro',
		'MF' => 'Saint Martin (French part)',
		'MG' => 'Madagascar',
		'MH' => 'Marshall Islands',
		'MK' => 'Macedonia',
		'ML' => 'Mali',
		'MM' => 'Myanmar',
		'MN' => 'Mongolia',
		'MO' => 'Macao S.A.R., China',
		'MP' => 'Northern Mariana Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MS' => 'Montserrat',
		'MT' => 'Malta',
		'MU' => 'Mauritius',
		'MV' => 'Maldives',
		'MW' => 'Malawi',
		'MX' => 'Mexico',
		'MY' => 'Malaysia',
		'MZ' => 'Mozambique',
		'NA' => 'Namibia',
		'NC' => 'New Caledonia',
		'NE' => 'Niger',
		'NF' => 'Norfolk Island',
		'NG' => 'Nigeria',
		'NI' => 'Nicaragua',
		'NL' => 'Netherlands',
		'NO' => 'Norway',
		'NP' => 'Nepal',
		'NR' => 'Nauru',
		'NU' => 'Niue',
		'NZ' => 'New Zealand',
		'OM' => 'Oman',
		'PA' => 'Panama',
		'PE' => 'Peru',
		'PF' => 'French Polynesia',
		'PG' => 'Papua New Guinea',
		'PH' => 'Philippines',
		'PK' => 'Pakistan',
		'PL' => 'Poland',
		'PM' => 'Saint Pierre and Miquelon',
		'PN' => 'Pitcairn',
		'PR' => 'Puerto Rico',
		'PS' => 'Palestinian Territory',
		'PT' => 'Portugal',
		'PW' => 'Palau',
		'PY' => 'Paraguay',
		'QA' => 'Qatar',
		'RE' => 'Reunion',
		'RO' => 'Romania',
		'RS' => 'Serbia',
		'RU' => 'Russia',
		'RW' => 'Rwanda',
		'SA' => 'Saudi Arabia',
		'SB' => 'Solomon Islands',
		'SC' => 'Seychelles',
		'SD' => 'Sudan',
		'SE' => 'Sweden',
		'SG' => 'Singapore',
		'SH' => 'Saint Helena',
		'SI' => 'Slovenia',
		'SJ' => 'Svalbard and Jan Mayen',
		'SK' => 'Slovakia',
		'SL' => 'Sierra Leone',
		'SM' => 'San Marino',
		'SN' => 'Senegal',
		'SO' => 'Somalia',
		'SR' => 'Suriname',
		'ST' => 'Sao Tome and Principe',
		'SV' => 'El Salvador',
		'SY' => 'Syria',
		'SZ' => 'Swaziland',
		'TC' => 'Turks and Caicos Islands',
		'TD' => 'Chad',
		'TF' => 'French Southern Territories',
		'TG' => 'Togo',
		'TH' => 'Thailand',
		'TJ' => 'Tajikistan',
		'TK' => 'Tokelau',
		'TL' => 'Timor-Leste',
		'TM' => 'Turkmenistan',
		'TN' => 'Tunisia',
		'TO' => 'Tonga',
		'TR' => 'Turkey',
		'TT' => 'Trinidad and Tobago',
		'TV' => 'Tuvalu',
		'TW' => 'Taiwan',
		'TZ' => 'Tanzania',
		'UA' => 'Ukraine',
		'UG' => 'Uganda',
		'UM' => 'United States Minor Outlying Islands',
		'US' => 'United States',
		'USAF' => 'US Armed Forces', 
		'UY' => 'Uruguay',
		'UZ' => 'Uzbekistan',
		'VA' => 'Vatican',
		'VC' => 'Saint Vincent and the Grenadines',
		'VE' => 'Venezuela',
		'VG' => 'British Virgin Islands',
		'VI' => 'U.S. Virgin Islands',
		'VN' => 'Vietnam',
		'VU' => 'Vanuatu',
		'WF' => 'Wallis and Futuna',
		'WS' => 'Samoa',
		'YE' => 'Yemen',
		'YT' => 'Mayotte',
		'ZA' => 'South Africa',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe'
	);
	
	var $states = array(
		'AU' => array(
			'ACT' =>  'Australian Capital Territory' ,
			'NSW' =>  'New South Wales' ,
			'NT' =>  'Northern Territory' ,
			'QLD' =>  'Queensland' ,
			'SA' =>  'South Australia' ,
			'TAS' =>  'Tasmania' ,
			'VIC' =>  'Victoria' ,
			'WA' =>  'Western Australia' 
		),
		'CA' => array(
			'AB' =>  'Alberta' ,
			'BC' =>  'British Columbia' ,
			'MB' =>  'Manitoba' ,
			'NB' =>  'New Brunswick' ,
			'NF' =>  'Newfoundland' ,
			'NT' =>  'Northwest Territories' ,
			'NS' =>  'Nova Scotia' ,
			'NU' =>  'Nunavut' ,
			'ON' =>  'Ontario' ,
			'PE' =>  'Prince Edward Island' ,
			'PQ' =>  'Quebec' ,
			'SK' =>  'Saskatchewan' ,
			'YT' =>  'Yukon Territory' 
		),
		/*'GB' => array(
			'England' => array(
				'Avon' => 'Avon',
				'Bedfordshire' => 'Bedfordshire',
				'Berkshire' => 'Berkshire',
				'Bristol' => 'Bristol',
				'Buckinghamshire' => 'Buckinghamshire',
				'Cambridgeshire' => 'Cambridgeshire',
				'Cheshire' => 'Cheshire',
				'Cleveland' => 'Cleveland',
				'Cornwall' => 'Cornwall',
				'Cumbria' => 'Cumbria',
				'Derbyshire' => 'Derbyshire',
				'Devon' => 'Devon',
				'Dorset' => 'Dorset',
				'Durham' => 'Durham',
				'East Riding of Yorkshire' => 'East Riding of Yorkshire',
				'East Sussex' => 'East Sussex',
				'Essex' => 'Essex',
				'Gloucestershire' => 'Gloucestershire',
				'Greater Manchester' => 'Greater Manchester',
				'Hampshire' => 'Hampshire',
				'Herefordshire' => 'Herefordshire',
				'Hertfordshire' => 'Hertfordshire',
				'Humberside' => 'Humberside',
				'Isle of Wight' => 'Isle of Wight',
				'Isles of Scilly' => 'Isles of Scilly',
				'Kent' => 'Kent',
				'Lancashire' => 'Lancashire',
				'Leicestershire' => 'Leicestershire',
				'Lincolnshire' => 'Lincolnshire',
				'London' => 'London',
				'Merseyside' => 'Merseyside',
				'Middlesex' => 'Middlesex',
				'Norfolk' => 'Norfolk',
				'North Yorkshire' => 'North Yorkshire',
				'Northamptonshire' => 'Northamptonshire',
				'Northumberland' => 'Northumberland',
				'Nottinghamshire' => 'Nottinghamshire',
				'Oxfordshire' => 'Oxfordshire',
				'Rutland' => 'Rutland',
				'Shropshire' => 'Shropshire',
				'Somerset' => 'Somerset',
				'South Yorkshire' => 'South Yorkshire',
				'Staffordshire' => 'Staffordshire',
				'Suffolk' => 'Suffolk',
				'Surrey' => 'Surrey',
				'Tyne and Wear' => 'Tyne and Wear',
				'Warwickshire' => 'Warwickshire',
				'West Midlands' => 'West Midlands',
				'West Sussex' => 'West Sussex',
				'West Yorkshire' => 'West Yorkshire',
				'Wiltshire' => 'Wiltshire',
				'Worcestershire' => 'Worcestershire'
			),
			'Northern Ireland' => array(
				'Antrim' => 'Antrim',
				'Armagh' => 'Armagh',
				'Down' => 'Down',
				'Fermanagh' => 'Fermanagh',
				'Londonderry' => 'Londonderry',
				'Tyrone' => 'Tyrone'
			),
			'Scotland' => array(
				'Aberdeen City' => 'Aberdeen City',
				'Aberdeenshire' => 'Aberdeenshire',
				'Angus' => 'Angus',
				'Argyll and Bute' => 'Argyll and Bute',
				'Borders' => 'Borders',
				'Clackmannan' => 'Clackmannan',
				'Dumfries and Galloway' => 'Dumfries and Galloway',
				'East Ayrshire' => 'East Ayrshire',
				'East Dunbartonshire' => 'East Dunbartonshire',
				'East Lothian' => 'East Lothian',
				'East Renfrewshire' => 'East Renfrewshire',
				'Edinburgh City' => 'Edinburgh City',
				'Falkirk' => 'Falkirk',
				'Fife' => 'Fife',
				'Glasgow' => 'Glasgow',
				'Highland' => 'Highland',
				'Inverclyde' => 'Inverclyde',
				'Midlothian' => 'Midlothian',
				'Moray' => 'Moray',
				'North Ayrshire' => 'North Ayrshire',
				'North Lanarkshire' => 'North Lanarkshire',
				'Orkney' => 'Orkney',
				'Perthshire and Kinross' => 'Perthshire and Kinross',
				'Renfrewshire' => 'Renfrewshire',
				'Roxburghshire' => 'Roxburghshire',
				'Shetland' => 'Shetland',
				'South Ayrshire' => 'South Ayrshire',
				'South Lanarkshire' => 'South Lanarkshire',
				'Stirling' => 'Stirling',
				'West Dunbartonshire' => 'West Dunbartonshire',
				'West Lothian' => 'West Lothian',
				'Western Isles' => 'Western Isles',
			),
			'Wales' => array(
				'Blaenau Gwent' => 'Blaenau Gwent',
				'Bridgend' => 'Bridgend',
				'Caerphilly' => 'Caerphilly',
				'Cardiff' => 'Cardiff',
				'Carmarthenshire' => 'Carmarthenshire',
				'Ceredigion' => 'Ceredigion',
				'Conwy' => 'Conwy',
				'Denbighshire' => 'Denbighshire',
				'Flintshire' => 'Flintshire',
				'Gwynedd' => 'Gwynedd',
				'Isle of Anglesey' => 'Isle of Anglesey',
				'Merthyr Tydfil' => 'Merthyr Tydfil',
				'Monmouthshire' => 'Monmouthshire',
				'Neath Port Talbot' => 'Neath Port Talbot',
				'Newport' => 'Newport',
				'Pembrokeshire' => 'Pembrokeshire',
				'Powys' => 'Powys',
				'Rhondda Cynon Taff' => 'Rhondda Cynon Taff',
				'Swansea' => 'Swansea',
				'Torfaen' => 'Torfaen',
				'The Vale of Glamorgan' => 'The Vale of Glamorgan',
				'Wrexham' => 'Wrexham'
			)
		),*/
		'US' => array(
			'AL' =>  'Alabama' ,
			'AK' =>  'Alaska' ,
			'AZ' =>  'Arizona' ,
			'AR' =>  'Arkansas' ,
			'CA' =>  'California' ,
			'CO' =>  'Colorado' ,
			'CT' =>  'Connecticut' ,
			'DE' =>  'Delaware' ,
			'DC' =>  'District Of Columbia' ,
			'FL' =>  'Florida' ,
			'GA' =>  'Georgia' ,
			'HI' =>  'Hawaii' ,
			'ID' =>  'Idaho' ,
			'IL' =>  'Illinois' ,
			'IN' =>  'Indiana' ,
			'IA' =>  'Iowa' ,
			'KS' =>  'Kansas' ,
			'KY' =>  'Kentucky' ,
			'LA' =>  'Louisiana' ,
			'ME' =>  'Maine' ,
			'MD' =>  'Maryland' ,
			'MA' =>  'Massachusetts' ,
			'MI' =>  'Michigan' ,
			'MN' =>  'Minnesota' ,
			'MS' =>  'Mississippi' ,
			'MO' =>  'Missouri' ,
			'MT' =>  'Montana' ,
			'NE' =>  'Nebraska' ,
			'NV' =>  'Nevada' ,
			'NH' =>  'New Hampshire' ,
			'NJ' =>  'New Jersey' ,
			'NM' =>  'New Mexico' ,
			'NY' =>  'New York' ,
			'NC' =>  'North Carolina' ,
			'ND' =>  'North Dakota' ,
			'OH' =>  'Ohio' ,
			'OK' =>  'Oklahoma' ,
			'OR' =>  'Oregon' ,
			'PA' =>  'Pennsylvania' ,
			'RI' =>  'Rhode Island' ,
			'SC' =>  'South Carolina' ,
			'SD' =>  'South Dakota' ,
			'TN' =>  'Tennessee' ,
			'TX' =>  'Texas' ,
			'UT' =>  'Utah' ,
			'VT' =>  'Vermont' ,
			'VA' =>  'Virginia' ,
			'WA' =>  'Washington' ,
			'WV' =>  'West Virginia' ,
			'WI' =>  'Wisconsin' ,
			'WY' =>  'Wyoming' 
		),
		'USAF' => array(
			'AA' =>  'Americas' ,
			'AE' =>  'Europe' ,
			'AP' =>  'Pacific' 
		)
	);
	
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
		
		asort($countries);
		
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
		if (in_array($woocommerce->customer->get_country(), array( 'GB', 'US', 'AE', 'CZ', 'DO', 'NL', 'PH', 'USAF' ))) $return = __('to the', 'woothemes');
		else $return = __('to', 'woothemes');
		return apply_filters('woocommerce_countries_shipping_to_prefix', $return, $woocommerce->customer->get_shipping_country());
	}
	
	/** Prefix certain countries with 'the' */
	function estimated_for_prefix() {
		global $woocommerce;
		$return = '';
		if (in_array($woocommerce->customer->get_country(), array( 'GB', 'US', 'AE', 'CZ', 'DO', 'NL', 'PH', 'USAF' ))) $return = __('the', 'woothemes') . ' ';
		return apply_filters('woocommerce_countries_estimated_for_prefix', $return, $woocommerce->customer->get_shipping_country());
	}
	
	/** Correctly name tax in some countries VAT on the frontend */
	function tax_or_vat() {
		global $woocommerce;
		
		$return = ( in_array($this->get_base_country(), $this->get_european_union_countries()) ) ? __('VAT', 'woothemes') : __('tax', 'woothemes');
		
		return apply_filters('woocommerce_countries_tax_or_vat', $return);
	}
	
	function inc_tax_or_vat() {
		global $woocommerce;
		
		$return = ( in_array($this->get_base_country(), $this->get_european_union_countries()) ) ? __('(inc. VAT)', 'woothemes') : __('(inc. tax)', 'woothemes');
		
		return apply_filters('woocommerce_countries_inc_tax_or_vat', $return);
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
		asort($countries);
		
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
		asort($countries);
		
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
}

