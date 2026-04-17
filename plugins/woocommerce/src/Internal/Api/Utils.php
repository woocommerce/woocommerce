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
		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Not HTML; serialized as JSON.
		try {
			return $command->execute( ...$execute_args );
		} catch ( \Automattic\WooCommerce\Api\ApiException $e ) {
			throw new \GraphQL\Error\Error(
				$e->getMessage(),
				extensions: array_merge(
					array( 'code' => $e->getErrorCode() ),
					$e->getExtensions()
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
