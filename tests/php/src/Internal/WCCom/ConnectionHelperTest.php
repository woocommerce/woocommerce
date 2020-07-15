<?php
/**
 * Tests for ConnectionHelper for WCCom
 *
 * @package WooCommerce|Tests|Internal|WCCom.
 */

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Internal\WCCom\ConnectionHelper;

/**
 * Class ConnectionHelperTest.
 *
 * @package Automattic\WooCommerce\Tests\Internal.
 */
class ConnectionHelperTest extends \WC_Unit_Test_Case {

	/**
	 * Test is_connected method based on option value.
	 */
	public function test_is_connected() {
		delete_option( 'woocommerce_helper_data' );
		$this->assertEquals( false, ConnectionHelper::is_connected() );

		update_option( 'woocommerce_helper_data', array( 'auth' => 'random token' ) );
		$this->assertEquals( true, ConnectionHelper::is_connected() );
	}
}
