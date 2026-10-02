<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth\Checks;

use Automattic\WooCommerce\Internal\SiteHealth\Checks\WebhookFailureCheck;
use WC_Unit_Test_Case;

/**
 * Tests for the WebhookFailureCheck class.
 */
class WebhookFailureCheckTest extends WC_Unit_Test_Case {

	/** Verifies a recommended status when recent webhook failures exceed the threshold. */
	public function test_recommended_when_threshold_exceeded() {
		$check = $this->getMockBuilder( WebhookFailureCheck::class )
			->onlyMethods( array( 'count_recent_failures' ) )
			->getMock();
		$check->method( 'count_recent_failures' )->willReturn( 25 );
		$this->assertSame( 'recommended', $check->run()['status'] );
	}

	/** Verifies a good status when recent webhook failures are below the threshold. */
	public function test_good_when_below_threshold() {
		$check = $this->getMockBuilder( WebhookFailureCheck::class )
			->onlyMethods( array( 'count_recent_failures' ) )
			->getMock();
		$check->method( 'count_recent_failures' )->willReturn( 3 );
		$this->assertSame( 'good', $check->run()['status'] );
	}

	/** Verifies the threshold filter overrides the default webhook-failure threshold. */
	public function test_threshold_filter_applies() {
		add_filter( 'woocommerce_site_health_check_webhook_failures_threshold', fn() => 1 );
		$check = $this->getMockBuilder( WebhookFailureCheck::class )
			->onlyMethods( array( 'count_recent_failures' ) )
			->getMock();
		$check->method( 'count_recent_failures' )->willReturn( 5 );
		$this->assertSame( 'recommended', $check->run()['status'] );
		remove_all_filters( 'woocommerce_site_health_check_webhook_failures_threshold' );
	}
}
