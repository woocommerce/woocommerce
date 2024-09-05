<?php

namespace Automattic\WooCommerce\RemoteSpecsValidation\Tests;

use Automattic\WooCommerce\RemoteSpecsValidation\RemoteSpecValidator;

/**
 * Tests fixtures.
 */
class FixtureTest extends TestCase {
	private function get_fixture( $file ) {
		return json_decode( file_get_contents( __DIR__ . '/fixtures/' . $file ) );
	}

	public function test_remote_inbox_notifications() {
	    $validator = RemoteSpecValidator::create_from_bundle( 'remote-inbox-notification' );
		$result = $validator->validate( $this->get_fixture( 'remote-inbox-notification.json' ) );
		$this->assertTrue( $result->is_valid() );
	}

	public function test_payment_gateway_suggestions() {
		$validator = RemoteSpecValidator::create_from_bundle( 'payment-gateway-suggestions' );
		$result = $validator->validate( $this->get_fixture( 'payment-gateway-suggestions.json' ) );
		// This is expected to fail as the payment-gateway-suggestions contains invalid format.
		// We should fix them and update this test.
		$this->assertFalse( $result->is_valid() );
	}

	public function test_obw_free_extensions() {
		$validator = RemoteSpecValidator::create_from_bundle( 'obw-free-extensions' );
		$result = $validator->validate( $this->get_fixture( 'obw-free-extensions.json' ) );
		$this->assertTrue( $result->is_valid() );
	}

	public function test_wcpay_promotions() {
		$validator = RemoteSpecValidator::create_from_bundle( 'wc-pay-promotions' );
		$result = $validator->validate( $this->get_fixture( 'wc-pay-promotions.json' ) );
		$this->assertTrue( $result->is_valid() );
	}

	public function test_shipping_partner_suggestions() {
		$validator = RemoteSpecValidator::create_from_bundle( 'shipping-partner-suggestions' );
		$result = $validator->validate( $this->get_fixture( 'shipping-partner-suggestions.json' ) );
		$this->assertTrue( $result->is_valid() );
	}
}
