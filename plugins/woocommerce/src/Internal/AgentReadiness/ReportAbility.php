<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Abilities\AbilityDefinition;

defined( 'ABSPATH' ) || exit;

/**
 * Definition of the ability that reads the readiness report, for AbilitiesLoader.
 * No name while the feature is disabled, so the loader skips it.
 *
 * @since 11.4.0
 */
class ReportAbility implements AbilityDefinition {

	/**
	 * Ability name, or an empty string while the feature is disabled.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return wc_get_container()->get( ReadinessAbilities::class )->is_enabled() ? ReadinessAbilities::GET_REPORT : '';
	}

	/**
	 * Registration arguments.
	 *
	 * @return array
	 */
	public static function get_registration_args(): array {
		return wc_get_container()->get( ReadinessAbilities::class )->registration_args( ReadinessAbilities::GET_REPORT );
	}
}
