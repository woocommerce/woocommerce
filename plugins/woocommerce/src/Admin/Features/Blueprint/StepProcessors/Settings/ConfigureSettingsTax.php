<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\Settings;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;
use WC_Tax;

class ConfigureSettingsTax extends MapFieldsToOptions implements StepProcessor {
	protected array $options_map = array(
		"prices_entered_with_tax"=> "woocommerce_prices_include_tax",
		"calculate_tax_based_on"=> "woocommerce_tax_based_on",
		"shipping_tax_class"=> "woocommerce_shipping_tax_class",
		"round_at_subtotal_level"=> "woocommerce_tax_round_at_subtotal",
		"additional_tax_classes"=> "woocommerce_tax_classes",
		"display_prices_in_the_shop"=> "woocommerce_tax_display_shop",
		"display_prices_during_cart_and_checkout"=> "woocommerce_tax_display_cart",
		"price_display_suffix"=> "woocommerce_price_display_suffix",
		"display_tax_totals"=> "woocommerce_tax_total_display",
	);

	public function process($schema): StepProcessorResult {
		$result = parent::process($schema);

		if ( isset($schema->rates)) {
			foreach ($schema->rates as $rate ) {
				$this->add_rate($rate);
			}
		}

		return $result;
	}

	protected function add_rate($rate) {
		$tax_rate = array_intersect_key(
			(array) $rate,
			array(
				'tax_rate_country'  => 1,
				'tax_rate_state'    => 1,
				'tax_rate'          => 1,
				'tax_rate_name'     => 1,
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 1,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 1,
				'tax_rate_class'    => 1
			)
		);

		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );

		if ( isset( $rate->postcode ) ) {
			$postcode = array_map( 'wc_clean', explode(';', $rate->postcode) );
			$postcode = array_map( 'wc_normalize_postcode', $postcode );
			WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, $postcode );
		}
		if ( isset( $rate->city ) ) {
			$cities = explode(';', $rate->city);
			WC_Tax::_update_tax_rate_cities( $tax_rate_id, array_map( 'wc_clean', array_map( 'wp_unslash', $cities ) ) );
		}

		return $tax_rate_id;
	}
}
