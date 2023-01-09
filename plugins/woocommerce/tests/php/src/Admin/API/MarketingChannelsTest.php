<?php
/**
 * Test the API controller class that handles the marketing channels REST response.
 *
 * @package WooCommerce\Admin\Tests\Admin\API
 */

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Admin\Marketing\MarketingChannelInterface;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannels as MarketingChannelsService;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * MarketingChannels API controller test.
 *
 * @class MarketingChannelsTest.
 */
class MarketingChannelsTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/marketing/channels';

	/**
	 * @var MarketingChannelsService
	 */
	private $marketing_channels_service;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		// Register an administrator user and log in.
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );

		$this->marketing_channels_service = wc_get_container()->get( MarketingChannelsService::class );
	}

	/**
	 * Test teardown.
	 */
	public function tearDown(): void {
		$this->marketing_channels_service->unregister_all();
		parent::tearDown();
	}

	/**
	 * Tests that the registered marketing channels are returned by the endpoint.
	 */
	public function test_returns_registered_marketing_channels() {
		// Register marketing channel.
		$test_channel_1 = $this->createMock( MarketingChannelInterface::class );
		$test_channel_1->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );
		$test_channel_1->expects( $this->any() )->method( 'get_name' )->willReturn( 'Test Channel One' );
		$this->marketing_channels_service->register( $test_channel_1 );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertCount( 1, $data );
		$this->assertEquals( 'test-channel-1', $data[0]['slug'] );
		$this->assertEquals( 'Test Channel One', $data[0]['name'] );
	}

}
