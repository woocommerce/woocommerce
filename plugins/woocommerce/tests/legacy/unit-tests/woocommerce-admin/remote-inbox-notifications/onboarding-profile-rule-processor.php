<?php
/**
 * Onboarding profile rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\OnboardingProfileRuleProcessor;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_OnboardingProfileRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_OnboardingProfileRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * Get the publish_before rule.
	 *
	 * @return object The rule.
	 */
	private function get_rule() {
		return json_decode(
			'{
				"type": "onboarding_profile",
				"index": "business_choice",
				"operation": "=",
				"value": "im_already_selling"
			}'
		);
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		update_option( OnboardingProfile::DATA_OPTION, array() );
	}

	/**
	 * Tests that the processor returns false if onboarding profile is empty.
	 *
	 * @group fast
	 */
	public function test_spec_fails_if_on_boarding_profile_is_empty() {
		$processor = new OnboardingProfileRuleProcessor();
		$result    = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Tests that the processor returns false if onboarding profile is not an array.
	 *
	 * @group fast
	 */
	public function test_spec_fails_if_on_boarding_profile_is_not_an_array() {
		update_option( OnboardingProfile::DATA_OPTION, 'not an array' );

		$processor = new OnboardingProfileRuleProcessor();
		$result    = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Tests that the processor returns true if the criteria is not met.
	 *
	 * @group fast
	 */
	public function test_spec_passes_if_criteria_is_met() {
		update_option( OnboardingProfile::DATA_OPTION, array( 'business_choice' => 'im_already_selling' ) );

		$processor = new OnboardingProfileRuleProcessor();
		$result    = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}
}
