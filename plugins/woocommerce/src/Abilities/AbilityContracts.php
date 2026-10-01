<?php
/**
 * Ability contracts feature gate.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * The experimental feature that turns on in-memory writes, the field registry
 * and the validator registry.
 *
 * @since 11.3.0
 */
final class AbilityContracts {

	public const FEATURE_ID = 'ability_contracts';

	/**
	 * Whether the store enabled the feature.
	 *
	 * @since 11.3.0
	 */
	public static function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_ID );
	}
}
