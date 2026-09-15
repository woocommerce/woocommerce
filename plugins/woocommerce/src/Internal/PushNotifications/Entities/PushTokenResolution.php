<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Entities;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;

/**
 * Structured result of resolving push tokens for a set of user roles.
 *
 * @internal
 *
 * @since 11.2.0
 */
class PushTokenResolution {
	/** Resolution completed with at least one valid token. */
	const OUTCOME_RESOLVED = 'resolved';

	/** No roles were supplied for recipient resolution. */
	const OUTCOME_NO_ROLES = 'no_roles_requested';

	/** No push token records are registered. */
	const OUTCOME_NO_REGISTERED_TOKENS = 'no_registered_tokens';

	/** Registered token owners did not match the eligible roles. */
	const OUTCOME_NO_ELIGIBLE_USERS = 'no_eligible_users';

	/** Eligible users had no valid token records. */
	const OUTCOME_NO_VALID_TOKENS = 'no_valid_tokens';

	/** Every resolved token was removed by notification preferences. */
	const OUTCOME_FILTERED_BY_PREFERENCES = 'all_tokens_filtered_by_preferences';

	/**
	 * Valid resolution outcomes.
	 *
	 * @var string[]
	 */
	private const VALID_OUTCOMES = array(
		self::OUTCOME_RESOLVED,
		self::OUTCOME_NO_ROLES,
		self::OUTCOME_NO_REGISTERED_TOKENS,
		self::OUTCOME_NO_ELIGIBLE_USERS,
		self::OUTCOME_NO_VALID_TOKENS,
		self::OUTCOME_FILTERED_BY_PREFERENCES,
	);

	/**
	 * Resolved push tokens.
	 *
	 * @var PushToken[]
	 */
	private array $tokens;

	/**
	 * Resolution outcome.
	 *
	 * @var string
	 */
	private string $outcome;

	/**
	 * Number of distinct users that own registered token records.
	 *
	 * @var int
	 */
	private int $registered_token_owner_count;

	/**
	 * Number of token owners matching the eligible roles.
	 *
	 * @var int
	 */
	private int $eligible_user_count;

	/**
	 * Creates a structured push token resolution.
	 *
	 * @param PushToken[] $tokens                       The valid tokens resolved for eligible users.
	 * @param string      $outcome                      The resolution outcome.
	 * @param int         $registered_token_owner_count Number of distinct registered token owners.
	 * @param int         $eligible_user_count          Number of eligible token owners.
	 *
	 * @throws InvalidArgumentException If the outcome or counts are invalid.
	 *
	 * @since 11.2.0
	 */
	public function __construct(
		array $tokens,
		string $outcome,
		int $registered_token_owner_count,
		int $eligible_user_count
	) {
		if ( ! in_array( $outcome, self::VALID_OUTCOMES, true ) ) {
			throw new InvalidArgumentException( 'Invalid push token resolution outcome.' );
		}

		if ( 0 > $registered_token_owner_count || 0 > $eligible_user_count ) {
			throw new InvalidArgumentException( 'Push token resolution counts cannot be negative.' );
		}

		$this->tokens                       = $tokens;
		$this->outcome                      = $outcome;
		$this->registered_token_owner_count = $registered_token_owner_count;
		$this->eligible_user_count          = $eligible_user_count;
	}

	/**
	 * Returns the resolved push tokens.
	 *
	 * @return PushToken[]
	 *
	 * @since 11.2.0
	 */
	public function get_tokens(): array {
		return $this->tokens;
	}

	/**
	 * Returns the resolution outcome.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_outcome(): string {
		return $this->outcome;
	}

	/**
	 * Returns structured, non-sensitive resolution diagnostics.
	 *
	 * @return array{resolution_outcome: string, registered_token_owner_count: int, eligible_user_count: int, resolved_token_count: int}
	 *
	 * @since 11.2.0
	 */
	public function get_diagnostics(): array {
		return array(
			'resolution_outcome'           => $this->outcome,
			'registered_token_owner_count' => $this->registered_token_owner_count,
			'eligible_user_count'          => $this->eligible_user_count,
			'resolved_token_count'         => count( $this->tokens ),
		);
	}
}
