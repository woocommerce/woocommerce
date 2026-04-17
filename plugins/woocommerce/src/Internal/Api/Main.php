<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Entry point for the WooCommerce GraphQL API.
 *
 * This class is intentionally free of PHP 8.0+ syntax so that it can be
 * loaded and called on PHP 7.4 without parse errors. The PHP-8.1-only
 * classes (GraphQLController, QueryCache, etc.) are resolved lazily from
 * the DI container only after is_enabled() confirms PHP 8.1+ is available.
 */
class Main {
	/**
	 * Feature flag slug registered in FeaturesController.
	 */
	private const FEATURE_SLUG = 'dual_code_graphql_api';

	/**
	 * Cached result of the feature-enabled check, null until first evaluated.
	 *
	 * @var ?bool
	 */
	private static ?bool $enabled = null;

	/**
	 * Check whether the Dual Code & GraphQL API feature is active.
	 *
	 * Requires PHP 8.1+ and the dual_code_graphql_api feature flag to be
	 * enabled. The result is cached for the lifetime of the request.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		if ( null === self::$enabled ) {
			self::$enabled = PHP_VERSION_ID >= 80100 && FeaturesUtil::feature_is_enabled( self::FEATURE_SLUG );
		}
		return self::$enabled;
	}

	/**
	 * Register the API or an autoloader guard.
	 *
	 * Safe to call on any PHP version — this file contains no PHP 8.0+
	 * syntax. When the feature is enabled (PHP 8.1+ and flag on), the
	 * GraphQLController is resolved from the DI container at rest_api_init
	 * time. When disabled, an autoloader guard is installed so that any
	 * reference to a class in the public Automattic\WooCommerce\Api\
	 * namespace gets a clear RuntimeException instead of a parse error.
	 */
	public static function register(): void {
		if ( self::is_enabled() ) {
			add_action(
				'rest_api_init',
				static function () {
					wc_get_container()->get( GraphQLController::class )->register();
				}
			);
		} else {
			self::register_version_guard();
		}
	}

	/**
	 * Register an autoloader guard on the public Code API namespace.
	 *
	 * Only installed when the feature is not active. Throws a
	 * RuntimeException for any class in Automattic\WooCommerce\Api\
	 * to prevent use of the disabled feature's classes.
	 */
	private static function register_version_guard(): void {
		spl_autoload_register(
			static function ( string $class ) {
				if ( 0 === strpos( $class, 'Automattic\\WooCommerce\\Api\\' ) ) {
					throw new \RuntimeException(
						esc_html__( 'The WooCommerce Dual Code and GraphQL API feature is not available. Requires PHP 8.1+ and the feature flag to be enabled.', 'woocommerce' )
					);
				}
			},
			true,
			true
		);
	}
}
