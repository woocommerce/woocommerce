<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Turns the extensible abilities feature on for one test, and puts the registries, the
 * action counts and the hooks back after it.
 */
trait ExtensibleAbilitiesTestTrait {

	/**
	 * The registries and the action counts from before the test changed them. Null when it did not.
	 *
	 * @var array{registries: array<string, array>, actions: array<string, int|null>}|null
	 */
	private ?array $extensible_abilities_before = null;

	/**
	 * Empty the registries of ExtensibleAbilities, so the test starts without resources and fields.
	 */
	private function isolate_extensible_abilities(): void {
		global $wp_actions;

		$registries = array();
		foreach ( array( 'resources', 'fields', 'validators', 'syncs', 'primes', 'filters' ) as $name ) {
			$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
			$property->setAccessible( true );
			$registries[ $name ] = $property->getValue();
			$property->setValue( null, array() );
		}

		$this->extensible_abilities_before ??= array(
			'registries' => $registries,
			'actions'    => array(
				'woocommerce_extensible_abilities_resources_init' => $wp_actions['woocommerce_extensible_abilities_resources_init'] ?? null,
				'woocommerce_extensible_abilities_init' => $wp_actions['woocommerce_extensible_abilities_init'] ?? null,
			),
		);
	}

	/**
	 * Turn the feature on with Core's resources registered, as a request does that starts with the feature on.
	 *
	 * @param callable|null $register Registers fields, validators, syncs or filters inside woocommerce_extensible_abilities_init.
	 */
	private function enable_extensible_abilities( ?callable $register = null ): void {
		$this->isolate_extensible_abilities();
		update_option( 'woocommerce_feature_' . ExtensibleAbilities::FEATURE_ID . '_enabled', 'yes' );

		$register_resources = array( AbilitiesLoader::class, 'register_core_resources' );
		$hooked             = has_action( 'woocommerce_extensible_abilities_resources_init', $register_resources );
		if ( false === $hooked ) {
			add_action( 'woocommerce_extensible_abilities_resources_init', $register_resources, 0 );
		}
		if ( $register ) {
			add_action( 'woocommerce_extensible_abilities_init', $register );
		}
		try {
			// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment -- Documented in AbilitiesLoader::init_extensions().
			do_action( 'woocommerce_extensible_abilities_resources_init' );
			do_action( 'woocommerce_extensible_abilities_init' );
			// phpcs:enable
		} finally {
			if ( false === $hooked ) {
				remove_action( 'woocommerce_extensible_abilities_resources_init', $register_resources, 0 );
			}
			if ( $register ) {
				remove_action( 'woocommerce_extensible_abilities_init', $register );
			}
		}
	}

	/**
	 * Put back what isolate_extensible_abilities() and enable_extensible_abilities() changed.
	 * The test transaction rolls the feature option back.
	 *
	 * @after
	 */
	public function restore_extensible_abilities(): void {
		global $wp_actions;

		if ( null === $this->extensible_abilities_before ) {
			return;
		}
		foreach ( $this->extensible_abilities_before['registries'] as $name => $value ) {
			$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
			$property->setAccessible( true );
			$property->setValue( null, $value );
		}
		foreach ( $this->extensible_abilities_before['actions'] as $action => $count ) {
			if ( null === $count ) {
				unset( $wp_actions[ $action ] );
			} else {
				$wp_actions[ $action ] = $count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the count from before the test.
			}
		}
		$this->extensible_abilities_before = null;
	}
}
