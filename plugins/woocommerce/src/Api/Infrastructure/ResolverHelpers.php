<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Infrastructure;

use Automattic\WooCommerce\Api\Infrastructure\Schema\Error;

/**
 * Shared utilities for the auto-generated GraphQL resolvers.
 *
 * The public surface uses only {@see Schema\Error} (a stable subclass of the
 * engine's Error) on throws/returns so generated code never imports an
 * engine-specific symbol — a future engine switch can rewrite the bodies
 * here without invalidating already-committed plugin trees.
 */
class ResolverHelpers {
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
	 * @throws Error When a pagination value is out of range.
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
	 * @throws Error When the factory throws InvalidArgumentException.
	 */
	public static function create_input( callable $factory ): mixed {
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not HTML; serialized as JSON.
		try {
			return $factory();
		} catch ( \InvalidArgumentException $e ) {
			throw new Error(
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
	 * @throws Error On any exception from the command.
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
	 * exception would propagate up to the engine and lose its error code and
	 * user-visible message on its way through the generic error formatter.
	 *
	 * @param object $command        The command instance (must have an authorize() method).
	 * @param array  $authorize_args Named arguments to pass to authorize().
	 *
	 * @return bool The return value of authorize().
	 * @throws Error On any exception from the authorize method.
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
	 * Used for class-level denials (operation-level "you cannot call this
	 * query/mutation"). For field-level denials that should carry a
	 * structured `subject` payload (type / field / attribute), see
	 * {@see self::build_field_authorization_error()}.
	 *
	 * @param object $principal The resolved request principal.
	 */
	public static function build_authorization_error( object $principal ): Error {
		$is_anonymous = method_exists( $principal, 'is_authenticated' ) && ! $principal->is_authenticated();
		return new Error(
			$is_anonymous ? 'Authentication required.' : 'You do not have permission to perform this action.',
			extensions: array( 'code' => $is_anonymous ? 'UNAUTHORIZED' : 'FORBIDDEN' )
		);
	}

	/**
	 * Like {@see self::build_authorization_error()} but carries a structured
	 * `subject` payload identifying *what* was denied — the enclosing type,
	 * the field (when applicable), and the attribute class name driving the
	 * decision. Clients can branch on `extensions.subject.field` to tell a
	 * field-level deny apart from an operation-level one.
	 *
	 * The error code (UNAUTHORIZED / FORBIDDEN) is preserved verbatim so
	 * existing client handlers continue to work; the subject payload is
	 * additive.
	 *
	 * @param object  $principal       The resolved request principal.
	 * @param string  $type            GraphQL type name carrying the gate.
	 * @param ?string $field           Field name when the deny is field-level; null for type/operation-level denies.
	 * @param string  $attribute_short Short class name of the deciding authorization attribute (no namespace).
	 */
	public static function build_field_authorization_error( object $principal, string $type, ?string $field, string $attribute_short ): Error {
		$is_anonymous = method_exists( $principal, 'is_authenticated' ) && ! $principal->is_authenticated();
		$subject      = array(
			'type'      => $type,
			'attribute' => $attribute_short,
		);
		if ( null !== $field ) {
			$subject['field'] = $field;
		}
		return new Error(
			$is_anonymous ? 'Authentication required.' : 'You do not have permission to perform this action.',
			extensions: array(
				'code'    => $is_anonymous ? 'UNAUTHORIZED' : 'FORBIDDEN',
				'subject' => $subject,
			)
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
	 * Scope is class-level (queries / mutations). Field-level authorization
	 * lives on output-type / input-type properties and is enforced inside
	 * the generated resolvers. To inspect a field's declared authorization
	 * from code, walk {@see \Automattic\WooCommerce\Api\Utils\SchemaHandle::find_metadata()}
	 * and read the `authorization` slice on each row.
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
			// {@see \Automattic\WooCommerce\Api\Infrastructure\DesignTime\ApiBuilder::resolve_authorization()}.
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

		$query_metadata = self::harvest_class_metadata( $ref );

		foreach ( $usages as $instance ) {
			$auth_method = new \ReflectionMethod( $instance, 'authorize' );
			$call_args   = self::build_authorize_call_args(
				$auth_method,
				$principal,
				array( 'query' => $query_metadata ),
				array(),
				null
			);
			$result      = $instance->authorize( ...$call_args );
			if ( ! $result ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Mirror of `ApiBuilder::harvest_metadata()` for the runtime path. Walks
	 * {@see \Automattic\WooCommerce\Api\Attributes\Metadata}-subclass attributes
	 * on a class reflector and returns `name => value`. Duplicate names are
	 * resolved last-wins — the build-time validator already errors on
	 * duplicates, so this is only relevant for in-process classes that
	 * never went through a build.
	 *
	 * The per-target `_apiMetadata` opt-out (`shows_in_metadata_query()`)
	 * is not applied here: the `$_metadata` slot threaded into a class-
	 * level attribute's `authorize()` is for policy input, not discovery,
	 * so attribute authors see every entry regardless of how it surfaces
	 * through `_apiMetadata`.
	 *
	 * @param \ReflectionClass $ref The class to read metadata from.
	 * @return array<string, bool|int|float|string|null>
	 */
	private static function harvest_class_metadata( \ReflectionClass $ref ): array {
		$entries = array();
		foreach ( $ref->getAttributes( \Automattic\WooCommerce\Api\Attributes\Metadata::class, \ReflectionAttribute::IS_INSTANCEOF ) as $attribute ) {
			$instance                         = $attribute->newInstance();
			$entries[ $instance->get_name() ] = $instance->get_value();
		}
		return $entries;
	}

	/**
	 * Build the positional/named argument list for an attribute's `authorize()`
	 * method based on which opt-in slots its signature declares.
	 *
	 * The principal is always passed first (positionally) when the method
	 * declares a non-`_`-prefixed parameter; infrastructure parameters
	 * (`$_metadata`, `$_args`, `$_parent`) are passed as named arguments so
	 * the attribute can omit any subset without affecting the call shape.
	 *
	 * @param \ReflectionMethod $method    The attribute's `authorize()` method.
	 * @param object            $principal The resolved principal to pass when the method takes one.
	 * @param array             $metadata  Value for `$_metadata` (passed if the method declares it).
	 * @param array             $args      Value for `$_args` (passed if the method declares it).
	 * @param mixed             $parent    Value for `$_parent` (passed if the method declares it).
	 *
	 * @return array<int|string, mixed> Positional principal first (if any), then named infra slots. Use with `...` spread.
	 */
	private static function build_authorize_call_args( \ReflectionMethod $method, object $principal, array $metadata, array $args, mixed $parent ): array {
		$call_args = array();
		foreach ( $method->getParameters() as $param ) {
			$name = $param->getName();
			if ( '_metadata' === $name ) {
				$call_args['_metadata'] = $metadata;
			} elseif ( '_args' === $name ) {
				$call_args['_args'] = $args;
			} elseif ( '_parent' === $name ) {
				$call_args['_parent'] = $parent;
			} elseif ( '' === $name || '_' !== $name[0] ) {
				// Principal — positional, must be the first entry in the spread.
				array_unshift( $call_args, $principal );
			}
		}
		return $call_args;
	}

	/**
	 * Collect attribute instances declared on $source whose class declares an
	 * authorization-shaped `authorize()` method.
	 *
	 * Mirrors {@see \Automattic\WooCommerce\Api\Infrastructure\DesignTime\ApiBuilder::collect_authorization_usages()}
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
	 * public, non-static, returns bool, and parameters drawn from the accepted
	 * set — at most one principal (any non-`_`-prefixed name, non-nullable
	 * typed) plus any subset of `$_metadata` (array), `$_args` (array), and
	 * `$_parent` (any type).
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

		$principal_seen = false;
		foreach ( $method->getParameters() as $param ) {
			$name = $param->getName();
			if ( '_metadata' === $name || '_args' === $name ) {
				$type = $param->getType();
				if ( ! $type instanceof \ReflectionNamedType || 'array' !== $type->getName() ) {
					return false;
				}
				continue;
			}
			if ( '_parent' === $name ) {
				continue;
			}
			if ( '' !== $name && '_' === $name[0] ) {
				// Unknown infra parameter — reject.
				return false;
			}
			if ( $principal_seen ) {
				return false;
			}
			$type = $param->getType();
			if ( ! $type instanceof \ReflectionNamedType || $type->allowsNull() ) {
				return false;
			}
			$principal_seen = true;
		}
		return true;
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
	 * @throws Error On any exception from the callable.
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
			throw new Error(
				$e->getMessage(),
				extensions: array_merge(
					$e->getExtensions(),
					array( 'code' => $e->getErrorCode() )
				)
			);
		} catch ( \InvalidArgumentException $e ) {
			throw new Error(
				$e->getMessage(),
				extensions: array( 'code' => 'INVALID_ARGUMENT' )
			);
		} catch ( \Throwable $e ) {
			throw new Error(
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
