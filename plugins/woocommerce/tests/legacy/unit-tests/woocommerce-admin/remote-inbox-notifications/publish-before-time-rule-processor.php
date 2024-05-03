<?php
/**
 * Publish before time rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\PublishBeforeTimeRuleProcessor;
use Automattic\WooCommerce\Admin\DateTimeProvider\DateTimeProviderInterface;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_PublishBeforeTimeRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_PublishBeforeTimeRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * Get the publish_before rule.
	 *
	 * @return object The rule.
	 */
	private function get_rule() {
		return json_decode(
			'{
				"type": "publish_before_time",
				"publish_before": "2020-04-22 12:00:00"
			}'
		);
	}

	/**
	 * Tests that the processor passes a publish_before_time rule with a
	 * publish_before time in the future.
	 *
	 * @group fast
	 */
	public function test_spec_passes_for_time_in_the_future() {
		$mock_date_time_provider = new MockDateTimeProvider(
			new \DateTime( '2020-04-22 08:00:00' )
		);
		$processor               = new PublishBeforeTimeRuleProcessor( $mock_date_time_provider );

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Tests that the processor passes a publish_before_time rule with a
	 * publish_before time right now.
	 *
	 * @group fast
	 */
	public function test_spec_passes_for_time_now() {
		$mock_date_time_provider = new MockDateTimeProvider(
			new \DateTime( '2020-04-22 12:00:00' )
		);
		$processor               = new PublishBeforeTimeRuleProcessor( $mock_date_time_provider );

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Tests that the processor does not pass a publish_before_time rule with a
	 * publish_before time in the past.
	 *
	 * @group fast
	 */
	public function test_spec_does_not_pass_for_time_in_the_future() {
		$mock_date_time_provider = new MockDateTimeProvider(
			new \DateTime( '2020-04-22 14:00:00' )
		);
		$processor               = new PublishBeforeTimeRuleProcessor( $mock_date_time_provider );

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( false, $result );
	}
}
