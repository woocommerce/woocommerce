<?php
/**
 * Base Location country rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\BaseLocationCountryRuleProcessor;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_PublishBeforeTimeRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_BaseLocationCountryRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * Get the publish_before rule.
	 *
	 * @return object The rule.
	 */
	private function get_rule() {
		return json_decode(
			'{
                    "type": "base_location_country",
                    "operation": "=",
                    "value": "US"
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
	 * Tests that the processor returns false if not default country.
	 *
	 * @group fast
	 */
	public function test_spec_fails_if_wc_get_base_location_is_not_an_array() {
		update_option( 'woocommerce_default_country', '' );

		$processor = new BaseLocationCountryRuleProcessor();

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Tests that the processor returns false if default country and not completed onboarding.
	 *
	 * @group fast
	 */
	public function test_spec_fails_if_base_location_is_default_and_onboarding_is_not_completed() {
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( OnboardingProfile::DATA_OPTION, array() );

		$processor = new BaseLocationCountryRuleProcessor();

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Tests that the processor returns true if default country completed onboarding.
	 *
	 * @group fast
	 */
	public function test_spec_succeeds_if_base_location_is_default_and_onboarding_is_completed() {
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( OnboardingProfile::DATA_OPTION, array( 'completed' => true ) );

		$processor = new BaseLocationCountryRuleProcessor();

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Tests that the processor returns true if default country skipped onboarding.
	 *
	 * @group fast
	 */
	public function test_spec_succeeds_if_base_location_is_default_and_onboarding_is_skipped() {
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( OnboardingProfile::DATA_OPTION, array( 'skipped' => true ) );

		$processor = new BaseLocationCountryRuleProcessor();

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Tests that the processor returns true if country does not equal default country.
	 *
	 * @group fast
	 */
	public function test_spec_succeeds_if_base_location_is_not_default() {
		update_option( 'woocommerce_default_country', 'US:FL' );
		update_option( OnboardingProfile::DATA_OPTION, array() );

		$processor = new BaseLocationCountryRuleProcessor();

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Tests that the processor returns true if profiler option's `is_store_country_set` is true.
	 *
	 * @group fast
	 */
	public function test_spec_succeeds_if_base_location_is_default_and_is_store_country_set_is_true() {
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( OnboardingProfile::DATA_OPTION, array( 'is_store_country_set' => true ) );

		$processor = new BaseLocationCountryRuleProcessor();

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Tests that the processor returns true if country is default but address is updated.
	 *
	 * @group fast
	 */
	public function test_spec_succeeds_if_store_address_is_updated() {
		update_option( 'woocommerce_store_address', 'updated' );
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( OnboardingProfile::DATA_OPTION, array() );

		$processor = new BaseLocationCountryRuleProcessor();

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}
}
