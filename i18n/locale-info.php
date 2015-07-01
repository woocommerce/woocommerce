<?php

return array(
	'BR' => array(
		'currency_code'  => 'BRL',
		'currency_pos'   => 'left',
		'thousand_sep'   => '.',
		'decimal_sep'    => ',',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array()
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
					'name'     => 'VAT',
					'shipping' => true
				)
			)
		)
	),
	'GB' => array(
		'currency_code'  => 'GBP',
		'currency_pos'   => 'left',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'' => array(
				array(
					'country'  => 'GB',
					'state'    => '',
					'rate'     => '20.0000',
					'name'     => 'VAT',
					'shipping' => true
				)
			)
		)
	),
	'NL' => array(
		'currency_code'  => 'EUR',
		'currency_pos'   => 'left',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates' => array(
			'' => array(
				array(
					'country'  => 'NL',
					'state'    => '',
					'rate'     => '21.0000',
					'name'     => 'VAT',
					'shipping' => true
				)
			)
		)
	),
	'US' => array(
		'currency_code'  => 'USD',
		'currency_pos'   => 'left',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 2,
		'weight_unit'    => 'lbs',
		'dimension_unit' => 'in',
		'tax_rates'      => array(
			'AL' => array(
				array(
					'country'  => 'US',
					'state'    => 'AL',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'AZ' => array(
				array(
					'country'  => 'US',
					'state'    => 'AZ',
					'rate'     => '5.6000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'AR' => array(
				array(
					'country'  => 'US',
					'state'    => 'AR',
					'rate'     => '6.5000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'CA' => array(
				array(
					'country'  => 'US',
					'state'    => 'CA',
					'rate'     => '7.5000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'CO' => array(
				array(
					'country'  => 'US',
					'state'    => 'CO',
					'rate'     => '2.9000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'CT' => array(
				array(
					'country'  => 'US',
					'state'    => 'CT',
					'rate'     => '6.3500',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'DC' => array(
				array(
					'country'  => 'US',
					'state'    => 'DC',
					'rate'     => '5.7500',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'FL' => array(
				array(
					'country'  => 'US',
					'state'    => 'FL',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'GA' => array(
				array(
					'country'  => 'US',
					'state'    => 'GA',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'GU' => array(
				array(
					'country'  => 'US',
					'state'    => 'GU',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'HI' => array(
				array(
					'country'  => 'US',
					'state'    => 'HI',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'ID' => array(
				array(
					'country'  => 'US',
					'state'    => 'ID',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'IL' => array(
				array(
					'country'  => 'US',
					'state'    => 'IL',
					'rate'     => '6.2500',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'IN' => array(
				array(
					'country'  => 'US',
					'state'    => 'IN',
					'rate'     => '7.0000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'IA' => array(
				array(
					'country'  => 'US',
					'state'    => 'IA',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'KS' => array(
				array(
					'country'  => 'US',
					'state'    => 'KS',
					'rate'     => '6.1500',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'KY' => array(
				array(
					'country'  => 'US',
					'state'    => 'KY',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'LA' => array(
				array(
					'country'  => 'US',
					'state'    => 'LA',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'ME' => array(
				array(
					'country'  => 'US',
					'state'    => 'ME',
					'rate'     => '5.5000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'MD' => array(
				array(
					'country'  => 'US',
					'state'    => 'MD',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'MA' => array(
				array(
					'country'  => 'US',
					'state'    => 'MA',
					'rate'     => '6.2500',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'MI' => array(
				array(
					'country'  => 'US',
					'state'    => 'MI',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'MN' => array(
				array(
					'country'  => 'US',
					'state'    => 'MN',
					'rate'     => '6.8750',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'MS' => array(
				array(
					'country'  => 'US',
					'state'    => 'MS',
					'rate'     => '7.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'MO' => array(
				array(
					'country'  => 'US',
					'state'    => 'MO',
					'rate'     => '4.225',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'NE' => array(
				array(
					'country'  => 'US',
					'state'    => 'NE',
					'rate'     => '5.5000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'NV' => array(
				array(
					'country'  => 'US',
					'state'    => 'NV',
					'rate'     => '6.8500',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'NJ' => array(
				array(
					'country'  => 'US',
					'state'    => 'NJ',
					'rate'     => '7.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'NM' => array(
				array(
					'country'  => 'US',
					'state'    => 'NM',
					'rate'     => '5.1250',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'NY' => array(
				array(
					'country'  => 'US',
					'state'    => 'NY',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'NC' => array(
				array(
					'country'  => 'US',
					'state'    => 'NC',
					'rate'     => '4.7500',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'ND' => array(
				array(
					'country'  => 'US',
					'state'    => 'ND',
					'rate'     => '5.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'OH' => array(
				array(
					'country'  => 'US',
					'state'    => 'OH',
					'rate'     => '5.7500',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'OK' => array(
				array(
					'country'  => 'US',
					'state'    => 'OK',
					'rate'     => '4.5000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'PA' => array(
				array(
					'country'  => 'US',
					'state'    => 'PA',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'PR' => array(
				array(
					'country'  => 'US',
					'state'    => 'PR',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'RI' => array(
				array(
					'country'  => 'US',
					'state'    => 'RI',
					'rate'     => '7.0000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'SC' => array(
				array(
					'country'  => 'US',
					'state'    => 'SC',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'SD' => array(
				array(
					'country'  => 'US',
					'state'    => 'SD',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'TN' => array(
				array(
					'country'  => 'US',
					'state'    => 'TN',
					'rate'     => '7.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'TX' => array(
				array(
					'country'  => 'US',
					'state'    => 'TX',
					'rate'     => '6.2500',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'UT' => array(
				array(
					'country'  => 'US',
					'state'    => 'UT',
					'rate'     => '5.9500',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'VT' => array(
				array(
					'country'  => 'US',
					'state'    => 'VT',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'VA' => array(
				array(
					'country'  => 'US',
					'state'    => 'VA',
					'rate'     => '5.3000',
					'name'     => 'State Tax',
					'shipping' => false
				)
			),
			'WA' => array(
				array(
					'country'  => 'US',
					'state'    => 'WA',
					'rate'     => '6.5000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'WV' => array(
				array(
					'country'  => 'US',
					'state'    => 'WV',
					'rate'     => '6.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'WI' => array(
				array(
					'country'  => 'US',
					'state'    => 'WI',
					'rate'     => '5.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			),
			'WY' => array(
				array(
					'country'  => 'US',
					'state'    => 'WY',
					'rate'     => '4.0000',
					'name'     => 'State Tax',
					'shipping' => true
				)
			)
		)
	)
);
