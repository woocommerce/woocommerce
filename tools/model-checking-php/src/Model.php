<?php
/**
 * Model contract for the isolated state-exploration POC.
 *
 * @package WooCommerce\Tools\ModelChecking
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tools\ModelChecking;

/**
 * Describes finite transitions and safety invariants over JSON-compatible states.
 *
 * @template TState of array<string, mixed>
 */
interface Model {
	/**
	 * Get the initial state.
	 *
	 * @since 11.4.0
	 * @return TState
	 */
	public function initial_state(): array;

	/**
	 * Get every enabled transition from a state.
	 *
	 * @since 11.4.0
	 * @param array $state Current state.
	 * @phpstan-param TState $state
	 * @return iterable<array{action: string, state: TState}>
	 */
	public function transitions( array $state ): iterable;

	/**
	 * Name the first violated invariant, if any.
	 *
	 * @since 11.4.0
	 * @param array $state State to check.
	 * @phpstan-param TState $state
	 * @return string|null
	 */
	public function violation( array $state ): ?string;
}
