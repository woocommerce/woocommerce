<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Controllers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Services\DriverAvailabilityService;
use Automattic\WooCommerce\Internal\PushNotifications\Traits\AuthorizesPushNotificationRequests;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Controller for the REST endpoint that reports which push notification drivers
 * are installed, connected, and available, and which one is enabled.
 *
 * Stays reachable even when push notifications are disabled, so clients (e.g.
 * the mobile apps) can discover the driver state and fall back to Jetpack Sync
 * when the remote proxy isn't available.
 *
 * @since 11.1.0
 */
class PushNotificationStatusRestController extends RestApiControllerBase {
	use AuthorizesPushNotificationRequests;

	/**
	 * The root namespace for the JSON REST API endpoints.
	 *
	 * @var string
	 */
	protected string $route_namespace = 'wc-push-notifications';

	/**
	 * The REST base for the endpoints URL.
	 *
	 * @var string
	 */
	protected string $rest_base = 'status';

	/**
	 * The driver availability service.
	 *
	 * @var DriverAvailabilityService
	 */
	private DriverAvailabilityService $driver_availability_service;

	/**
	 * Initialize injected dependencies.
	 *
	 * @internal
	 *
	 * @param DriverAvailabilityService $driver_availability_service The driver availability service.
	 *
	 * @since 11.1.0
	 */
	final public function init( DriverAvailabilityService $driver_availability_service ): void {
		$this->driver_availability_service = $driver_availability_service;
	}

	/**
	 * Class identifier used by `woocommerce_rest_api_get_rest_namespaces`.
	 *
	 * Intentionally distinct from the URL `$route_namespace` — the filter keys
	 * one class per value here, so sharing the value with sibling controllers
	 * in the same module would overwrite them.
	 *
	 * @since 11.1.0
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'wc-push-notifications-status';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 *
	 * @since 11.1.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn ( WP_REST_Request $request ) => $this->run( $request, 'get_status' ),
					'permission_callback' => array( $this, 'authorize_as_authenticated_ignoring_enablement' ),
				),
			)
		);
	}

	/**
	 * Return the installed push notification drivers and the enabled one.
	 *
	 * @since 11.1.0
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_status() {
		return new WP_REST_Response(
			$this->driver_availability_service->get_status(),
			WP_Http::OK
		);
	}
}
