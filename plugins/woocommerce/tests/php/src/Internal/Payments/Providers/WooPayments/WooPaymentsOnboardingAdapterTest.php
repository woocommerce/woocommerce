<?php
/**
 * WooPaymentsOnboardingAdapter tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\PaymentGateway;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsOnboardingAdapter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPayments onboarding adapter used by native admin surfaces.
 */
class WooPaymentsOnboardingAdapterTest extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var WooPaymentsOnboardingAdapter
	 */
	private WooPaymentsOnboardingAdapter $adapter;

	/**
	 * Mockable legacy proxy.
	 *
	 * @var MockableLegacyProxy
	 */
	private MockableLegacyProxy $legacy_proxy;

	/**
	 * WooPayments provider mock.
	 *
	 * @var WooPaymentsProvider|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $provider;

	/**
	 * Admin payment gateway provider.
	 *
	 * @var PaymentGateway
	 */
	private PaymentGateway $payment_gateway_provider;

	/**
	 * Legacy gateway test double.
	 *
	 * @var FakePaymentGateway
	 */
	private FakePaymentGateway $gateway;

	/**
	 * Account service mock.
	 *
	 * @var object|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $account_service;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->legacy_proxy = wc_get_container()->get( LegacyProxy::class );
		$this->legacy_proxy->reset();

		$this->provider = $this->getMockBuilder( WooPaymentsProvider::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'can_process_payments' ) )
			->getMock();

		$this->payment_gateway_provider = new PaymentGateway( $this->legacy_proxy );
		$this->gateway                  = new FakePaymentGateway( 'woocommerce_payments' );
		$this->account_service          = $this->getMockBuilder( \stdClass::class )
			->addMethods( array( 'is_stripe_account_valid', 'get_account_status_data' ) )
			->getMock();

		$this->legacy_proxy->register_static_mocks(
			array(
				'\WC_Payments'         => array(
					'get_gateway'         => fn() => $this->gateway,
					'get_account_service' => fn() => $this->account_service,
				),
				'\WC_Payments_Account' => array(
					'get_connect_url'       => fn() => 'https://example.com/kyc',
					'get_overview_page_url' => fn() => 'https://example.com/overview',
				),
			)
		);

		$this->adapter = new WooPaymentsOnboardingAdapter();
		$this->adapter->init( $this->legacy_proxy, $this->provider );
	}

	/**
	 * Reset proxy mocks after each test.
	 */
	public function tearDown(): void {
		$this->legacy_proxy->reset();

		parent::tearDown();
	}

	/**
	 * @testdox The legacy extension makes onboarding runtime available during the transition.
	 */
	public function test_runtime_available_when_legacy_extension_is_active(): void {
		$this->legacy_proxy->register_function_mocks(
			array(
				'class_exists' => fn( $class_to_check ) => '\WC_Payments' === $class_to_check,
			)
		);
		$this->provider->method( 'can_process_payments' )->willReturn( false );

		self::assertTrue( $this->adapter->is_onboarding_runtime_available() );
	}

	/**
	 * @testdox A future native provider transport can make onboarding available without the plugin class.
	 */
	public function test_runtime_available_when_native_provider_can_process_without_plugin(): void {
		$this->legacy_proxy->register_function_mocks(
			array(
				'class_exists' => fn() => false,
			)
		);
		$this->provider->method( 'can_process_payments' )->willReturn( true );

		self::assertTrue( $this->adapter->is_onboarding_runtime_available() );
	}

	/**
	 * @testdox Account state fails closed when neither plugin nor native provider can operate.
	 */
	public function test_account_state_is_fail_closed_when_no_runtime_is_available(): void {
		$this->legacy_proxy->register_function_mocks(
			array(
				'class_exists' => fn() => false,
			)
		);
		$this->provider->method( 'can_process_payments' )->willReturn( false );

		self::assertFalse( $this->adapter->has_account( $this->payment_gateway_provider ) );
		self::assertFalse( $this->adapter->has_valid_account( $this->payment_gateway_provider ) );
		self::assertFalse( $this->adapter->has_working_account( $this->payment_gateway_provider ) );
	}

	/**
	 * @testdox Legacy account state is normalized through the adapter.
	 */
	public function test_legacy_account_state_is_normalized(): void {
		$this->legacy_proxy->register_function_mocks(
			array(
				'class_exists' => fn( $class_to_check ) => '\WC_Payments' === $class_to_check,
			)
		);
		$this->provider->method( 'can_process_payments' )->willReturn( false );
		$this->account_service
			->method( 'is_stripe_account_valid' )
			->willReturn( true );
		$this->account_service
			->method( 'get_account_status_data' )
			->willReturn(
				array(
					'paymentsEnabled' => true,
					'testDrive'       => true,
					'isLive'          => false,
				)
			);

		self::assertTrue( $this->adapter->has_account( $this->payment_gateway_provider ) );
		self::assertTrue( $this->adapter->has_valid_account( $this->payment_gateway_provider ) );
		self::assertTrue( $this->adapter->has_working_account( $this->payment_gateway_provider ) );
		self::assertTrue( $this->adapter->has_test_account( $this->payment_gateway_provider ) );
		self::assertFalse( $this->adapter->has_sandbox_account( $this->payment_gateway_provider ) );
		self::assertFalse( $this->adapter->has_live_account( $this->payment_gateway_provider ) );
	}
}
