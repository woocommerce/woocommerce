<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPointsRewardsCompatibilityProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyPointsRewardsCompatibilityProjectionService class.
 */
class MultiCurrencyPointsRewardsCompatibilityProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project Points and Rewards compatibility hook manifest.
	 */
	public function test_projects_points_rewards_compatibility_hook_manifest(): void {
		$manifest = MultiCurrencyPointsRewardsCompatibilityProjectionService::get_hook_manifest();

		$this->assertSame(
			array(
				'option_wc_points_rewards_earn_points_ratio',
				'option_wc_points_rewards_redeem_points_ratio',
			),
			array_column( $manifest['filters'], 'hook' )
		);
		$this->assertSame( array(), $manifest['actions'] );
		$this->assertSame( 'convert_points_ratio', $manifest['filters'][0]['callback'] );
		$this->assertSame( 50, $manifest['filters'][0]['priority'] );
		$this->assertSame( 1, $manifest['filters'][0]['accepted_args'] );
	}

	/**
	 * @testdox Should require Points and Rewards runtime and frontend request.
	 */
	public function test_requires_points_rewards_runtime_and_frontend_request(): void {
		$this->assertTrue( MultiCurrencyPointsRewardsCompatibilityProjectionService::should_register( true, false ) );
		$this->assertFalse( MultiCurrencyPointsRewardsCompatibilityProjectionService::should_register( true, true ) );
		$this->assertFalse( MultiCurrencyPointsRewardsCompatibilityProjectionService::should_register( false, false ) );
	}

	/**
	 * @testdox Should decide when points ratios should convert.
	 */
	public function test_decides_when_points_ratios_should_convert(): void {
		$this->assertFalse( MultiCurrencyPointsRewardsCompatibilityProjectionService::should_convert_ratio( false, false ) );
		$this->assertFalse( MultiCurrencyPointsRewardsCompatibilityProjectionService::should_convert_ratio( true, true ) );
		$this->assertTrue( MultiCurrencyPointsRewardsCompatibilityProjectionService::should_convert_ratio( true, false ) );
	}

	/**
	 * @testdox Should convert monetary side of points ratio by selected rate.
	 */
	public function test_converts_monetary_side_of_points_ratio_by_selected_rate(): void {
		$this->assertSame( '10:1.6', MultiCurrencyPointsRewardsCompatibilityProjectionService::convert_ratio_value( '10:2', 0.8 ) );
		$this->assertSame( '0:0', MultiCurrencyPointsRewardsCompatibilityProjectionService::convert_ratio_value( '', 0.8 ) );
		$this->assertSame( '0:0', MultiCurrencyPointsRewardsCompatibilityProjectionService::convert_ratio_value( 'bad', 0.8 ) );
	}
}
