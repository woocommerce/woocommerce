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
	 * Option name for the "Enable GET endpoint" setting.
	 *
	 * When disabled, the GraphQL route only accepts POST requests.
	 */
	public const OPTION_GET_ENDPOINT_ENABLED = 'woocommerce_graphql_get_endpoint_enabled';

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
	 * Whether the GraphQL endpoint accepts GET requests.
	 *
	 * Defaults to false. Reads from the option written by the GraphQL
	 * settings section so the REST route registration can decide which
	 * HTTP methods to accept.
	 */
	public static function is_get_endpoint_enabled(): bool {
		return wc_string_to_bool( get_option( self::OPTION_GET_ENDPOINT_ENABLED, 'yes' ) );
	}

	/**
	 * Register the GraphQL endpoint when the feature is active.
	 *
	 * When the feature is off this is a no-op. Classes in the public
	 * Automattic\WooCommerce\Api\ namespace remain autoloadable — extensions
	 * that want to know whether the feature is active should check
	 * FeaturesUtil::feature_is_enabled( 'dual_code_graphql_api' ) rather
	 * than class_exists() on the Api namespace.
	 */
	public static function register(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		add_action(
			'rest_api_init',
			static function () {
				wc_get_container()->get( GraphQLController::class )->register();
			}
		);

		$settings = wc_get_container()->get( Settings::class );
		$settings->register();
	}
}
