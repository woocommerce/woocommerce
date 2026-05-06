<?php
/**
 * Abilities loader class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

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
	 * Directory containing bundled domain ability definitions.
	 *
	 * @var string
	 */
	private const DOMAIN_ABILITIES_DIRECTORY = __DIR__ . '/Domain';

	/**
	 * Namespace containing bundled domain ability definitions.
	 *
	 * @var string
	 */
	private const DOMAIN_ABILITIES_NAMESPACE = __NAMESPACE__ . '\\Domain\\';

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
		add_action( 'abilities_api_categories_init', array( AbilitiesCategories::class, 'register_categories' ) );
		add_action( 'wp_abilities_api_categories_init', array( AbilitiesCategories::class, 'register_categories' ) );
		add_action( 'abilities_api_init', array( __CLASS__, 'register_abilities' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );

		AbilitiesRestBridge::init();

		self::$initialized = true;
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

			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_name ) ) {
				continue;
			}

			wp_register_ability( $ability_name, $class_name::get_registration_args() );
		}
	}

	/**
	 * Get all ability definition classes that should be loaded.
	 *
	 * @return array<int, class-string>
	 */
	private static function get_ability_definition_classes(): array {
		$classes = self::discover_core_ability_definition_classes();

		/**
		 * Filter WooCommerce ability definition classes.
		 *
		 * Extensions can append autoloadable classes that implement
		 * {@see AbilityDefinition}. The loader will call get_name() and
		 * get_registration_args() on each definition class and register the ability on the
		 * Abilities API init hook.
		 *
		 * @since 10.9.0
		 *
		 * @param array<int, class-string> $classes Ability definition class names.
		 */
		$classes = apply_filters( 'woocommerce_ability_definition_classes', $classes );

		if ( ! is_array( $classes ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( $classes, 'is_string' ) ) );
	}

	/**
	 * Discover bundled WooCommerce ability definition classes from the Domain directory.
	 *
	 * @return array<int, class-string>
	 */
	private static function discover_core_ability_definition_classes(): array {
		if ( ! is_dir( self::DOMAIN_ABILITIES_DIRECTORY ) ) {
			return array();
		}

		$classes  = array();
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator(
				self::DOMAIN_ABILITIES_DIRECTORY,
				\FilesystemIterator::SKIP_DOTS
			)
		);

		foreach ( $iterator as $file ) {
			if ( ! $file instanceof \SplFileInfo || 'php' !== $file->getExtension() ) {
				continue;
			}

			$relative_path = substr( $file->getPathname(), strlen( self::DOMAIN_ABILITIES_DIRECTORY ) + 1 );
			$class_name    = self::DOMAIN_ABILITIES_NAMESPACE . str_replace(
				DIRECTORY_SEPARATOR,
				'\\',
				substr( $relative_path, 0, -4 )
			);

			if ( class_exists( $class_name ) && is_a( $class_name, AbilityDefinition::class, true ) ) {
				$classes[] = $class_name;
			}
		}

		sort( $classes );

		return $classes;
	}
}
