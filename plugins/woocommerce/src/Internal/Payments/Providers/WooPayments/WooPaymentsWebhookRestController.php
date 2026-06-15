<?php
/**
 * WooPaymentsWebhookRestController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use InvalidArgumentException;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Native WooPayments webhook REST controller.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsWebhookRestController implements RegisterHooksInterface {

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Event ingestor.
	 *
	 * @var WooPaymentsEventIngestor
	 */
	private WooPaymentsEventIngestor $event_ingestor;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter        Runtime owner arbiter.
	 * @param WooPaymentsEventIngestor     $event_ingestor Event ingestor.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsEventIngestor $event_ingestor ): void {
		$this->arbiter        = $arbiter;
		$this->event_ingestor = $event_ingestor;
	}

	/**
	 * Register REST hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		if ( false === has_action( 'rest_api_init', array( $this, 'register_routes' ) ) ) {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}

	/**
	 * Register the WooPayments-compatible webhook route.
	 */
	public function register_routes(): void {
		register_rest_route(
			'wc/v3',
			'/payments/webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
			)
		);
	}

	/**
	 * Handle a webhook delivery.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response
	 */
	public function handle_webhook( WP_REST_Request $request ): WP_REST_Response {
		$payload = $request->get_json_params();
		if ( ! is_array( $payload ) ) {
			$payload = $request->get_body_params();
		}

		try {
			$this->event_ingestor->process( is_array( $payload ) ? $payload : array() );
			return new WP_REST_Response( array( 'result' => 'success' ), 200 );
		} catch ( InvalidArgumentException $exception ) {
			return new WP_REST_Response( array( 'result' => 'bad_request' ), 400 );
		} catch ( Throwable $exception ) {
			return new WP_REST_Response( array( 'result' => 'error' ), 500 );
		}
	}
}
