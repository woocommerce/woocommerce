<?php
/**
 * AbilitiesTracking class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Records a Tracks event for each WooCommerce ability invocation.
 *
 * Hooks only collect data in memory. Events are sent once per request on shutdown through the batched
 * Tracks client. Ability input and output are never recorded.
 *
 * WordPress 7.0 only fires hooks around successful executions, so failures there are recorded on shutdown
 * with an `unknown` error code and no duration. WordPress 7.1+ hooks expose every outcome.
 *
 * @since 11.3.0
 */
class AbilitiesTracking {

	/**
	 * Tracks event name, without the `wcadmin_` prefix.
	 *
	 * @var string
	 */
	private const EVENT_NAME = 'ability_called';

	/**
	 * Only abilities in this namespace are tracked.
	 *
	 * @var string
	 */
	private const ABILITY_PREFIX = 'woocommerce/';

	/**
	 * Upper bound of tracked invocations per request, to keep memory and the shutdown flush bounded.
	 *
	 * @var int
	 */
	private const MAX_EVENTS_PER_REQUEST = 25;

	/**
	 * Matches the core Abilities API route that runs an ability.
	 *
	 * @var string
	 */
	private const ABILITIES_RUN_ROUTE_REGEX = '#^/wp-abilities/v\d+/abilities/.+/run$#';

	/**
	 * Value sent for properties that don't apply or are unknown.
	 *
	 * @var string
	 */
	private const NONE = 'none';

	/**
	 * Open ability invocations, innermost last.
	 *
	 * @var array<int, array{name: string, channel: string, start: float, executing: bool}>
	 */
	private array $frames = array();

	/**
	 * Event properties waiting to be sent on shutdown.
	 *
	 * @var array<int, array<string, string|int>>
	 */
	private array $events = array();

	/**
	 * Whether an MCP Adapter tool call happened in this request.
	 *
	 * @var bool
	 */
	private bool $is_mcp_request = false;

	/**
	 * Normalized MCP client name, resolved on the first MCP tool call.
	 *
	 * @var string
	 */
	private string $mcp_client = self::NONE;

