<?php

namespace Automattic\WooCommerce\Tests\Admin\Marketing;

use Automattic\WooCommerce\Admin\Marketing\MarketingCampaign;
use WC_Unit_Test_Case;

/**
 * Tests for the MarketingCampaign class.
 */
class MarketingCampaignTest extends WC_Unit_Test_Case {

	/**
	 * @testdox `get_id`, `get_title`, `get_manage_url`, and `get_cost` return the class properties set by the constructor.
	 */
	public function test_get_methods_return_properties() {
		$marketing_campaign = new MarketingCampaign( '1234', 'Ad #1234', 'https://example.com/manage-campaigns', '$1000' );

		$this->assertEquals( '1234', $marketing_campaign->get_id() );
		$this->assertEquals( 'Ad #1234', $marketing_campaign->get_title() );
		$this->assertEquals( 'https://example.com/manage-campaigns', $marketing_campaign->get_manage_url() );
		$this->assertEquals( '$1000', $marketing_campaign->get_cost() );
	}

	/**
	 * @testdox `cost` property can be null.
	 */
	public function test_cost_can_be_null() {
		$marketing_campaign = new MarketingCampaign( '1234', 'Ad #1234', 'https://example.com/manage-campaigns' );

		$this->assertNull( $marketing_campaign->get_cost() );
	}

	/**
	 * @testdox It can be serialized to JSON including all its properties.
	 */
	public function test_can_be_serialized_to_json() {
		$marketing_campaign = new MarketingCampaign( '1234', 'Ad #1234', 'https://example.com/manage-campaigns', '$1000' );

		$json = wp_json_encode( $marketing_campaign );
		$this->assertNotEmpty( $json );
		$this->assertEqualSets(
			[
				'id'         => $marketing_campaign->get_id(),
				'title'      => $marketing_campaign->get_title(),
				'manage_url' => $marketing_campaign->get_manage_url(),
				'cost'       => $marketing_campaign->get_cost(),
			],
			json_decode( $json, true )
		);
	}
}
