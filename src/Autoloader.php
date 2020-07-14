<?php
/**
 * Includes the composer Autoloader used for packages and classes in the src/ directory.
 *
 * @package Automattic/WooCommerce
 */

namespace Automattic\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Autoloader class.
 *
 * @since 3.7.0
 */
class Autoloader {

	/**
	 * Static-only class.
	 */
	private function __construct() {}

	/**
	 * These namespaces are autoloaded by our temporary PSR-4 autoloader. This enables us to bypass the limitations
	 * imposed by the 1.x branch of the Jetpack Autoloader until the 2.x branch is in-use.
	 *
	 * Note: Due to the way we load the files you must place more specific namespaces first or they won't be used!
	 *
	 * @var string[]
	 */
	private static $autoloaded_namespaces = array(
		'Psr\\Container\\'                        => __DIR__ . '/../vendor/psr/container/src/',
		'League\\Container\\'                     => __DIR__ . '/../vendor/league/container/src/',
		'Automattic\\WooCommerce\\Tests\\'        => __DIR__ . '/../tests/php/src/',
		'Automattic\\WooCommerce\\Testing\\Tools' => __DIR__ . '/../tests/Tools/',
		'Automattic\\WooCommerce\\'               => __DIR__ . '/',
	);

	/**
	 * These namespaces are excluded from being autoloaded by our temporary PSR-4 autoloader.
	 *
	 * @var string[]
	 */
	private static $excluded_namespaces = array(
		'Automattic\\WooCommerce\\Admin\\',
		'Automattic\\WooCommerce\\Blocks\\',
		'Automattic\\WooCommerce\\RestApi\\',
	);

	/**
	 * Require the autoloader and return the result.
	 *
	 * If the autoloader is not present, let's log the failure and display a nice admin notice.
	 *
	 * @return boolean
	 */
	public static function init() {
		$autoloader = dirname( __DIR__ ) . '/vendor/autoload_packages.php';

		if ( ! is_readable( $autoloader ) ) {
			self::missing_autoloader();
			return false;
		}

		self::register_psr4_autoloader();

		$autoloader_result = require $autoloader;
		if ( ! $autoloader_result ) {
			return false;
		}

		return $autoloader_result;
	}

	/**
	 * Define a PSR-4 autoloader to load any desired classes before the Jetpack Autoloader to avoid triggering its
	 * error messages.
	 *
	 * TODO: Remove after the JetPack Autoloader dependency has been updated to v2.
	 */
	protected static function register_psr4_autoloader() {
		spl_autoload_register(
			function ( $class ) {
				foreach ( self::$excluded_namespaces as $namespace ) {
					if ( substr( $class, 0, strlen( $namespace ) ) === $namespace ) {
						return;
					}
				}

				foreach ( self::$autoloaded_namespaces as $namespace => $directory ) {
					$len = strlen( $namespace );
					if ( substr( $class, 0, $len ) === $namespace ) {
						require $directory . str_replace( '\\', '/', substr( $class, $len ) ) . '.php';
						return;
					}
				}
			},
			true,
			true
		);
	}

	/**
	 * If the autoloader is missing, add an admin notice.
	 */
	protected static function missing_autoloader() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(  // phpcs:ignore
				esc_html__( 'Your installation of WooCommerce is incomplete. If you installed WooCommerce from GitHub, please refer to this document to set up your development environment: https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment', 'woocommerce' )
			);
		}
		add_action(
			'admin_notices',
			function() {
				?>
				<div class="notice notice-error">
					<p>
						<?php
						printf(
							/* translators: 1: is a link to a support document. 2: closing link */
							esc_html__( 'Your installation of WooCommerce is incomplete. If you installed WooCommerce from GitHub, %1$splease refer to this document%2$s to set up your development environment.', 'woocommerce' ),
							'<a href="' . esc_url( 'https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment' ) . '" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}
		);
	}
}
