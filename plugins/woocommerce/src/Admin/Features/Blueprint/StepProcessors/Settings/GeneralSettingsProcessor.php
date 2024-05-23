<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\Settings;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class GeneralSettingsProcessor implements StepProcessor {
	private $options_map = [
		// store address
		'address_line_1' => 'woocommerce_store_address',
		'address_line_2' => 'woocommerce_store_address_2',
		'city' => 'woocommerce_store_city',
		'country_state' => 'woocommerce_default_country',
		'postcode_zip' => 'woocommerce_store_postcode',
		// general options
		'selling_location' => 'woocommerce_allowed_countries',
		'selling_location_except' => 'woocommerce_all_except_countries',
		'selling_location_specific'=> 'woocommerce_specific_allowed_countries',
		'shipping_location' => 'woocommerce_ship_to_countries',
		'shipping_location_specific' => 'woocommerce_specific_ship_to_countries',
		'default_customer_location' => 'woocommerce_default_customer_address',
		'enable_taxes' => 'woocommerce_calc_taxes',
		'enable_coupons' => 'woocommerce_enable_coupons',
		'calculate_coupon_discounts_sequentially' => 'woocommerce_calc_discounts_sequentially',
		// currency options
		'currency' => 'woocommerce_currency',
		'currency_position' => 'woocommerce_currency_pos',
		"thousand_separator" => 'woocommerce_price_thousand_sep',
		"decimal_separator" => 'woocommerce_price_decimal_sep',
		"number_of_decimals" => 'woocommerce_price_num_decimals',

	];

	public function process($schema)
	{
		// flatten fields
		$fields = iterator_to_array( new RecursiveIteratorIterator( new RecursiveArrayIterator( $schema ) ), true );
		foreach ($fields as $field_name => $field) {
			$option_key = $this->options_map[$field_name] ?? null;
			if ($option_key !== null) {
				update_option($option_key, $field);
			}
		}
	}
}
