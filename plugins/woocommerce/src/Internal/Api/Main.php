<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Entry point for the WooCommerce GraphQL API. Registers the controller during initialization.
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
	 * The GraphQL controller instance.
	 *
	 * @var GraphQLController
	 */
	private GraphQLController $controller;

	/**
	 * DI: injected by WooCommerce container.
	 *
	 * @internal
	 * @param GraphQLController $controller The GraphQL controller.
	 */
	final public function init( GraphQLController $controller ): void {
		$this->controller = $controller;
	}

	/**
	 * Check whether the Dual Code & GraphQL API feature is active.
	 *
	 * The result is cached for the lifetime of the request so that
	 * repeated calls (e.g. from the autoloader guard) don't hit the
	 * options table each time.
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
	 * Register the API or an autoloader guard. Called during WooCommerce initialization.
	 */
	public function register(): void {
		if ( self::is_enabled() ) {
			add_action( 'rest_api_init', array( $this->controller, 'register' ) );
		} else {
			$this->register_version_guard();
		}
	}

	/**
	 * Register an autoloader guard on the public Code API namespace.
	 *
	 * Only installed when the feature is not active. Throws a
	 * RuntimeException for any class in `Automattic\WooCommerce\Api\`
	 * to prevent use of the disabled feature's classes.
	 */
	private function register_version_guard(): void {
		spl_autoload_register(
			static function ( string $class ): void {
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
