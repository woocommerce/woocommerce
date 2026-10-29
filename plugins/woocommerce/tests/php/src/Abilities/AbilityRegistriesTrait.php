<?php
/**
 * AbilityRegistriesTrait file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Abilities;

use Automattic\WooCommerce\Abilities\AbilityResources;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Abilities\PersonalData;
use Automattic\WooCommerce\Internal\Abilities\ResourceFields;

/**
 * The registries keep their state for the whole request, and all tests run in one request.
 */
trait AbilityRegistriesTrait {

	/**
	 * Register resources inside the resources action, the only place outside Core where it is accepted.
	 *
	 * @param string ...$resource_names Resources.
	 */
	protected function register_resources( string ...$resource_names ): void {
		$register = static function () use ( $resource_names ) {
			foreach ( $resource_names as $resource_name ) {
				AbilityResources::register_resource( $resource_name );
			}
		};
		add_action( 'woocommerce_extensible_abilities_resources_init', $register );
		do_action( 'woocommerce_extensible_abilities_resources_init' );
		remove_action( 'woocommerce_extensible_abilities_resources_init', $register );
	}

	/**
	 * Personal data is decided once per request, and a test is not a new request.
	 */
	protected static function forget_personal_data_decisions(): void {
		$shared = new \ReflectionProperty( PersonalData::class, 'shared' );
		$shared->setAccessible( true );
		$shared->setValue( null, array() );
		ResourceFields::forget();
	}

	/**
	 * Empty every static array that the registries keep, so array properties added later are reset too.
	 */
	protected static function reset_ability_resources(): void {
		foreach ( array( AbilityResources::class, ExtensibleAbilities::class, PersonalData::class, ResourceFields::class ) as $class ) {
			foreach ( ( new \ReflectionClass( $class ) )->getProperties( \ReflectionProperty::IS_STATIC ) as $property ) {
				$property->setAccessible( true );
				if ( is_array( $property->getValue() ) ) {
					$property->setValue( null, array() );
				}
			}
		}
	}

	/**
	 * Drop every registered resource, field and ability, so the next read fires the init actions again.
	 */
	protected static function reset_registries(): void {
		global $wp_actions;

		self::reset_ability_resources();
		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'], $wp_actions['woocommerce_extensible_abilities_resources_init'], $wp_actions['woocommerce_extensible_abilities_init'] );
	}
}
