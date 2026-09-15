<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Controllers;

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Connection\Rest_Authentication;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\Notification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\StepLogQuery;
use Automattic\WooCommerce\Internal\PushNotifications\Traits\AuthorizesPushNotificationRequests;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Read-only REST endpoints through which Mission Control reads the push
 * notification step log: the journey of one notification, and the attempts
 * made against one device or one user's devices.
 *
 * Only WPCOM can call these. Every response carries the store's logging state
 * and the earliest time the read covered, so a screen with no rows can say why.
 *
 * @since 11.3.0
 */
class StepLogRestController extends RestApiControllerBase {
	use AuthorizesPushNotificationRequests;

	/**
	 * How far back a request reads when it gives no `from`.
	 */
	const DEFAULT_RANGE_SECONDS = 2 * DAY_IN_SECONDS;

	/**
	 * The longest range a request may ask for.
	 *
	 * The file reader opens one path per day per rotation per source, so an
	 * unbounded range is a stat call per day of however long the caller asked
	 * for. Set past the 30 day default log retention, since nothing older than
	 * that survives to be read anyway.
	 */
	const MAX_RANGE_SECONDS = 90 * DAY_IN_SECONDS;

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
	protected string $rest_base = 'step-logs';

	/**
	 * Rows returned when the caller asks for no particular number.
	 */
	const DEFAULT_LIMIT = 100;

	/**
	 * Most rows a caller may ask for in one page.
	 */
	const MAX_LIMIT = 500;

	/**
	 * The step log query service.
	 *
	 * @var StepLogQuery
	 */
	private StepLogQuery $query;

	/**
	 * Initialize injected dependencies.
	 *
	 * @internal
	 *
	 * @param StepLogQuery $query The step log query service.
	 *
	 * @since 11.3.0
	 */
	final public function init( StepLogQuery $query ): void {
		$this->query = $query;
	}

	/**
	 * Class identifier used by `woocommerce_rest_api_get_rest_namespaces`.
	 *
	 * Distinct from the URL namespace, because the filter keys one class per
	 * value and sibling controllers share the URL namespace.
	 *
	 * @return string
	 *
	 * @since 11.3.0
	 */
	protected function get_rest_api_namespace(): string {
		return 'wc-push-notifications-step-logs';
	}

	/**
	 * Registers the routes.
	 *
	 * @return void
	 *
	 * @since 11.3.0
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->route_namespace,
			$this->rest_base . '/site',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn ( WP_REST_Request $request ) => $this->run( $request, 'get_site' ),
					'permission_callback' => array( $this, 'authorize_as_from_wpcom' ),
					'args'                => $this->get_range_args(),
				),
			)
		);

		register_rest_route(
			$this->route_namespace,
			$this->rest_base . '/notifications/(?P<type>[a-z_]+)/(?P<resource_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn ( WP_REST_Request $request ) => $this->run( $request, 'get_notification' ),
					'permission_callback' => array( $this, 'authorize_as_from_wpcom' ),
					'args'                => array_merge(
						array(
							'type'        => array(
								'type'              => 'string',
								'required'          => true,
								'enum'              => array_keys( Notification::NOTIFICATION_CLASSES ),
								'sanitize_callback' => 'sanitize_key',
								'validate_callback' => 'rest_validate_request_arg',
							),
							'resource_id' => array(
								'type'              => 'integer',
								'required'          => true,
								'minimum'           => 1,
								'sanitize_callback' => 'absint',
								'validate_callback' => 'rest_validate_request_arg',
							),
						),
						$this->get_range_args()
					),
				),
			)
		);

		register_rest_route(
			$this->route_namespace,
			$this->rest_base . '/tokens/(?P<token_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn ( WP_REST_Request $request ) => $this->run( $request, 'get_token' ),
					'permission_callback' => array( $this, 'authorize_as_from_wpcom' ),
					'args'                => array_merge(
						array(
							'token_id' => array(
								'type'              => 'integer',
								'required'          => true,
								'minimum'           => 1,
								'sanitize_callback' => 'absint',
								'validate_callback' => 'rest_validate_request_arg',
							),
						),
						$this->get_range_args()
					),
				),
			)
		);

		register_rest_route(
			$this->route_namespace,
			$this->rest_base . '/users/(?P<user_id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn ( WP_REST_Request $request ) => $this->run( $request, 'get_user' ),
					'permission_callback' => array( $this, 'authorize_as_from_wpcom' ),
					'args'                => array_merge(
						array(
							'user_id' => array(
								'type'              => 'integer',
								'required'          => true,
								'minimum'           => 1,
								'sanitize_callback' => 'absint',
								'validate_callback' => 'rest_validate_request_arg',
							),
						),
						$this->get_range_args()
					),
				),
			)
		);
	}

	/**
	 * Handles the notification journey request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 */
	protected function get_notification( WP_REST_Request $request ): WP_REST_Response {
		list( $from, $to ) = $this->get_range( $request );

		return new WP_REST_Response(
			$this->query->for_notification(
				(string) $request->get_param( 'type' ),
				(int) $request->get_param( 'resource_id' ),
				$from,
				$to,
				$this->get_limit( $request )
			)
		);
	}

