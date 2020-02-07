<?php
/**
 * WooCommerce Admin: Feature plugin main class.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes_Facebook_Extension;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes_Historical_Data;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes_Order_Milestones;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes_Welcome_Message;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes_Woo_Subscriptions_Notes;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes_Tracking_Opt_In;

/**
 * Feature plugin main class.
 *
 * @internal This file will not be bundled with woo core, only the feature plugin.
 * @internal Note this is not called WC_Admin due to a class already existing in core with that name.
 */
class FeaturePlugin {
	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @return object Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Init the feature plugin, only if we can detect both Gutenberg and WooCommerce.
	 */
	public function init() {
		/**
		 * Filter allowing WooCommerce Admin to be disabled.
		 *
		 * @param bool $disabled False.
		 */
		if ( apply_filters( 'woocommerce_admin_disabled', false ) ) {
			return;
		}

		$this->define_constants();

		require_once WC_ADMIN_ABSPATH . '/includes/core-functions.php';
		require_once WC_ADMIN_ABSPATH . '/includes/feature-config.php';
		require_once WC_ADMIN_ABSPATH . '/includes/page-controller-functions.php';
		require_once WC_ADMIN_ABSPATH . '/includes/wc-admin-update-functions.php';

		register_activation_hook( WC_ADMIN_PLUGIN_FILE, array( $this, 'on_activation' ) );
		register_deactivation_hook( WC_ADMIN_PLUGIN_FILE, array( $this, 'on_deactivation' ) );
		if ( did_action( 'plugins_loaded' ) ) {
			self::on_plugins_loaded();
		} else {
			add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
		}
		add_filter( 'action_scheduler_store_class', array( $this, 'replace_actionscheduler_store_class' ) );
	}

	/**
	 * Install DB and create cron events when activated.
	 *
	 * @return void
	 */
	public function on_activation() {
		Install::create_tables();
		Install::create_events();
	}

	/**
	 * Remove WooCommerce Admin scheduled actions on deactivate.
	 *
	 * @return void
	 */
	public function on_deactivation() {
		// Don't clean up if the WooCommerce Admin package is in core.
		// NOTE: Any future divergence from the core package will need to be accounted for here.
		if ( defined( 'WC_ADMIN_PACKAGE_EXISTS' ) && WC_ADMIN_PACKAGE_EXISTS ) {
			return;
		}

		// Check if we are deactivating due to dependencies not being satisfied.
		// If WooCommerce is disabled we can't include files that depend upon it.
		if ( ! $this->has_satisfied_dependencies() ) {
			return;
		}

		$this->includes();
		ReportsSync::clear_queued_actions();
		WC_Admin_Notes::clear_queued_actions();
		wp_clear_scheduled_hook( 'wc_admin_daily' );
		wp_clear_scheduled_hook( 'generate_category_lookup_table' );
	}

	/**
	 * Setup plugin once all other plugins are loaded.
	 *
	 * @return void
	 */
	public function on_plugins_loaded() {
		$this->load_plugin_textdomain();

		if ( ! $this->has_satisfied_dependencies() ) {
			add_action( 'admin_init', array( $this, 'deactivate_self' ) );
			add_action( 'admin_notices', array( $this, 'render_dependencies_notice' ) );
			return;
		}

		if ( ! $this->check_build() ) {
			add_action( 'admin_notices', array( $this, 'render_build_notice' ) );
		}

		$this->includes();
		$this->hooks();
	}

	/**
	 * Define Constants.
	 */
	protected function define_constants() {
		$this->define( 'WC_ADMIN_APP', 'wc-admin-app' );
		$this->define( 'WC_ADMIN_ABSPATH', dirname( __DIR__ ) . '/' );
		$this->define( 'WC_ADMIN_DIST_JS_FOLDER', 'dist/' );
		$this->define( 'WC_ADMIN_DIST_CSS_FOLDER', 'dist/' );
		$this->define( 'WC_ADMIN_PLUGIN_FILE', WC_ADMIN_ABSPATH . 'woocommerce-admin.php' );
		// WARNING: Do not directly edit this version number constant.
		// It is updated as part of the prebuild process from the package.json value.
		$this->define( 'WC_ADMIN_VERSION_NUMBER', '0.25.1' );
	}