	/**
	 * Whether the core Abilities API run endpoint is being dispatched.
	 *
	 * @var bool
	 */
	private bool $in_abilities_rest_run = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'handle_init' ) );
	}

	/**
	 * Attach the tracking hooks when the store allows usage tracking.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		if ( ! \WC_Site_Tracking::is_tracking_enabled() ) {
			return;
		}

		add_action( 'wp_ability_invoked', array( $this, 'handle_wp_ability_invoked' ) );
		add_filter( 'wp_pre_execute_ability', array( $this, 'handle_wp_pre_execute_ability' ), PHP_INT_MAX, 2 );
		add_filter( 'wp_ability_validate_input', array( $this, 'handle_wp_ability_validate_input' ), PHP_INT_MAX, 3 );
		add_filter( 'wp_ability_permission_result', array( $this, 'handle_wp_ability_permission_result' ), PHP_INT_MAX, 2 );
		add_action( 'wp_before_execute_ability', array( $this, 'handle_wp_before_execute_ability' ) );
		add_filter( 'wp_ability_execute_result', array( $this, 'handle_wp_ability_execute_result' ), PHP_INT_MAX, 2 );
		add_filter( 'wp_ability_validate_output', array( $this, 'handle_wp_ability_validate_output' ), PHP_INT_MAX, 3 );
		add_action( 'wp_after_execute_ability', array( $this, 'handle_wp_after_execute_ability' ) );
		add_filter( 'mcp_adapter_pre_tool_call', array( $this, 'handle_mcp_adapter_pre_tool_call' ) );
		add_filter( 'rest_request_before_callbacks', array( $this, 'handle_rest_request_before_callbacks' ), 10, 3 );
		add_filter( 'rest_request_after_callbacks', array( $this, 'handle_rest_request_after_callbacks' ), 10, 3 );
		// Runs before WC_Tracks_Client sends batched pixels at priority 20.
		add_action( 'shutdown', array( $this, 'handle_shutdown' ), 19 );
	}

	/**
	 * Handle the wp_ability_invoked action (WordPress 7.1+), which fires for every call.
	 *
	 * @internal
	 *
	 * @param string $ability_name The ability name.
	 */
	public function handle_wp_ability_invoked( $ability_name ): void {
		if ( self::is_tracked_ability( $ability_name ) ) {
			$this->open_frame( $ability_name, false );
		}
	}

	/**
	 * Handle the wp_pre_execute_ability filter (WordPress 7.1+), to record short-circuited calls.
	 *
	 * @internal
	 *
	 * @param mixed  $pre          The pre-computed result, or the core sentinel to continue execution.
	 * @param string $ability_name The ability name.
	 * @return mixed The unchanged value.
	 */
	public function handle_wp_pre_execute_ability( $pre, $ability_name ) {
		if ( ! is_a( $pre, 'WP_Filter_Sentinel' ) ) {
			$this->close_frame( $ability_name, false, $pre );
		}
		return $pre;
	}

	/**
	 * Handle the wp_ability_validate_input filter (WordPress 7.1+), to record invalid input.
	 *
	 * @internal
	 *
	 * @param mixed  $is_valid     True, or false or a WP_Error when the input is invalid.
	 * @param mixed  $input        The input. Not recorded.
	 * @param string $ability_name The ability name.
	 * @return mixed The unchanged value.
	 */
	public function handle_wp_ability_validate_input( $is_valid, $input, $ability_name ) {
		unset( $input );
		$error = self::get_validation_error( $is_valid, 'ability_invalid_input' );
		if ( $error ) {
			$this->close_frame( $ability_name, false, $error );
		}
		return $is_valid;
	}

	/**
	 * Handle the wp_ability_permission_result filter (WordPress 7.1+), to record permission denials.
	 *
	 * @internal
	 *
	 * @param mixed  $permission   True when the call is allowed.
	 * @param string $ability_name The ability name.
	 * @return mixed The unchanged value.
	 */
	public function handle_wp_ability_permission_result( $permission, $ability_name ) {
		if ( true !== $permission ) {
			// Matches the error WP_Ability::execute() returns for denied calls.
			$this->close_frame( $ability_name, false, new WP_Error( 'ability_invalid_permissions' ) );
		}
		return $permission;
	}

	/**
	 * Handle the wp_before_execute_ability action.
	 *
	 * @internal
	 *
	 * @param string $ability_name The ability name.
	 */
	public function handle_wp_before_execute_ability( $ability_name ): void {
		if ( ! self::is_tracked_ability( $ability_name ) ) {
			return;
		}

		$index = $this->find_frame( $ability_name );
		if ( null !== $index && ! $this->frames[ $index ]['executing'] ) {
			// Opened by wp_ability_invoked; time only the execution so durations match on WordPress 7.0.
			$this->frames[ $index ]['executing'] = true;
			$this->frames[ $index ]['start']     = microtime( true );
			return;
		}

		$this->open_frame( $ability_name, true );
	}

	/**
	 * Handle the wp_ability_execute_result filter (WordPress 7.1+), to record execution errors.
	 *
	 * @internal
	 *
	 * @param mixed  $result       The execution result. Not recorded.
	 * @param string $ability_name The ability name.
	 * @return mixed The unchanged value.
	 */
	public function handle_wp_ability_execute_result( $result, $ability_name ) {
		if ( is_wp_error( $result ) ) {
			$this->close_frame( $ability_name, true, $result );
		}
		return $result;
	}

	/**
	 * Handle the wp_ability_validate_output filter (WordPress 7.1+), to record invalid output.
	 *
	 * @internal
	 *
	 * @param mixed  $is_valid     True, or false or a WP_Error when the output is invalid.
	 * @param mixed  $output       The output. Not recorded.
	 * @param string $ability_name The ability name.
	 * @return mixed The unchanged value.
	 */
	public function handle_wp_ability_validate_output( $is_valid, $output, $ability_name ) {
		unset( $output );
		$error = self::get_validation_error( $is_valid, 'ability_invalid_output' );
		if ( $error ) {
			$this->close_frame( $ability_name, true, $error );
		}
		return $is_valid;
	}

	/**
	 * Handle the wp_after_execute_ability action, which fires after a successful execution.
	 *
	 * @internal
	 *
	 * @param string $ability_name The ability name.
	 */
	public function handle_wp_after_execute_ability( $ability_name ): void {
		$this->close_frame( $ability_name, true, null );
	}

	/**
	 * Handle the mcp_adapter_pre_tool_call filter, to mark the request as an MCP request.
	 *
	 * @internal
	 *
	 * @param mixed $args The tool arguments.
	 * @return mixed The unchanged arguments.
	 */
	public function handle_mcp_adapter_pre_tool_call( $args ) {
		if ( ! $this->is_mcp_request ) {
			$this->is_mcp_request = true;
			$this->mcp_client     = self::get_mcp_client_name();
		}
		return $args;
	}

	/**
	 * Handle the rest_request_before_callbacks filter, to detect the core Abilities API run endpoint.
	 *
	 * @internal
	 *
	 * @param mixed $response The response, or null to continue dispatching.
	 * @param mixed $handler  The route handler.
	 * @param mixed $request  The request.
	 * @return mixed The unchanged response.
	 */
	public function handle_rest_request_before_callbacks( $response, $handler, $request ) {
		unset( $handler );
		if ( self::is_abilities_run_request( $request ) ) {
			$this->in_abilities_rest_run = true;
		}
		return $response;
	}

	/**
	 * Handle the rest_request_after_callbacks filter.
	 *
	 * @internal
	 *
	 * @param mixed $response The response.
	 * @param mixed $handler  The route handler.
	 * @param mixed $request  The request.
	 * @return mixed The unchanged response.
	 */
	public function handle_rest_request_after_callbacks( $response, $handler, $request ) {
		unset( $handler );
		if ( self::is_abilities_run_request( $request ) ) {
			$this->in_abilities_rest_run = false;
		}
		return $response;
	}

	/**
	 * Handle the shutdown action: record calls that never finished, then send the queued events.
	 *
	 * @internal
	 */
	public function handle_shutdown(): void {
		while ( $this->frames ) {
			$this->add_event( array_pop( $this->frames ), null, false );
		}

		if ( ! $this->events ) {
			return;
		}

		$is_test = 'production' === wp_get_environment_type() ? 'false' : 'true';
		foreach ( $this->events as $event ) {
			$event['is_test'] = $is_test;
			$properties       = \WC_Tracks::get_properties( \WC_Tracks::PREFIX . self::EVENT_NAME, $event );
			// The request URL and referrer can carry ability input, e.g. in the query string of GET run requests.
			unset( $properties['_dl'], $properties['_dr'] );
			$this->send_event( $properties );
		}
		$this->events = array();
	}

	/**
	 * Queue one event in the batched Tracks client, which sends queued events on shutdown.
	 *
	 * @param array<string, mixed> $properties All Tracks properties of the event.
	 */
	protected function send_event( array $properties ): void {
		\WC_Tracks_Client::record_event_batched( $properties );
	}

	/**
	 * Start tracking an invocation.
	 *
	 * @param string $ability_name The ability name.
	 * @param bool   $executing    Whether execution already started (WordPress 7.0 path).
	 */
	private function open_frame( string $ability_name, bool $executing ): void {
		// Calls that fail without a closing hook keep their frame until shutdown, so loops must not grow this unbounded.
		if ( count( $this->frames ) >= self::MAX_EVENTS_PER_REQUEST ) {
			return;
		}

		$this->frames[] = array(
			'name'      => $ability_name,
			'channel'   => $this->get_channel(),
			'start'     => microtime( true ),
			'executing' => $executing,
		);
	}

	/**
	 * Finish tracking the innermost open invocation of an ability.
	 *
	 * Invocations opened after it are calls that failed without a closing hook (WordPress 7.0).
	 *
	 * @param mixed $ability_name The ability name.
	 * @param bool  $executing    Execution state the invocation must be in; hooks before and after execution share filters with other callers.
	 * @param mixed $result       Null for success, a WP_Error for failure, or a short-circuited result.
	 */
	private function close_frame( $ability_name, bool $executing, $result ): void {
		if ( ! self::is_tracked_ability( $ability_name ) ) {
			return;
		}

		$index = $this->find_frame( $ability_name );
		if ( null === $index || $executing !== $this->frames[ $index ]['executing'] ) {
			return;
		}

		$frame = $this->frames[ $index ];
		foreach ( array_splice( $this->frames, $index ) as $offset => $closed_frame ) {
			if ( $offset > 0 ) {
				$this->add_event( $closed_frame, null, false );
			}
		}

		$this->add_event( $frame, is_wp_error( $result ) ? $result : true, true );
	}

	/**
	 * Find the innermost open invocation of an ability.
	 *
	 * @param string $ability_name The ability name.
	 * @return int|null Index in the frames stack, or null when none is open.
	 */
	private function find_frame( string $ability_name ): ?int {
		for ( $index = count( $this->frames ) - 1; $index >= 0; $index-- ) {
			if ( $ability_name === $this->frames[ $index ]['name'] ) {
				return $index;
			}
		}
		return null;
	}

	/**
	 * Turn a finished invocation into event properties.
	 *
	 * @param array{name: string, channel: string, start: float, executing: bool} $frame        The invocation.
	 * @param true|WP_Error|null                                                  $outcome      True for success, a WP_Error for a known failure, null for an unknown failure.
	 * @param bool                                                                $has_duration Whether the duration is known.
	 */
	private function add_event( array $frame, $outcome, bool $has_duration ): void {
		if ( count( $this->events ) >= self::MAX_EVENTS_PER_REQUEST ) {
			return;
		}

		$error_code = self::NONE;
		if ( $outcome instanceof WP_Error ) {
			$error_code = self::normalize_value( (string) $outcome->get_error_code() );
		} elseif ( null === $outcome ) {
			$error_code = 'unknown';
		}

		$event = array(
			'ability_name'    => self::normalize_value( $frame['name'] ),
			'outcome'         => true === $outcome ? 'success' : 'error',
			'error_code'      => $error_code,
			'channel'         => $frame['channel'],
			'external_client' => 'mcp' === $frame['channel'] ? $this->mcp_client : self::NONE,
		);

		if ( $has_duration ) {
			$event['duration_ms'] = (int) round( ( microtime( true ) - $frame['start'] ) * 1000 );
		}

		$this->events[] = $event;
	}

	/**
	 * Identify how the ability was invoked.
	 *
	 * @return string
	 */
	private function get_channel(): string {
		if ( $this->is_mcp_request ) {
			return 'mcp';
		}
		if ( $this->in_abilities_rest_run ) {
			return 'rest';
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return 'cli';
		}
		if ( wp_doing_cron() ) {
			return 'cron';
		}
		return 'php';
	}

	/**
	 * Get the error a validation filter result stands for, matching how WP_Ability treats it.
	 *
	 * @param mixed  $is_valid     The filtered validation result.
	 * @param string $default_code Error code to use when the result is false.
	 * @return WP_Error|null The error, or null when the value is valid.
	 */
	private static function get_validation_error( $is_valid, string $default_code ): ?WP_Error {
		if ( false === $is_valid ) {
			return new WP_Error( $default_code );
		}
		return is_wp_error( $is_valid ) && $is_valid->has_errors() ? $is_valid : null;
	}

	/**
	 * Whether an ability is in the tracked namespace.
	 *
	 * @param mixed $ability_name The ability name.
	 * @return bool
	 */
	private static function is_tracked_ability( $ability_name ): bool {
		return is_string( $ability_name ) && 0 === strpos( $ability_name, self::ABILITY_PREFIX );
	}

	/**
	 * Whether a REST request targets the core Abilities API run endpoint.
	 *
	 * @param mixed $request The request.
	 * @return bool
	 */
	private static function is_abilities_run_request( $request ): bool {
		return $request instanceof WP_REST_Request && 1 === preg_match( self::ABILITIES_RUN_ROUTE_REGEX, $request->get_route() );
	}

	/**
	 * Get the MCP client name from the MCP Adapter session (2025-11-25 protocol and earlier).
	 *
	 * The name is supplied by the MCP client.
	 *
	 * @return string Normalized client name, or 'none' when unavailable.
	 */
	private static function get_mcp_client_name(): string {
		$session_manager = array( 'WP\MCP\Transport\Infrastructure\SessionManager', 'get_session' );
		$session_id      = isset( $_SERVER['HTTP_MCP_SESSION_ID'] ) && is_string( $_SERVER['HTTP_MCP_SESSION_ID'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_MCP_SESSION_ID'] ) ) : '';

		if ( '' === $session_id || ! get_current_user_id() || ! is_callable( $session_manager ) ) {
			return self::NONE;
		}

		$session = call_user_func( $session_manager, get_current_user_id(), $session_id );
		$name    = is_array( $session ) ? ( $session['client_params']['clientInfo']['name'] ?? null ) : null;

		return is_string( $name ) ? self::normalize_value( $name ) : self::NONE;
	}

	/**
	 * Convert a value to lowercase words separated by underscores, as Tracks property values expect.
	 *
	 * @param string $value The value.
	 * @return string The normalized value, or 'none' when nothing is left.
	 */
	private static function normalize_value( string $value ): string {
		$value = trim( (string) preg_replace( '/[^a-z0-9]+/', '_', strtolower( $value ) ), '_' );
		return '' === $value ? self::NONE : substr( $value, 0, 64 );
	}
}
