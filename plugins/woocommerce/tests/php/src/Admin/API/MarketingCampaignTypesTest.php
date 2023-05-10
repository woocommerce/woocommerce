<?php
/**
 * Test the API controller class that handles the marketing campaign types REST response.
 *
 * @package WooCommerce\Admin\Tests\Admin\API
 */

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Admin\Marketing\MarketingCampaignType;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannelInterface;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannels as MarketingChannelsService;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * MarketingCampaigns API controller test.
 *
 * @class MarketingCampaignTypesTest.
 */
class MarketingCampaignTypesTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/marketing/campaign-types';

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
	 * Tests that the marketing campaigns for all registered channels are aggregated and returned by the endpoint.
	 */
	public function test_returns_aggregated_marketing_campaigns() {
		// Create a mock marketing channel.
		$test_channel_1 = $this->createMock( MarketingChannelInterface::class );
		$test_channel_1->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );
		// Create a mock marketing campaign type.
		$test_campaign_type_1 = $this->createMock( MarketingCampaignType::class );
		$test_campaign_type_1->expects( $this->any() )->method( 'get_id' )->willReturn( 'test-campaign-type-1' );
		$test_campaign_type_1->expects( $this->any() )->method( 'get_channel' )->willReturn( $test_channel_1 );
		// Return the sample campaign type by the mock marketing channel.
		$test_channel_1->expects( $this->any() )->method( 'get_supported_campaign_types' )->willReturn( [ $test_campaign_type_1 ] );
		// Register the marketing channel.
		$this->marketing_channels_service->register( $test_channel_1 );

		// Create a second mock marketing channel.
		$test_channel_2 = $this->createMock( MarketingChannelInterface::class );
		$test_channel_2->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-2' );
		// Create a mock marketing campaign type for the second marketing channel.
		$test_campaign_type_2 = $this->createMock( MarketingCampaignType::class );
		$test_campaign_type_2->expects( $this->any() )->method( 'get_id' )->willReturn( 'test-campaign-type-2' );
		$test_campaign_type_2->expects( $this->any() )->method( 'get_channel' )->willReturn( $test_channel_2 );
		// Return the sample campaign by the second mock marketing channel.
		$test_channel_2->expects( $this->any() )->method( 'get_supported_campaign_types' )->willReturn( [ $test_campaign_type_2 ] );
		// Register the second marketing channel.
		$this->marketing_channels_service->register( $test_channel_2 );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertCount( 2, $data );
		$this->assertEquals(
			[
				'test-campaign-type-1',
				'test-campaign-type-2',
			],
			array_column( $data, 'id' )
		);
	}

}
