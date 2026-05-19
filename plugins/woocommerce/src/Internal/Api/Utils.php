<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

/**
 * Shared utilities for the auto-generated GraphQL resolvers.
 */
class Utils {
	/**
	 * Compute the complexity cost of a paginated connection field.
	 *
	 * Used as the `complexity` callable on every generated resolver field
	 * that returns a `Connection`. Runs during query validation (before
	 * resolver execution, so before `PaginationParams::validate_args()` has
	 * a chance to reject bad input) — so out-of-range / wrong-type values
	 * are clamped to MAX_PAGE_SIZE here. Using MAX_PAGE_SIZE as the
	 * fallback means a malicious attempt to shrink cost via e.g. a
	 * negative `first` value only inflates the computed complexity,
	 * closing the cost-bypass angle.
	 *
	 * @param int   $child_complexity The complexity of a single child node.
	 * @param array $args             The field arguments (expects `first` / `last`).
	 *
	 * @return int The total complexity for this connection field.
	 */
	public static function complexity_from_pagination( int $child_complexity, array $args ): int {
		$requested = $args['first'] ?? $args['last'] ?? \Automattic\WooCommerce\Api\Pagination\PaginationParams::get_default_page_size();
		$page_size = ( is_int( $requested ) && $requested >= 0 && $requested <= \Automattic\WooCommerce\Api\Pagination\PaginationParams::MAX_PAGE_SIZE )
			? $requested
			: \Automattic\WooCommerce\Api\Pagination\PaginationParams::MAX_PAGE_SIZE;
		return $page_size * ( $child_complexity + 1 );
	}

	/**
	 * Build a PaginationParams instance from the standard GraphQL pagination
	 * arguments (first, last, after, before).
	 *
	 * @param array $args The GraphQL field arguments.
	 *
	 * @return \Automattic\WooCommerce\Api\Pagination\PaginationParams
	 * @throws \Automattic\WooCommerce\Vendor\GraphQL\Error\Error When a pagination value is out of range.
	 */
	public static function create_pagination_params( array $args ): \Automattic\WooCommerce\Api\Pagination\PaginationParams {
		return self::create_input(
			fn() => new \Automattic\WooCommerce\Api\Pagination\PaginationParams(
				first: $args['first'] ?? null,
				last: $args['last'] ?? null,
				after: $args['after'] ?? null,
				before: $args['before'] ?? null,
			)
		);
	}

	/**
	 * Invoke a factory callable, catching InvalidArgumentException and
	 * converting it to a client-visible GraphQL error.
	 *
	 * Used to wrap construction of unrolled input types (PaginationParams,
	 * ProductFilterInput, etc.) whose constructors may validate their
	 * arguments and throw.
	 *
	 * @param callable $factory A callable that returns the constructed object.
	 *
	 * @return mixed The return value of the factory.
	 * @throws \Automattic\WooCommerce\Vendor\GraphQL\Error\Error When the factory throws InvalidArgumentException.
	 */
	public static function create_input( callable $factory ): mixed {
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not HTML; serialized as JSON.
		try {
			return $factory();
		} catch ( \InvalidArgumentException $e ) {
			throw new \Automattic\WooCommerce\Vendor\GraphQL\Error\Error(
				$e->getMessage(),
				extensions: array( 'code' => 'INVALID_ARGUMENT' )
			);
		}
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}

	/**
	 * Execute a command's execute() method, translating any thrown exceptions
	 * into spec-compliant GraphQL errors.
	 *
	 * @param object $command      The command instance (must have an execute() method).
	 * @param array  $execute_args Named arguments to pass to execute().
	 *
	 * @return mixed The return value of execute().
	 * @throws \Automattic\WooCommerce\Vendor\GraphQL\Error\Error On any exception from the command.
	 */
	public static function execute_command( object $command, array $execute_args ): mixed {
		return self::translate_exceptions(
			static fn() => $command->execute( ...$execute_args )
		);
	}

	/**
	 * Invoke a command's authorize() method, translating any thrown exceptions
	 * into spec-compliant GraphQL errors.
	 *
	 * Mirror of execute_command() for the authorize step. Needed because an
	 * authorize() call can throw an ApiException (e.g. UnauthorizedException
	 * when a target record does not exist); without this wrapper the
	 * exception would propagate up to webonyx and lose its error code and
	 * user-visible message on its way through the generic error formatter.
	 *
	 * @param object $command        The command instance (must have an authorize() method).
	 * @param array  $authorize_args Named arguments to pass to authorize().
	 *
	 * @return bool The return value of authorize().
	 * @throws \Automattic\WooCommerce\Vendor\GraphQL\Error\Error On any exception from the authorize method.
	 */
	public static function authorize_command( object $command, array $authorize_args ): bool {
		return self::translate_exceptions(
			static fn() => $command->authorize( ...$authorize_args )
		);
	}

