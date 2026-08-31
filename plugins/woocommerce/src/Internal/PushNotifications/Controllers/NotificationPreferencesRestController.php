<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Controllers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationPreferencesService;
use Automattic\WooCommerce\Internal\PushNotifications\Traits\AuthorizesPushNotificationRequests;
use Automattic\WooCommerce\Internal\PushNotifications\Traits\ConvertsExceptionsToWpError;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Exception;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Controller for the REST endpoints associated with the current user's
 * push notification preferences.
 *
 * @since 10.8.0
 */
class NotificationPreferencesRestController extends RestApiControllerBase {
	use AuthorizesPushNotificationRequests;
	use ConvertsExceptionsToWpError;

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
	protected string $rest_base = 'preferences';

	/**
	 * The notification preferences service.
	 *
	 * @var NotificationPreferencesService
	 */
	private NotificationPreferencesService $preferences_service;

	/**
	 * Initialize injected dependencies.
	 *
	 * @internal
	 *
	 * @param NotificationPreferencesService $preferences_service The preferences service.
	 *
	 * @since 10.8.0
	 */
	final public function init( NotificationPreferencesService $preferences_service ): void {
		$this->preferences_service = $preferences_service;
	}

	/**
	 * Class identifier used by `woocommerce_rest_api_get_rest_namespaces`.
	 *
	 * Intentionally distinct from the URL `$route_namespace` — the filter keys
	 * one class per value here, so sharing the value with sibling controllers
	 * (e.g. `PushTokenRestController`) would overwrite them.
	 *
	 * @since 10.8.0
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'wc-push-notifications-preferences';
	}

	/**
	 * Register the REST API endpoints handled by this controller.
	 *
	 * @since 10.8.0
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
					'callback'            => fn ( WP_REST_Request $request ) => $this->run( $request, 'get_preferences' ),
					'permission_callback' => array( $this, 'authorize_as_authenticated' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => fn ( WP_REST_Request $request ) => $this->run( $request, 'update_preferences' ),
					'permission_callback' => array( $this, 'authorize_as_authenticated' ),
					'args'                => $this->get_args(),
				),
			)
		);
	}

	/**
	 * Return the current user's notification preferences.
	 *
	 * @since 10.8.0
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_preferences( WP_REST_Request $request ) {
		unset( $request );

		$preferences = $this->preferences_service->get_preferences( get_current_user_id() );

		return new WP_REST_Response( $preferences, WP_Http::OK );
	}

	/**
	 * Partially update the current user's notification preferences and return
	 * the merged result.
	 *
	 * @since 10.8.0
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_preferences( WP_REST_Request $request ) {
		try {
			$merged = $this->preferences_service->save_preferences(
				get_current_user_id(),
				$request->get_params()
			);
		} catch ( Exception $e ) {
			return $this->convert_exception_to_wp_error( $e );
		}

		return new WP_REST_Response( $merged, WP_Http::OK );
	}

	/**
	 * Get the accepted arguments for the POST request.
	 *
	 * Each preference is an object so future sub-fields can be added without
	 * a schema-version bump. Keys are derived from the service's defaults so
	 * this stays in lock-step with the list of supported notification types.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_args(): array {
		$args     = array();
		$defaults = $this->preferences_service->get_defaults();

		foreach ( $defaults as $key => $shape ) {
			$properties = array(
				'enabled' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether this notification type is enabled.', 'woocommerce' ),
				),
			);

			if ( array_key_exists( 'min_amount', $shape ) ) {
				$properties['min_amount'] = array(
					'type'             => array( 'number', 'null' ),
					'minimum'          => 0,
					'exclusiveMinimum' => true,
					'description'      => __( 'Minimum order amount required to trigger this notification, or null to disable the threshold.', 'woocommerce' ),
				);
			}

			if ( array_key_exists( 'max_rating', $shape ) ) {
				$properties['max_rating'] = array(
					'type'        => array( 'integer', 'null' ),
					'minimum'     => 1,
					'maximum'     => 5,
					'description' => __( 'Maximum star rating that triggers a review notification (1–5), or null to disable the threshold.', 'woocommerce' ),
				);
			}

			$boolean_sub_fields = array( 'low_stock', 'out_of_stock', 'on_backorder' );
			foreach ( $boolean_sub_fields as $sub_field ) {
				if ( array_key_exists( $sub_field, $shape ) ) {
					$properties[ $sub_field ] = array(
						'type'        => 'boolean',
						'description' => sprintf(
							/* translators: %s: sub-field name (e.g. low_stock). */
							__( 'Whether %s notifications are enabled for this type.', 'woocommerce' ),
							$sub_field
						),
					);
				}
			}

			$args[ $key ] = array(
				'description'       => sprintf(
					/* translators: %s: notification preference key (e.g. store_order). */
					__( 'Preferences for the %s push notification type.', 'woocommerce' ),
					$key
				),
				'type'              => 'object',
				'properties'        => $properties,
				'required'          => false,
				'validate_callback' => 'rest_validate_request_arg',
			);
		}//end foreach

		return $args;
	}
}
