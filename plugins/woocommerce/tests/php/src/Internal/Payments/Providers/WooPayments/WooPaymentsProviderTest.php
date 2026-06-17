<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\CapabilityManifest;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProviderGatewayAdapter;
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
	 * @testdox A3 provider publishes money-moving operations once native processing exists.
	 */
	public function test_provider_publishes_money_moving_operations_for_native_processing(): void {
		foreach (
			array(
				'charge',
				'capture',
				'cancel',
				'refund',
			) as $method
		) {
			$this->assertTrue( method_exists( $this->sut, $method ), "{$method} must be exposed through ProviderContract for A3." );
		}
	}

	/**
	 * @testdox Provider capabilities should expose the WooPayments native processing surface.
	 */
	public function test_provider_capabilities_expose_native_processing_surface(): void {
		$manifest = $this->sut->get_capability_manifest();

		foreach (
			array(
				CapabilityManifest::CAPABILITY_CARDS,
				CapabilityManifest::CAPABILITY_SAVED_TOKENS,
				CapabilityManifest::CAPABILITY_MANDATES,
				CapabilityManifest::CAPABILITY_ASYNC_REDIRECT,
				CapabilityManifest::CAPABILITY_REFUNDS,
				CapabilityManifest::CAPABILITY_PARTIAL_REFUNDS,
				CapabilityManifest::CAPABILITY_MANUAL_CAPTURE,
				CapabilityManifest::CAPABILITY_EXPRESS_CHECKOUT,
				CapabilityManifest::CAPABILITY_HOSTED_SESSION,
				CapabilityManifest::CAPABILITY_SUBSCRIPTIONS,
				CapabilityManifest::CAPABILITY_IN_PERSON,
			) as $capability
		) {
			$this->assertTrue( $manifest->supports( $capability ), "{$capability} should be declared for WooPayments native processing." );
		}
	}

	/**
	 * @testdox Provider availability requires native transport and account readiness.
	 *
	 * @dataProvider provider_native_readiness
	 *
	 * @param bool $transport_available Whether native transport is available.
	 * @param bool $account_ready       Whether the account can process payments.
	 * @param bool $expected            Expected readiness.
	 */
	public function test_can_process_payments_requires_native_transport_and_account_readiness( bool $transport_available, bool $account_ready, bool $expected ): void {
		$gateway_adapter = $this->getMockBuilder( WooPaymentsProviderGatewayAdapter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available' ) )
			->getMock();
		$gateway_adapter
			->expects( $this->never() )
			->method( 'is_available' );
		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available' ) )
			->getMock();
		$api_client
			->expects( $this->once() )
			->method( 'is_available' )
			->willReturn( $transport_available );
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'can_process_payments' ) )
			->getMock();

		if ( $transport_available ) {
			$account_service
				->expects( $this->once() )
				->method( 'can_process_payments' )
				->willReturn( $account_ready );
		} else {
			$account_service
				->expects( $this->never() )
				->method( 'can_process_payments' );
		}

		$provider = new WooPaymentsProvider();
		$provider->init( $gateway_adapter, $api_client, $account_service );

		$this->assertSame( $expected, $provider->can_process_payments() );
	}

	/**
	 * @testdox Provider onboarding availability requires native transport, not account readiness.
	 *
	 * @dataProvider boolean_provider
	 *
	 * @param bool $transport_available Whether native transport is available.
	 */
	public function test_can_manage_onboarding_requires_native_transport_only( bool $transport_available ): void {
		$gateway_adapter = $this->getMockBuilder( WooPaymentsProviderGatewayAdapter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available' ) )
			->getMock();
		$gateway_adapter
			->expects( $this->never() )
			->method( 'is_available' );
		$api_client = $this->getMockBuilder( WooPaymentsApiClient::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_available' ) )
			->getMock();
		$api_client
			->expects( $this->once() )
			->method( 'is_available' )
			->willReturn( $transport_available );
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'can_process_payments' ) )
			->getMock();
		$account_service
			->expects( $this->never() )
			->method( 'can_process_payments' );

		$provider = new WooPaymentsProvider();
		$provider->init( $gateway_adapter, $api_client, $account_service );

		$this->assertSame( $transport_available, $provider->can_manage_onboarding() );
	}

	/**
	 * Data provider for native provider readiness.
	 *
	 * @return array<string,array{bool,bool,bool}>
	 */
	public function provider_native_readiness(): array {
		return array(
			'transport and account ready' => array( true, true, true ),
			'transport unavailable'       => array( false, true, false ),
			'account unavailable'         => array( true, false, false ),
		);
	}

	/**
	 * Data provider for boolean inputs.
	 *
	 * @return array<string,array{bool}>
	 */
	public function boolean_provider(): array {
		return array(
			'true'  => array( true ),
			'false' => array( false ),
		);
	}

	/**
	 * @testdox Provider should receive the gateway adapter through dependency injection.
	 */
	public function test_provider_gateway_adapter_access_is_injected(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads local plugin source for provider-boundary regression coverage.
		$source = (string) file_get_contents( WC()->plugin_path() . '/src/Internal/Payments/Providers/WooPayments/WooPaymentsProvider.php' );

		$this->assertDoesNotMatchRegularExpression(
			'/wc_get_container\(\)\s*->get\(\s*WooPaymentsProviderGatewayAdapter::class\s*\)/',
			$source,
			'WooPaymentsProvider should receive the gateway adapter through init injection.'
		);
		$this->assertStringNotContainsString(
			'get_gateway_adapter()->is_available()',
			$source,
			'WooPaymentsProvider readiness should use native transport and account readiness, not legacy gateway adapter availability.'
		);
	}
}