	/**
	 * Handles the whole-store request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 */
	protected function get_site( WP_REST_Request $request ): WP_REST_Response {
		list( $from, $to ) = $this->get_range( $request );

		return new WP_REST_Response( $this->query->for_site( $from, $to, $this->get_limit( $request ) ) );
	}

	/**
	 * Handles the device request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 */
	protected function get_token( WP_REST_Request $request ): WP_REST_Response {
		list( $from, $to ) = $this->get_range( $request );

		return new WP_REST_Response( $this->query->for_token( (int) $request->get_param( 'token_id' ), $from, $to, $this->get_limit( $request ) ) );
	}

	/**
	 * Handles the user request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 */
	protected function get_user( WP_REST_Request $request ): WP_REST_Response {
		list( $from, $to ) = $this->get_range( $request );

		return new WP_REST_Response( $this->query->for_user( (int) $request->get_param( 'user_id' ), $from, $to, $this->get_limit( $request ) ) );
	}

	/**
	 * The `from` and `to` arguments every route accepts, as Unix timestamps.
	 *
	 * @return array
	 */
	private function get_range_args(): array {
		return array(
			'limit' => array(
				'description'       => __( 'Rows to return, newest first. A page carries every row of its last second, so it can exceed this.', 'woocommerce' ),
				'type'              => 'integer',
				'default'           => self::DEFAULT_LIMIT,
				'minimum'           => 1,
				'maximum'           => self::MAX_LIMIT,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'from'  => array(
				'description'       => __( 'Earliest Unix timestamp to include. Defaults to 48 hours before `to`, and is clamped to 90 days before it.', 'woocommerce' ),
				'type'              => 'integer',
				'minimum'           => 0,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'to'    => array(
				'description'       => __( 'Latest Unix timestamp to include. Defaults to now.', 'woocommerce' ),
				'type'              => 'integer',
				'minimum'           => 0,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * The page size the request asked for, or the default.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return int
	 */
	private function get_limit( WP_REST_Request $request ): int {
		$limit = (int) $request->get_param( 'limit' );

		return $limit > 0 ? $limit : self::DEFAULT_LIMIT;
	}

	/**
	 * Resolves the requested time range, applying the defaults.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array{0: int, 1: int}
	 */
	private function get_range( WP_REST_Request $request ): array {
		$to   = (int) $request->get_param( 'to' );
		$from = (int) $request->get_param( 'from' );

		if ( $to <= 0 ) {
			$to = time();
		}

		if ( $from <= 0 ) {
			$from = $to - self::DEFAULT_RANGE_SECONDS;
		}

		$from = min( $from, $to );

		return array( max( $from, $to - self::MAX_RANGE_SECONDS ), $to );
	}
}
