<?php
/**
 * MockGetRuleProcessor.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\PublishAfterTimeRuleProcessor;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\FailRuleProcessor;

/**
 * MockGetRuleProcessor.
 */
class MockGetRuleProcessor {
	/**
	 * Get the processor for the specified rule type.
	 *
	 * @param string $rule_type The rule type.
	 *
	 * @return object The matching processor for the specified rule type, or a FailRuleProcessor if no matching processor is found.
	 */
	public static function get_processor( $rule_type ) {
		if ( 'publish_after_time' === $rule_type ) {
			return new PublishAfterTimeRuleProcessor(
				new MockDateTimeProvider( new \DateTime( '2020-04-24 10:00:00' ) )
			);
		}

		return new FailRuleProcessor();
	}
}

