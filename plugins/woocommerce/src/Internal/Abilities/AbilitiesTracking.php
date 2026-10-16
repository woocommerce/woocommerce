<?php
/**
 * AbilitiesTracking class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\Jetpack\Status;
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
 * with an `unknown` error code and no duration. WordPress 7.1+ hooks expose the other outcomes. A missing
 * input schema is returned before those hooks and stays `unknown`.
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
	private const ABILITIES_RUN_ROUTE_REGEX = '#^/wp-abilities/v\d+/abilities/.+/run$#i';

	/**
	 * Known MCP clients, as normalized name tokens mapped to the recorded value. Checked in order.
	 *
	 * @var array<string, string>
	 */
	private const KNOWN_MCP_CLIENTS = array(
		'claude_code'          => 'claude_code',
		'claude'               => 'claude',
		'codex'                => 'codex',
		'chatgpt'              => 'chatgpt',
		'openai'               => 'chatgpt',
		'cursor'               => 'cursor',
		'windsurf'             => 'windsurf',
		'vscode'               => 'vscode',
		'visual_studio_code'   => 'vscode',
		'copilot'              => 'copilot',
		'gemini'               => 'gemini',
		'cline'                => 'cline',
		'goose'                => 'goose',
		'zed'                  => 'zed',
		'mcp_inspector'        => 'mcp_inspector',
		'mcp_wordpress_remote' => 'mcp_wordpress_remote',
	);

	/**
	 * Value sent for properties that don't apply or are unknown.
	 *
	 * @var string
	 */
	private const NONE = 'none';

	/**
	 * Open ability invocations, innermost last.
	 *
	 * @var array<int, array{name: string, channel: string, rest_namespace: string, external_client: string, start: float, executing: bool}>
	 */
	private array $frames = array();

	/**
	 * Tracks properties of events waiting to be sent.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $events = array();

	/**
	 * Whether this request is an MCP request: an MCP Adapter tool call happened, or the request has MCP HTTP headers.
	 *
	 * @var bool|null Null until checked.
	 */
	private ?bool $is_mcp_request = null;

	/**
	 * MCP client recorded with events, resolved when first needed.
	 *
	 * @var string|null
	 */
	private ?string $external_client = null;

	/**
	 * REST requests being dispatched, outermost first.
	 *
	 * @var array<int, WP_REST_Request<array<string, mixed>>>
	 */
	private array $rest_requests = array();

	/**
	 * Number of invocations opened when each REST request in $rest_requests started dispatching.
	 *
	 * @var int[]
	 */
	private array $rest_frames_opened = array();

	/**
	 * Number of events not recorded because the per-request limit was reached.
	 *
	 * @var int
	 */
	private int $events_dropped = 0;

	/**
	 * Number of invocations opened in this request.
	 *
	 * @var int
	 */
	private int $frames_opened = 0;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'handle_init' ) );
	}

	/**
	 * Attach the tracking hooks when the store allows usage tracking, or on multisite.
	 *
	 * Consent is per site and a request can switch sites, so on multisite the hooks are always attached and consent is
	 * checked for each call instead.
	 *
	 * @internal
	 */
	public function handle_init(): void {
		if ( ! is_multisite() && ! self::is_tracking_allowed() ) {
			return;
		}

		add_action( 'wp_ability_invoked', array( $this, 'handle_wp_ability_invoked' ) );
		add_filter( 'wp_ability_normalize_input', array( $this, 'handle_wp_ability_normalize_input' ), PHP_INT_MAX, 2 );
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
	 * Handle the wp_ability_normalize_input filter (WordPress 7.1+), to record a rejected normalization.
	 *
	 * A missing input schema never reaches this filter. That failure stays unclosed and is recorded as unknown.
	 *
	 * @internal
	 *
	 * @param mixed  $input        The normalized input, or a WP_Error when a filter rejected it. Not recorded.
	 * @param string $ability_name The ability name.
	 * @return mixed The unchanged value.
	 */
	public function handle_wp_ability_normalize_input( $input, $ability_name ) {
		if ( is_wp_error( $input ) ) {
			$this->close_frame( $ability_name, false, $input );
		}
		return $input;
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
		// WP_Filter_Sentinel ships with WordPress 7.1, which the PHPStan WordPress stubs predate (see #69841).
		if ( ! $pre instanceof \WP_Filter_Sentinel ) { // @phpstan-ignore class.notFound
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

		$index = $this->find_frame( $ability_name, false );
		if ( null !== $index ) {
			// Opened by wp_ability_invoked; time only the execution so durations match on WordPress 7.0.
			$frame                  = $this->frames[ $index ];
			$frame['executing']     = true;
			$frame['start']         = microtime( true );
			$this->frames[ $index ] = $frame;
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
	 * Covers MCP Adapter transports without MCP HTTP headers, such as STDIO.
	 *
	 * @internal
	 *
	 * @param mixed $args The tool arguments.
	 * @return mixed The unchanged arguments.
	 */
	public function handle_mcp_adapter_pre_tool_call( $args ) {
		$this->is_mcp_request = true;
		return $args;
	}

	/**
	 * Handle the rest_request_before_callbacks filter, to know which REST routes are being dispatched.
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
		if ( $request instanceof WP_REST_Request ) {
			$this->rest_requests[]      = $request;
			$this->rest_frames_opened[] = $this->frames_opened;
		}
		return $response;
	}

	/**
	 * Handle the rest_request_after_callbacks filter.
	 *
	 * Also records run requests that the Abilities API rejected before execution, for example on permission checks.
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
		if ( ! $request instanceof WP_REST_Request ) {
			return $response;
		}

		$frames_opened_before = (int) array_pop( $this->rest_frames_opened );
		$ability_name         = $request['name'];
		if (
			is_wp_error( $response )
			// Abilities not exposed to REST look like unknown routes to callers; probing them isn't ability usage.
			&& 'rest_ability_not_found' !== $response->get_error_code()
			&& $this->frames_opened === $frames_opened_before
			&& 1 === preg_match( self::ABILITIES_RUN_ROUTE_REGEX, $request->get_route() )
			&& self::is_tracked_ability( $ability_name )
			&& wp_has_ability( $ability_name )
			&& self::is_tracking_allowed()
		) {
			$this->add_event( $this->create_frame( $ability_name, false ), $response, false );
		}

		array_pop( $this->rest_requests );
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

		$this->flush_events();
	}

	/**
	 * Queue one event in the batched Tracks client, which sends queued events on shutdown.
	 *
	 * @param array<string, mixed> $properties All Tracks properties of the event.
	 */
	protected function send_event( array $properties ): void {
		$user = wp_get_current_user();
		// WC_Tracks::record_event() skips the test suite, whose users use the wptests_ table prefix.
		if ( $user instanceof \WP_User && 'wptests_capabilities' === $user->cap_key ) {
			return;
		}
		\WC_Tracks_Client::record_event_batched( $properties );
	}

	/**
	 * Send the pixels queued in the batched Tracks client now, instead of on shutdown.
	 */
	protected function send_queued_pixels(): void {
		\WC_Tracks_Client::send_batched_pixels();
	}

	/**
	 * Whether this process can outlive many requests' worth of calls, such as an MCP STDIO server run with WP-CLI.
	 *
	 * @return bool
	 */
	protected function is_long_running_process(): bool {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Hand the recorded events to the Tracks client.
	 */
	private function flush_events(): void {
		// Runs on shutdown, where an exception would also stop later shutdown callbacks.
		try {
			$last = array_key_last( $this->events );
			if ( null !== $last && $this->events_dropped > 0 ) {
				$this->events[ $last ]['events_dropped'] = $this->events_dropped;
				$this->events_dropped                    = 0;
			}

			$events       = $this->events;
			$this->events = array();
			foreach ( $events as $properties ) {
				$this->send_event( $properties );
			}
		} catch ( \Throwable $e ) {
			unset( $e );
		}
	}

	/**
	 * Start tracking an invocation.
	 *
	 * @param string $ability_name The ability name.
	 * @param bool   $executing    Whether execution already started (WordPress 7.0 path).
	 */
	private function open_frame( string $ability_name, bool $executing ): void {
		// Consent is per site, and the site can change mid-request with switch_to_blog().
		if ( ! self::is_tracking_allowed() ) {
			return;
		}

		// Calls that fail without a closing hook keep their frame until shutdown. Recording the oldest one as an
		// unknown failure keeps the stack bounded and lets later closing hooks still find their own frame.
		if ( count( $this->frames ) >= self::MAX_EVENTS_PER_REQUEST ) {
			$oldest = $this->frames[0];
			array_shift( $this->frames );
			$this->add_event( $oldest, null, false );
		}

		$this->frames[] = $this->create_frame( $ability_name, $executing );
		++$this->frames_opened;
	}

	/**
	 * Create the record of an invocation, with the context it started in.
	 *
	 * @param string $ability_name The ability name.
	 * @param bool   $executing    Whether execution already started.
	 * @return array{name: string, channel: string, rest_namespace: string, external_client: string, start: float, executing: bool}
	 */
	private function create_frame( string $ability_name, bool $executing ): array {
		$channel = $this->get_channel();
		return array(
			'name'            => $ability_name,
			'channel'         => $channel,
			'rest_namespace'  => $this->get_rest_namespace(),
			// Resolved now: the request metadata it reads is gone once the REST request finishes.
			'external_client' => 'mcp' === $channel ? $this->get_external_client() : self::NONE,
			'start'           => microtime( true ),
			'executing'       => $executing,
		);
	}

	/**
	 * Finish tracking the innermost open invocation of an ability.
	 *
	 * Invocations opened after it are calls that failed without a closing hook. On WordPress 7.0, a failed recursive
	 * call of the same ability can't be told apart from the outer call, so their outcomes can swap; counts stay right.
	 *
	 * @param mixed $ability_name The ability name.
	 * @param bool  $executing    Execution state the invocation must be in; hooks before and after execution share filters with other callers.
	 * @param mixed $result       Null for success, a WP_Error for failure, or a short-circuited result.
	 */
	private function close_frame( $ability_name, bool $executing, $result ): void {
		if ( ! self::is_tracked_ability( $ability_name ) ) {
			return;
		}

		$index = $this->find_frame( $ability_name, $executing );
		if ( null === $index ) {
			return;
		}

		$frame = $this->frames[ $index ];
		foreach ( array_splice( $this->frames, $index ) as $offset => $closed_frame ) {
			if ( $offset > 0 ) {
				$this->add_event( $closed_frame, null, false );
			}
		}

		// Only executed calls report a duration, so it always measures execution.
		$this->add_event( $frame, is_wp_error( $result ) ? $result : true, $frame['executing'] );
	}

	/**
	 * Find the innermost open invocation of an ability in a given execution state.
	 *
	 * Matching the state keeps a recursive call that failed without a closing hook from hiding its outer call.
	 *
	 * @param string $ability_name The ability name.
	 * @param bool   $executing    Whether the invocation must have started executing.
	 * @return int|null Index in the frames stack, or null when none is open.
	 */
	private function find_frame( string $ability_name, bool $executing ): ?int {
		for ( $index = count( $this->frames ) - 1; $index >= 0; $index-- ) {
			if ( $ability_name === $this->frames[ $index ]['name'] && $executing === $this->frames[ $index ]['executing'] ) {
				return $index;
			}
		}
		return null;
	}

	/**
	 * Turn a finished invocation into Tracks properties.
	 *
	 * Properties are built now, so the user, site and time are those of the call rather than of shutdown.
	 *
	 * @param array{name: string, channel: string, rest_namespace: string, external_client: string, start: float, executing: bool} $frame        The invocation.
	 * @param true|WP_Error|null                                                                                                   $outcome      True for success, a WP_Error for a known failure, null for an unknown failure.
	 * @param bool                                                                                                                 $has_duration Whether the duration is known.
	 */
	private function add_event( array $frame, $outcome, bool $has_duration ): void {
		// Telemetry must never break the ability call: this runs third-party filters and Jetpack code inside core's hooks.
		try {
			if ( count( $this->events ) >= self::MAX_EVENTS_PER_REQUEST ) {
				if ( ! $this->is_long_running_process() ) {
					++$this->events_dropped;
					return;
				}
				$this->flush_events();
				$this->send_queued_pixels();
			}

			// The call itself may have turned tracking off.
			if ( ! self::is_tracking_allowed() ) {
				return;
			}

			// Web requests from logged-out visitors aren't merchant or agent usage, and anyone could trigger them.
			$auth = self::get_auth_method();
			if ( 'none' === $auth && ! ( defined( 'WP_CLI' ) && WP_CLI ) && ! wp_doing_cron() ) {
				return;
			}

			$error_code = self::NONE;
			if ( $outcome instanceof WP_Error ) {
				$error_code = self::get_error_code( $outcome );
			} elseif ( null === $outcome ) {
				$error_code = 'unknown';
			}

			$event = array(
				// Not truncated, and normalized per part with `__` at the `/`: either would let different abilities collide.
				'ability_name'    => implode( '__', array_map( static fn( $part ) => self::normalize_value( $part, null ), explode( '/', $frame['name'], 2 ) ) ),
				'outcome'         => true === $outcome ? 'success' : 'error',
				'error_code'      => $error_code,
				'channel'         => $frame['channel'],
				'rest_namespace'  => $frame['rest_namespace'],
				'auth'            => $auth,
				'external_client' => $frame['external_client'],
			);

			if ( $has_duration ) {
				$event['duration_ms'] = (int) round( ( microtime( true ) - $frame['start'] ) * 1000 );
			}
			// Many local installs (localhost, .local, .test, DDEV, Lando) still report production.
			$event['is_test'] = ( 'production' !== wp_get_environment_type() || ( class_exists( Status::class ) && ( new Status() )->is_local_site() ) ) ? 'true' : 'false';
			$event['is_a11n'] = self::is_a11n() ? 'true' : 'false';

			$properties = \WC_Tracks::get_properties( \WC_Tracks::PREFIX . self::EVENT_NAME, $event );
			// The request URL and referrer can carry ability input, e.g. in the query string of GET run requests.
			unset( $properties['_dl'], $properties['_dr'] );
			if ( null === $outcome ) {
				// Unknown failures are recorded later than the call (WordPress 7.0 has no hook for them), so use the call's
				// start time. Site and user are still read now; they differ only if the request switched them since.
				$properties['_ts'] = number_format( round( $frame['start'] * 1000 ), 0, '', '' );
			}

			$this->events[] = $properties;
		} catch ( \Throwable $e ) {
			unset( $e );
		}
	}

	/**
	 * Identify how the ability was invoked.
	 *
	 * @return string
	 */
	private function get_channel(): string {
		if ( $this->is_mcp_request() ) {
			return 'mcp';
		}
		foreach ( $this->rest_requests as $request ) {
			if ( 1 === preg_match( self::ABILITIES_RUN_ROUTE_REGEX, $request->get_route() ) ) {
				return 'rest';
			}
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return 'cli';
		}
		if ( wp_doing_cron() ) {
			return 'cron';
		}
		return 'other';
	}

	/**
	 * Whether this is an MCP request.
	 *
	 * MCP Streamable HTTP clients send MCP-Protocol-Version on every request after initialization (2025-06-18 and
	 * later) and Mcp-Session-Id when the server assigned a session, whichever server implementation handles them.
	 *
	 * @return bool
	 */
	private function is_mcp_request(): bool {
		if ( null === $this->is_mcp_request ) {
			$this->is_mcp_request = isset( $_SERVER['HTTP_MCP_PROTOCOL_VERSION'] ) || isset( $_SERVER['HTTP_MCP_SESSION_ID'] );
		}
		return $this->is_mcp_request;
	}

	/**
	 * Get the namespace of the outermost REST route being dispatched, such as `wp_abilities` or `mcp`.
	 *
	 * Only the namespace is used: the rest of the route can hold IDs.
	 *
	 * @return string
	 */
	private function get_rest_namespace(): string {
		if ( ! $this->rest_requests ) {
			return self::NONE;
		}
		$segments = explode( '/', ltrim( $this->rest_requests[0]->get_route(), '/' ), 2 );
		return self::normalize_value( $segments[0] );
	}

	/**
	 * Get the error code to record for an error.
	 *
	 * Codes are normally fixed slugs, but any filter can return an error whose code was built from input, so codes
	 * that don't look like slugs are recorded as `other`.
	 *
	 * @param WP_Error $error The error.
	 * @return string
	 */
	private static function get_error_code( WP_Error $error ): string {
		$code = (string) $error->get_error_code();
		return 1 === preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $code ) ? self::normalize_value( $code ) : 'other';
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
	 * Identify how the caller authenticated, so agents can be told apart from people in wp-admin.
	 *
	 * @return string `application_password`, `jetpack`, `cookie`, `other` (another REST authentication method) or
	 *                `none` (logged out, WP-CLI or cron).
	 */
	private static function get_auth_method(): string {
		if ( function_exists( 'rest_get_authenticated_app_password' ) && rest_get_authenticated_app_password() ) {
			return 'application_password';
		}
		if ( self::is_jetpack_signed_request() ) {
			return 'jetpack';
		}
		if ( ! is_user_logged_in() || ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron() ) {
			return 'none';
		}
		if ( wp_is_serving_rest_request() ) {
			global $wp_rest_auth_cookie;
			return true === $wp_rest_auth_cookie ? 'cookie' : 'other';
		}
		return 'cookie';
	}

	/**
	 * Whether Jetpack authenticated this request with a blog or user token signature.
	 *
	 * Jetpack is only asked when the request carries its signature arguments, so its REST authentication isn't
	 * initialized here on other requests.
	 *
	 * @return bool
	 */
	private static function is_jetpack_signed_request(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checks for the presence of Jetpack's signature arguments.
		if ( ! isset( $_GET['token'], $_GET['signature'] ) ) {
			return false;
		}
		$rest_authentication = 'Automattic\Jetpack\Connection\Rest_Authentication';
		return is_callable( array( $rest_authentication, 'is_signed_with_blog_token' ) )
			&& is_callable( array( $rest_authentication, 'is_signed_with_user_token' ) )
			&& ( $rest_authentication::is_signed_with_blog_token() || $rest_authentication::is_signed_with_user_token() );
	}

	/**
	 * Whether an Automattician made the call: signed in on WordPress.com, or through the Automattic proxy.
	 *
	 * Only detectable on WordPress.com and Atomic; always false elsewhere.
	 *
	 * @return bool
	 */
	private static function is_a11n(): bool {
		if ( function_exists( 'is_automattician' ) && is_automattician() ) {
			return true;
		}
		if ( defined( 'AT_PROXIED_REQUEST' ) && AT_PROXIED_REQUEST ) {
			return true;
		}
		if ( function_exists( 'wpcom_is_proxied_request' ) ) {
			return (bool) wpcom_is_proxied_request();
		}
		if ( isset( $_SERVER['A8C_PROXIED_REQUEST'] ) && is_string( $_SERVER['A8C_PROXIED_REQUEST'] ) ) {
			return '1' === sanitize_text_field( wp_unslash( $_SERVER['A8C_PROXIED_REQUEST'] ) );
		}
		return defined( 'A8C_PROXIED_REQUEST' ) && A8C_PROXIED_REQUEST;
	}

	/**
	 * Whether the current site allows usage tracking.
	 *
	 * Unlike WC_Site_Tracking::is_tracking_enabled() alone, this requires the stored opt-in: that method also treats a
	 * posted `wc_tracker_checkbox=yes` (the onboarding opt-in) as consent, and any visitor can post to the REST API.
	 *
	 * @return bool
	 */
	private static function is_tracking_allowed(): bool {
		try {
			return 'yes' === get_option( 'woocommerce_allow_tracking' ) && \WC_Site_Tracking::is_tracking_enabled();
		} catch ( \Throwable $e ) {
			// The consent filters are third-party code; a failure there must not break the ability call.
			return false;
		}
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
	 * Get the MCP client, as one of the known clients, `other` or `none`.
	 *
	 * The client supplies its own name, so only known clients are recorded by name.
	 *
	 * @return string
	 */
	private function get_external_client(): string {
		try {
			return $this->resolve_external_client();
		} catch ( \Throwable $e ) {
			// The session lookup is MCP Adapter code; a failure there must not break the ability call.
			return self::NONE;
		}
	}

	/**
	 * Resolve and cache the MCP client.
	 *
	 * @return string
	 */
	private function resolve_external_client(): string {
		if ( null === $this->external_client ) {
			$name = $this->get_mcp_client_name_from_request() ?? self::get_mcp_client_name_from_session();
			if ( null === $name ) {
				$this->external_client = self::NONE;
			} else {
				$this->external_client = 'other';
				$tokens                = '_' . self::normalize_value( $name ) . '_';
				foreach ( self::KNOWN_MCP_CLIENTS as $needle => $client ) {
					if ( false !== strpos( $tokens, '_' . $needle . '_' ) ) {
						$this->external_client = $client;
						break;
					}
				}
			}
		}
		return $this->external_client;
	}

	/**
	 * Get the MCP client name from the request metadata, which MCP 2026-07-28 sends with every request.
	 *
	 * @return string|null
	 */
	private function get_mcp_client_name_from_request(): ?string {
		$request = $this->rest_requests[0] ?? null;
		$message = $request ? $request->get_json_params() : null;
		$name    = is_array( $message ) ? ( $message['params']['_meta']['io.modelcontextprotocol/clientInfo']['name'] ?? null ) : null;
		return is_string( $name ) ? $name : null;
	}

	/**
	 * Get the MCP client name from the MCP Adapter session (MCP 2025-11-25 and earlier).
	 *
	 * @return string|null
	 */
	private static function get_mcp_client_name_from_session(): ?string {
		$session_manager = array( 'WP\MCP\Transport\Infrastructure\SessionManager', 'get_session' );
		$session_id      = isset( $_SERVER['HTTP_MCP_SESSION_ID'] ) && is_string( $_SERVER['HTTP_MCP_SESSION_ID'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_MCP_SESSION_ID'] ) ) : '';

		if ( '' === $session_id || ! get_current_user_id() || ! is_callable( $session_manager ) ) {
			return null;
		}

		$session = call_user_func( $session_manager, get_current_user_id(), $session_id );
		$name    = is_array( $session ) ? ( $session['client_params']['clientInfo']['name'] ?? null ) : null;

		return is_string( $name ) ? $name : null;
	}

	/**
	 * Convert a value to lowercase words separated by underscores, as Tracks property values expect.
	 *
	 * @param string   $value      The value.
	 * @param int|null $max_length Maximum length, or null for no limit.
	 * @return string The normalized value, or 'none' when nothing is left.
	 */
	private static function normalize_value( string $value, ?int $max_length = 64 ): string {
		$value = trim( (string) preg_replace( '/[^a-z0-9]+/', '_', strtolower( $value ) ), '_' );
		if ( '' === $value ) {
			return self::NONE;
		}
		return null === $max_length ? $value : (string) substr( $value, 0, $max_length );
	}
}
