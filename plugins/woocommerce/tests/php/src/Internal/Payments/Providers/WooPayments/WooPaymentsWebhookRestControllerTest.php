<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsEventIngestor;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsWebhookRestController;
use InvalidArgumentException;
use RuntimeException;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Tests for the WooPaymentsWebhookRestController class.
 */
class WooPaymentsWebhookRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsWebhookRestController
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( WooPaymentsWebhookRestController::class );
		$this->remove_rest_hook();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->remove_rest_hook();
		remove_all_filters( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED );
		$this->reset_legacy_proxy_mocks();
		parent::tearDown();
	}

	/**
	 * @testdox The webhook route is not registered when the plugin owns runtime.
	 */
	public function test_registers_no_route_when_plugin_owns_runtime(): void {
		$this->fake_plugin( true );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );

		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
	}

	/**
	 * @testdox The controller registers POST /wc/v3/payments/webhook when native owns runtime.
	 */
	public function test_registers_wc_v3_payments_webhook_when_native_owns_runtime(): void {
		$this->fake_plugin( false );
		add_filter( NativePaymentsRuntimeArbiter::FILTER_NATIVE_ENABLED, '__return_true' );

		$this->sut->register();
		// The controller registers routes on the REST API initialization hook in production.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wc/v3/payments/webhook', $routes );
		$this->assertRouteHasPostMethod( $routes['/wc/v3/payments/webhook'] );
	}

	/**
	 * @testdox Successful webhook processing returns the WooPayments success envelope.
	 */
	public function test_success_response_matches_woopayments_envelope(): void {
		$payload    = array( 'type' => 'customer.created' );
		$controller = $this->create_controller_with_ingestor(
			new class() extends WooPaymentsEventIngestor {
				/**
				 * Processed payloads.
				 *
				 * @var array<int,array<string,mixed>>
				 */
				public array $processed_payloads = array();

				/**
				 * Process a payload.
				 *
				 * @param array<string,mixed> $event Event payload.
				 */
				public function process( array $event ): void {
					$this->processed_payloads[] = $event;
				}
			}
		);
		$request    = $this->create_post_request( $payload );
		$response   = $controller->handle_webhook( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'result' => 'success' ), $response->get_data() );
	}

	/**
	 * @testdox Bad webhook payloads return the WooPayments bad_request envelope.
	 */
	public function test_bad_payload_returns_bad_request_envelope(): void {
		$logger     = $this->create_recording_logger();
		$controller = $this->create_controller_with_ingestor(
			new class() extends WooPaymentsEventIngestor {
				/**
				 * Process a payload.
				 *
				 * @param array<string,mixed> $event Event payload.
				 */
				public function process( array $event ): void {
					throw new InvalidArgumentException( 'bad payload' );
				}
			},
			$logger
		);

		$response = $controller->handle_webhook( $this->create_post_request( array( 'type' => 'bad' ) ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( array( 'result' => 'bad_request' ), $response->get_data() );
		$this->assertCount( 1, $logger->entries );
		$this->assertSame( 'native-payments-webhook', $logger->entries[0]['context']['source'] );
		$this->assertStringContainsString( 'bad payload', $logger->entries[0]['message'] );
	}

	/**
	 * @testdox Processing exceptions return the WooPayments error envelope.
	 */
	public function test_processing_exception_returns_error_envelope(): void {
		$logger     = $this->create_recording_logger();
		$controller = $this->create_controller_with_ingestor(
			new class() extends WooPaymentsEventIngestor {
				/**
				 * Process a payload.
				 *
				 * @param array<string,mixed> $event Event payload.
				 */
				public function process( array $event ): void {
					throw new RuntimeException( 'server failed' );
				}
			},
			$logger
		);

		$response = $controller->handle_webhook( $this->create_post_request( array( 'type' => 'bad' ) ) );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( array( 'result' => 'error' ), $response->get_data() );
		$this->assertCount( 1, $logger->entries );
		$this->assertSame( 'native-payments-webhook', $logger->entries[0]['context']['source'] );
		$this->assertStringContainsString( 'server failed', $logger->entries[0]['message'] );
	}

	/**
	 * @testdox Logger failures do not replace the webhook error envelope.
	 */
	public function test_logger_failures_do_not_replace_webhook_error_envelope(): void {
		$controller = $this->create_controller_with_ingestor(
			new class() extends WooPaymentsEventIngestor {
				/**
				 * Process a payload.
				 *
				 * @param array<string,mixed> $event Event payload.
				 */
				public function process( array $event ): void {
					throw new RuntimeException( 'server failed' );
				}
			},
			new class() {
				/**
				 * Record an error log entry.
				 *
				 * @param string              $message Log message.
				 * @param array<string,mixed> $context Log context.
				 */
				public function error( string $message, array $context = array() ): void {
					unset( $message, $context );

					throw new RuntimeException( 'logger failed' );
				}
			}
		);

		$response = $controller->handle_webhook( $this->create_post_request( array( 'type' => 'bad' ) ) );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( array( 'result' => 'error' ), $response->get_data() );
	}

	/**
	 * Remove the controller REST hook.
	 */
	private function remove_rest_hook(): void {
		remove_action( 'rest_api_init', array( $this->sut, 'register_routes' ) );
	}

	/**
	 * Control every WooPayments-plugin detection signal in a single mock registration.
	 *
	 * @param bool $active Whether the WooPayments plugin should appear active.
	 */
	private function fake_plugin( bool $active ): void {
		$entry = NativePaymentsRuntimeArbiter::PLUGIN_FILE;
		$this->register_legacy_proxy_function_mocks(
			array(
				'get_option'      => function ( $name, $default_value = false ) use ( $active, $entry ) {
					if ( 'active_plugins' === $name ) {
						return $active ? array( $entry ) : array();
					}
					return get_option( $name, $default_value );
				},
				'get_site_option' => function ( $name, $default_value = false ) {
					if ( 'active_sitewide_plugins' === $name ) {
						return array();
					}
					return get_site_option( $name, $default_value );
				},
				'class_exists'    => function ( $class_name, $autoload = true ) use ( $active ) {
					if ( 'WC_Payments' === ltrim( (string) $class_name, '\\' ) ) {
						return $active;
					}
					return class_exists( $class_name, $autoload );
				},
			)
		);
	}

	/**
	 * Create a controller with a supplied ingestor.
	 *
	 * @param WooPaymentsEventIngestor $ingestor Ingestor test double.
	 * @param object|null              $logger   Optional logger test double.
	 * @return WooPaymentsWebhookRestController
	 */
	private function create_controller_with_ingestor( WooPaymentsEventIngestor $ingestor, ?object $logger = null ): WooPaymentsWebhookRestController {
		$runtime = new WooPaymentsLegacyRuntime();
		$runtime->init( new LegacyRuntimeProxy( true, null, null, null, $logger ) );

		$controller = new WooPaymentsWebhookRestController();
		$controller->init( wc_get_container()->get( NativePaymentsRuntimeArbiter::class ), $ingestor, $runtime );

		return $controller;
	}

	/**
	 * Create a recording logger test double.
	 *
	 * @return object
	 */
	private function create_recording_logger(): object {
		return new class() {
			/**
			 * Logged entries.
			 *
			 * @var array<int,array{message:string,context:array<string,mixed>}>
			 */
			public array $entries = array();

			/**
			 * Record an error log entry.
			 *
			 * @param string              $message Log message.
			 * @param array<string,mixed> $context Log context.
			 */
			public function error( string $message, array $context = array() ): void {
				$this->entries[] = array(
					'message' => $message,
					'context' => $context,
				);
			}
		};
	}

	/**
	 * Create a POST request with body params.
	 *
	 * @param array<string,mixed> $payload Payload.
	 * @return WP_REST_Request
	 */
	private function create_post_request( array $payload ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/webhook' );
		$request->set_body_params( $payload );

		return $request;
	}

	/**
	 * Assert a route handler accepts POST.
	 *
	 * @param array<int,array<string,mixed>> $route_handlers Route handlers.
	 */
	private function assertRouteHasPostMethod( array $route_handlers ): void {
		foreach ( $route_handlers as $handler ) {
			if ( isset( $handler['methods'][ WP_REST_Server::CREATABLE ] ) ) {
				$this->assertTrue( $handler['methods'][ WP_REST_Server::CREATABLE ] );
				return;
			}
		}

		$this->fail( 'Route does not accept POST.' );
	}
}
