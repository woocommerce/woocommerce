<?php
/**
 * Domain ability base class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helpers for WooCommerce domain ability definitions.
 */
abstract class AbstractDomainAbility {

	use DomainAbilityHelpers;
}
