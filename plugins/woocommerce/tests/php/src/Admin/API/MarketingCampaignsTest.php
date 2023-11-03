<?php
/**
 * Test the API controller class that handles the marketing campaigns REST response.
 *
 * @package WooCommerce\Admin\Tests\Admin\API
 */

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Admin\Marketing\MarketingCampaign;
use Automattic\WooCommerce\Admin\Marketing\MarketingCampaignType;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannelInterface;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannels as MarketingChannelsService;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * MarketingCampaigns API controller test.
 *
 * @class MarketingCampaignsTest.
 */
class MarketingCampaignsTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/marketing/campaigns';

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
		$test_campaign_type_1->expects( $this->any() )->method( 'get_channel' )->willReturn( $test_channel_1 );
		// Create a mock marketing campaign.
		$test_campaign_1 = $this->createMock( MarketingCampaign::class );
		$test_campaign_1->expects( $this->any() )->method( 'get_id' )->willReturn( 'test-campaign-1' );
		$test_campaign_1->expects( $this->any() )->method( 'get_type' )->willReturn( $test_campaign_type_1 );
		// Return the sample campaign by the mock marketing channel.
		$test_channel_1->expects( $this->any() )->method( 'get_campaigns' )->willReturn( [ $test_campaign_1 ] );
		// Register the marketing channel.
		$this->marketing_channels_service->register( $test_channel_1 );

		// Create a second mock marketing channel.
		$test_channel_2 = $this->createMock( MarketingChannelInterface::class );
		$test_channel_2->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-2' );
		// Create a mock marketing campaign type for the second marketing channel.
		$test_campaign_type_2 = $this->createMock( MarketingCampaignType::class );
		$test_campaign_type_2->expects( $this->any() )->method( 'get_channel' )->willReturn( $test_channel_2 );
		// Create a mock marketing campaign for the second marketing channel.
		$test_campaign_2 = $this->createMock( MarketingCampaign::class );
		$test_campaign_2->expects( $this->any() )->method( 'get_id' )->willReturn( 'test-campaign-2' );
		$test_campaign_2->expects( $this->any() )->method( 'get_type' )->willReturn( $test_campaign_type_2 );
		// Return the sample campaign by the second mock marketing channel.
		$test_channel_2->expects( $this->any() )->method( 'get_campaigns' )->willReturn( [ $test_campaign_2 ] );
		// Register the second marketing channel.
		$this->marketing_channels_service->register( $test_channel_2 );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertCount( 2, $data );
		$this->assertEquals(
			[
				'test-campaign-1',
				'test-campaign-2',
			],
			array_column( $data, 'id' )
		);
		$this->assertEquals(
			[
				'test-channel-1',
				'test-channel-2',
			],
			array_column( $data, 'channel' )
		);
	}

	/**
	 * Tests that the marketing campaigns are paginated and then returned by the endpoint.
	 */
	public function test_paginates_marketing_campaigns() {
		// Create a mock marketing channel.
		$test_channel_1 = $this->createMock( MarketingChannelInterface::class );
		$test_channel_1->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );
		// Return mock campaigns by the mock marketing channel.
		$test_channel_1->expects( $this->any() )->method( 'get_campaigns' )->willReturn(
			[
				$this->createMock( MarketingCampaign::class ),
				$this->createMock( MarketingCampaign::class ),
				$this->createMock( MarketingCampaign::class ),
				$this->createMock( MarketingCampaign::class ),
				$this->createMock( MarketingCampaign::class ),
			]
		);
		// Register the marketing channel.
		$this->marketing_channels_service->register( $test_channel_1 );

		$endpoint = self::ENDPOINT;
		$request  = new WP_REST_Request( 'GET', $endpoint );
		$request->set_query_params(
			[
				'page'     => '1',
				'per_page' => '2',
			]
		);
		$response = $this->server->dispatch( $request );
		$headers  = $response->get_headers();

		$this->assertCount( 2, $response->get_data() );

		$this->assertArrayHasKey( 'Link', $headers );
		$this->assertArrayHasKey( 'X-WP-Total', $headers );
		$this->assertArrayHasKey( 'X-WP-TotalPages', $headers );
		$this->assertEquals( 5, $headers['X-WP-Total'] );
		$this->assertEquals( 3, $headers['X-WP-TotalPages'] );
	}

}
