<?php
/**
 * WooCommerce Admin Unit Tests Bootstrap
 *
 * @package WooCommerce\Admin\Tests
 */

use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;

/**
 * Class WC_Admin_Unit_Tests_Bootstrap
 */
class WC_Admin_Unit_Tests_Bootstrap {

	/** @var WC_Admin_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/** @var string WC core directory */
	public $wc_core_dir;

	/**
	 * Setup the unit testing environment.
	 */
	public function __construct() {
		ini_set( 'display_errors', 'on' ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Blacklisted
		error_reporting( E_ALL ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting

		// Ensure theme install tests use direct filesystem method.
		define( 'FS_METHOD', 'direct' );

		// Ensure server variable is set for WP email functions.
		// phpcs:disable WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		if ( ! isset( $_SERVER['SERVER_NAME'] ) ) {
			$_SERVER['SERVER_NAME'] = 'localhost';
		}
		// phpcs:enable WordPress.VIP.SuperGlobalInputUsage.AccessDetected

		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );
		$this->wc_core_dir  = getenv( 'WC_CORE_DIR' ) ? getenv( 'WC_CORE_DIR' ) : dirname( $this->plugin_dir ) . '/woocommerce';
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';

		$wc_tests_framework_base_dir = $this->wc_core_dir . '/tests';

		if ( ! is_dir( $wc_tests_framework_base_dir . '/framework' ) ) {
			$wc_tests_framework_base_dir .= '/legacy';
		}
		$this->wc_core_tests_dir = $wc_tests_framework_base_dir;

		// load test function so tests_add_filter() is available.
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// load WC.
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_wc' ) );

		// install WC.
		tests_add_filter( 'setup_theme', array( $this, 'install_wc' ) );

		// Set up WC-Admin config.
		tests_add_filter( 'woocommerce_admin_get_feature_config', array( $this, 'add_development_features' ) );

		/*
		* Load PHPUnit Polyfills for the WP testing suite.
		* @see https://github.com/WordPress/wordpress-develop/pull/1563/
		*/
		define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', __DIR__ . '/../vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' );

		// load the WP testing environment.
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';

		// load WC testing framework.
		$this->includes();

		// replace LegacyProxy class to MockableLegacyProxy from WC container.
		$this->replace_legacy_proxy();
	}

	/**
	 * Load WooCommerce Admin.
	 */
	public function load_wc() {
		define( 'WC_TAX_ROUNDING_MODE', 'auto' );
		define( 'WC_USE_TRANSACTIONS', false );
		update_option( 'woocommerce_enable_coupons', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_onboarding_opt_in', 'yes' );

		require_once $this->wc_core_dir . '/woocommerce.php';
		require $this->plugin_dir . '/vendor/autoload.php';
		require $this->plugin_dir . '/woocommerce-admin.php';
	}

	/**
	 * Install WooCommerce after the test environment and WC have been loaded.
	 */
	public function install_wc() {
		// Clean existing install first.
		define( 'WP_UNINSTALL_PLUGIN', true );
		define( 'WC_REMOVE_ALL_DATA', true );
		include $this->plugin_dir . '/uninstall.php';

		WC_Install::install();

		// Initialize the WC API extensions.
		\Automattic\WooCommerce\Internal\Admin\Install::create_tables();
		\Automattic\WooCommerce\Internal\Admin\Install::create_events();

		// Reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374.
		if ( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) ) {
			$GLOBALS['wp_roles']->reinit();
		} else {
			$GLOBALS['wp_roles'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			wp_roles();
		}

		echo esc_html( 'Installing WooCommerce and WooCommerce Admin...' . PHP_EOL );
	}

	/**
	 * Load WC-specific test cases and factories.
	 */
	public function includes() {
		// WooCommerce test classes.
		$wc_tests_framework_base_dir = $this->wc_core_tests_dir;

		// Framework.
		require_once $wc_tests_framework_base_dir . '/framework/class-wc-unit-test-factory.php';
		require_once $wc_tests_framework_base_dir . '/framework/class-wc-mock-session-handler.php';
		require_once $wc_tests_framework_base_dir . '/framework/class-wc-mock-wc-data.php';
		require_once $wc_tests_framework_base_dir . '/framework/class-wc-mock-wc-object-query.php';
		require_once $wc_tests_framework_base_dir . '/framework/class-wc-mock-payment-gateway.php';
		require_once $this->tests_dir . '/framework/class-wc-mock-enhanced-payment-gateway.php';
		require_once $wc_tests_framework_base_dir . '/framework/class-wc-payment-token-stub.php';
		require_once $wc_tests_framework_base_dir . '/framework/vendor/class-wp-test-spy-rest-server.php';

		// Test cases.
		require_once $wc_tests_framework_base_dir . '/includes/wp-http-testcase.php';
		require_once $wc_tests_framework_base_dir . '/framework/class-wc-unit-test-case.php';
		require_once $wc_tests_framework_base_dir . '/framework/class-wc-api-unit-test-case.php';
		require_once $wc_tests_framework_base_dir . '/framework/class-wc-rest-unit-test-case.php';

		// Helpers.
		require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-product.php';
		require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-coupon.php';
		require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-fee.php';
		require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-shipping.php';
		require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-customer.php';
		require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-order.php';
		require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-shipping-zones.php';
		require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-payment-token.php';
		require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-settings.php';

		// Include wc-admin helpers.
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-reports.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-admin-notes.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-test-action-queue.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-queue.php';
	}

	/**
	 * Use the `development` features for testing.
	 *
	 * @param array $flags Existing feature flags.
	 * @return array Filtered feature flags.
	 */
	public function add_development_features( $flags ) {
		$config = json_decode( file_get_contents( $this->plugin_dir . '/config/development.json' ) ); // @codingStandardsIgnoreLine.
		foreach ( $config->features as $feature => $bool ) {
			$flags[ $feature ] = $bool;
		}
		return $flags;
	}

	/**
	 * Get the single class instance.
	 * @return WC_Admin_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Replace LegacyProxy to MockableLegacyProxy from the WC container.
	 *
	 * @throws \Exception Thrown when reflection fails.
	 */
	private function replace_legacy_proxy() {
		try {
			$inner_container_property = new \ReflectionProperty( \Automattic\WooCommerce\Container::class, 'container' );
		} catch ( ReflectionException $ex ) {
			throw new \Exception( "Error when trying to get the private 'container' property from the " . \Automattic\WooCommerce\Container::class . ' class using reflection during unit testing bootstrap, has the property been removed or renamed?' );
		}

		$inner_container_property->setAccessible( true );
		$inner_container = $inner_container_property->getValue( wc_get_container() );

		$inner_container->replace( LegacyProxy::class, MockableLegacyProxy::class );
		$inner_container->reset_all_resolved();

		$GLOBALS['wc_container'] = $inner_container;
	}
}

WC_Admin_Unit_Tests_Bootstrap::instance();
