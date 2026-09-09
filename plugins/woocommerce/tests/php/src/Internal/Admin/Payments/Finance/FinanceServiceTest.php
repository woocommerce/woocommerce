<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance;

use Automattic\WooCommerce\Admin\Payments\Finance\V1\Balance;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataException;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataPage;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataProviderRegistry;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataQuery;
use Automattic\WooCommerce\Admin\Payments\Finance\V1\Payout;
use Automattic\WooCommerce\Enums\FinanceDataSource;
use Automattic\WooCommerce\Enums\PayoutStatus;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceDataSerializer;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceService;
use Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance\Mocks\FakeBaseFinanceDataProvider;
use Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance\Mocks\FakeFinanceDataProvider;
use Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance\Mocks\FakeLogger;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use WC_Unit_Test_Case;

/**
 * Tests for the FinanceService class.
 */
class FinanceServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var FinanceService
	 */
	private FinanceService $sut;

	/**
	 * The provider registry injected into the SUT.
	 *
	 * @var FinanceDataProviderRegistry
	 */
	private FinanceDataProviderRegistry $registry;

	/**
	 * The logger injected through the logging class filter.
	 *
	 * @var FakeLogger
	 */
	private FakeLogger $logger;

	/**
	 * The filter adding the test gateways.
	 *
	 * @var callable
	 */
	private $gateways_filter;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->registry = new FinanceDataProviderRegistry();
		$this->sut      = new FinanceService();
		$this->sut->init( $this->registry, new FinanceDataSerializer() );

		$this->logger = new FakeLogger();
		add_filter( 'woocommerce_logging_class', fn() => $this->logger );

		$this->gateways_filter = static function ( array $gateways ): array {
			$gateways[] = 'WC_Mock_Payment_Gateway';
			$gateways[] = new FakePaymentGateway( 'mock_two', array( 'method_title' => 'Mock Two' ) );
			$gateways[] = new FakePaymentGateway( 'mock_three', array( 'method_title' => 'Mock Three' ) );
			return $gateways;
		};
		add_filter( 'woocommerce_payment_gateways', $this->gateways_filter );
		self::reload_payment_gateways();
	}

	/**
	 * Remove the test gateways from the shared gateway list.
	 */
	public function tearDown(): void {
		try {
			remove_filter( 'woocommerce_payment_gateways', $this->gateways_filter );
			self::reload_payment_gateways();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should list providers whose gateway exists, with their icon and only the data types core can serve.
	 */
	public function test_lists_providers_with_servable_data_types(): void {
		$icon_url           = home_url( '/wp-content/plugins/mock/icon.svg' );
		$provider           = new FakeFinanceDataProvider( 'mock' );
		$provider->icon_url = $icon_url;
		$this->registry->register( $provider );
		$this->registry->register( new FakeBaseFinanceDataProvider( 'mock_two', array( FinanceDataSource::BALANCE => 1 ) ) );
		$this->registry->register(
			new FakeFinanceDataProvider(
				'mock_three',
				array(
					FinanceDataSource::BALANCE => 2,
					FinanceDataSource::PAYOUTS => 1,
				)
			)
		);

		$result = $this->sut->get_providers();

		$this->assertSame(
			array(
				'providers' => array(
					array(
						'provider_id' => 'mock',
						'title'       => 'Mock Gateway',
						'icon_url'    => $icon_url,
						'data_types'  => array(
							array(
								'type'           => 'balance',
								'schema_version' => 1,
							),
							array(
								'type'           => 'payouts',
								'schema_version' => 1,
							),
						),
					),
					array(
						'provider_id' => 'mock_three',
						'title'       => 'Mock Three',
						'icon_url'    => null,
						'data_types'  => array(
							array(
								'type'           => 'payouts',
								'schema_version' => 1,
							),
						),
					),
				),
			),
			$result
		);
		$this->assertCount( 2, $this->logger->get_messages( 'debug' ), 'The unimplemented type and the unsupported version should each be logged.' );
	}

	/**
	 * @testdox Should only keep icon URLs that stay on the store's own host.
	 *
	 * @dataProvider icon_url_provider
	 *
	 * @param string      $icon_url The icon URL the provider declares.
	 * @param string|null $expected The icon URL in the response.
	 */
	public function test_keeps_only_local_icon_urls( string $icon_url, ?string $expected ): void {
		$provider           = new FakeFinanceDataProvider( 'mock' );
		$provider->icon_url = $icon_url;
		$this->registry->register( $provider );

		$result = $this->sut->get_providers();

		$this->assertSame( $expected, $result['providers'][0]['icon_url'] );
	}

	/**
	 * Icon URLs a provider may declare, with the value the response should carry.
	 *
	 * @return array
	 */
	public function icon_url_provider(): array {
		$home_url = get_home_url();

		return array(
			'plugin asset on the store host' => array( $home_url . '/wp-content/plugins/mock/icon.svg', $home_url . '/wp-content/plugins/mock/icon.svg' ),
			'another host'                   => array( 'https://cdn.example.net/mock/icon.svg', null ),
			'path traversal'                 => array( $home_url . '/wp-content/plugins/../uploads/icon.svg', null ),
			'empty'                          => array( '', null ),
		);
	}

	/**
	 * @testdox Should omit a provider whose data type declaration throws, and log the failure.
	 */
	public function test_omits_provider_whose_declaration_throws(): void {
		$provider                        = new FakeFinanceDataProvider( 'mock' );
		$provider->declaration_throwable = new \Error( 'nope' );
		$this->registry->register( $provider );

		$result = $this->sut->get_providers();

		$this->assertSame( array( 'providers' => array() ), $result );
		$this->assertCount( 1, $this->logger->get_messages( 'error' ) );
		$this->assertStringContainsString( 'Error: nope', $this->logger->get_messages( 'error' )[0] );
	}

	/**
	 * @testdox Should return the serialized balances envelope for a supported gateway.
	 */
	public function test_get_balances_returns_envelope(): void {
		$provider                = new FakeFinanceDataProvider( 'mock' );
		$provider->balances_page = new FinanceDataPage( array( ( new Balance( 'mock', 'usd', '10.00' ) )->set_available_amount( '5.00' ) ) );
		$this->registry->register( $provider );

		$result = $this->sut->get_balances( 'mock' );

		$this->assertSame( 1, $result['schema_version'] );
		$this->assertFalse( $result['has_more'] );
		$this->assertNull( $result['next_cursor'] );
		$this->assertNull( $result['prev_cursor'] );
		$this->assertCount( 1, $result['items'] );
		$this->assertSame( 'mock', $result['items'][0]['provider_id'] );
		$this->assertSame( 'USD', $result['items'][0]['currency'] );
		$this->assertSame( '5.00', $result['items'][0]['available_amount'] );
		$this->assertNull( $provider->last_query->get_next_cursor() );
		$this->assertNull( $provider->last_query->get_prev_cursor() );
	}

	/**
	 * @testdox Should pass the query to the payouts provider and return its pagination state.
	 */
	public function test_get_payouts_passes_query_and_returns_pagination(): void {
		$provider               = new FakeFinanceDataProvider( 'mock' );
		$provider->payouts_page = new FinanceDataPage(
			array( new Payout( 'mock', 'po_1', 'USD', '1.00', PayoutStatus::PENDING, new \DateTimeImmutable( '2026-09-01T12:00:00+00:00' ) ) ),
			true,
			'next-cursor',
			'prev-cursor'
		);
		$this->registry->register( $provider );

		$result = $this->sut->get_payouts( 'mock', new FinanceDataQuery( 'to-next', 'to-prev', 5 ) );

		$this->assertSame( 'to-next', $provider->last_query->get_next_cursor() );
		$this->assertSame( 'to-prev', $provider->last_query->get_prev_cursor() );
		$this->assertSame( 5, $provider->last_query->get_per_page() );
		$this->assertTrue( $result['has_more'] );
		$this->assertSame( 'next-cursor', $result['next_cursor'] );
		$this->assertSame( 'prev-cursor', $result['prev_cursor'] );
		$this->assertSame( 'mock', $result['items'][0]['provider_id'] );
		$this->assertSame( 'po_1', $result['items'][0]['id'] );
	}

	/**
	 * @testdox Should report a gateway without a provider, or a provider without a gateway, as not found.
	 *
	 * @testWith ["mock"]
	 *           ["unknown_gateway"]
	 *
	 * @param string $gateway_id The gateway id.
	 */
	public function test_reports_missing_provider_or_gateway_as_not_found( string $gateway_id ): void {
		$exception = $this->catch_exception( fn() => $this->sut->get_balances( $gateway_id ) );

		$this->assertSame( 'provider_not_found', $exception->get_error_code() );
		$this->assertSame( 404, $exception->get_http_status() );
	}

	/**
	 * @testdox Should report an undeclared data type, or an unsupported version, as not supported.
	 *
	 * @testWith [{"payouts": 1}]
	 *           [{"balance": 2}]
	 *
	 * @param array $declared The declared.
	 */
	public function test_reports_undeclared_or_unsupported_data_type_as_not_supported( array $declared ): void {
		$this->registry->register( new FakeFinanceDataProvider( 'mock', $declared ) );

		$exception = $this->catch_exception( fn() => $this->sut->get_balances( 'mock' ) );

		$this->assertSame( 'data_type_not_supported', $exception->get_error_code() );
		$this->assertSame( 404, $exception->get_http_status() );
	}

	/**
	 * @testdox Should flag a provider that declares a data type without implementing its interface.
	 */
	public function test_flags_declared_but_unimplemented_data_type(): void {
		$this->setExpectedIncorrectUsage( FinanceService::class . '::resolve_provider' );
		$this->registry->register( new FakeBaseFinanceDataProvider( 'mock', array( FinanceDataSource::BALANCE => 1 ) ) );

		$exception = $this->catch_exception( fn() => $this->sut->get_balances( 'mock' ) );

		$this->assertSame( 'data_type_not_supported', $exception->get_error_code() );
	}

	/**
	 * @testdox Should pass provider exceptions through with a sanitized code and a specific status.
	 *
	 * @testWith ["Not-Connected!", 302, "not-connected", 400]
	 *           ["account_review", 409, "account_review", 409]
	 *           ["", 999, "provider_error", 599]
	 *
	 * @param string $code            The code.
	 * @param int    $status          The status.
	 * @param string $expected_code   The expected code.
	 * @param int    $expected_status The expected status.
	 */
	public function test_passes_provider_exceptions_through( string $code, int $status, string $expected_code, int $expected_status ): void {
		$provider                  = new FakeFinanceDataProvider( 'mock' );
		$provider->fetch_throwable = new FinanceDataException( 'Connect your account.', $code, $status );
		$this->registry->register( $provider );

		$exception = $this->catch_exception( fn() => $this->sut->get_payouts( 'mock', new FinanceDataQuery() ) );

		$this->assertSame( 'Connect your account.', $exception->getMessage() );
		$this->assertSame( $expected_code, $exception->get_error_code() );
		$this->assertSame( $expected_status, $exception->get_http_status() );
		$this->assertSame( $provider->fetch_throwable, $exception->getPrevious() );
		$this->assertSame( array(), $this->logger->get_messages( 'error' ), 'Provider exceptions are expected and should not be logged as errors.' );
	}

	/**
	 * @testdox Should wrap unexpected provider failures in a generic error and log them.
	 */
	public function test_wraps_unexpected_provider_failures(): void {
		$provider                  = new FakeFinanceDataProvider( 'mock' );
		$provider->fetch_throwable = new \Error( 'boom' );
		$this->registry->register( $provider );

		$exception = $this->catch_exception( fn() => $this->sut->get_balances( 'mock' ) );

		$this->assertSame( 'provider_error', $exception->get_error_code() );
		$this->assertSame( 500, $exception->get_http_status() );
		$this->assertSame( 'The payment provider could not return finance data.', $exception->getMessage() );
		$this->assertSame( $provider->fetch_throwable, $exception->getPrevious() );
		$this->assertCount( 1, $this->logger->get_messages( 'error' ) );
		$this->assertStringContainsString( '"mock" failed: Error: boom', $this->logger->get_messages( 'error' )[0] );
	}

	/**
	 * @testdox Should reject a page whose items are not of the expected type.
	 */
	public function test_rejects_items_of_wrong_type(): void {
		$provider                = new FakeFinanceDataProvider( 'mock' );
		$provider->balances_page = new FinanceDataPage(
			array( new Payout( 'mock', 'po_1', 'USD', '1.00', PayoutStatus::PENDING, new \DateTimeImmutable( '2026-09-01T12:00:00+00:00' ) ) )
		);
		$this->registry->register( $provider );

		$exception = $this->catch_exception( fn() => $this->sut->get_balances( 'mock' ) );

		$this->assertSame( 'invalid_provider_data', $exception->get_error_code() );
		$this->assertSame( 500, $exception->get_http_status() );
		$this->assertCount( 1, $this->logger->get_messages( 'error' ) );
	}

	/**
	 * Run a callback that must throw a FinanceDataException and return the exception.
	 *
	 * @param callable $callback The callback.
	 * @return FinanceDataException
	 */
	private function catch_exception( callable $callback ): FinanceDataException {
		try {
			$callback();
		} catch ( FinanceDataException $e ) {
			return $e;
		}

		$this->fail( 'A FinanceDataException was expected.' );
	}

	/**
	 * Rebuild the loaded payment gateways from the current filters.
	 *
	 * WC_Payment_Gateways::init() adds to the loaded list rather than replacing it.
	 */
	private static function reload_payment_gateways(): void {
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();
	}
}
