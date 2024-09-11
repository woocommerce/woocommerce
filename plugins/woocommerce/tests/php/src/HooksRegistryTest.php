<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests;

use Automattic\WooCommerce\HooksRegistry;

/**
 * Test HooksRegistry class.
 */
class HooksRegistryTest extends \WC_Unit_Test_Case {

	/**
	 * @testDox Make sure hooks are loaded as expected
	 */
	public function test_load_hooks() {
		$all_request_action = array( 'upgrader_process_complete', array( 'WC_Helper_Updater', 'upgrader_process_complete' ) );
		$all_request_filter = array( 'rest_api_init', array( 'WC_Helper_Subscriptions_API', 'register_rest_routes' ) );
		HooksRegistry::unload_hooks();
		$this->assertFalse( has_filter( ...$all_request_action ) );
		$this->assertFalse( has_filter( ...$all_request_filter ) );

		HooksRegistry::load_hooks();
		$this->assertEquals( 10, has_filter( ...$all_request_action ) );
		$this->assertEquals( 10, has_filter( ...$all_request_filter ) );
	}
}
