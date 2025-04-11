<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Starter_Plugin
 */

/**
 * Class WC_Unit_Tests_Bootstrap.
 */
class Email_Editor_Unit_Tests_Bootstrap {

	/**
	 * Instance of the bootstrap class.
	 *
	 * @var WC_Unit_Tests_Bootstrap instance.
	 */
	protected static $instance = null;

	/**
	 * Path to WordPress tests directory.
	 *
	 * @var string $wp_tests_dir directory where wordpress-tests-lib is installed.
	 */
	public $wp_tests_dir;

	/**
	 * Path to tests directory.
	 *
	 * @var string $tests_dir testing directory.
	 */
	public $tests_dir;

	/**
	 * Path to plugin directory.
	 *
	 * @var string plugin directory.
	 */
	public $plugin_dir;

	/**
	 * Setup the unit testing environment.
	 */
	public function __construct() {

		$this->tests_dir  = __DIR__;
		$this->plugin_dir = dirname( dirname( $this->tests_dir ) );

		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : sys_get_temp_dir() . '/wordpress-tests-lib';

		// load test function so tests_add_filter() is available.
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// load WC.
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_plugin' ) );

		/*
		* Load PHPUnit Polyfills for the WP testing suite.
		* @see https://github.com/WordPress/wordpress-develop/pull/1563/
		*/
		define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', __DIR__ . '/../../vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' );

		// load the WP testing environment.
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';
		require_once $this->plugin_dir . '/tests/integration/Email_Editor_Integration_Test_Case.php';
	}

	/**
	 * Load WooCommerce.
	 */
	public function load_plugin(): void {
		require_once $this->plugin_dir . '/email-editor.php';
	}

	/**
	 * Get the single class instance.
	 *
	 * @return Email_Editor_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Email_Editor_Unit_Tests_Bootstrap::instance();
