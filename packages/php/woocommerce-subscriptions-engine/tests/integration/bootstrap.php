<?php
/**
 * Integration-test bootstrap for the WooCommerce Subscriptions Engine.
 *
 * Loads the WordPress test framework, the engine plugin file, and installs the
 * baseline schema once up front so per-test transaction rollback (provided by
 * WP_UnitTestCase) keeps each test isolated without re-running DDL.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed -- Bootstrap file mixes a class and procedural setup.

/**
 * Bootstrap runner for the integration suite.
 */
class SubscriptionsEngineTestsBootstrap {

	/**
	 * Singleton instance.
	 *
	 * @var SubscriptionsEngineTestsBootstrap|null
	 */
	protected static $instance = null;

	/**
	 * Path to the WordPress tests directory.
	 *
	 * @var string
	 */
	public $wp_tests_dir;

	/**
	 * Path to this tests directory.
	 *
	 * @var string
	 */
	public $tests_dir;

	/**
	 * Path to the package root.
	 *
	 * @var string
	 */
	public $plugin_dir;

	/**
	 * Set up the integration testing environment.
	 */
	public function __construct() {
		$this->tests_dir  = __DIR__;
		$this->plugin_dir = dirname( dirname( $this->tests_dir ) );

		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : sys_get_temp_dir() . '/wordpress-tests-lib';

		require_once $this->wp_tests_dir . '/includes/functions.php';

		tests_add_filter( 'muplugins_loaded', array( $this, 'load_plugin' ) );

		if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
			define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', __DIR__ . '/../../vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' );
		}

		require_once $this->wp_tests_dir . '/includes/bootstrap.php';

		// Install once, outside any test transaction, so the init-hook installer
		// short-circuits during tests and DDL never breaks rollback isolation.
		SchemaInstaller::install();

		require_once $this->plugin_dir . '/tests/integration/EngineIntegrationTestCase.php';
	}

	/**
	 * Load the engine plugin file.
	 */
	public function load_plugin(): void {
		require_once $this->plugin_dir . '/woocommerce-subscriptions-engine.php';
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return SubscriptionsEngineTestsBootstrap
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

SubscriptionsEngineTestsBootstrap::instance();
