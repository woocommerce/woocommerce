<?php
/**
 * Publish after time rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\PublishAfterTimeRuleProcessor;
use Automattic\WooCommerce\Admin\DateTimeProvider\DateTimeProviderInterface;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_PublishAfterTimeRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_PublishAfterTimeRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * Get the publish_after rule.
	 *
	 * @return object The rule.
	 */
	private function get_rule() {
		return json_decode(
			'{
				"type": "publish_after_time",
				"publish_after": "2020-04-22 12:00:00"
			}'
		);
	}

	/**
	 * Tests that the processor passes a publish_after_time rule with a
	 * publish_after time in the past.
	 *
	 * @group fast
	 */
	public function test_spec_passes_for_time_in_the_past() {
		$mock_date_time_provider = new MockDateTimeProvider(
			new \DateTime( '2020-04-22 14:00:00' )
		);
		$processor               = new PublishAfterTimeRuleProcessor( $mock_date_time_provider );

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Tests that the processor passes a publish_after_time rule with a
	 * publish_after time right now.
	 *
	 * @group fast
	 */
	public function test_spec_passes_for_time_now() {
		$mock_date_time_provider = new MockDateTimeProvider(
			new \DateTime( '2020-04-22 12:00:00' )
		);
		$processor               = new PublishAfterTimeRuleProcessor( $mock_date_time_provider );

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( true, $result );
	}

	/**
	 * Tests that the processor does not pass a publish_after_time rule with a
	 * publish_after time in the future.
	 *
	 * @group fast
	 */
	public function test_spec_does_not_pass_for_time_in_future() {
		$mock_date_time_provider = new MockDateTimeProvider(
			new \DateTime( '2020-04-22 09:00:00' )
		);
		$processor               = new PublishAfterTimeRuleProcessor( $mock_date_time_provider );

		$result = $processor->process( $this->get_rule(), new stdClass() );

		$this->assertEquals( false, $result );
	}
}
