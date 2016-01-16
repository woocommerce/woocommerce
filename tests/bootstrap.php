<?php
/**
 * WooCommerce Unit Tests Bootstrap
 *
 * @since 2.2
 */
class WC_Unit_Tests_Bootstrap {

	/** @var \WC_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/**
	 * Setup the unit testing environment.
	 *
	 * @since 2.2
	 */
	public function __construct() {

		ini_set( 'display_errors','on' );
		error_reporting( E_ALL );

		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';

		// load test function so tests_add_filter() is available
		require_once( $this->wp_tests_dir . '/includes/functions.php' );

		// load WC
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_wc' ) );

		// install WC
		tests_add_filter( 'setup_theme', array( $this, 'install_wc' ) );

		// load the WP testing environment
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		// load WC testing framework
		$this->includes();
	}

	/**
	 * Load WooCommerce.
	 *
	 * @since 2.2
	 */
	public function load_wc() {
		require_once( $this->plugin_dir . '/woocommerce.php' );
	}

	/**
	 * Install WooCommerce after the test environment and WC have been loaded.
	 *
	 * @since 2.2
	 */
	public function install_wc() {

		// clean existing install first
		define( 'WP_UNINSTALL_PLUGIN', true );
		include( $this->plugin_dir . '/uninstall.php' );

		WC_Install::install();
		update_option( 'woocommerce_calc_shipping', 'yes' ); // Needed for tests cart and shipping methods

		// reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374
		$GLOBALS['wp_roles']->reinit();

		echo "Installing WooCommerce..." . PHP_EOL;
	}

	/**
	 * Load WC-specific test cases and factories.
	 *
	 * @since 2.2
	 */
	public function includes() {

		// factories
		require_once( $this->tests_dir . '/framework/factories/class-wc-unit-test-factory-for-webhook.php' );
		require_once( $this->tests_dir . '/framework/factories/class-wc-unit-test-factory-for-webhook-delivery.php' );

		// framework
		require_once( $this->tests_dir . '/framework/class-wc-unit-test-factory.php' );
		require_once( $this->tests_dir . '/framework/class-wc-mock-session-handler.php' );

		// test cases
		require_once( $this->tests_dir . '/framework/class-wc-unit-test-case.php' );
		require_once( $this->tests_dir . '/framework/class-wc-api-unit-test-case.php' );

		// Helpers
		require_once( $this->tests_dir . '/framework/helpers/class-wc-helper-product.php' );
		require_once( $this->tests_dir . '/framework/helpers/class-wc-helper-coupon.php' );
		require_once( $this->tests_dir . '/framework/helpers/class-wc-helper-fee.php' );
		require_once( $this->tests_dir . '/framework/helpers/class-wc-helper-shipping.php' );
		require_once( $this->tests_dir . '/framework/helpers/class-wc-helper-customer.php' );
		require_once( $this->tests_dir . '/framework/helpers/class-wc-helper-order.php' );
	}

	/**
	 * Get the single class instance.
	 *
	 * @since 2.2
	 * @return WC_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

WC_Unit_Tests_Bootstrap::instance();
