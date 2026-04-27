<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Controllers;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationPreferencesService;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use WC_Data_Exception;
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

		$preferences = wc_get_container()
			->get( NotificationPreferencesService::class )
			->get_preferences( get_current_user_id() );

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
		$service = wc_get_container()->get( NotificationPreferencesService::class );
		$input   = array_intersect_key( $request->get_params(), $service->get_defaults() );

		try {
			$merged = $service->save_preferences(
				get_current_user_id(),
				array_map( 'boolval', $input )
			);
		} catch ( Exception $e ) {
			return $this->convert_exception_to_wp_error( $e );
		}

		return new WP_REST_Response( $merged, WP_Http::OK );
	}

	/**
	 * Checks user is authenticated and authorized to access this endpoint.
	 *
	 * @since 10.8.0
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return bool|WP_Error
	 */
	public function authorize_as_authenticated( WP_REST_Request $request ) {
		if ( ! get_current_user_id() ) {
			return new WP_Error(
				'woocommerce_rest_cannot_view',
				__( 'Sorry, you are not allowed to do that.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! wc_get_container()->get( PushNotifications::class )->should_be_enabled() ) {
			return false;
		}

		$has_valid_role = array_reduce(
			PushNotifications::ROLES_WITH_PUSH_NOTIFICATIONS_ENABLED,
			fn ( $carry, $role ) => $this->check_permission( $request, $role ) === true ? true : $carry,
			false
		);

		return $has_valid_role ? true : false;
	}

	/**
	 * Get the accepted arguments for the POST request.
	 *
	 * Boolean preference keys are derived from the service's defaults map so
	 * this stays in lock-step with the list of supported notification types.
	 *
	 * @since 10.8.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_args(): array {
		$args     = array();
		$defaults = wc_get_container()->get( NotificationPreferencesService::class )->get_defaults();

		foreach ( array_keys( $defaults ) as $key ) {
			$args[ $key ] = array(
				'description'       => sprintf(
					/* translators: %s: notification preference key (e.g. store_order). */
					__( 'Enable the %s push notification type.', 'woocommerce' ),
					$key
				),
				'type'              => 'boolean',
				'required'          => false,
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		return $args;
	}

	/**
	 * Convert an exception thrown by the service into a WP_Error suitable for
	 * the REST response.
	 *
	 * Mirrors PushTokenRestController::convert_exception_to_wp_error: surface
	 * domain-specific WC_Data_Exception details for client-recoverable failures
	 * (non-500 codes), and log + return a generic internal-error response for
	 * 500-level / unknown failures.
	 *
	 * @since 10.8.0
	 *
	 * @param Exception $e The exception to convert.
	 * @return WP_Error
	 */
	private function convert_exception_to_wp_error( Exception $e ): WP_Error {
		if (
			$e instanceof WC_Data_Exception
			&& $e->getCode() !== WP_Http::INTERNAL_SERVER_ERROR
		) {
			return new WP_Error(
				$e->getErrorCode(),
				$e->getMessage(),
				$e->getErrorData()
			);
		}

		wc_get_container()
			->get( LegacyProxy::class )
			->call_function( 'wc_get_logger' )
			->error( (string) $e->getMessage(), array( 'source' => PushNotifications::FEATURE_NAME ) );

		return new WP_Error(
			'woocommerce_internal_error',
			'Internal server error',
			array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
		);
	}
}
