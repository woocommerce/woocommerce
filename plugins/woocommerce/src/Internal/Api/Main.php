<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

/**
 * Entry point for the WooCommerce GraphQL API. Registers the controller during initialization.
 */
class Main {
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
	 * Register the API. Called during WooCommerce initialization.
	 */
	public function register(): void {
		$this->register_version_guard();
		add_action( 'rest_api_init', array( $this->controller, 'register' ) );
	}

	/**
	 * On PHP < 8.1, register an autoloader that throws a clear error when
	 * any class in the public API namespace is used. Without this, extensions
	 * would get a cryptic parse error from Composer trying to load files
	 * that contain enums and other 8.1+ syntax.
	 */
	private function register_version_guard(): void {
		if ( PHP_VERSION_ID >= 80100 ) {
			return;
		}

		spl_autoload_register(
			static function ( string $class ): void {
				if ( 0 === strpos( $class, 'Automattic\\WooCommerce\\Api\\' ) ) {
					throw new \RuntimeException(
						sprintf(
							'The WooCommerce Code API requires PHP 8.1 or later. Current version: %s.',
							PHP_VERSION
						)
					);
				}
			},
			true,
			true
		);
	}
}
