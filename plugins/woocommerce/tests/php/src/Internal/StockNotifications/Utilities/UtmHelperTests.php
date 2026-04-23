<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Utilities;

use Automattic\WooCommerce\Internal\StockNotifications\Utilities\UtmHelper;

/**
 * Tests for UtmHelper.
 */
class UtmHelperTests extends \WC_Unit_Test_Case {

	/**
	 * @testdox Should append standard UTM params to a plain URL.
	 */
	public function test_add_email_utm_params_to_plain_url() {
		$url = UtmHelper::add_email_utm_params( 'https://shop.example.com/product/test/' );

		$this->assertStringContainsString( 'utm_source=back-in-stock-notifications', $url );
		$this->assertStringContainsString( 'utm_medium=email', $url );
	}

	/**
	 * @testdox Should preserve existing query params when appending UTMs.
	 */
	public function test_add_email_utm_params_preserves_existing_query() {
		$url = UtmHelper::add_email_utm_params( 'https://shop.example.com/product/test/?notification_id=123' );

		$this->assertStringContainsString( 'notification_id=123', $url );
		$this->assertStringContainsString( 'utm_source=back-in-stock-notifications', $url );
		$this->assertStringContainsString( 'utm_medium=email', $url );
	}

	/**
	 * @testdox Should use an explicit medium when supplied.
	 */
	public function test_add_email_utm_params_uses_custom_medium() {
		$url = UtmHelper::add_email_utm_params( 'https://shop.example.com/', 'custom-medium' );

		$this->assertStringContainsString( 'utm_medium=custom-medium', $url );
	}

	/**
	 * @testdox Should return an empty string when given an empty URL.
	 */
	public function test_add_email_utm_params_returns_empty_for_empty_input() {
		$this->assertSame( '', UtmHelper::add_email_utm_params( '' ) );
	}
}
