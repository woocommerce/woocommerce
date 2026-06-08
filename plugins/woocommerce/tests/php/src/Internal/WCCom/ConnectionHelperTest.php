<?php

namespace Automattic\WooCommerce\Tests\Internal\WCCom;

use Automattic\WooCommerce\Internal\WCCom\ConnectionHelper;

/**
 * Class ConnectionHelperTest.
 */
class ConnectionHelperTest extends \WC_Unit_Test_Case {
	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'pre_option_woocommerce_helper_data' );
		delete_option( 'woocommerce_helper_data' );

		parent::tearDown();
	}

	/**
	 * Test is_connected method based on option value.
	 */
	public function test_is_connected() {
		delete_option( 'woocommerce_helper_data' );
		$this->assertEquals( false, ConnectionHelper::is_connected() );

		update_option( 'woocommerce_helper_data', array( 'auth' => 'non-empty-value' ) );
		$this->assertEquals( true, ConnectionHelper::is_connected() );
	}

	/**
	 * Test is_site_connected method based on option value.
	 *
	 * @dataProvider site_connected_provider
	 *
	 * @param mixed $helper_data WooCommerce.com helper data.
	 * @param bool  $expected Whether the site should be considered connected.
	 */
	public function test_is_site_connected( $helper_data, bool $expected ) {
		add_filter(
			'pre_option_woocommerce_helper_data',
			function () use ( $helper_data ) {
				return $helper_data;
			}
		);

		$this->assertSame( $expected, ConnectionHelper::is_site_connected() );
	}

	/**
	 * Data provider for test_is_site_connected.
	 *
	 * @return array[]
	 */
	public function site_connected_provider(): array {
		return array(
			'missing helper data' => array(
				'helper_data' => array(),
				'expected'    => false,
			),
			'malformed auth data' => array(
				'helper_data' => array( 'auth' => 'non-empty-value' ),
				'expected'    => false,
			),
			'missing token'       => array(
				'helper_data' => array(
					'auth' => array(
						'site_id' => 1,
					),
				),
				'expected'    => false,
			),
			'connected site'      => array(
				'helper_data' => array(
					'auth' => array(
						'access_token' => 'non-empty-value',
						'site_id'      => 1,
					),
				),
				'expected'    => true,
			),
		);
	}
}