	/**
	 * Build the GraphQL error to throw when an authorization check fails.
	 *
	 * Distinguishes the two HTTP-correct shapes:
	 *  - **UNAUTHORIZED (401)** when the principal is anonymous — the caller
	 *    could plausibly fix it by authenticating, so the response invites
	 *    re-auth.
	 *  - **FORBIDDEN (403)** otherwise — the principal is recognised but
	 *    isn't allowed; re-authenticating wouldn't help.
	 *
	 * The "anonymous" check is opt-in by convention: the principal's
	 * `is_authenticated(): bool` method, when present, decides. Principals
	 * that don't define it fall through to FORBIDDEN — generated resolvers
	 * still emit a coded error, just without the 401/403 distinction.
	 *
	 * @param object $principal The resolved request principal.
	 */
	public static function build_authorization_error( object $principal ): \Automattic\WooCommerce\Internal\Api\Schema\Error {
		$is_anonymous = method_exists( $principal, 'is_authenticated' ) && ! $principal->is_authenticated();
		return new \Automattic\WooCommerce\Internal\Api\Schema\Error(
			$is_anonymous ? 'Authentication required.' : 'You do not have permission to perform this action.',
			extensions: array( 'code' => $is_anonymous ? 'UNAUTHORIZED' : 'FORBIDDEN' )
		);
	}

