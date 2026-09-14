<?php
/**
 * DuplicateCycleException - a cycle insert rejected by the chain's UNIQUE indexes
 * (`(contract_id, kind, count)` / `(contract_id, kind, sequence_no)`): the position is
 * already taken. This is the expected create-as-claim race signal, distinguished from
 * any other write failure so callers can treat the collision as benign without masking
 * real database errors.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown when a cycle insert collides with an existing chain row.
 */
final class DuplicateCycleException extends RuntimeException {
}