	/**
	 * Load Localisation files.
	 */
	protected function load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-admin', false, basename( dirname( __DIR__ ) ) . '/languages' );
	}

	/**
	 * Include WC Admin classes.
	 */
	public function includes() {
		// Initialize the WC API extensions.
		ReportsSync::init();
		Install::init();
		Events::instance()->init();
		new API\Init();
		ReportExporter::init();

		// CRUD classes.
		WC_Admin_Notes::init();

		// Initialize category lookup.
		CategoryLookup::instance()->init();

		// Admin note providers.
		// @todo These should be bundled in the features/ folder, but loading them from there currently has a load order issue.
		new WC_Admin_Notes_Woo_Subscriptions_Notes();
		new WC_Admin_Notes_Historical_Data();
		new WC_Admin_Notes_Order_Milestones();
		new WC_Admin_Notes_Welcome_Message();
		new WC_Admin_Notes_Facebook_Extension();
		new WC_Admin_Notes_Tracking_Opt_In();
	}

	/**
	 * Filter in our ActionScheduler Store class.
	 *
	 * @param string $store_class ActionScheduler Store class name.
	 * @return string ActionScheduler Store class name.
	 */
	public function replace_actionscheduler_store_class( $store_class ) {
		// Don't override any other overrides.
		if ( 'ActionScheduler_wpPostStore' !== $store_class ) {
			return $store_class;
		}

		// Don't override if action scheduler is 3.0.0 or greater.
		if ( version_compare( \ActionScheduler_Versions::instance()->latest_version(), '3.0', '>=' ) ) {
			return $store_class;
		}

		return 'Automattic\WooCommerce\Admin\Overrides\WPPostStore';
	}

	/**
	 * Set up our admin hooks and plugin loader.
	 */
	protected function hooks() {
		add_filter( 'woocommerce_admin_features', array( $this, 'replace_supported_features' ) );
		add_action( 'admin_menu', array( $this, 'register_devdocs_page' ) );

		new Loader();
	}

	/**
	 * Get an array of dependency error messages.
	 *
	 * @return array
	 */
	protected function get_dependency_errors() {
		$errors                      = array();
		$wordpress_version           = get_bloginfo( 'version' );
		$minimum_wordpress_version   = '5.3';
		$minimum_woocommerce_version = '3.6';
		$wordpress_minimum_met       = version_compare( $wordpress_version, $minimum_wordpress_version, '>=' );
		$woocommerce_minimum_met     = class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, $minimum_woocommerce_version, '>=' );

		if ( ! $woocommerce_minimum_met ) {
			$errors[] = sprintf(
				/* translators: 1: URL of WooCommerce plugin, 2: The minimum WooCommerce version number */
				__( 'The WooCommerce Admin feature plugin requires <a href="%1$s">WooCommerce</a> %2$s or greater to be installed and active.', 'woocommerce-admin' ),
				'https://wordpress.org/plugins/woocommerce/',
				$minimum_woocommerce_version
			);
		}

		if ( ! $wordpress_minimum_met ) {
			$errors[] = sprintf(
				/* translators: 1: URL of WordPress.org, 2: The minimum WordPress version number */
				__( 'The WooCommerce Admin feature plugin requires <a href="%1$s">WordPress</a> %2$s or greater to be installed and active.', 'woocommerce-admin' ),
				'https://wordpress.org/',
				$minimum_wordpress_version
			);
		}

		return $errors;
	}

	/**
	 * Returns true if all dependencies for the wc-admin plugin are loaded.
	 *
	 * @return bool
	 */
	protected function has_satisfied_dependencies() {
		$dependency_errors = $this->get_dependency_errors();
		return 0 === count( $dependency_errors );
	}

	/**
	 * Returns true if build file exists.
	 *
	 * @return bool
	 */
	protected function check_build() {
		return file_exists( plugin_dir_path( __DIR__ ) . '/dist/app/index.js' );
	}

	/**
	 * Deactivates this plugin.
	 */
	public function deactivate_self() {
		deactivate_plugins( plugin_basename( WC_ADMIN_PLUGIN_FILE ) );
		unset( $_GET['activate'] );
	}

	/**
	 * Notify users of the plugin requirements.
	 */
	public function render_dependencies_notice() {
		$message = $this->get_dependency_errors();
		printf( '<div class="error"><p>%s</p></div>', implode( ' ', $message ) ); /* phpcs:ignore xss ok. */
	}

	/**
	 * Notify users that the plugin needs to be built.
	 */
	public function render_build_notice() {
		$message_one = __( 'You have installed a development version of WooCommerce Admin which requires files to be built. From the plugin directory, run <code>npm install</code> to install dependencies, <code>npm run build</code> to build the files.', 'woocommerce-admin' );
		$message_two = sprintf(
			/* translators: 1: URL of GitHub Repository build page */
			__( 'Or you can download a pre-built version of the plugin by visiting <a href="%1$s">the releases page in the repository</a>.', 'woocommerce-admin' ),
			'https://github.com/woocommerce/woocommerce-admin/releases'
		);
		printf( '<div class="error"><p>%s %s</p></div>', $message_one, $message_two ); /* phpcs:ignore xss ok. */
	}

	/**
	 * Overwrites the allowed features array using a local `feature-config.php` file.
	 *
	 * @param array $features Array of feature slugs.
	 */
	public function replace_supported_features( $features ) {
		$feature_config = apply_filters( 'woocommerce_admin_get_feature_config', wc_admin_get_feature_config() );
		$features       = array_keys( array_filter( $feature_config ) );
		return $features;
	}

	/**
	 * Adds a menu item for the wc-admin devdocs.
	 */
	public function register_devdocs_page() {
		if ( Loader::is_dev() ) {
			wc_admin_register_page(
				array(
					'title'  => 'DevDocs',
					'parent' => 'woocommerce',
					'path'   => '/devdocs',
				)
			);
		}
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	protected function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserializing.
	 */
	private function __wakeup() {}
}
