<?php
return array(
	'AU' => array(
		'currency_code'  => 'AUD',
		'currency_pos'   => 'left',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'AU',
					'state'    => '',
					'rate'     => '10.0000',
					'name'     => 'GST',
					'shipping' => true,
				),
			),
		),
	),
	'BD' => array(
		'currency_code'  => 'BDT',
		'currency_pos'   => 'left',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'in',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'BD',
					'state'    => '',
					'rate'     => '15.0000',
					'name'     => 'VAT',
					'shipping' => true,
				),
			),
		),
	),
	'BE' => array(
		'currency_code'  => 'EUR',
		'currency_pos'   => 'left',
		'thousand_sep'   => ' ',
		'decimal_sep'    => ',',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
			  array(
					'country'  => 'BE',
					'state'    => '',
					'rate'     => '21.0000',
					'name'     => 'BTW',
					'shipping' => true,
				),
			),
		),
	),
	'BR' => array(
		'currency_code'  => 'BRL',
		'currency_pos'   => 'left',
		'thousand_sep'   => '.',
		'decimal_sep'    => ',',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(),
	),
	'CA' => array(
		'currency_code'  => 'CAD',
		'currency_pos'   => 'left',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'BC' => array(
				array(
					'country'  => 'CA',
					'state'    => 'BC',
					'rate'     => '7.0000',
					'name'     => _x( 'PST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => false,
					'priority' => 2,
				),
			),
			'SK' => array(
				array(
					'country'  => 'CA',
					'state'    => 'SK',
					'rate'     => '5.0000',
					'name'     => _x( 'PST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => false,
					'priority' => 2,
				),
			),
			'MB' => array(
				array(
					'country'  => 'CA',
					'state'    => 'MB',
					'rate'     => '8.0000',
					'name'     => _x( 'PST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => false,
					'priority' => 2,
				),
			),
			'QC' => array(
				array(
					'country'  => 'CA',
					'state'    => 'QC',
					'rate'     => '9.975',
					'name'     => _x( 'QST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => false,
					'priority' => 2,
				),
			),
			'*' => array(
				array(
					'country'  => 'CA',
					'state'    => 'ON',
					'rate'     => '13.0000',
					'name'     => _x( 'HST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
				array(
					'country'  => 'CA',
					'state'    => 'NL',
					'rate'     => '13.0000',
					'name'     => _x( 'HST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
				array(
					'country'  => 'CA',
					'state'    => 'NB',
					'rate'     => '13.0000',
					'name'     => _x( 'HST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
				array(
					'country'  => 'CA',
					'state'    => 'PE',
					'rate'     => '14.0000',
					'name'     => _x( 'HST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
				array(
					'country'  => 'CA',
					'state'    => 'NS',
					'rate'     => '15.0000',
					'name'     => _x( 'HST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
				array(
					'country'  => 'CA',
					'state'    => 'AB',
					'rate'     => '5.0000',
					'name'     => _x( 'GST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
				array(
					'country'  => 'CA',
					'state'    => 'BC',
					'rate'     => '5.0000',
					'name'     => _x( 'GST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
				array(
					'country'  => 'CA',
					'state'    => 'NT',
					'rate'     => '5.0000',
					'name'     => _x( 'GST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
				array(
					'country'  => 'CA',
					'state'    => 'NU',
					'rate'     => '5.0000',
					'name'     => _x( 'GST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
				array(
					'country'  => 'CA',
					'state'    => 'YT',
					'rate'     => '5.0000',
					'name'     => _x( 'GST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
				array(
					'country'  => 'CA',
					'state'    => 'SK',
					'rate'     => '5.0000',
					'name'     => _x( 'GST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
				array(
					'country'  => 'CA',
					'state'    => 'MB',
					'rate'     => '5.0000',
					'name'     => _x( 'GST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
				array(
					'country'  => 'CA',
					'state'    => 'QC',
					'rate'     => '5.0000',
					'name'     => _x( 'GST', 'Canadian Tax Rates', 'woocommerce' ),
					'shipping' => true,
				),
			),
		),
	),
	'DE' => array(
		'currency_code'  => 'EUR',
		'currency_pos'   => 'left',
		'thousand_sep'   => '.',
		'decimal_sep'    => ',',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'DE',
					'state'    => '',
					'rate'     => '19.0000',
					'name'     => 'Mwst.',
					'shipping' => true,
				),
			),
		),
	),
	'ES' => array(
		'currency_code'  => 'EUR',
		'currency_pos'   => 'right',
		'thousand_sep'   => '.',
		'decimal_sep'    => ',',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'ES',
					'state'    => '',
					'rate'     => '21.0000',
					'name'     => 'VAT',
					'shipping' => true,
				),
			),
		),
	),
	'FI' => array(
		'currency_code'  => 'EUR',
		'currency_pos'   => 'right_space',
		'thousand_sep'   => ' ',
		'decimal_sep'    => ',',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'FI',
					'state'    => '',
					'rate'     => '24.0000',
					'name'     => 'ALV',
					'shipping' => true,
				),
			),
		),
	),
	'FR' => array(
		'currency_code'  => 'EUR',
		'currency_pos'   => 'right',
		'thousand_sep'   => ' ',
		'decimal_sep'    => ',',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'FR',
					'state'    => '',
					'rate'     => '20.0000',
					'name'     => 'TVA',
					'shipping' => true,
				),
			),
		),
	),
	'GB' => array(
		'currency_code'  => 'GBP',
		'currency_pos'	=> 'left',
		'thousand_sep'	=> ',',
		'decimal_sep'	 => '.',
		'num_decimals'	=> 2,
		'weight_unit'	 => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'		=> array(
			'' => array(
				array(
					'country'  => 'GB',
					'state'	 => '',
					'rate'	  => '20.0000',
					'name'	  => 'VAT',
					'shipping' => true,
				),
			),
		),
	),
	'HU' => array(
		'currency_code'  => 'HUF',
		'currency_pos'   => 'right_space',
		'thousand_sep'   => ' ',
		'decimal_sep'    => ',',
		'num_decimals'   => 0,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'HU',
					'state'    => '',
					'rate'     => '27.0000',
					'name'     => 'ÁFA',
					'shipping' => true,
				),
			),
		),
	),
	'IT' => array(
		'currency_code'  => 'EUR',
		'currency_pos'   => 'right',
		'thousand_sep'   => '.',
		'decimal_sep'    => ',',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'IT',
					'state'    => '',
					'rate'     => '22.0000',
					'name'     => 'IVA',
					'shipping' => true,
				),
			),
		),
	),
	'JP' => array(
		'currency_code'  => 'JPY',
		'currency_pos'   => 'left',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 0,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'JP',
					'state'    => '',
					'rate'     => '8.0000',
					'name'     => __( 'Consumption tax', 'woocommerce' ),
					'shipping' => true,
				),
			),
		),
	),
	'NL' => array(
		'currency_code'  => 'EUR',
		'currency_pos'   => 'left',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'NL',
					'state'    => '',
					'rate'     => '21.0000',
					'name'     => 'VAT',
					'shipping' => true,
				),
			),
		),
	),
	'NO' => array(
		'currency_code'  => 'Kr',
		'currency_pos'   => 'left_space',
		'thousand_sep'   => '.',
		'decimal_sep'    => ',',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'NO',
					'state'    => '',
					'rate'     => '25.0000',
					'name'     => 'MVA',
					'shipping' => true,
				),
			),
		),
	),
	'NP' => array(
		'currency_code'  => 'NPR',
		'currency_pos'   => 'left_space',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'NP',
					'state'    => '',
					'rate'     => '13.0000',
					'name'     => 'VAT',
					'shipping' => true,
				),
			),
		),
	),
	'PL' => array(
		'currency_code'  => 'PLN',
		'currency_pos'   => 'right_space',
		'thousand_sep'   => ' ',
		'decimal_sep'    => ',',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
			 	array(
					'country'  => 'PL',
					'state'    => '',
					'rate'     => '23.0000',
					'name'     => 'VAT',
					'shipping' => true,
				),
			),
		),
	),
	'RO' => array(
		'currency_code'  => 'RON',
		'currency_pos'   => 'right_space',
		'thousand_sep'   => '.',
		'decimal_sep'    => ',',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'RO',
					'state'    => '',
					'rate'     => '19.0000',
					'name'     => 'TVA',
					'shipping' => true,
				),
			),
		),
	),
	'TH' => array(
		'currency_code'  => 'THB',
		'currency_pos'   => 'left',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'TH',
					'state'    => '',
					'rate'     => '7.0000',
					'name'     => 'VAT',
					'shipping' => true,
				),
			),
		),
	),
	'TR' => array(
		'currency_code'  => 'TRY',
		'currency_pos'   => 'left_space',
		'thousand_sep'   => '.',
		'decimal_sep'    => ',',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'TR',
					'state'    => '',
					'rate'     => '18.0000',
					'name'     => 'KDV',
					'shipping' => true,
				),
			),
		),
	),
	'US' => array(
		'currency_code'  => 'USD',
		'currency_pos'	=> 'left',
		'thousand_sep'	=> ',',
		'decimal_sep'	 => '.',
		'num_decimals'	=> 2,
		'weight_unit'	 => 'lbs',
		'dimension_unit' => 'in',
		'tax_rates'		=> array(
			'AL' => array(
				array(
					'country'  => 'US',
					'state'    => 'AL',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'AZ' => array(
				array(
					'country'  => 'US',
					'state'    => 'AZ',
					'rate'     => '5.6000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'AR' => array(
				array(
					'country'  => 'US',
					'state'    => 'AR',
					'rate'     => '6.5000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'CA' => array(
				array(
					'country'  => 'US',
					'state'    => 'CA',
					'rate'     => '7.5000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'CO' => array(
				array(
					'country'  => 'US',
					'state'    => 'CO',
					'rate'     => '2.9000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'CT' => array(
				array(
					'country'  => 'US',
					'state'    => 'CT',
					'rate'     => '6.3500',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'DC' => array(
				array(
					'country'  => 'US',
					'state'    => 'DC',
					'rate'     => '5.7500',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'FL' => array(
				array(
					'country'  => 'US',
					'state'    => 'FL',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'GA' => array(
				array(
					'country'  => 'US',
					'state'    => 'GA',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'GU' => array(
				array(
					'country'  => 'US',
					'state'    => 'GU',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'HI' => array(
				array(
					'country'  => 'US',
					'state'    => 'HI',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'ID' => array(
				array(
					'country'  => 'US',
					'state'    => 'ID',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'IL' => array(
				array(
					'country'  => 'US',
					'state'    => 'IL',
					'rate'     => '6.2500',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'IN' => array(
				array(
					'country'  => 'US',
					'state'    => 'IN',
					'rate'     => '7.0000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'IA' => array(
				array(
					'country'  => 'US',
					'state'    => 'IA',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'KS' => array(
				array(
					'country'  => 'US',
					'state'    => 'KS',
					'rate'     => '6.1500',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'KY' => array(
				array(
					'country'  => 'US',
					'state'    => 'KY',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'LA' => array(
				array(
					'country'  => 'US',
					'state'    => 'LA',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'ME' => array(
				array(
					'country'  => 'US',
					'state'    => 'ME',
					'rate'     => '5.5000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'MD' => array(
				array(
					'country'  => 'US',
					'state'    => 'MD',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'MA' => array(
				array(
					'country'  => 'US',
					'state'    => 'MA',
					'rate'     => '6.2500',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'MI' => array(
				array(
					'country'  => 'US',
					'state'    => 'MI',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'MN' => array(
				array(
					'country'  => 'US',
					'state'    => 'MN',
					'rate'     => '6.8750',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'MS' => array(
				array(
					'country'  => 'US',
					'state'    => 'MS',
					'rate'     => '7.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'MO' => array(
				array(
					'country'  => 'US',
					'state'    => 'MO',
					'rate'     => '4.225',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'NE' => array(
				array(
					'country'  => 'US',
					'state'    => 'NE',
					'rate'     => '5.5000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'NV' => array(
				array(
					'country'  => 'US',
					'state'    => 'NV',
					'rate'     => '6.8500',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'NJ' => array(
				array(
					'country'  => 'US',
					'state'    => 'NJ',
					'rate'     => '7.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'NM' => array(
				array(
					'country'  => 'US',
					'state'    => 'NM',
					'rate'     => '5.1250',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'NY' => array(
				array(
					'country'  => 'US',
					'state'    => 'NY',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'NC' => array(
				array(
					'country'  => 'US',
					'state'    => 'NC',
					'rate'     => '4.7500',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'ND' => array(
				array(
					'country'  => 'US',
					'state'    => 'ND',
					'rate'     => '5.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'OH' => array(
				array(
					'country'  => 'US',
					'state'    => 'OH',
					'rate'     => '5.7500',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'OK' => array(
				array(
					'country'  => 'US',
					'state'    => 'OK',
					'rate'     => '4.5000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'PA' => array(
				array(
					'country'  => 'US',
					'state'    => 'PA',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'PR' => array(
				array(
					'country'  => 'US',
					'state'    => 'PR',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'RI' => array(
				array(
					'country'  => 'US',
					'state'    => 'RI',
					'rate'     => '7.0000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'SC' => array(
				array(
					'country'  => 'US',
					'state'    => 'SC',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'SD' => array(
				array(
					'country'  => 'US',
					'state'    => 'SD',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'TN' => array(
				array(
					'country'  => 'US',
					'state'    => 'TN',
					'rate'     => '7.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'TX' => array(
				array(
					'country'  => 'US',
					'state'    => 'TX',
					'rate'     => '6.2500',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'UT' => array(
				array(
					'country'  => 'US',
					'state'    => 'UT',
					'rate'     => '5.9500',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'VT' => array(
				array(
					'country'  => 'US',
					'state'    => 'VT',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'VA' => array(
				array(
					'country'  => 'US',
					'state'    => 'VA',
					'rate'     => '5.3000',
					'name'     => 'State Tax',
					'shipping' => false,
				),
			),
			'WA' => array(
				array(
					'country'  => 'US',
					'state'    => 'WA',
					'rate'     => '6.5000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'WV' => array(
				array(
					'country'  => 'US',
					'state'    => 'WV',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'WI' => array(
				array(
					'country'  => 'US',
					'state'    => 'WI',
					'rate'     => '5.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
			'WY' => array(
				array(
					'country'  => 'US',
					'state'    => 'WY',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => true,
				),
			),
		),
	),
	'ZA' => array(
		'currency_code'  => 'ZAR',
		'currency_pos'   => 'left',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'ZA',
					'state'    => '',
					'rate'     => '14.0000',
					'name'     => 'VAT',
					'shipping' => true,
				),
			),
		),
	),
);
