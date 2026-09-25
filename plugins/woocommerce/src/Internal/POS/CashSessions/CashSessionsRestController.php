<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Automattic\WooCommerce\Utilities\StringUtil;
use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API for POS cash sessions under /wc/pos/v1/cash-sessions.
 *
 * Every route needs an authenticated user with the POS "process sales" capability or manage_woocommerce.
 * Recording a cash refund also needs the POS "issue refunds" capability or manage_woocommerce.
 *
 * @since 11.3.0
 */
class CashSessionsRestController extends RestApiControllerBase {

	/**
	 * Route namespace.
	 *
	 * @var string
	 */
	protected string $route_namespace = 'wc/pos/v1';

	/**
	 * Business rules.
	 *
	 * @var CashSessionService
	 */
	private CashSessionService $service;

	/**
	 * Arguments, schemas and formatting.
	 *
	 * @var CashSessionsSchema
	 */
	private CashSessionsSchema $schema;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param CashSessionService $service Business rules.
	 * @param CashSessionsSchema $schema  Arguments, schemas and formatting.
	 */
	final public function init( CashSessionService $service, CashSessionsSchema $schema ): void {
		$this->service = $service;
		$this->schema  = $schema;
	}

	/**
	 * Get the WooCommerce REST API namespace key for the class.
	 *
	 * @return string
	 */
	protected function get_rest_api_namespace(): string {
		return 'pos-cash-sessions';
	}

	/**
	 * Register the routes.
	 *
	 * @since 11.3.0
	 */
	public function register_routes(): void {
		$permission          = fn() => $this->check_access( Capabilities::CAP_PROCESS_SALES );
		$movement_permission = fn( WP_REST_Request $request ) => $this->check_access(
			CashMovementType::CASH_REFUND === $request->get_param( 'type' ) ? Capabilities::CAP_ISSUE_REFUNDS : Capabilities::CAP_PROCESS_SALES
		);
		$session_id          = array(
			'id' => array(
				'description' => __( 'Cash session ID.', 'woocommerce' ),
				'type'        => 'integer',
				'minimum'     => 1,
			),
		);

		register_rest_route(
			$this->route_namespace,
			'/cash-sessions',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( WP_REST_Request $request ) => $this->run( $request, 'list_sessions' ),
					'permission_callback' => $permission,
					'args'                => $this->schema->get_list_sessions_args(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( WP_REST_Request $request ) => $this->run( $request, 'open_session' ),
					'permission_callback' => $permission,
					'args'                => $this->schema->get_open_session_args(),
				),
				'schema' => fn() => $this->schema->get_session_schema(),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/cash-sessions/(?P<id>[\d]+)',
			array(
				'args'   => $session_id,
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( WP_REST_Request $request ) => $this->run( $request, 'get_session' ),
					'permission_callback' => $permission,
				),
				'schema' => fn() => $this->schema->get_session_schema(),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/cash-sessions/(?P<id>[\d]+)/close',
			array(
				'args'   => $session_id,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( WP_REST_Request $request ) => $this->run( $request, 'close_session' ),
					'permission_callback' => $permission,
					'args'                => $this->schema->get_close_session_args(),
				),
				'schema' => fn() => $this->schema->get_session_schema(),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/cash-sessions/(?P<id>[\d]+)/movements',
			array(
				'args'   => $session_id,
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( WP_REST_Request $request ) => $this->run( $request, 'list_movements' ),
					'permission_callback' => $permission,
					'args'                => $this->schema->get_pagination_args(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( WP_REST_Request $request ) => $this->run( $request, 'create_movement' ),
					'permission_callback' => $movement_permission,
					'args'                => $this->schema->get_create_movement_args(),
				),
				'schema' => fn() => $this->schema->get_movement_schema(),
			)
		);

		register_rest_route(
			$this->route_namespace,
			'/cash-sessions/(?P<id>[\d]+)/drawer-events',
			array(
				'args'   => $session_id,
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => fn( WP_REST_Request $request ) => $this->run( $request, 'list_drawer_events' ),
					'permission_callback' => $permission,
					'args'                => $this->schema->get_pagination_args(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => fn( WP_REST_Request $request ) => $this->run( $request, 'create_drawer_event' ),
					'permission_callback' => $permission,
					'args'                => $this->schema->get_create_drawer_event_args(),
				),
				'schema' => fn() => $this->schema->get_drawer_event_schema(),
			)
		);
	}

	/**
	 * Run a handler and convert cash session errors into REST errors with their own status and data.
	 *
	 * @param WP_REST_Request $request     The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @param string          $method_name Protected handler method.
	 * @return WP_Error|\WP_HTTP_Response|WP_REST_Response
	 */
	protected function run( WP_REST_Request $request, string $method_name ) {
		try {
			return rest_ensure_response( $this->$method_name( $request ) );
		} catch ( CashSessionException $e ) {
			return $e->to_wp_error();
		} catch ( Exception $e ) {
			wc_get_logger()->error( StringUtil::class_name_without_namespace( static::class ) . ": when executing method $method_name: {$e->getMessage()}" );
			return $this->internal_wp_error( $e );
		}
	}

