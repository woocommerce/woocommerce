<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Vendor\GraphQL\GraphQL;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FieldNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\InlineFragmentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\OperationDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionSetNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;
use Automattic\WooCommerce\Vendor\GraphQL\Error\DebugFlag;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\DocumentValidator;
use Automattic\WooCommerce\Vendor\GraphQL\Validator\Rules\DisableIntrospection;

/**
 * Handles incoming GraphQL requests over the WooCommerce REST API.
 */
abstract class GraphQLController {
	/**
	 * Default nesting-depth limit applied when the option is unset or non-positive.
	 *
	 * Queries exceeding the configured limit are rejected during validation,
	 * before any resolver runs. See {@see self::get_max_query_depth()} for the accessor.
	 */
	public const DEFAULT_MAX_QUERY_DEPTH = 15;

	/**
	 * Default complexity-score limit applied when the option is unset or non-positive.
	 *
	 * Complexity is the sum of per-field scores; connection fields multiply
	 * their child score by the requested page size. Queries exceeding the
	 * configured limit are rejected during validation. See
	 * {@see self::get_max_query_complexity()} for the accessor.
	 */
	public const DEFAULT_MAX_QUERY_COMPLEXITY = 1000;

	/**
	 * Cached GraphQL schema instance.
	 *
	 * @var ?Schema
	 */
	private ?Schema $schema = null;

	/**
	 * Query cache / APQ resolver.
	 *
	 * @var QueryCache
	 */
	private QueryCache $query_cache;

	/**
	 * DI: injected by WooCommerce container.
	 *
	 * @internal
	 * @param QueryCache $query_cache The query cache instance.
	 */
	final public function init( QueryCache $query_cache ): void {
		$this->query_cache = $query_cache;
	}

	/**
	 * The maximum nesting depth allowed in a GraphQL query.
	 *
	 * Reads the {@see Main::OPTION_MAX_QUERY_DEPTH} store option; falls back
	 * to {@see self::DEFAULT_MAX_QUERY_DEPTH} when the option is unset, empty,
	 * or non-positive.
	 */
	public static function get_max_query_depth(): int {
		$value = (int) get_option( Main::OPTION_MAX_QUERY_DEPTH, self::DEFAULT_MAX_QUERY_DEPTH );
		return $value > 0 ? $value : self::DEFAULT_MAX_QUERY_DEPTH;
	}

	/**
	 * The maximum computed complexity score allowed for a GraphQL query.
	 *
	 * Reads the {@see Main::OPTION_MAX_QUERY_COMPLEXITY} store option; falls
	 * back to {@see self::DEFAULT_MAX_QUERY_COMPLEXITY} when the option is
	 * unset, empty, or non-positive.
	 */
	public static function get_max_query_complexity(): int {
		$value = (int) get_option( Main::OPTION_MAX_QUERY_COMPLEXITY, self::DEFAULT_MAX_QUERY_COMPLEXITY );
		return $value > 0 ? $value : self::DEFAULT_MAX_QUERY_COMPLEXITY;
	}

