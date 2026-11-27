<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Turns the extensible abilities feature on for one test, and puts the registries back after it.
 * The WordPress test case restores the hooks and the action counts.
 */
trait ExtensibleAbilitiesTestTrait {

	/**
	 * The registries from before the test changed them. Null when it did not.
	 *
	 * @var array<string, array>|null
	 */
	private ?array $extensible_abilities_before = null;

	/**
	 * Empty the registries of ExtensibleAbilities, so the test starts with Core's resources and without fields.
	 * The resources are also registered with the feature off, because a write reads the ID of an object from its resource.
	 */
	private function isolate_extensible_abilities(): void {
		$registries = array();
		foreach ( array( 'resources', 'fields', 'validators', 'syncs', 'primes', 'filters' ) as $name ) {
			$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
			$property->setAccessible( true );
			$registries[ $name ] = $property->getValue();
			$property->setValue( null, array() );
		}
		$this->extensible_abilities_before ??= $registries;

		add_action( 'woocommerce_extensible_abilities_resources_init', array( AbilitiesLoader::class, 'register_core_resources' ), 0 );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented in AbilitiesLoader::init_extensions().
		do_action( 'woocommerce_extensible_abilities_resources_init' );
	}

	/**
	 * Turn the feature on with Core's resources registered, as a request does that starts with the feature on.
	 *
	 * @param callable|null $register Registers fields, validators, syncs or filters inside woocommerce_extensible_abilities_init.
	 */
	private function enable_extensible_abilities( ?callable $register = null ): void {
		$this->isolate_extensible_abilities();
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'yes' );

		if ( $register ) {
			add_action( 'woocommerce_extensible_abilities_init', $register );
		}
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented in AbilitiesLoader::init_extensions().
		do_action( 'woocommerce_extensible_abilities_init' );
	}

	/**
	 * Put the registries back. The test transaction rolls the feature option back.
	 *
	 * @after
	 */
	public function restore_extensible_abilities(): void {
		foreach ( $this->extensible_abilities_before ?? array() as $name => $value ) {
			$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
			$property->setAccessible( true );
			$property->setValue( null, $value );
		}
		$this->extensible_abilities_before = null;
	}
}
