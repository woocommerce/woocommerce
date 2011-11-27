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
	
	/**
	 * Constructor
	 */
	function __construct() {
	
		$this->countries = array(
			'AD' => __('Andorra', 'woothemes'),
	    	'AE' => __('United Arab Emirates', 'woothemes'),
			'AF' => __('Afghanistan', 'woothemes'),
			'AG' => __('Antigua and Barbuda', 'woothemes'),
			'AI' => __('Anguilla', 'woothemes'),
			'AL' => __('Albania', 'woothemes'),
			'AM' => __('Armenia', 'woothemes'),
			'AN' => __('Netherlands Antilles', 'woothemes'),
			'AO' => __('Angola', 'woothemes'),
			'AQ' => __('Antarctica', 'woothemes'),
			'AR' => __('Argentina', 'woothemes'),
			'AS' => __('American Samoa', 'woothemes'),
			'AT' => __('Austria', 'woothemes'),
			'AU' => __('Australia', 'woothemes'),
			'AW' => __('Aruba', 'woothemes'),
			'AX' => __('Aland Islands', 'woothemes'),
			'AZ' => __('Azerbaijan', 'woothemes'),
			'BA' => __('Bosnia and Herzegovina', 'woothemes'),
			'BB' => __('Barbados', 'woothemes'),
			'BD' => __('Bangladesh', 'woothemes'),
			'BE' => __('Belgium', 'woothemes'),
			'BF' => __('Burkina Faso', 'woothemes'),
			'BG' => __('Bulgaria', 'woothemes'),
			'BH' => __('Bahrain', 'woothemes'),
			'BI' => __('Burundi', 'woothemes'),
			'BJ' => __('Benin', 'woothemes'),
			'BL' => __('Saint Barthélemy', 'woothemes'),
			'BM' => __('Bermuda', 'woothemes'),
			'BN' => __('Brunei', 'woothemes'),
			'BO' => __('Bolivia', 'woothemes'),
			'BR' => __('Brazil', 'woothemes'),
			'BS' => __('Bahamas', 'woothemes'),
			'BT' => __('Bhutan', 'woothemes'),
			'BV' => __('Bouvet Island', 'woothemes'),
			'BW' => __('Botswana', 'woothemes'),
			'BY' => __('Belarus', 'woothemes'),
			'BZ' => __('Belize', 'woothemes'),
			'CA' => __('Canada', 'woothemes'),
			'CC' => __('Cocos (Keeling) Islands', 'woothemes'),
			'CD' => __('Congo (Kinshasa)', 'woothemes'),
			'CF' => __('Central African Republic', 'woothemes'),
			'CG' => __('Congo (Brazzaville)', 'woothemes'),
			'CH' => __('Switzerland', 'woothemes'),
			'CI' => __('Ivory Coast', 'woothemes'),
			'CK' => __('Cook Islands', 'woothemes'),
			'CL' => __('Chile', 'woothemes'),
			'CM' => __('Cameroon', 'woothemes'),
			'CN' => __('China', 'woothemes'),
			'CO' => __('Colombia', 'woothemes'),
			'CR' => __('Costa Rica', 'woothemes'),
			'CU' => __('Cuba', 'woothemes'),
			'CV' => __('Cape Verde', 'woothemes'),
			'CX' => __('Christmas Island', 'woothemes'),
			'CY' => __('Cyprus', 'woothemes'),
			'CZ' => __('Czech Republic', 'woothemes'),
			'DE' => __('Germany', 'woothemes'),
			'DJ' => __('Djibouti', 'woothemes'),
			'DK' => __('Denmark', 'woothemes'),
			'DM' => __('Dominica', 'woothemes'),
			'DO' => __('Dominican Republic', 'woothemes'),
			'DZ' => __('Algeria', 'woothemes'),
			'EC' => __('Ecuador', 'woothemes'),
			'EE' => __('Estonia', 'woothemes'),
			'EG' => __('Egypt', 'woothemes'),
			'EH' => __('Western Sahara', 'woothemes'),
			'ER' => __('Eritrea', 'woothemes'),
			'ES' => __('Spain', 'woothemes'),
			'ET' => __('Ethiopia', 'woothemes'),
			'FI' => __('Finland', 'woothemes'),
			'FJ' => __('Fiji', 'woothemes'),
			'FK' => __('Falkland Islands', 'woothemes'),
			'FM' => __('Micronesia', 'woothemes'),
			'FO' => __('Faroe Islands', 'woothemes'),
			'FR' => __('France', 'woothemes'),
			'GA' => __('Gabon', 'woothemes'),
			'GB' => __('United Kingdom', 'woothemes'),
			'GD' => __('Grenada', 'woothemes'),
			'GE' => __('Georgia', 'woothemes'),
			'GF' => __('French Guiana', 'woothemes'),
			'GG' => __('Guernsey', 'woothemes'),
			'GH' => __('Ghana', 'woothemes'),
			'GI' => __('Gibraltar', 'woothemes'),
			'GL' => __('Greenland', 'woothemes'),
			'GM' => __('Gambia', 'woothemes'),
			'GN' => __('Guinea', 'woothemes'),
			'GP' => __('Guadeloupe', 'woothemes'),
			'GQ' => __('Equatorial Guinea', 'woothemes'),
			'GR' => __('Greece', 'woothemes'),
			'GS' => __('South Georgia/Sandwich Islands', 'woothemes'),
			'GT' => __('Guatemala', 'woothemes'),
			'GU' => __('Guam', 'woothemes'),
			'GW' => __('Guinea-Bissau', 'woothemes'),
			'GY' => __('Guyana', 'woothemes'),
			'HK' => __('Hong Kong S.A.R., China', 'woothemes'),
			//'HM' => __('Heard Island and McDonald Islands', 'woothemes'), // Uninhabitted :)
			'HN' => __('Honduras', 'woothemes'),
			'HR' => __('Croatia', 'woothemes'),
			'HT' => __('Haiti', 'woothemes'),
			'HU' => __('Hungary', 'woothemes'),
			'ID' => __('Indonesia', 'woothemes'),
			'IE' => __('Ireland', 'woothemes'),
			'IL' => __('Israel', 'woothemes'),
			'IM' => __('Isle of Man', 'woothemes'),
			'IN' => __('India', 'woothemes'),
			'IO' => __('British Indian Ocean Territory', 'woothemes'),
			'IQ' => __('Iraq', 'woothemes'),
			'IR' => __('Iran', 'woothemes'),
			'IS' => __('Iceland', 'woothemes'),
			'IT' => __('Italy', 'woothemes'),
			'JE' => __('Jersey', 'woothemes'),
			'JM' => __('Jamaica', 'woothemes'),
			'JO' => __('Jordan', 'woothemes'),
			'JP' => __('Japan', 'woothemes'),
			'KE' => __('Kenya', 'woothemes'),
			'KG' => __('Kyrgyzstan', 'woothemes'),
			'KH' => __('Cambodia', 'woothemes'),
			'KI' => __('Kiribati', 'woothemes'),
			'KM' => __('Comoros', 'woothemes'),
			'KN' => __('Saint Kitts and Nevis', 'woothemes'),
			'KP' => __('North Korea', 'woothemes'),
			'KR' => __('South Korea', 'woothemes'),
			'KW' => __('Kuwait', 'woothemes'),
			'KY' => __('Cayman Islands', 'woothemes'),
			'KZ' => __('Kazakhstan', 'woothemes'),
			'LA' => __('Laos', 'woothemes'),
			'LB' => __('Lebanon', 'woothemes'),
			'LC' => __('Saint Lucia', 'woothemes'),
			'LI' => __('Liechtenstein', 'woothemes'),
			'LK' => __('Sri Lanka', 'woothemes'),
			'LR' => __('Liberia', 'woothemes'),
			'LS' => __('Lesotho', 'woothemes'),
			'LT' => __('Lithuania', 'woothemes'),
			'LU' => __('Luxembourg', 'woothemes'),
			'LV' => __('Latvia', 'woothemes'),
			'LY' => __('Libya', 'woothemes'),
			'MA' => __('Morocco', 'woothemes'),
			'MC' => __('Monaco', 'woothemes'),
			'MD' => __('Moldova', 'woothemes'),
			'ME' => __('Montenegro', 'woothemes'),
			'MF' => __('Saint Martin (French part)', 'woothemes'),
			'MG' => __('Madagascar', 'woothemes'),
			'MH' => __('Marshall Islands', 'woothemes'),
			'MK' => __('Macedonia', 'woothemes'),
			'ML' => __('Mali', 'woothemes'),
			'MM' => __('Myanmar', 'woothemes'),
			'MN' => __('Mongolia', 'woothemes'),
			'MO' => __('Macao S.A.R., China', 'woothemes'),
			'MP' => __('Northern Mariana Islands', 'woothemes'),
			'MQ' => __('Martinique', 'woothemes'),
			'MR' => __('Mauritania', 'woothemes'),
			'MS' => __('Montserrat', 'woothemes'),
			'MT' => __('Malta', 'woothemes'),
			'MU' => __('Mauritius', 'woothemes'),
			'MV' => __('Maldives', 'woothemes'),
			'MW' => __('Malawi', 'woothemes'),
			'MX' => __('Mexico', 'woothemes'),
			'MY' => __('Malaysia', 'woothemes'),
			'MZ' => __('Mozambique', 'woothemes'),
			'NA' => __('Namibia', 'woothemes'),
			'NC' => __('New Caledonia', 'woothemes'),
			'NE' => __('Niger', 'woothemes'),
			'NF' => __('Norfolk Island', 'woothemes'),
			'NG' => __('Nigeria', 'woothemes'),
			'NI' => __('Nicaragua', 'woothemes'),
			'NL' => __('Netherlands', 'woothemes'),
			'NO' => __('Norway', 'woothemes'),
			'NP' => __('Nepal', 'woothemes'),
			'NR' => __('Nauru', 'woothemes'),
			'NU' => __('Niue', 'woothemes'),
			'NZ' => __('New Zealand', 'woothemes'),
			'OM' => __('Oman', 'woothemes'),
			'PA' => __('Panama', 'woothemes'),
			'PE' => __('Peru', 'woothemes'),
			'PF' => __('French Polynesia', 'woothemes'),
			'PG' => __('Papua New Guinea', 'woothemes'),
			'PH' => __('Philippines', 'woothemes'),
			'PK' => __('Pakistan', 'woothemes'),
			'PL' => __('Poland', 'woothemes'),
			'PM' => __('Saint Pierre and Miquelon', 'woothemes'),
			'PN' => __('Pitcairn', 'woothemes'),
			'PR' => __('Puerto Rico', 'woothemes'),
			'PS' => __('Palestinian Territory', 'woothemes'),
			'PT' => __('Portugal', 'woothemes'),
			'PW' => __('Palau', 'woothemes'),
			'PY' => __('Paraguay', 'woothemes'),
			'QA' => __('Qatar', 'woothemes'),
			'RE' => __('Reunion', 'woothemes'),
			'RO' => __('Romania', 'woothemes'),
			'RS' => __('Serbia', 'woothemes'),
			'RU' => __('Russia', 'woothemes'),
			'RW' => __('Rwanda', 'woothemes'),
			'SA' => __('Saudi Arabia', 'woothemes'),
			'SB' => __('Solomon Islands', 'woothemes'),
			'SC' => __('Seychelles', 'woothemes'),
			'SD' => __('Sudan', 'woothemes'),
			'SE' => __('Sweden', 'woothemes'),
			'SG' => __('Singapore', 'woothemes'),
			'SH' => __('Saint Helena', 'woothemes'),
			'SI' => __('Slovenia', 'woothemes'),
			'SJ' => __('Svalbard and Jan Mayen', 'woothemes'),
			'SK' => __('Slovakia', 'woothemes'),
			'SL' => __('Sierra Leone', 'woothemes'),
			'SM' => __('San Marino', 'woothemes'),
			'SN' => __('Senegal', 'woothemes'),
			'SO' => __('Somalia', 'woothemes'),
			'SR' => __('Suriname', 'woothemes'),
			'ST' => __('Sao Tome and Principe', 'woothemes'),
			'SV' => __('El Salvador', 'woothemes'),
			'SY' => __('Syria', 'woothemes'),
			'SZ' => __('Swaziland', 'woothemes'),
			'TC' => __('Turks and Caicos Islands', 'woothemes'),
			'TD' => __('Chad', 'woothemes'),
			'TF' => __('French Southern Territories', 'woothemes'),
			'TG' => __('Togo', 'woothemes'),
			'TH' => __('Thailand', 'woothemes'),
			'TJ' => __('Tajikistan', 'woothemes'),
			'TK' => __('Tokelau', 'woothemes'),
			'TL' => __('Timor-Leste', 'woothemes'),
			'TM' => __('Turkmenistan', 'woothemes'),
			'TN' => __('Tunisia', 'woothemes'),
			'TO' => __('Tonga', 'woothemes'),
			'TR' => __('Turkey', 'woothemes'),
			'TT' => __('Trinidad and Tobago', 'woothemes'),
			'TV' => __('Tuvalu', 'woothemes'),
			'TW' => __('Taiwan', 'woothemes'),
			'TZ' => __('Tanzania', 'woothemes'),
			'UA' => __('Ukraine', 'woothemes'),
			'UG' => __('Uganda', 'woothemes'),
			'UM' => __('US Minor Outlying Islands', 'woothemes'),
			'US' => __('United States', 'woothemes'),
			'USAF' => __('US Armed Forces', 'woothemes'), 
			'UY' => __('Uruguay', 'woothemes'),
			'UZ' => __('Uzbekistan', 'woothemes'),
			'VA' => __('Vatican', 'woothemes'),
			'VC' => __('Saint Vincent and the Grenadines', 'woothemes'),
			'VE' => __('Venezuela', 'woothemes'),
			'VG' => __('British Virgin Islands', 'woothemes'),
			'VI' => __('U.S. Virgin Islands', 'woothemes'),
			'VN' => __('Vietnam', 'woothemes'),
			'VU' => __('Vanuatu', 'woothemes'),
			'WF' => __('Wallis and Futuna', 'woothemes'),
			'WS' => __('Samoa', 'woothemes'),
			'YE' => __('Yemen', 'woothemes'),
			'YT' => __('Mayotte', 'woothemes'),
			'ZA' => __('South Africa', 'woothemes'),
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
			/*'GB' => array(
				'England' => array(
					'Avon' => __('Avon', 'woothemes'),
					'Bedfordshire' => __('Bedfordshire', 'woothemes'),
					'Berkshire' => __('Berkshire', 'woothemes'),
					'Bristol' => __('Bristol', 'woothemes'),
					'Buckinghamshire' => __('Buckinghamshire', 'woothemes'),
					'Cambridgeshire' => __('Cambridgeshire', 'woothemes'),
					'Cheshire' => __('Cheshire', 'woothemes'),
					'Cleveland' => __('Cleveland', 'woothemes'),
					'Cornwall' => __('Cornwall', 'woothemes'),
					'Cumbria' => __('Cumbria', 'woothemes'),
					'Derbyshire' => __('Derbyshire', 'woothemes'),
					'Devon' => __('Devon', 'woothemes'),
					'Dorset' => __('Dorset', 'woothemes'),
					'Durham' => __('Durham', 'woothemes'),
					'East Riding of Yorkshire' => __('East Riding of Yorkshire', 'woothemes'),
					'East Sussex' => __('East Sussex', 'woothemes'),
					'Essex' => __('Essex', 'woothemes'),
					'Gloucestershire' => __('Gloucestershire', 'woothemes'),
					'Greater Manchester' => __('Greater Manchester', 'woothemes'),
					'Hampshire' => __('Hampshire', 'woothemes'),
					'Herefordshire' => __('Herefordshire', 'woothemes'),
					'Hertfordshire' => __('Hertfordshire', 'woothemes'),
					'Humberside' => __('Humberside', 'woothemes'),
					'Isle of Wight' => __('Isle of Wight', 'woothemes'),
					'Isles of Scilly' => __('Isles of Scilly', 'woothemes'),
					'Kent' => __('Kent', 'woothemes'),
					'Lancashire' => __('Lancashire', 'woothemes'),
					'Leicestershire' => __('Leicestershire', 'woothemes'),
					'Lincolnshire' => __('Lincolnshire', 'woothemes'),
					'London' => __('London', 'woothemes'),
					'Merseyside' => __('Merseyside', 'woothemes'),
					'Middlesex' => __('Middlesex', 'woothemes'),
					'Norfolk' => __('Norfolk', 'woothemes'),
					'North Yorkshire' => __('North Yorkshire', 'woothemes'),
					'Northamptonshire' => __('Northamptonshire', 'woothemes'),
					'Northumberland' => __('Northumberland', 'woothemes'),
					'Nottinghamshire' => __('Nottinghamshire', 'woothemes'),
					'Oxfordshire' => __('Oxfordshire', 'woothemes'),
					'Rutland' => __('Rutland', 'woothemes'),
					'Shropshire' => __('Shropshire', 'woothemes'),
					'Somerset' => __('Somerset', 'woothemes'),
					'South Yorkshire' => __('South Yorkshire', 'woothemes'),
					'Staffordshire' => __('Staffordshire', 'woothemes'),
					'Suffolk' => __('Suffolk', 'woothemes'),
					'Surrey' => __('Surrey', 'woothemes'),
					'Tyne and Wear' => __('Tyne and Wear', 'woothemes'),
					'Warwickshire' => __('Warwickshire', 'woothemes'),
					'West Midlands' => __('West Midlands', 'woothemes'),
					'West Sussex' => __('West Sussex', 'woothemes'),
					'West Yorkshire' => __('West Yorkshire', 'woothemes'),
					'Wiltshire' => __('Wiltshire', 'woothemes'),
					'Worcestershire' => __('Worcestershire', 'woothemes')
				),
				'Northern Ireland' => array(
					'Antrim' => __('Antrim', 'woothemes'),
					'Armagh' => __('Armagh', 'woothemes'),
					'Down' => __('Down', 'woothemes'),
					'Fermanagh' => __('Fermanagh', 'woothemes'),
					'Londonderry' => __('Londonderry', 'woothemes'),
					'Tyrone' => __('Tyrone', 'woothemes')
				),
				'Scotland' => array(
					'Aberdeen City' => __('Aberdeen City', 'woothemes'),
					'Aberdeenshire' => __('Aberdeenshire', 'woothemes'),
					'Angus' => __('Angus', 'woothemes'),
					'Argyll and Bute' => __('Argyll and Bute', 'woothemes'),
					'Borders' => __('Borders', 'woothemes'),
					'Clackmannan' => __('Clackmannan', 'woothemes'),
					'Dumfries and Galloway' => __('Dumfries and Galloway', 'woothemes'),
					'East Ayrshire' => __('East Ayrshire', 'woothemes'),
					'East Dunbartonshire' => __('East Dunbartonshire', 'woothemes'),
					'East Lothian' => __('East Lothian', 'woothemes'),
					'East Renfrewshire' => __('East Renfrewshire', 'woothemes'),
					'Edinburgh City' => __('Edinburgh City', 'woothemes'),
					'Falkirk' => __('Falkirk', 'woothemes'),
					'Fife' => __('Fife', 'woothemes'),
					'Glasgow' => __('Glasgow', 'woothemes'),
					'Highland' => __('Highland', 'woothemes'),
					'Inverclyde' => __('Inverclyde', 'woothemes'),
					'Midlothian' => __('Midlothian', 'woothemes'),
					'Moray' => __('Moray', 'woothemes'),
					'North Ayrshire' => __('North Ayrshire', 'woothemes'),
					'North Lanarkshire' => __('North Lanarkshire', 'woothemes'),
					'Orkney' => __('Orkney', 'woothemes'),
					'Perthshire and Kinross' => __('Perthshire and Kinross', 'woothemes'),
					'Renfrewshire' => __('Renfrewshire', 'woothemes'),
					'Roxburghshire' => __('Roxburghshire', 'woothemes'),
					'Shetland' => __('Shetland', 'woothemes'),
					'South Ayrshire' => __('South Ayrshire', 'woothemes'),
					'South Lanarkshire' => __('South Lanarkshire', 'woothemes'),
					'Stirling' => __('Stirling', 'woothemes'),
					'West Dunbartonshire' => __('West Dunbartonshire', 'woothemes'),
					'West Lothian' => __('West Lothian', 'woothemes'),
					'Western Isles' => __('Western Isles', 'woothemes'),
				),
				'Wales' => array(
					'Blaenau Gwent' => __('Blaenau Gwent', 'woothemes'),
					'Bridgend' => __('Bridgend', 'woothemes'),
					'Caerphilly' => __('Caerphilly', 'woothemes'),
					'Cardiff' => __('Cardiff', 'woothemes'),
					'Carmarthenshire' => __('Carmarthenshire', 'woothemes'),
					'Ceredigion' => __('Ceredigion', 'woothemes'),
					'Conwy' => __('Conwy', 'woothemes'),
					'Denbighshire' => __('Denbighshire', 'woothemes'),
					'Flintshire' => __('Flintshire', 'woothemes'),
					'Gwynedd' => __('Gwynedd', 'woothemes'),
					'Isle of Anglesey' => __('Isle of Anglesey', 'woothemes'),
					'Merthyr Tydfil' => __('Merthyr Tydfil', 'woothemes'),
					'Monmouthshire' => __('Monmouthshire', 'woothemes'),
					'Neath Port Talbot' => __('Neath Port Talbot', 'woothemes'),
					'Newport' => __('Newport', 'woothemes'),
					'Pembrokeshire' => __('Pembrokeshire', 'woothemes'),
					'Powys' => __('Powys', 'woothemes'),
					'Rhondda Cynon Taff' => __('Rhondda Cynon Taff', 'woothemes'),
					'Swansea' => __('Swansea', 'woothemes'),
					'Torfaen' => __('Torfaen', 'woothemes'),
					'The Vale of Glamorgan' => __('The Vale of Glamorgan', 'woothemes'),
					'Wrexham' => __('Wrexham', 'woothemes')
				)
			),*/
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
		
		$return = ( in_array($this->get_base_country(), $this->get_european_union_countries()) ) ? __('VAT', 'woothemes') : __('Tax', 'woothemes');
		
		return apply_filters('woocommerce_countries_tax_or_vat', $return);
	}
	
	function inc_tax_or_vat( $rate = false ) {
		global $woocommerce;
		
		if ( $rate > 0 || $rate === 0 ) :
			$rate = trim(trim($rate, '0'), '.');
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

