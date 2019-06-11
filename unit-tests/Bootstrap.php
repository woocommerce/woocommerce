<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WooCommerce/RestApi
 */
namespace WooCommerce\RestApi\UnitTests;

require __DIR__ . '/../src/Utilities/SingletonTrait.php';

use WooCommerce\Utilities\SingletonTrait;

class Bootstrap {
	use SingletonTrait;

	/**
	 * Directory path to WP core tests.
	 *
	 * @var string
	 */
	protected $wp_tests_dir;

	/**
	 * Directory path to woocommerce core tests.
	 *
	 * @var string
	 */
	protected $wc_tests_dir;
	/**
	 * Init unit testing library.
	 */
	public function init() {
		$this->wc_tests_dir = dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce/tests';
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' );

		if ( ! $this->wp_tests_dir ) {
			$this->wp_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
		}

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
	 * Does WC Admin exist?
	 *
	 * @return boolean
	 */
	protected function wc_admin_exists() {
		return file_exists( dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce-admin/woocommerce-admin.php' );
	}

	/**
	 * Setup hooks.
	 */
	protected function setup_hooks() {
		// Give access to tests_add_filter() function.
		require_once $this->wp_tests_dir . '/includes/functions.php';

		\tests_add_filter( 'muplugins_loaded', function() {
			require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce/woocommerce.php';
			require_once dirname( dirname( __FILE__ ) ) . '/woocommerce-rest-api.php';

			if ( $this->wc_admin_exists() ) {
				require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce-admin/woocommerce-admin.php';
			}
		} );

		\tests_add_filter( 'setup_theme', function() {
			echo \esc_html( 'Installing WooCommerce...' . PHP_EOL );

			define( 'WP_UNINSTALL_PLUGIN', true );
			define( 'WC_REMOVE_ALL_DATA', true );
			include dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce/uninstall.php';

			\WC_Install::install();

			if ( $this->wc_admin_exists() ) {
				echo esc_html( 'Installing WooCommerce Admin...' . PHP_EOL );
				require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce-admin/includes/class-wc-admin-install.php';
				\WC_Admin_Install::create_tables();
				\WC_Admin_Install::create_events();
			}

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

		require_once dirname( __FILE__ ) . '/Helpers/AdminNotesHelper.php';
		require_once dirname( __FILE__ ) . '/Helpers/CouponHelper.php';
		require_once dirname( __FILE__ ) . '/Helpers/CustomerHelper.php';
		require_once dirname( __FILE__ ) . '/Helpers/OrderHelper.php';
		require_once dirname( __FILE__ ) . '/Helpers/ProductHelper.php';
		require_once dirname( __FILE__ ) . '/Helpers/ShippingHelper.php';
		require_once dirname( __FILE__ ) . '/Helpers/SettingsHelper.php';
		require_once dirname( __FILE__ ) . '/Helpers/QueueHelper.php';
		require_once dirname( __FILE__ ) . '/AbstractRestApiTest.php';
		require_once dirname( __FILE__ ) . '/AbstractReportsTest.php';
	}
}

Bootstrap::instance()->init();
