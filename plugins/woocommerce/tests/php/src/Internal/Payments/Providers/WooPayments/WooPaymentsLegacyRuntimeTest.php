<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsLegacyRuntime class.
 */
class WooPaymentsLegacyRuntimeTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should fail closed when the WooPayments runtime is absent.
	 */
	public function test_fails_closed_when_woopayments_runtime_is_absent(): void {
		$sut = new WooPaymentsLegacyRuntime();
		$sut->init( new LegacyRuntimeProxy( false ) );

		$this->assertFalse( $sut->is_loaded() );
		$this->assertNull( $sut->get_gateway() );
		$this->assertNull( $sut->get_account_service() );
		$this->assertNull( $sut->get_payments_api_client() );
	}

	/**
	 * @testdox Should return WooPayments runtime services when available.
	 */
	public function test_returns_woopayments_runtime_services_when_available(): void {
		$gateway    = (object) array( 'id' => 'gateway' );
		$account    = (object) array( 'id' => 'account' );
		$api_client = (object) array( 'id' => 'api_client' );
		$logger     = (object) array( 'id' => 'logger' );
		$sut        = new WooPaymentsLegacyRuntime();
		$sut->init( new LegacyRuntimeProxy( true, $gateway, $account, $api_client, $logger ) );

		$this->assertTrue( $sut->is_loaded() );
		$this->assertSame( $gateway, $sut->get_gateway() );
		$this->assertSame( $account, $sut->get_account_service() );
		$this->assertSame( $api_client, $sut->get_payments_api_client() );
		$this->assertSame( $logger, $sut->get_logger() );
	}

	/**
	 * @testdox Should swallow legacy runtime lookup exceptions.
	 */
	public function test_swallows_runtime_lookup_exceptions(): void {
		$sut = new WooPaymentsLegacyRuntime();
		$sut->init( new ThrowingLegacyRuntimeProxy() );

		$this->assertFalse( $sut->is_loaded() );
		$this->assertNull( $sut->get_gateway() );
		$this->assertNull( $sut->get_account_service() );
		$this->assertNull( $sut->get_payments_api_client() );
		$this->assertNull( $sut->get_logger() );
	}
}
