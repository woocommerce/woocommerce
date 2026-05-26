<?php
/**
 * Abilities Categories class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Abilities Categories class for WooCommerce.
 *
 * Registers categories for WooCommerce abilities to improve organization
 * and discoverability in the WordPress Abilities API v0.3.0+.
 */
class AbilitiesCategories {

	/**
	 * Initialize category registration.
	 *
	 * @internal
	 */
	final public static function init(): void {
		/*
		 * Register categories when Abilities API categories are ready.
		 * Support both old (pre-6.9) and new (6.9+) action names.
		 */
		add_action( 'abilities_api_categories_init', array( __CLASS__, 'register_categories' ) );
		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_categories' ) );
	}

	/**
	 * Register WooCommerce ability categories.
	 *
	 * @since 10.9.0
	 */
	public static function register_categories(): void {
		// Only register if the function exists.
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		if ( ! function_exists( 'wp_has_ability_category' ) || ! wp_has_ability_category( 'woocommerce' ) ) {
			wp_register_ability_category(
				'woocommerce',
				array(
					'label'       => __( 'WooCommerce', 'woocommerce' ),
					'description' => __( 'Abilities for WooCommerce store operations, including core commerce features and extension-provided capabilities.', 'woocommerce' ),
				)
			);
		}

		if ( ! function_exists( 'wp_has_ability_category' ) || ! wp_has_ability_category( 'woocommerce-rest' ) ) {
			wp_register_ability_category(
				'woocommerce-rest',
				array(
					'label'       => __( 'WooCommerce REST API', 'woocommerce' ),
					'description' => __( 'REST API operations for store resources including products, orders, and other store data.', 'woocommerce' ),
				)
			);
		}
	}
}
