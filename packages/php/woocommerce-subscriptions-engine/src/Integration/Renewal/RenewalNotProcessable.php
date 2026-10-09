<?php
/**
 * RenewalNotProcessable - a renewal cannot start: a pre-flight impossibility discovered
 * before any charge (no billing chain to advance, or a billing plan that no longer
 * resolves). Thrown by {@see RenewalEngine::process()} so the invoking flow decides the
 * response - the caller (which already holds the contract id) parks the contract, clearing
 * its next payment so it leaves the due window and cannot re-poison the scan every tick; a
 * repair re-arms it.
 *
 * A marker exception: the message carries the reason, the caller carries the contract.
 * Distinct from an ordinary skip (an idempotent no-op returns null, no park) and from a
 * charge that ran and did not settle (that settles the cycle `failed`, not an exception).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown when a renewal cannot be processed at all (a park-worthy pre-flight failure).
 */
final class RenewalNotProcessable extends RuntimeException {
}
