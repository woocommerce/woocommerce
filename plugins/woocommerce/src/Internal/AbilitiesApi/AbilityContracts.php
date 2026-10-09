<?php
/**
 * Ability contracts feature gate class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * The experimental feature that lets extensions add fields to abilities.
 *
 * @since 11.3.0
 */
final class AbilityContracts {

	public const FEATURE_ID = 'ability_contracts';

	/**
	 * Whether the store enabled the feature.
	 */
	public static function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_ID );
	}
}
