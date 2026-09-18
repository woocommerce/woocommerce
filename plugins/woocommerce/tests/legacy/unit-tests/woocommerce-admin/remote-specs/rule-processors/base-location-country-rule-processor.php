<?php
/**
 * Base Location country rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteSpecs
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\BaseLocationCountryRuleProcessor;
use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;

/**
 * class WC_Admin_Tests_RemoteSpecs_RuleProcessors_BaseLocationCountryRuleProcessor
 */
class WC_Admin_Tests_RemoteSpecs_RuleProcessors_BaseLocationCountryRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * Store base country fixture.
	 *
	 * @var string
	 */
	private $default_country;

	/**
	 * Store address fixture.
	 *
	 * @var string
	 */
	private $store_address;

	/**
	 * Onboarding profile fixture.
	 *
	 * @var array
	 */
	private $onboarding_profile;

	/**
	 * Option filters installed for the current test.
	 *
	 * @var array
	 */
	private $option_filters = array();

	/**
	 * Set up option fixtures without triggering unrelated update hooks.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->default_country    = 'US:CA';
		$this->store_address      = '';
		$this->onboarding_profile = array();
		$this->option_filters     = array(
			'woocommerce_default_country'  => function () {
				return $this->default_country;
			},
			'woocommerce_store_address'    => function () {
				return $this->store_address;
			},
			OnboardingProfile::DATA_OPTION => function () {
				return $this->onboarding_profile;
			},
		);

		foreach ( $this->option_filters as $option => $filter ) {
			add_filter( 'pre_option_' . $option, $filter );
		}
	}

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
		foreach ( $this->option_filters as $option => $filter ) {
			remove_filter( 'pre_option_' . $option, $filter );
		}

		parent::tearDown();
	}

	/**
	 * Tests that the processor returns false if the base country is empty.
	 *
	 * @group fast
	 */
	public function test_spec_fails_if_base_country_is_empty() {
		$this->default_country = '';

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
		$this->onboarding_profile = array( 'completed' => true );

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
		$this->onboarding_profile = array( 'skipped' => true );

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
		$this->default_country = 'US:FL';

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
		$this->onboarding_profile = array( 'is_store_country_set' => true );

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
		$this->store_address = 'updated';

		$processor = new BaseLocationCountryRuleProcessor();

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}
}
