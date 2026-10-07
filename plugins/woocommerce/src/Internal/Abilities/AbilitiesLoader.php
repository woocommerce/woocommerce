<?php
/**
 * Abilities loader class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\OrderAddNote;
use Automattic\WooCommerce\Internal\Abilities\Domain\OrderUpdateStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\OrdersQuery;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductCreate;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductDelete;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductUpdate;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductsQuery;
use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityContracts;
use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityFields;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks WooCommerce ability definitions into the WordPress Abilities API.
 */
class AbilitiesLoader {

	/**
	 * Whether the loader hooks have been registered.
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Canonical WooCommerce domain ability definition classes.
	 *
	 * @var array<int, class-string>
	 */
	private const CORE_ABILITY_DEFINITION_CLASSES = array(
		OrdersQuery::class,
		OrderAddNote::class,
		OrderUpdateStatus::class,
		ProductsQuery::class,
		ProductCreate::class,
		ProductDelete::class,
		ProductUpdate::class,
	);

	/**
	 * The object type and output key of each core ability that returns extension fields.
	 *
	 * @var array<class-string, array{object_type: string, output: string}>
	 */
	private const EXTENSION_FIELDS = array(
		OrdersQuery::class       => array(
			'object_type' => 'order',
			'output'      => 'orders',
		),
		OrderAddNote::class      => array(
			'object_type' => 'order',
			'output'      => 'order',
		),
		OrderUpdateStatus::class => array(
			'object_type' => 'order',
			'output'      => 'order',
		),
		ProductsQuery::class     => array(
			'object_type' => 'product',
			'output'      => 'products',
		),
		ProductCreate::class     => array(
			'object_type' => 'product',
			'output'      => 'product',
		),
		ProductUpdate::class     => array(
			'object_type' => 'product',
			'output'      => 'product',
		),
	);

	/**
	 * Log source for ability registration notices.
	 *
	 * @var string
	 */
	private const LOG_SOURCE = 'woocommerce-abilities';

	/**
	 * Core ability instances registered by this loader in the current request.
	 *
	 * @var array<string, object>
	 */
	private static array $registered_core_abilities = array();

	/**
	 * Initialize ability registration hooks.
	 *
	 * @internal
	 *
	 * @since 10.9.0
	 */
	final public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		/*
		 * Register abilities when Abilities API is ready.
		 * Support both old (pre-6.9) and new (6.9+) action names.
		 */
		AbilitiesCategories::init();
		add_action( 'abilities_api_init', array( __CLASS__, 'register_abilities' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );

		AbilitiesRestBridge::init();
		AbilityFields::init();
		add_filter( 'woocommerce_ability_object', array( __CLASS__, 'load_object' ), 10, 3 );

		self::$initialized = true;
	}

	/**
	 * Load the product or order an ability output names, so its extension fields can be read.
	 *
	 * @internal
	 *
	 * @param mixed  $subject     Object another filter loaded.
	 * @param string $object_type Object type.
	 * @param mixed  $id          Object ID.
	 * @return mixed
	 */
	public static function load_object( $subject, $object_type, $id ) {
		if ( null !== $subject ) {
			return $subject;
		}
		if ( 'product' === $object_type ) {
			$subject = wc_get_product( $id );
		} elseif ( 'order' === $object_type ) {
			$subject = wc_get_order( $id );
		}
		return $subject ? $subject : null;
	}

	/**
	 * Register all configured ability definitions.
	 *
	 * @since 10.9.0
	 */
	public static function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( self::get_ability_definition_classes() as $class_name ) {
			if ( ! is_string( $class_name ) || ! class_exists( $class_name ) ) {
				continue;
			}

			if ( ! is_a( $class_name, AbilityDefinition::class, true ) ) {
				continue;
			}

			$ability_name = $class_name::get_name();

			if ( '' === $ability_name ) {
				continue;
			}

			$is_core_ability = self::is_core_ability_definition_class( $class_name );

			if ( self::is_reserved_woocommerce_ability_name( $ability_name ) && ! $is_core_ability ) {
				continue;
			}

			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_name ) ) {
				$existing_ability = function_exists( 'wp_get_ability' ) ? wp_get_ability( $ability_name ) : null;

				if (
					$is_core_ability
					&& isset( self::$registered_core_abilities[ $ability_name ] )
					&& $existing_ability === self::$registered_core_abilities[ $ability_name ]
				) {
					continue;
				}

				if ( ! $is_core_ability || ! function_exists( 'wp_unregister_ability' ) ) {
					continue;
				}

				// Drop stale instance tracking before replacing a shadowed registration.
				unset( self::$registered_core_abilities[ $ability_name ] );
				wp_unregister_ability( $ability_name );
				self::log_replaced_reserved_ability( $ability_name, $class_name );
			}

			$args = $class_name::get_registration_args();
			if ( isset( self::EXTENSION_FIELDS[ $class_name ] ) && AbilityContracts::is_enabled() ) {
				$args['meta'][ AbilityFields::META ]['extension_fields'] = self::EXTENSION_FIELDS[ $class_name ];
			}

			$registered_ability = wp_register_ability( $ability_name, $args );

			if ( $is_core_ability && null !== $registered_ability ) {
				self::$registered_core_abilities[ $ability_name ] = $registered_ability;
			}
		}
	}

	/**
	 * Log when WooCommerce replaces a pre-existing registration in its reserved namespace.
	 *
	 * @param string       $ability_name Ability name.
	 * @param class-string $class_name Ability definition class name.
	 */
	private static function log_replaced_reserved_ability( string $ability_name, string $class_name ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		wc_get_logger()->warning(
			'WooCommerce unregistered a previously registered ability before registering its canonical definition.',
			array(
				'source'           => self::LOG_SOURCE,
				'ability_name'     => $ability_name,
				'definition_class' => $class_name,
				'reserved_prefix'  => 'woocommerce/',
			)
		);
	}

	/**
	 * Check whether an ability definition class is provided by WooCommerce core.
	 *
	 * @param class-string $class_name Ability definition class name.
	 * @return bool
	 */
	private static function is_core_ability_definition_class( string $class_name ): bool {
		return in_array( $class_name, self::CORE_ABILITY_DEFINITION_CLASSES, true );
	}

	/**
	 * Check whether an ability name uses WooCommerce's reserved namespace.
	 *
	 * @param string $ability_name Ability name.
	 * @return bool
	 */
	private static function is_reserved_woocommerce_ability_name( string $ability_name ): bool {
		return 0 === strpos( $ability_name, 'woocommerce/' );
	}

	/**
	 * Get all ability definition classes that should be loaded.
	 *
	 * @return array<int, class-string>
	 */
	private static function get_ability_definition_classes(): array {
		/**
		 * Filter WooCommerce ability definition classes.
		 *
		 * Extensions can append autoloadable classes that implement
		 * {@see AbilityDefinition}. The loader will call get_name() and
		 * get_registration_args() on each definition class and register the ability on the
		 * Abilities API init hook. Returning a subset will not unregister core abilities;
		 * core classes are always retained.
		 *
		 * @since 10.9.0
		 *
		 * @param array<int, class-string> $classes Ability definition class names.
		 */
		$classes = apply_filters( 'woocommerce_ability_definition_classes', self::CORE_ABILITY_DEFINITION_CLASSES );

		if ( ! is_array( $classes ) ) {
			$classes = array();
		}

		return array_values(
			array_unique(
				array_filter(
					array_merge( self::CORE_ABILITY_DEFINITION_CLASSES, $classes ),
					'is_string'
				)
			)
		);
	}
}
