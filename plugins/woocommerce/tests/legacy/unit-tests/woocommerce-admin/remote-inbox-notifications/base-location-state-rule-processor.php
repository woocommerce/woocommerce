<?php
/**
 * Base Location state rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\BaseLocationStateRuleProcessor;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_BaseLocationStateRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_BaseLocationStateRuleProcessor extends WC_Unit_Test_Case {

	/**
	 * Get the base_location_state rule.
	 *
	 * @return object The rule.
	 */
	private function get_rule() {
		return json_decode(
			'{
				"type": "base_location_state",
				"operation": "=",
				"value": "CA"
			}'
		);
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		update_option( 'woocommerce_store_address', '' );
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( OnboardingProfile::DATA_OPTION, array() );
	}

	/**
	 * Tests that the processor returns false if country is not set.
	 *
	 * @group fast
	 */
	public function test_spec_fails_if_country_is_not_set() {
		update_option( 'woocommerce_default_country', '' );

		$processor = new BaseLocationStateRuleProcessor();
		$result    = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Tests that the processor returns true if location is the same.
	 *
	 * @group fast
	 */
	public function test_spec_passes_if_location_is_the_same() {
		update_option( 'woocommerce_default_country', 'US:CA' );

		$processor = new BaseLocationStateRuleProcessor();
		$result    = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}
}
