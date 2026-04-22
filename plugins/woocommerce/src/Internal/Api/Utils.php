<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

/**
 * Shared utilities for the auto-generated GraphQL resolvers.
 */
class Utils {
	/**
	 * Assert that the current user has the given WordPress capability.
	 *
	 * Throws a GraphQL UNAUTHORIZED error if the check fails. Intended to
	 * be called from generated resolver methods so the capability-check
	 * boilerplate doesn't have to be repeated in every resolver.
	 *
	 * @param string $capability A WordPress capability slug.
	 *
	 * @throws \GraphQL\Error\Error When the current user lacks the capability.
	 */
	public static function check_current_user_can( string $capability ): void {
		if ( ! current_user_can( $capability ) ) {
			throw new \GraphQL\Error\Error(
				'You do not have permission to perform this action.',
				extensions: array( 'code' => 'UNAUTHORIZED' )
			);
		}
	}

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
	 * @throws \GraphQL\Error\Error When a pagination value is out of range.
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
	 * @throws \GraphQL\Error\Error When the factory throws InvalidArgumentException.
	 */
	public static function create_input( callable $factory ): mixed {
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not HTML; serialized as JSON.
		try {
			return $factory();
		} catch ( \InvalidArgumentException $e ) {
			throw new \GraphQL\Error\Error(
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
	 * @throws \GraphQL\Error\Error On any exception from the command.
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
	 * authorize() call can throw an ApiException (e.g. AuthorizationException
	 * when a target record does not exist); without this wrapper the
	 * exception would propagate up to webonyx and lose its error code and
	 * user-visible message on its way through the generic error formatter.
	 *
	 * @param object $command        The command instance (must have an authorize() method).
	 * @param array  $authorize_args Named arguments to pass to authorize().
	 *
	 * @return bool The return value of authorize().
	 * @throws \GraphQL\Error\Error On any exception from the authorize method.
	 */
	public static function authorize_command( object $command, array $authorize_args ): bool {
		return self::translate_exceptions(
			static fn() => $command->authorize( ...$authorize_args )
		);
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
	 * @throws \GraphQL\Error\Error On any exception from the callable.
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
			throw new \GraphQL\Error\Error(
				$e->getMessage(),
				extensions: array_merge(
					$e->getExtensions(),
					array( 'code' => $e->getErrorCode() )
				)
			);
		} catch ( \InvalidArgumentException $e ) {
			throw new \GraphQL\Error\Error(
				$e->getMessage(),
				extensions: array( 'code' => 'INVALID_ARGUMENT' )
			);
		} catch ( \Throwable $e ) {
			throw new \GraphQL\Error\Error(
				'An unexpected error occurred.',
				previous: $e,
				extensions: array( 'code' => 'INTERNAL_ERROR' )
			);
		}//end try
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}
}
