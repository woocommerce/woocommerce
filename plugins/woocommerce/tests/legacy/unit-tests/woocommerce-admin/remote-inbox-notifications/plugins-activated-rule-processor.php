<?php
/**
 * Plugins activated rule processor tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\PluginsActivatedRuleProcessor;
use Automattic\WooCommerce\Admin\PluginsProvider\PluginsProviderInterface;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_PluginsActivatedRuleProcessor
 */
class WC_Admin_Tests_RemoteInboxNotifications_PluginsActivatedRuleProcessor extends WC_Unit_Test_Case {
	/**
	 * Tests that the processor does not pass a plugins_activated rule with
	 * no plugins to verify.
	 *
	 * @group fast
	 */
	public function test_spec_does_not_pass_with_no_plugins_to_verify() {
		$mock_plugins_provider = new MockPluginsProvider( array() );
		$processor             = new PluginsActivatedRuleProcessor( $mock_plugins_provider );
		$rule                  = json_decode(
			'{
				"type": "plugins_activated",
				"plugins": [
				]
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Tests that the processor does not pass a plugins_activated rule with
	 * no active plugins.
	 *
	 * @group fast
	 */
	public function test_spec_does_not_pass_with_no_active_plugins() {
		$mock_plugins_provider = new MockPluginsProvider( array() );
		$processor             = new PluginsActivatedRuleProcessor( $mock_plugins_provider );
		$rule                  = json_decode(
			'{
				"type": "plugins_activated",
				"plugins": [
					"plugin-slug-1"
				]
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Tests that the processor does not pass a plugins_activated rule with
	 * no matching active plugins.
	 *
	 * @group fast
	 */
	public function test_spec_does_not_pass_with_no_matching_plugins() {
		$mock_plugins_provider = new MockPluginsProvider(
			array(
				'non-matching-slug',
			)
		);
		$processor             = new PluginsActivatedRuleProcessor( $mock_plugins_provider );
		$rule                  = json_decode(
			'{
				"type": "plugins_activated",
				"plugins": [
					"plugin-slug-1"
				]
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Tests that the processor does not pass a plugins_activated rule with
	 * only one matching plugin.
	 *
	 * @group fast
	 */
	public function test_spec_does_not_pass_with_only_one_matching_plugin() {
		$mock_plugins_provider = new MockPluginsProvider(
			array(
				'plugin-slug-1',
				'plugin-slug-2',
			)
		);
		$processor             = new PluginsActivatedRuleProcessor( $mock_plugins_provider );
		$rule                  = json_decode(
			'{
				"type": "plugins_activated",
				"plugins": [
					"plugin-slug-1",
					"plugin-slug-3"
				]
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( false, $result );
	}

	/**
	 * Tests that the processor passes a plugins_activated rule with both
	 * matching plugins.
	 *
	 * @group fast
	 */
	public function test_spec_does_pass_with_both_matching_plugins() {
		$mock_plugins_provider = new MockPluginsProvider(
			array(
				'plugin-slug-1',
				'plugin-slug-2',
			)
		);
		$processor             = new PluginsActivatedRuleProcessor( $mock_plugins_provider );
		$rule                  = json_decode(
			'{
				"type": "plugins_activated",
				"plugins": [
					"plugin-slug-1",
					"plugin-slug-2"
				]
			}'
		);

		$result = $processor->process( $rule, new stdClass() );

		$this->assertEquals( true, $result );
	}
}
