<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Automattic/WooCommerce/RestApi
 */
namespace Automattic\WooCommerce\RestApi\UnitTests;

require __DIR__ . '/../src/Utilities/SingletonTrait.php';

use Automattic\WooCommerce\RestApi\Utilities\SingletonTrait;

class Bootstrap {
	use SingletonTrait;

	/**
	 * Directory path to WP core tests.
	 *
	 * @var string
	 */
	protected $wp_tests_dir;

	/**
	 * unit-tests directory.
	 *
	 * @var string
	 */
	protected $tests_dir;

	/**
	 * WC Core unit-tests directory.
	 *
	 * @var string
	 */
	protected $wc_tests_dir;

	/**
	 * This plugin directory.
	 *
	 * @var string
	 */
	protected $plugin_dir;

	/**
	 * Plugins directory.
	 *
	 * @var string
	 */
	protected $plugins_dir;

	/**
	 * Init unit testing library.
	 */
	public function init() {
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );

		if ( file_exists( dirname( $this->plugin_dir ) . '/woocommerce/woocommerce.php' ) ) {
			// From plugin directory.
			$this->plugins_dir = dirname( $this->plugin_dir );
		} else {
			// Travis.
			$this->plugins_dir = getenv( 'WP_CORE_DIR' ) . '/wp-content/plugins';
		}

		$this->wc_tests_dir = $this->plugins_dir . '/woocommerce/tests';

		$this->setup_hooks();
		$this->load_framework();
	}

	/**
	 * Get tests dir.
	 *
	 * @return string
	 */
	public function get_dir() {
		return dirname( __FILE__ );
	}

	/**
	 * Setup hooks.
	 */
	protected function setup_hooks() {
		// Give access to tests_add_filter() function.
		require_once $this->wp_tests_dir . '/includes/functions.php';

		\tests_add_filter( 'muplugins_loaded', function() {
			require_once $this->plugins_dir . '/woocommerce/woocommerce.php';
			require_once $this->plugin_dir . '/woocommerce-rest-api.php';
		} );

		\tests_add_filter( 'setup_theme', function() {
			echo \esc_html( 'Installing WooCommerce...' . PHP_EOL );

			define( 'WP_UNINSTALL_PLUGIN', true );
			define( 'WC_REMOVE_ALL_DATA', true );
			include $this->plugins_dir . '/woocommerce/uninstall.php';

			\WC_Install::install();

			$GLOBALS['wp_roles'] = null; // WPCS: override ok.
			\wp_roles();
		} );
	}

	/**
	 * Load the testing framework.
	 */
	protected function load_framework() {
		// Start up the WP testing environment.
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';

		// WooCommerce Core Testing Framework.
		require_once $this->wc_tests_dir . '/framework/class-wc-unit-test-factory.php';
		require_once $this->wc_tests_dir . '/framework/vendor/class-wp-test-spy-rest-server.php';
		require_once $this->wc_tests_dir . '/includes/wp-http-testcase.php';
		require_once $this->wc_tests_dir . '/framework/class-wc-unit-test-case.php';
		require_once $this->wc_tests_dir . '/framework/class-wc-rest-unit-test-case.php';

		require_once $this->tests_dir . '/Helpers/AdminNotesHelper.php';
		require_once $this->tests_dir . '/Helpers/CouponHelper.php';
		require_once $this->tests_dir . '/Helpers/CustomerHelper.php';
		require_once $this->tests_dir . '/Helpers/OrderHelper.php';
		require_once $this->tests_dir . '/Helpers/ProductHelper.php';
		require_once $this->tests_dir . '/Helpers/ShippingHelper.php';
		require_once $this->tests_dir . '/Helpers/SettingsHelper.php';
		require_once $this->tests_dir . '/Helpers/QueueHelper.php';
		require_once $this->tests_dir . '/AbstractRestApiTest.php';
	}
}

Bootstrap::instance()->init();