	/**
	 * Register the GraphQL REST route.
	 */
	public function register(): void {
		$methods = Main::filter_methods_against_settings( array( 'GET', 'POST' ) );
		if ( empty( $methods ) ) {
			return;
		}

		register_rest_route(
			'wc',
			'/graphql',
			array(
				'methods'             => $methods,
				'callback'            => array( $this, 'handle_request' ),
				// Auth is handled per-query/mutation.
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle an incoming GraphQL request.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 */
	public function handle_request( \WP_REST_Request $request ): \WP_REST_Response {
		try {
			return $this->process_request( $request );
		} catch ( \Throwable $e ) {
			$output = array(
				'errors' => array(
					$this->format_exception( $e, $request ),
				),
			);

			$status = $this->get_error_status( $output['errors'] );
			return new \WP_REST_Response( $output, $status );
		}
	}

	/**
	 * Process the GraphQL request. Extracted so that handle_request() can
	 * wrap everything in a single try/catch that respects debug mode.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 */
	private function process_request( \WP_REST_Request $request ): \WP_REST_Response {
		// 2. Parse request. GET query-string `variables` and `extensions`
		// arrive as JSON strings; decode_json_param() unifies them with the
		// already-decoded-array path from POST bodies and rejects malformed
		// or non-object payloads up front so they surface as HTTP 400
		// INVALID_ARGUMENT instead of as confusing resolver errors (null
		// decode) or HTTP 500 TypeErrors (scalar decode).
		$query          = $request->get_param( 'query' );
		$operation_name = $request->get_param( 'operationName' );
		$variables      = $this->decode_json_param( $request->get_param( 'variables' ), 'variables' );
		$extensions     = $this->decode_json_param( $request->get_param( 'extensions' ), 'extensions' );

		// 3. Resolve query (cache lookup / APQ / parse).
		$source = $this->query_cache->resolve( $query, $extensions );
		if ( is_array( $source ) ) {
			return new \WP_REST_Response( $source, $this->get_resolve_error_status( $source ) );
		}

		// 4. Reject mutations over GET (GraphQL over HTTP spec).
		if ( 'GET' === $request->get_method() && $this->document_has_mutation( $source, $operation_name ) ) {
			return new \WP_REST_Response(
				array(
					'errors' => array(
						array(
							'message'    => 'Mutations are not allowed over GET requests. Use POST instead.',
							'extensions' => array( 'code' => 'METHOD_NOT_ALLOWED' ),
						),
					),
				),
				405
			);
		}

		// 5. Load schema.
		$schema = $this->get_schema();

		// 6. Build validation rules.
		// A single complexity-rule instance is kept so its computed score can
		// be surfaced in the debug extensions after execution.
		$complexity_rule    = new QueryComplexityRule( self::get_max_query_complexity() );
		$validation_rules   = array_values( DocumentValidator::allRules() );
		$validation_rules[] = new QueryDepthRule( self::get_max_query_depth() );
		$validation_rules[] = $complexity_rule;
		if ( ! $this->is_introspection_allowed( $request ) ) {
			$validation_rules[] = new DisableIntrospection( DisableIntrospection::ENABLED );
		}

		// 7. Execute.
		$result = GraphQL::executeQuery(
			schema: $schema,
			source: $source,
			variableValues: $variables,
			operationName: $operation_name,
			validationRules: $validation_rules,
		);

		// Install an error formatter that guarantees every error carries an
		// `extensions.code`. Our resolvers route everything through
		// Utils::execute_command / Utils::authorize_command, which already
		// translate domain exceptions (ApiException, InvalidArgumentException,
		// generic Throwable) into coded GraphQL errors at the throw site.
		// What reaches us uncoded here is webonyx-native validation and
		// execution output, so we infer from webonyx's ClientAware signal:
		// client-safe errors become BAD_USER_INPUT (400), the rest become
		// INTERNAL_ERROR (500).
		//
		// In debug mode the same formatter also walks the previous-exception
		// chain so wrapped errors (e.g. a \ValueError caught by a resolver and
		// re-thrown as INTERNAL_ERROR) stay visible to the developer instead
		// of being masked behind the generic "Internal server error" message.
		$debug_mode = $this->is_debug_mode( $request );
		$result->setErrorFormatter(
			function ( \Throwable $error ) use ( $debug_mode ): array {
				$formatted = \Automattic\WooCommerce\Vendor\GraphQL\Error\FormattedError::createFromException( $error );

				if ( ! isset( $formatted['extensions']['code'] ) ) {
					$client_safe                     = $error instanceof \Automattic\WooCommerce\Vendor\GraphQL\Error\ClientAware && $error->isClientSafe();
					$formatted['extensions']['code'] = $client_safe ? 'BAD_USER_INPUT' : 'INTERNAL_ERROR';
				}

				// SerializationError (thrown during schema-type coercion, e.g. when
				// a resolver returns an Int that doesn't fit 32 bits) extends
				// \Exception rather than webonyx's ClientAware Error, so it lands
				// in the INTERNAL_ERROR bucket above. Its message is actually
				// client-actionable ("value out of range — send smaller inputs"),
				// so promote it to BAD_USER_INPUT when it shows up anywhere in
				// the previous-exception chain.
				if ( 'BAD_USER_INPUT' !== ( $formatted['extensions']['code'] ?? null ) ) {
					$cursor = $error;
					while ( $cursor instanceof \Throwable ) {
						if ( $cursor instanceof \Automattic\WooCommerce\Vendor\GraphQL\Error\SerializationError ) {
							$formatted['extensions']['code'] = 'BAD_USER_INPUT';
							break;
						}
						$cursor = $cursor->getPrevious();
					}
				}

				if ( $debug_mode ) {
					$chain = $this->extract_previous_chain( $error );
					if ( ! empty( $chain ) ) {
						$formatted['extensions']['previous'] = $chain;
					}
				}

				return $formatted;
			}
		);

		$debug_flags = $this->get_debug_flags( $request );
		$output      = $result->toArray( $debug_flags );

		// 8. Debug-mode metrics: expose the computed complexity and depth so
		// clients tuning queries can see what the server scored the request at.
		if ( $this->is_debug_mode( $request ) ) {
			if ( ! isset( $output['extensions'] ) ) {
				$output['extensions'] = array();
			}
			if ( ! isset( $output['extensions']['debug'] ) ) {
				$output['extensions']['debug'] = array();
			}
			$output['extensions']['debug']['complexity'] = $complexity_rule->getQueryComplexity();
			$output['extensions']['debug']['depth']      = $this->compute_query_depth( $source, $operation_name );
		}

		// 9. Determine HTTP status code. GraphQL emits `data: { field: null }`
		// for nullable root fields even when the resolver errored, so gating
		// the status override on `data` being absent would leave nearly every
		// error response on HTTP 200. Always derive the status from the
		// errors array when one is present — clients that need "200 with
		// partial data" semantics can still read the `errors` array.
		$status = isset( $output['errors'] ) ? $this->get_error_status( $output['errors'] ) : 200;

		return new \WP_REST_Response( $output, $status );
	}

	/**
	 * Build and cache the GraphQL schema.
	 */
	private function get_schema(): Schema {
		if ( null === $this->schema ) {
			$this->schema = $this->build_schema();
		}
		return $this->schema;
	}

	/**
	 * Construct the GraphQL schema.
	 *
	 * Implemented by the autogenerated subclass emitted by ApiBuilder
	 * (both for WooCommerce core and for sibling plugins that reuse this
	 * infrastructure) so the base class stays agnostic to any specific
	 * autogenerated namespace.
	 */
	abstract protected function build_schema(): Schema;

	/**
	 * Decode an optional JSON-object param (`variables` / `extensions`) into an array.
	 *
	 * WP_REST_Request delivers POST-body params as already-decoded arrays,
	 * but GET query-string equivalents arrive as raw JSON strings. This
	 * helper unifies the two and rejects malformed JSON or non-object
	 * payloads with an InvalidArgumentException — which handle_request()
	 * surfaces as HTTP 400 INVALID_ARGUMENT, rather than letting a null
	 * decode slip through as "no variables" or a scalar decode trigger a
	 * downstream TypeError / HTTP 500.
	 *
	 * @param mixed  $value The param value from WP_REST_Request::get_param().
	 * @param string $name  The param name, used in error messages.
	 * @return array The decoded object, or an empty array when the param is omitted / empty / JSON null.
	 * @throws \InvalidArgumentException When the payload is not a JSON object or not valid JSON.
	 */
	private function decode_json_param( $value, string $name ): array {
		if ( null === $value ) {
			return array();
		}
		if ( is_array( $value ) ) {
			return $value;
		}
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not HTML; serialized as JSON.
		if ( ! is_string( $value ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Argument `%s` must be a JSON object or omitted.', $name )
			);
		}
		if ( '' === $value ) {
			return array();
		}
		$decoded = json_decode( $value, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			throw new \InvalidArgumentException(
				sprintf( 'Argument `%s` is not valid JSON: %s', $name, json_last_error_msg() )
			);
		}
		if ( null === $decoded ) {
			// Literal "null" JSON payload — treat as omitted.
			return array();
		}
		if ( ! is_array( $decoded ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Argument `%s` must be a JSON object (got %s).', $name, gettype( $decoded ) )
			);
		}
		return $decoded;
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}

	/**
	 * Determine debug flags based on WP_DEBUG, user role, and query string.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 */
	private function get_debug_flags( \WP_REST_Request $request ): int {
		if ( ! $this->is_debug_mode( $request ) ) {
			return DebugFlag::NONE;
		}
		return DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
	}

	/**
	 * Check whether GraphQL introspection is allowed for this request.
	 *
	 * Introspection is permitted if either condition holds:
	 * - The request is in debug mode ({@see self::is_debug_mode()}).
	 * - The caller has the `manage_woocommerce` capability.
	 *
	 * Gating on capability rather than mere authentication keeps the full
	 * schema (including admin-only mutations) hidden from low-privilege
	 * roles such as `customer`, which every storefront account is assigned
	 * at checkout — while still allowing admin tooling (e.g. GraphiQL-like
	 * explorers) to query it.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 */
	private function is_introspection_allowed( \WP_REST_Request $request ): bool {
		return $this->is_debug_mode( $request ) || current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Check if debug mode is active.
	 *
	 * Debug mode is active when either:
	 * - WP_DEBUG is enabled AND the current user is an administrator (or in a local environment).
	 * - The current user is an administrator (or in a local environment) AND `_debug=1` is in the query string.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 */
	private function is_debug_mode( \WP_REST_Request $request ): bool {
		if ( ! $this->is_local_environment() && ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return true;
		}

		return '1' === $request->get_param( '_debug' );
	}

	/**
	 * Format a caught exception into a GraphQL error array.
	 *
	 * @param \Throwable       $e       The caught exception.
	 * @param \WP_REST_Request $request The REST request.
	 */
	private function format_exception( \Throwable $e, \WP_REST_Request $request ): array {
		if ( $e instanceof ApiException ) {
			// Caller-supplied extensions come first so the canonical
			// getErrorCode() can't be silently overridden by an extensions
			// entry keyed 'code'. Mirrors the same invariant enforced by
			// Utils::translate_exceptions() for the execute/authorize paths.
			$error = array(
				'message'    => $e->getMessage(),
				'extensions' => array_merge(
					$e->getExtensions(),
					array( 'code' => $e->getErrorCode() )
				),
			);
		} elseif ( $e instanceof \InvalidArgumentException ) {
			$error = array(
				'message'    => $e->getMessage(),
				'extensions' => array( 'code' => 'INVALID_ARGUMENT' ),
			);
		} else {
			$error = array(
				'message'    => 'An unexpected error occurred.',
				'extensions' => array( 'code' => 'INTERNAL_ERROR' ),
			);
		}

		if ( $this->is_debug_mode( $request ) ) {
			$error['extensions']['debug'] = array(
				'message' => $e->getMessage(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine(),
				'trace'   => $e->getTraceAsString(),
			);

			$chain = $this->extract_previous_chain( $e );
			if ( ! empty( $chain ) ) {
				$error['extensions']['debug']['previous'] = $chain;
			}
		}

		return $error;
	}

	/**
	 * Walk the `getPrevious()` chain of a Throwable and return one entry per
	 * wrapped exception. Used in debug mode so that resolver-level wrappers
	 * (which bury the real cause behind a generic "INTERNAL_ERROR") still
	 * surface the underlying class/message/file/line/trace.
	 *
	 * @param \Throwable $e The outermost exception.
	 * @return array<int, array{class: string, message: string, file: string, line: int, trace: string[]}>
	 */
	private function extract_previous_chain( \Throwable $e ): array {
		$chain = array();
		for ( $prev = $e->getPrevious(); null !== $prev; $prev = $prev->getPrevious() ) {
			$chain[] = array(
				'class'   => get_class( $prev ),
				'message' => $prev->getMessage(),
				'file'    => $prev->getFile(),
				'line'    => $prev->getLine(),
				'trace'   => explode( "\n", $prev->getTraceAsString() ),
			);
		}
		return $chain;
	}

	/**
	 * Mapping from machine-readable error codes to HTTP status codes.
	 *
	 * Any code not listed here defaults to 500, so unknown/unrecognised codes
	 * from third-party resolvers stay on the safe side. The error formatter
	 * installed in process_request() guarantees every error carries a code
	 * from this table before get_error_status() inspects it.
	 */
	private const ERROR_STATUS_MAP = array(
		'UNAUTHORIZED'              => 401,
		'FORBIDDEN'                 => 403,
		'NOT_FOUND'                 => 404,
		'METHOD_NOT_ALLOWED'        => 405,
		'INVALID_ARGUMENT'          => 400,
		'BAD_USER_INPUT'            => 400,
		'GRAPHQL_PARSE_ERROR'       => 400,
		'GRAPHQL_PARSE_FAILED'      => 400,
		'GRAPHQL_VALIDATION_FAILED' => 400,
		'VALIDATION_ERROR'          => 422,
		'INTERNAL_ERROR'            => 500,
	);

	/**
	 * Determine the HTTP status code from an array of GraphQL errors.
	 *
	 * Applies the code-to-status lookup to each error and returns the worst
	 * (highest) status seen. A single genuine 5xx among mixed errors surfaces
	 * as 500, which is the more useful signal for monitoring and logs.
	 *
	 * @param array $errors The GraphQL errors array.
	 */
	private function get_error_status( array $errors ): int {
		$status = 200;
		foreach ( $errors as $error ) {
			$code   = $error['extensions']['code'] ?? null;
			$mapped = self::ERROR_STATUS_MAP[ $code ] ?? 500;
			if ( $mapped > $status ) {
				$status = $mapped;
			}
		}
		return $status;
	}

	/**
	 * Determine the HTTP status code for an error returned by QueryCache::resolve().
	 *
	 * PERSISTED_QUERY_NOT_FOUND uses 200 per the Apollo APQ convention (protocol signal, not error).
	 *
	 * @param array $response The error response array from resolve().
	 */
	private function get_resolve_error_status( array $response ): int {
		$code = $response['errors'][0]['extensions']['code'] ?? '';

		if ( 'PERSISTED_QUERY_NOT_FOUND' === $code ) {
			return 200;
		}

		return 400;
	}

	/**
	 * Compute the maximum nesting depth of the executing operation, under two
	 * different metrics:
	 *
	 * - `tree_only`: only fields whose own selection set is non-empty count
	 *   toward depth; leaves are excluded. This is the number directly
	 *   comparable to the "Maximum query depth" setting's limit, and matches
	 *   what webonyx's QueryDepth validation rule measures for the enforcement
	 *   decision.
	 * - `in_depth`: counts every field in the deepest chain, leaves included.
	 *   Useful as a shape metric when inspecting a query.
	 *
	 * Inline fragments pass through without incrementing either metric.
	 * Named-fragment spreads are not expanded here, so both numbers are lower
	 * bounds when spreads are present. The webonyx QueryDepth validation rule
	 * (which does expand spreads) remains the authoritative gate.
	 *
	 * @param DocumentNode $document       The parsed GraphQL document.
	 * @param ?string      $operation_name The requested operation name, if any.
	 * @return array{tree_only: int, in_depth: int}
	 */
	private function compute_query_depth( DocumentNode $document, ?string $operation_name ): array {
		$tree_only = 0;
		$in_depth  = 0;
		foreach ( $document->definitions as $definition ) {
			if ( ! $definition instanceof OperationDefinitionNode ) {
				continue;
			}

			if ( null !== $operation_name && ( $definition->name->value ?? null ) !== $operation_name ) {
				continue;
			}

			$tree_only = max( $tree_only, $this->walk_depth_tree_only( $definition->selectionSet, 0 ) );
			$in_depth  = max( $in_depth, $this->walk_depth_in_depth( $definition->selectionSet, 0 ) );
		}

		return array(
			'tree_only' => $tree_only,
			'in_depth'  => $in_depth,
		);
	}

	/**
	 * Walk a selection set counting only fields with child selections, matching
	 * webonyx's QueryDepth rule so the returned number is directly comparable
	 * to the configured "Maximum query depth" limit.
	 *
	 * @param ?SelectionSetNode $selection_set The selection set to walk.
	 * @param int               $depth         The depth at which fields in this selection set sit.
	 */
	private function walk_depth_tree_only( ?SelectionSetNode $selection_set, int $depth ): int {
		if ( null === $selection_set ) {
			return 0;
		}

		$max = 0;
		foreach ( $selection_set->selections as $selection ) {
			if ( $selection instanceof FieldNode ) {
				if ( null !== $selection->selectionSet ) {
					$max = max( $max, $depth, $this->walk_depth_tree_only( $selection->selectionSet, $depth + 1 ) );
				}
			} elseif ( $selection instanceof InlineFragmentNode ) {
				$max = max( $max, $this->walk_depth_tree_only( $selection->selectionSet, $depth ) );
			}
		}

		return $max;
	}

	/**
	 * Walk a selection set counting every field in the deepest chain, leaves
	 * included. Produces the "shape" metric surfaced alongside the enforcement
	 * metric in debug output.
	 *
	 * @param ?SelectionSetNode $selection_set The selection set to walk, or null for a leaf.
	 * @param int               $depth         The depth of the selection set's parent.
	 */
	private function walk_depth_in_depth( ?SelectionSetNode $selection_set, int $depth ): int {
		if ( null === $selection_set ) {
			return $depth;
		}

		$max = $depth;
		foreach ( $selection_set->selections as $selection ) {
			if ( $selection instanceof FieldNode ) {
				$max = max( $max, $this->walk_depth_in_depth( $selection->selectionSet, $depth + 1 ) );
			} elseif ( $selection instanceof InlineFragmentNode ) {
				$max = max( $max, $this->walk_depth_in_depth( $selection->selectionSet, $depth ) );
			}
		}

		return $max;
	}

	/**
	 * Check whether the parsed document contains a mutation operation.
	 *
	 * When an operation name is given, only that operation is checked;
	 * otherwise any mutation definition in the document triggers a match.
	 *
	 * @param DocumentNode $document       The parsed GraphQL document.
	 * @param ?string      $operation_name The requested operation name, if any.
	 */
	private function document_has_mutation( DocumentNode $document, ?string $operation_name ): bool {
		foreach ( $document->definitions as $definition ) {
			if ( ! $definition instanceof OperationDefinitionNode ) {
				continue;
			}

			if ( null !== $operation_name && ( $definition->name->value ?? null ) !== $operation_name ) {
				continue;
			}

			if ( 'mutation' === $definition->operation ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if running in a local/development environment.
	 *
	 * Prefers {@see wp_get_environment_type()} when available. Otherwise
	 * parses the site URL and performs a case-insensitive *exact* match
	 * against the hostname — not a substring check, to avoid matching
	 * impostor domains like `mylocalhost.com` or `127.0.0.1.attacker.example`.
	 */
	private function is_local_environment(): bool {
		if ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() ) {
			return true;
		}

		$host = wp_parse_url( get_site_url(), PHP_URL_HOST );
		if ( ! is_string( $host ) ) {
			return false;
		}

		$host = strtolower( $host );
		return 'localhost' === $host || '127.0.0.1' === $host;
	}
}
