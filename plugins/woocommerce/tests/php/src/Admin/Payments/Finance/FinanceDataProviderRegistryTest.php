<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Payments\Finance;

use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataProviderRegistry;
use Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance\Mocks\FakeFinanceDataProvider;
use Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance\Mocks\FakeLogger;
use WC_Payment_Gateway;
use WC_Unit_Test_Case;

/**
 * Tests for the FinanceDataProviderRegistry class.
 */
class FinanceDataProviderRegistryTest extends WC_Unit_Test_Case {

	/**
	 * The mock gateway id.
	 *
	 * @var string
	 */
	private const MOCK_GATEWAY_ID = 'mock_gateway';

	/**
	 * The System Under Test.
	 *
	 * @var FinanceDataProviderRegistry
	 */
	private FinanceDataProviderRegistry $sut;

	/**
	 * A hook used to mock the main WooCommerce payment gateway list.
	 *
	 * @var callable|null
	 */
	private $gateway_hook = null;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new FinanceDataProviderRegistry();

		$mock_gateway = $this->createMock( WC_Payment_Gateway::class );
		$mock_gateway->id = self::MOCK_GATEWAY_ID;

		$this->gateway_hook = function ( $gateways ) use ( $mock_gateway ) {
			$gateways[ self::MOCK_GATEWAY_ID ] = $mock_gateway;
			return $gateways;
		};
		add_filter( 'woocommerce_payment_gateways', $this->gateway_hook, 10, 1 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_payment_gateways', $this->gateway_hook, 10 );
		parent::tearDown();
	}

	/**
	 * @testdox Should register a provider and look it up by its exact gateway id.
	 */
	public function test_registers_provider_and_looks_it_up_by_exact_id(): void {
		$provider = new FakeFinanceDataProvider( self::MOCK_GATEWAY_ID );

		$this->assertTrue( $this->sut->register( $provider ) );

		$this->assertSame( $provider, $this->sut->get_provider( self::MOCK_GATEWAY_ID ) );
		$this->assertNull( $this->sut->get_provider( strtoupper( self::MOCK_GATEWAY_ID ) ), 'Gateway ids are case sensitive.' );
		$this->assertSame( array( self::MOCK_GATEWAY_ID => $provider ), $this->sut->get_providers() );
	}

	/**
	 * @testdox Should reject providers whose gateway id is empty or not routable.
	 *
	 * @testWith [""]
	 *           ["bad id"]
	 *           ["bad.id"]
	 *           ["bad/id"]
	 *
	 * @param string $gateway_id The gateway id.
	 */
	public function test_rejects_invalid_gateway_ids( string $gateway_id ): void {
		$this->setExpectedIncorrectUsage( FinanceDataProviderRegistry::class . '::register' );

		$this->assertFalse( $this->sut->register( new FakeFinanceDataProvider( $gateway_id ) ) );
		$this->assertSame( array(), $this->sut->get_providers() );
	}

	/**
	 * @testdox Should keep the first provider when a second one is registered for the same gateway.
	 */
	public function test_rejects_duplicate_registration(): void {
		$this->setExpectedIncorrectUsage( FinanceDataProviderRegistry::class . '::register' );
		$first  = new FakeFinanceDataProvider( self::MOCK_GATEWAY_ID );
		$second = new FakeFinanceDataProvider( self::MOCK_GATEWAY_ID );

		$this->assertTrue( $this->sut->register( $first ) );
		$this->assertFalse( $this->sut->register( $second ) );

		$this->assertSame( $first, $this->sut->get_provider( self::MOCK_GATEWAY_ID ) );
	}

	/**
	 * @testdox Should fire the registration action once, passing the registry, before the first lookup.
	 */
	public function test_fires_registration_action_once_with_registry(): void {
		$calls    = 0;
		$received = null;
		$provider = new FakeFinanceDataProvider( self::MOCK_GATEWAY_ID );
		add_action(
			'woocommerce_payments_finance_providers_registration',
			function ( $registry ) use ( &$calls, &$received, $provider ) {
				++$calls;
				$received = $registry;
				$registry->register( $provider );
			}
		);

		$this->assertSame( $provider, $this->sut->get_provider( self::MOCK_GATEWAY_ID ) );
		$this->assertSame( array( self::MOCK_GATEWAY_ID => $provider ), $this->sut->get_providers() );

		$this->assertSame( 1, $calls, 'The registration action should fire only once.' );
		$this->assertSame( $this->sut, $received );
	}

	/**
	 * @testdox Should log a failing registration callback and not retry it.
	 */
	public function test_logs_failing_registration_callback_without_retrying(): void {
		$fake_logger = new FakeLogger();
		add_filter( 'woocommerce_logging_class', fn() => $fake_logger );
		$calls = 0;
		add_action(
			'woocommerce_payments_finance_providers_registration',
			function () use ( &$calls ) {
				++$calls;
				throw new \Error( 'Broken finance provider registration.' );
			}
		);

		$first  = $this->sut->get_providers();
		$second = $this->sut->get_providers();

		$this->assertSame( array(), $first );
		$this->assertSame( array(), $second );
		$this->assertSame( 1, $calls, 'A failing registration action should not be retried.' );
		$this->assertCount( 1, $fake_logger->get_messages( 'error' ) );
		$this->assertStringContainsString( 'Error: Broken finance provider registration.', $fake_logger->get_messages( 'error' )[0] );
		$this->assertSame( 'payments-finance', $fake_logger->calls['error'][0]['context']['source'] );
	}

	/**
	 * @testdox Should clear providers and fire the registration action again after unregister_all().
	 */
	public function test_unregister_all_resets_providers_and_initialization(): void {
		$calls = 0;
		add_action(
			'woocommerce_payments_finance_providers_registration',
			function ( $registry ) use ( &$calls ) {
				++$calls;
				$registry->register( new FakeFinanceDataProvider( self::MOCK_GATEWAY_ID ) );
			}
		);
		$this->sut->get_providers();

		$this->sut->unregister_all();

		$this->assertSame( 1, $calls );
		$this->assertArrayHasKey( self::MOCK_GATEWAY_ID, $this->sut->get_providers(), 'Lookups after a reset should fire the action again.' );
		$this->assertSame( 2, $calls );
	}
}