	/**
	 * Compute the value `_preauthorized` would carry for the given command and
	 * principal (the AND of the autodiscovered authorization attributes'
	 * authorize() outcomes).
	 *
	 * Lets code-API callers (and tests) ask "would this command's attribute-based
	 * authorization grant access to this principal?" without going through the
	 * GraphQL pipeline.
	 *
	 * Note that it returns true when the command has no authorization attributes
	 * (in that case the command's own `authorize()` method, if any, is the sole
	 * guard; and consulting it requires running the command, which this helper
	 * deliberately doesn't do).
	 *
	 * Note: this provides the attribute-level authorization only. A command with
	 * both attributes and an `authorize()` method composes the two via the
	 * `_preauthorized` infrastructure parameter; this helper returns the value
	 * that `_preauthorized` would carry, not the final `authorize()` outcome.
	 *
	 * @param string $command_fqcn Fully-qualified command class name.
	 * @param object $principal    The resolved principal. Anonymous requests are represented by a sentinel principal (e.g. {@see \Automattic\WooCommerce\Api\Infrastructure\Principal} whose underlying WP_User has ID=0), not by null.
	 *
	 * @throws \InvalidArgumentException When `$command_fqcn` does not name an existing class.
	 */
	public static function compute_preauthorized( string $command_fqcn, object $principal ): bool {
		if ( ! class_exists( $command_fqcn ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Class %s does not exist.', esc_html( $command_fqcn ) )
			);
		}
		$ref    = new \ReflectionClass( $command_fqcn );
		$direct = self::collect_authorization_instances( $ref );
		$usages = $direct;
		if ( empty( $usages ) ) {
			// No direct attribute — collect from the entire ancestor tree:
			// the parent chain plus each ancestor's traits and interfaces
			// (recursively). All inherited sources contribute as peers; the
			// only thing direct attributes shadow is the inherited tree as a
			// whole. Mirrors
			// {@see \Automattic\WooCommerce\Internal\Api\DesignTime\Scripts\ApiBuilder::resolve_authorization()}.
			$visited = array();
			$stack   = array_merge(
				$ref->getParentClass() ? array( $ref->getParentClass() ) : array(),
				$ref->getTraits(),
				$ref->getInterfaces(),
			);
			while ( ! empty( $stack ) ) {
				$source = array_shift( $stack );
				$name   = $source->getName();
				if ( in_array( $name, $visited, true ) ) {
					continue;
				}
				$visited[] = $name;
				$usages    = array_merge( $usages, self::collect_authorization_instances( $source ) );
				if ( false !== $source->getParentClass() ) {
					$stack[] = $source->getParentClass();
				}
				$stack = array_merge( $stack, $source->getTraits(), $source->getInterfaces() );
			}
		}

		foreach ( $usages as $instance ) {
			$auth_method = new \ReflectionMethod( $instance, 'authorize' );
			$result      = $auth_method->getNumberOfParameters() > 0
				? $instance->authorize( $principal )
				: $instance->authorize();
			if ( ! $result ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Collect attribute instances declared on $source whose class declares an
	 * authorization-shaped `authorize()` method.
	 *
	 * Mirrors {@see \Automattic\WooCommerce\Internal\Api\DesignTime\Scripts\ApiBuilder::collect_authorization_usages()}
	 * for the runtime path: same direct-then-inherited precedence, same
	 * "any class with a bool-returning authorize() method qualifies" rule.
	 *
	 * @param \ReflectionClass $source Class/trait/interface to read attributes from.
	 *
	 * @return array<int, object>
	 */
	private static function collect_authorization_instances( \ReflectionClass $source ): array {
		$instances = array();
		foreach ( $source->getAttributes() as $attr ) {
			$name = $attr->getName();
			if ( ! class_exists( $name ) || ! method_exists( $name, 'authorize' ) ) {
				continue;
			}
			$method = new \ReflectionMethod( $name, 'authorize' );
			if ( ! self::authorize_method_shape_is_valid( $method ) ) {
				continue;
			}
			$instances[] = $attr->newInstance();
		}
		return $instances;
	}

	/**
	 * Whether a method's shape matches the authorization-attribute contract:
	 * public, non-static, returns bool, and takes either 0 parameters or
	 * exactly 1 typed, non-nullable parameter (the principal — anonymous
	 * requests use a sentinel non-null principal, so attributes never see null).
	 *
	 * Mirrors the build-time `ApiBuilder::validate_attribute_authorize_shape()`
	 * check so the runtime helper recognises the same set of attributes ApiBuilder
	 * would have emitted into a resolver.
	 *
	 * @param \ReflectionMethod $method The method to inspect.
	 */
	private static function authorize_method_shape_is_valid( \ReflectionMethod $method ): bool {
		if ( $method->isStatic() || ! $method->isPublic() ) {
			return false;
		}
		$return_type = $method->getReturnType();
		if ( ! $return_type instanceof \ReflectionNamedType || 'bool' !== $return_type->getName() ) {
			return false;
		}
		$params = $method->getParameters();
		if ( count( $params ) > 1 ) {
			return false;
		}
		if ( 0 === count( $params ) ) {
			return true;
		}
		$param_type = $params[0]->getType();
		return $param_type instanceof \ReflectionNamedType && ! $param_type->allowsNull();
	}

	/**
	 * Invoke a callable, translating any thrown exception into a
	 * spec-compliant GraphQL error with a machine-readable code.
	 *
	 * - ApiException       → its own code + extensions, with the original message.
	 * - InvalidArgumentException → INVALID_ARGUMENT, with the original message.
	 * - Any other Throwable     → INTERNAL_ERROR, with a generic message; the
	 *   original throwable is attached as `previous` for debug-mode surfacing.
	 *
	 * Public so that generated resolvers can wrap Code-API calls that happen
	 * outside the execute()/authorize() pair (e.g. the Connection::slice()
	 * call emitted for nested paginated connection fields, which can throw
	 * InvalidArgumentException when pagination bounds are exceeded).
	 *
	 * @param callable $operation Callable to invoke.
	 *
	 * @return mixed The return value of the callable.
	 * @throws \Automattic\WooCommerce\Vendor\GraphQL\Error\Error On any exception from the callable.
	 */
	public static function translate_exceptions( callable $operation ): mixed {
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not HTML; serialized as JSON.
		try {
			return $operation();
		} catch ( \Automattic\WooCommerce\Api\ApiException $e ) {
			// Caller-supplied extensions come first so the canonical
			// getErrorCode() can't be silently overridden by an extensions
			// entry keyed 'code'. The invariant "the code on the wire
			// equals ApiException::getErrorCode()" is worth enforcing.
			throw new \Automattic\WooCommerce\Vendor\GraphQL\Error\Error(
				$e->getMessage(),
				extensions: array_merge(
					$e->getExtensions(),
					array( 'code' => $e->getErrorCode() )
				)
			);
		} catch ( \InvalidArgumentException $e ) {
			throw new \Automattic\WooCommerce\Vendor\GraphQL\Error\Error(
				$e->getMessage(),
				extensions: array( 'code' => 'INVALID_ARGUMENT' )
			);
		} catch ( \Throwable $e ) {
			throw new \Automattic\WooCommerce\Vendor\GraphQL\Error\Error(
				'An unexpected error occurred.',
				previous: $e,
				extensions: array( 'code' => 'INTERNAL_ERROR' )
			);
		}//end try
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}

	/**
	 * Lazy-initialize and return the WP_Filesystem global, or null when the
	 * direct method isn't available (e.g. credentials prompt would be needed).
	 */
	public static function wp_filesystem(): ?\WP_Filesystem_Base {
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if ( ! WP_Filesystem() ) {
				return null;
			}
		}
		return $wp_filesystem;
	}
}
