<?php

namespace Automattic\WooCommerce\Tests\Admin\Marketing;

use Automattic\WooCommerce\Admin\Marketing\MarketingCampaign;
use Automattic\WooCommerce\Admin\Marketing\MarketingCampaignType;
use Automattic\WooCommerce\Admin\Marketing\Price;
use WC_Unit_Test_Case;

/**
 * Tests for the MarketingCampaign class.
 */
class MarketingCampaignTest extends WC_Unit_Test_Case {

	/**
	 * @testdox `get_id`, `get_type`, `get_title`, `get_manage_url`, `get_cost`, and `get_sales` return the class properties set by the constructor.
	 */
	public function test_get_methods_return_properties() {
		$test_campaign_type_1 = $this->createMock( MarketingCampaignType::class );

		$marketing_campaign = new MarketingCampaign( '1234', $test_campaign_type_1, 'Ad #1234', 'https://example.com/manage-campaigns', new Price( '1000', 'USD' ), new Price( '2500', 'USD' ) );

		$this->assertEquals( '1234', $marketing_campaign->get_id() );
		$this->assertEquals( $test_campaign_type_1, $marketing_campaign->get_type() );
		$this->assertEquals( 'Ad #1234', $marketing_campaign->get_title() );
		$this->assertEquals( 'https://example.com/manage-campaigns', $marketing_campaign->get_manage_url() );
		$this->assertNotNull( $marketing_campaign->get_cost() );
		$this->assertEquals( 'USD', $marketing_campaign->get_cost()->get_currency() );
		$this->assertEquals( '1000', $marketing_campaign->get_cost()->get_value() );
		$this->assertNotNull( $marketing_campaign->get_sales() );
		$this->assertEquals( 'USD', $marketing_campaign->get_sales()->get_currency() );
		$this->assertEquals( '2500', $marketing_campaign->get_sales()->get_value() );
	}

	/**
	 * @testdox `cost` and `sales` properties can be null.
	 */
	public function test_cost_and_sales_can_be_null() {
		$test_campaign_type_1 = $this->createMock( MarketingCampaignType::class );

		$marketing_campaign = new MarketingCampaign( '1234', $test_campaign_type_1, 'Ad #1234', 'https://example.com/manage-campaigns' );

		$this->assertNull( $marketing_campaign->get_cost() );
		$this->assertNull( $marketing_campaign->get_sales() );
	}
}
