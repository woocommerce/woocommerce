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
	protected $wp_tests_dir;

	/** @var string testing directory */
	protected $tests_dir;

	/** @var string plugin directory */
	protected $plugin_dir;


	/**
	 * Setup the unit testing environment
	 *
	 * @since 2.2
	 */
	public function __construct() {

		ini_set( 'display_errors','on' );
		error_reporting( E_ALL );

		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : $this->plugin_dir . '/tmp/wordpress-tests-lib';

		// load the WP testing environment
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		$this->install_wc();

		activate_plugin( 'woocommerce/woocommerce.php' );

		$this->includes();
	}


	/**
	 * Install WooCommerce after the test environment has loaded, but prior
	 * to activating the plugin
	 *
	 * @since 2.2
	 */
	public function install_wc() {

		echo 'Installing WooCommerce...' . PHP_EOL;

		require_once( $this->plugin_dir . '/woocommerce.php' );

		$installer = include( $this->plugin_dir . '/includes/class-wc-install.php' );
		$installer->install();

		$GLOBALS['current_user'] = new WP_User(1);
		$GLOBALS['current_user']->set_role('administrator');
	}


	/**
	 * Load WC-specific test cases and factories
	 *
	 * @since 2.2
	 */
	public function includes() {

		require_once( $this->tests_dir . '/framework/wc-unit-test-factory.php' );
		require_once( $this->tests_dir . '/framework/class-wc-unit-test-case.php' );
		require_once( $this->tests_dir . '/framework/class-wc-api-unit-test-case.php' );
	}


	/**
	 * Get the single class instance
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
