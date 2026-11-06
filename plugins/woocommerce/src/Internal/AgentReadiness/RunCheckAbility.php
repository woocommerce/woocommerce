<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Definition of the ability that checks a product again or queues a store check, for AbilitiesLoader.
 * No name while the feature is disabled, so the loader skips it.
 *
 * @since 11.4.0
 */
class RunCheckAbility implements AbilityDefinition {

	/**
	 * Ability name, or an empty string while the feature is disabled.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return wc_get_container()->get( ReadinessAbilities::class )->is_enabled() ? ReadinessAbilities::RUN_CHECK : '';
	}

	/**
	 * Registration arguments.
	 *
	 * @return array
	 */
	public static function get_registration_args(): array {
		return wc_get_container()->get( ReadinessAbilities::class )->registration_args( ReadinessAbilities::RUN_CHECK );
	}
}
