<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPaymentMethodDetailsService;
use RuntimeException;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsPaymentMethodDetailsService class.
 */
class WooPaymentsPaymentMethodDetailsServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsPaymentMethodDetailsService
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( WooPaymentsPaymentMethodDetailsService::class );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->reset_legacy_proxy_mocks();
		parent::tearDown();
	}

	/**
	 * @testdox Empty payment method IDs return no details.
	 */
	public function test_returns_empty_when_payment_method_id_is_empty(): void {
		$this->assertSame( array(), $this->sut->get_payment_method_details( '' ) );
	}

	/**
	 * @testdox Missing plugin runtime returns no details.
	 */
	public function test_returns_empty_when_plugin_runtime_is_absent(): void {
		$this->register_legacy_proxy_function_mocks(
			array(
				'class_exists' => function ( $class_name, $autoload = true ) {
					if ( 'WC_Payments' === ltrim( (string) $class_name, '\\' ) ) {
						return false;
					}
					return class_exists( $class_name, $autoload );
				},
			)
		);

		$this->assertSame( array(), $this->sut->get_payment_method_details( 'pm_123' ) );
	}

	/**
	 * @testdox Plugin runtime requests proxy to the WooPayments API client.
	 */
	public function test_proxies_plugin_api_client_when_plugin_runtime_is_active(): void {
		$details = array(
			'type' => 'card',
			'card' => array(
				'brand' => 'visa',
				'last4' => '4242',
			),
		);

		$this->mock_woopayments_api_client(
			new class( $details ) {
				/**
				 * Details to return.
				 *
				 * @var array<string,mixed>
				 */
				private array $details;

				/**
				 * Constructor.
				 *
				 * @param array<string,mixed> $details Details to return.
				 */
				public function __construct( array $details ) {
					$this->details = $details;
				}

				/**
				 * Get a payment method.
				 *
				 * @param string $payment_method_id Payment method ID.
				 * @return array<string,mixed>
				 */
				public function get_payment_method( string $payment_method_id ): array {
					return $this->details + array( 'requested_id' => $payment_method_id );
				}
			}
		);

		$this->assertSame( $details + array( 'requested_id' => 'pm_123' ), $this->sut->get_payment_method_details( 'pm_123' ) );
	}

	/**
	 * @testdox Client exceptions are logged and return no details.
	 */
	public function test_logs_and_returns_empty_when_client_throws(): void {
		$logger = new class() {
			/**
			 * Error log entries.
			 *
			 * @var array<int,array{message:string,context:array<string,mixed>}>
			 */
			public array $entries = array();

			/**
			 * Record an error.
			 *
			 * @param string              $message Message.
			 * @param array<string,mixed> $context Context.
			 */
			public function error( string $message, array $context = array() ): void {
				$this->entries[] = array(
					'message' => $message,
					'context' => $context,
				);
			}
		};

		$this->mock_woopayments_api_client(
			new class() {
				/**
				 * Get a payment method.
				 *
				 * @param string $payment_method_id Payment method ID.
				 * @throws RuntimeException Always thrown for this test double.
				 */
				public function get_payment_method( string $payment_method_id ) {
					throw new RuntimeException( 'API failed' );
				}
			},
			$logger
		);

		$this->assertSame( array(), $this->sut->get_payment_method_details( 'pm_123' ) );
		$this->assertCount( 1, $logger->entries );
		$this->assertSame( 'payment-info', $logger->entries[0]['context']['source'] );
		$this->assertStringContainsString( 'pm_123', $logger->entries[0]['message'] );
		$this->assertStringContainsString( 'API failed', $logger->entries[0]['message'] );
	}

	/**
	 * Mock WooPayments API client access.
	 *
	 * @param object      $api_client API client.
	 * @param object|null $logger     Optional logger.
	 */
	private function mock_woopayments_api_client( object $api_client, ?object $logger = null ): void {
		$this->register_legacy_proxy_function_mocks(
			array(
				'class_exists'  => function ( $class_name, $autoload = true ) {
					if ( 'WC_Payments' === ltrim( (string) $class_name, '\\' ) ) {
						return true;
					}
					return class_exists( $class_name, $autoload );
				},
				'wc_get_logger' => function () use ( $logger ) {
					return $logger ? $logger : wc_get_logger();
				},
			)
		);

		$this->register_legacy_proxy_static_mocks(
			array(
				'WC_Payments' => array(
					'get_payments_api_client' => function () use ( $api_client ) {
						return $api_client;
					},
				),
			)
		);
	}
}
