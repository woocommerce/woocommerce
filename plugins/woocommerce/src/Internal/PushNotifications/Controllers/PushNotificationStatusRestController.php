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
 * the mobile apps) can discover which drivers are installed and configured, and
 * know to fall back to Jetpack Sync when the remote proxy is not set up on this
 * store.
 *
 * Reports configuration state only. A driver reported as available is installed
 * and connected; whether a given notification is delivered promptly is a separate
 * concern, covered by delivery logging rather than by this endpoint.
 *
 * @since 11.2.0
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
	 * @since 11.2.0
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
	 * @since 11.2.0
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'wc-push-notifications-status';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 *
	 * @since 11.2.0
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
					'permission_callback' => array( $this, 'authorize_as_from_wpcom_or_logged_in_user' ),
				),
				// A sibling of the endpoint array, not a key inside it. WP_REST_Server
				// promotes only non-numeric top-level keys into its route options, and
				// reads the schema exclusively from there, so nesting this one level
				// deeper drops it silently.
				'schema' => array( $this, 'get_schema' ),
			)
		);
	}

	/**
	 * The schema for the status response.
	 *
	 * `installed_drivers` is keyed by driver identifier, so its shape is
	 * described with `additionalProperties` rather than named properties.
	 *
	 * @since 11.2.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_schema(): array {
		$driver_flags = array(
			'type'       => 'object',
			'properties' => array(
				'connected' => array(
					'description' => __( "Whether the driver's underlying connection is present. Null when the check could not be performed.", 'woocommerce' ),
					'type'        => array( 'boolean', 'null' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'enabled'   => array(
					'description' => __( 'Whether the driver itself is switched on. Null when the check could not be performed.', 'woocommerce' ),
					'type'        => array( 'boolean', 'null' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'available' => array(
					'description' => __( 'Whether the driver is definitively both connected and enabled. An undetermined flag makes the driver unavailable.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'push_notification_status',
			'type'       => 'object',
			'properties' => array(
				'installed_drivers' => array(
					'description'          => __( 'The installed notification drivers, keyed by driver identifier.', 'woocommerce' ),
					'type'                 => 'object',
					'context'              => array( 'view' ),
					'readonly'             => true,
					'additionalProperties' => $driver_flags,
				),
				'preferred_driver'  => array(
					'description' => __( 'The driver the site prefers, being the first available one in precedence order, or null when none are available. Not a statement about what is delivering notifications to a given app.', 'woocommerce' ),
					'type'        => array( 'string', 'null' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);
	}

	/**
	 * Return the installed push notification drivers and the preferred one.
	 *
	 * @since 11.2.0
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
