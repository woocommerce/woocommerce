<?php


namespace Automattic\WooCommerce\Tests\Internal\Admin\RemoteFreeExtensions;

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\EvaluateOverrides;
use WC_Unit_Test_Case;

/**
 * Class EvaluateOverridesTest
 */
class EvaluateOverridesTest extends WC_Unit_Test_Case {

	/**
	 * Get the extensions.
	 * @return mixed
	 */
	protected function get_extensions() {
		$extensions = file_get_contents( __DIR__ . '/fixtures/extensions.json' );
		return json_decode( $extensions );
	}

	/**
	 * Test that the evaluator can evaluate the extensions.
	 * @return void
	 */
	public function test_it_overrides_value() {
		$evaluator = new EvaluateOverrides();

		$extensions = $this->get_extensions();
		// Use "PayPal". It has default order value of 1 and overrides rule that changes it to 3 with plugins context
		// This should not change the order as we're not passing a context.
		$result = $evaluator->evaluate( array( $extensions[2] ) );

		$this->assertEquals( 1, $result[0]->order );

		$result = $evaluator->evaluate(
			array( $extensions[2] ),
			array(
				'plugins' => $extensions,
			)
		);

		$this->assertEquals( 3, $result[0]->order );
	}

	/**
	 * Test that the evaluator can evaluate the extensions.
	 * @return void
	 */
	public function test_overrides_can_use_existing_rules() {
		$current_wc_country = get_option( 'woocommerce_default_country' );
		$evaluator          = new EvaluateOverrides();

		$extensions = $this->get_extensions();
		// Set default country to "CA".
		update_option( 'woocommerce_default_country', 'CA' );
		// Use "Test". It has an override that uses "base_location_country" rule.
		$result = $evaluator->evaluate( array( $extensions[3] ) );

		// Evaluation should fail and fallback to the default value.
		$this->assertEquals( 1, $result[0]->order );

		// Set default country to "US".
		update_option( 'woocommerce_default_country', 'US' );

		$extensions = $this->get_extensions();
		$result     = $evaluator->evaluate( array( $extensions[3] ) );

		// Evaluation should pass and return the overriden value.
		$this->assertEquals( 2, $result[0]->order );

		// Reset the default country.
		update_option( 'woocommerce_default_country', $current_wc_country );
	}
}
