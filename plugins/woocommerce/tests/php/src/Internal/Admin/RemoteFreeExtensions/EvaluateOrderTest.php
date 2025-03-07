<?php

namespace Automattic\WooCommerce\Tests\Internal\Admin\RemoteFreeExtensions;

use Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\EvaluateOrder;
use WC_Unit_Test_Case;

class EvaluateOrderTest extends WC_Unit_Test_Case {

	protected function get_extensions() {
		$extensions = file_get_contents( __DIR__ . '/fixtures/extensions.json' );
		return json_decode( $extensions );
	}

	public function test_it_returns_default_value_if_no_overrides_found() {
		$evaluator = new EvaluateOrder();
		// Use "WooPayments", which does not have any overrides.
		$extensions = $this->get_extensions();
		$plugins    = $evaluator->evaluate( array( $extensions[0] ) );
		$this->assertEquals( 1, $plugins[0]->order );
	}

	public function test_it_returns_9999_when_order_is_not_defined() {
		$evaluator  = new EvaluateOrder();
		$extensions = $this->get_extensions();
		// Use "WooCommerce Shipping". It does not have "order" defined.
		$plugins = $evaluator->evaluate( array( $extensions[1] ) );
		$this->assertEquals( 9999, $plugins[0]->order );
	}

	public function test_it_uses_default_value_when_overrides_do_not_match() {
		$evaluator = new EvaluateOrder();
		// Evaluate without any context. This should force the default value to be used.
		$plugins = $evaluator->evaluate( $this->get_extensions() );
		$this->assertEquals( 1, $plugins[2]->order );
	}

	public function test_it_uses_overriden_value_with_matching_context() {
		$extensions = $this->get_extensions();
		$evaluator  = new EvaluateOrder();
		$context    = array(
			'plugins' => $extensions,
			'vars'    => array(
				'platform' => 'desktop',
			),
		);
		$plugins    = $evaluator->evaluate( $extensions, $context );
		$this->assertEquals( 3, $plugins[2]->order );

		$context['vars']['platform'] = 'mobile';
		// get a new set of extensions since it was modified from the previous call.
		$plugins = $evaluator->evaluate( $this->get_extensions(), $context );
		$this->assertEquals( 2, $plugins[2]->order );
	}

	public function test_overrides_can_use_existing_rules() {
		$current_wc_country = get_option( 'woocommerce_default_country' );
		$evaluator          = new EvaluateOrder();

		$extensions = $this->get_extensions();
		// Set default country to "CA".
		update_option( 'woocommerce_default_country', 'CA' );
		// Use "Test". It has an override that uses "base_location_country" rule.
		$plugins = $evaluator->evaluate( array( $extensions[3] ) );

		// Evaluation should fail and fallback to the default value.
		$this->assertEquals( 1, $plugins[0]->order );

		// Set default country to "US".
		update_option( 'woocommerce_default_country', 'US' );

		$extensions = $this->get_extensions();
		$plugins    = $evaluator->evaluate( array( $extensions[3] ) );

		// Evaluation should pass and return the overriden value.
		$this->assertEquals( 2, $plugins[0]->order );

		// Reset the default country.
		update_option( 'woocommerce_default_country', $current_wc_country );
	}
}