	/**
	 * List sessions.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 * @throws CashSessionException When a filter is invalid.
	 */
	protected function list_sessions( WP_REST_Request $request ): WP_REST_Response {
		$filters = array_filter(
			array(
				'device_id' => $request->get_param( 'device_id' ),
				'drawer_id' => $request->get_param( 'drawer_id' ),
				'status'    => $request->get_param( 'status' ),
			),
			fn( $value ) => null !== $value
		);
		$result  = $this->service->list_sessions( $filters, (int) $request['page'], (int) $request['per_page'] );

		return $this->paginated_response( array_map( array( $this->schema, 'format_session' ), $result['items'] ), $result['total'], (int) $request['per_page'] );
	}

	/**
	 * Open a session.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 * @throws CashSessionException When the session cannot be opened.
	 */
	protected function open_session( WP_REST_Request $request ): WP_REST_Response {
		$result   = $this->service->open_session( $this->get_body( $request, array( 'request_id', 'device_id', 'opening_amount', 'drawer_id' ) ) );
		$session  = $this->schema->format_session( $result['session'] );
		$response = new WP_REST_Response( $session, $result['created'] ? 201 : 200 );
		if ( $result['created'] ) {
			$response->header( 'Location', rest_url( sprintf( '%s/cash-sessions/%d', $this->route_namespace, $session['id'] ) ) );
		}
		return $response;
	}

	/**
	 * Get a session.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array<string, mixed>
	 * @throws CashSessionException When the session does not exist.
	 */
	protected function get_session( WP_REST_Request $request ): array {
		return $this->schema->format_session( $this->service->get_session( (int) $request['id'] ) );
	}

	/**
	 * Close a session.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return array<string, mixed>
	 * @throws CashSessionException When the session cannot be closed.
	 */
	protected function close_session( WP_REST_Request $request ): array {
		$params = $this->get_body( $request, array( 'request_id', 'expected_revision', 'counted_amount', 'note' ) );
		return $this->schema->format_session( $this->service->close_session( (int) $request['id'], $params ) );
	}

	/**
	 * List the movements of a session.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 * @throws CashSessionException When the session does not exist.
	 */
	protected function list_movements( WP_REST_Request $request ): WP_REST_Response {
		$result = $this->service->list_movements( (int) $request['id'], (int) $request['page'], (int) $request['per_page'] );
		$items  = array_map( fn( array $row ) => $this->schema->format_movement( $row, $result['precision'] ), $result['items'] );

		return $this->paginated_response( $items, $result['total'], (int) $request['per_page'] );
	}

	/**
	 * Record a movement.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 * @throws CashSessionException When the movement cannot be recorded.
	 */
	protected function create_movement( WP_REST_Request $request ): WP_REST_Response {
		$params    = $this->get_body( $request, array( 'request_id', 'type', 'amount', 'reason', 'order_id', 'refund_id', 'occurred_at' ) );
		$result    = $this->service->record_movement( (int) $request['id'], $params );
		$precision = (int) $result['session']['row']['currency_precision'];

		return new WP_REST_Response( $this->schema->format_movement( $result['movement'], $precision ), $result['created'] ? 201 : 200 );
	}

	/**
	 * List the drawer events of a session.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 * @throws CashSessionException When the session does not exist.
	 */
	protected function list_drawer_events( WP_REST_Request $request ): WP_REST_Response {
		$result = $this->service->list_drawer_events( (int) $request['id'], (int) $request['page'], (int) $request['per_page'] );

		return $this->paginated_response( array_map( array( $this->schema, 'format_drawer_event' ), $result['items'] ), $result['total'], (int) $request['per_page'] );
	}

	/**
	 * Record a drawer event.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 * @throws CashSessionException When the event cannot be recorded.
	 */
	protected function create_drawer_event( WP_REST_Request $request ): WP_REST_Response {
		$params = $this->get_body(
			$request,
			array( 'request_id', 'type', 'reason', 'occurred_at', 'drawer_id', 'correlation_id', 'order_id', 'refund_id', 'movement_id' )
		);
		$result = $this->service->record_drawer_event( (int) $request['id'], $params );

		return new WP_REST_Response( $this->schema->format_drawer_event( $result['event'] ), $result['created'] ? 201 : 200 );
	}

	/**
	 * Check authentication and capabilities. Runs on every request, including retries.
	 *
	 * @param string $capability POS capability that grants access; manage_woocommerce also does.
	 * @return true|WP_Error
	 */
	private function check_access( string $capability ) {
		if ( is_user_logged_in() && ( current_user_can( $capability ) || current_user_can( 'manage_woocommerce' ) ) ) {
			return true;
		}

		return new WP_Error(
			'woocommerce_rest_cannot_access_cash_sessions',
			__( 'Sorry, you are not allowed to manage cash sessions.', 'woocommerce' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Collect the given body parameters that were supplied.
	 *
	 * @param WP_REST_Request $request The request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @param string[]        $keys    Parameter names.
	 * @return array<string, mixed>
	 */
	private function get_body( WP_REST_Request $request, array $keys ): array {
		$params = array();
		foreach ( $keys as $key ) {
			$value = $request->get_param( $key );
			if ( null !== $value ) {
				$params[ $key ] = $value;
			}
		}
		return $params;
	}

	/**
	 * Build a list response with WordPress pagination headers.
	 *
	 * @param array<int, array<string, mixed>> $items    Formatted items.
	 * @param int                              $total    Total matching items.
	 * @param int                              $per_page Page size.
	 * @return WP_REST_Response
	 */
	private function paginated_response( array $items, int $total, int $per_page ): WP_REST_Response {
		$response = new WP_REST_Response( $items, 200 );
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) ceil( $total / max( 1, $per_page ) ) );
		return $response;
	}
}
