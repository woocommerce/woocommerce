<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\CapabilityManifest;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsProvider class.
 */
class WooPaymentsProviderTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsProvider
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( WooPaymentsProvider::class );
	}

	/**
	 * @testdox Provider identity preserves the WooPayments gateway ID.
	 */
	public function test_provider_identity_preserves_woopayments_gateway_id(): void {
		$this->assertSame( OrderPaymentStore::GATEWAY_ID, $this->sut->get_id() );
		$this->assertInstanceOf( CapabilityManifest::class, $this->sut->get_capability_manifest() );
	}

	/**
	 * @testdox A1 provider does not publish money-moving operations before real callers exist.
	 */
	public function test_provider_does_not_publish_money_moving_operations_before_real_callers_exist(): void {
		foreach (
			array(
				'charge',
				'capture',
				'cancel',
			) as $method
		) {
			$this->assertFalse( method_exists( $this->sut, $method ), "{$method} must not be exposed before native processing has real callers." );
		}
	}
}
