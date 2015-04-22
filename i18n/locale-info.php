<?php

return array(
	'GB' => array(
		'currency_code'  => 'GBP',
		'currency_pos'   => 'left',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 2,
		'weight_unit'    => 'kg',
		'dimension_unit' => 'cm',
		'tax_rates'      => array(
			'GB' => array(
				'' => array(
					'rate'     => '20.0000',
					'name'     => 'VAT'
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
		'dimension_unit' => 'cm'
	),
	'US' => array(
		'currency_code'  => 'USD',
		'currency_pos'   => 'left',
		'thousand_sep'   => ',',
		'decimal_sep'    => '.',
		'num_decimals'   => 2,
		'weight_unit'    => 'lbs',
		'dimension_unit' => 'in'
	)
);