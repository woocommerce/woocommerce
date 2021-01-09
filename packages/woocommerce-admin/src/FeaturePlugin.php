<?php
/**
 * WooCommerce Admin: Feature plugin main class.
 */

namespace Automattic\WooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Notes\Notes;
use \Automattic\WooCommerce\Admin\Notes\HistoricalData;
use \Automattic\WooCommerce\Admin\Notes\OrderMilestones;
use \Automattic\WooCommerce\Admin\Notes\WooSubscriptionsNotes;
use \Automattic\WooCommerce\Admin\Notes\TrackingOptIn;
use \Automattic\WooCommerce\Admin\Notes\WooCommercePayments;
use \Automattic\WooCommerce\Admin\Notes\InstallJPAndWCSPlugins;
use \Automattic\WooCommerce\Admin\Notes\DrawAttention;
use \Automattic\WooCommerce\Admin\Notes\CouponPageMoved;
use \Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsEngine;
use \Automattic\WooCommerce\Admin\Notes\HomeScreenFeedback;
use \Automattic\WooCommerce\Admin\Notes\SetUpAdditionalPaymentTypes;
use \Automattic\WooCommerce\Admin\Notes\TestCheckout;
use \Automattic\WooCommerce\Admin\Notes\SellingOnlineCourses;

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

		require_once WC_ADMIN_ABSPATH . '/src/Notes/DeprecatedNotes.php';
		require_once WC_ADMIN_ABSPATH . '/includes/core-functions.php';
		require_once WC_ADMIN_ABSPATH . '/includes/feature-config.php';
		require_once WC_ADMIN_ABSPATH . '/includes/page-controller-functions.php';
		require_once WC_ADMIN_ABSPATH . '/includes/wc-admin-update-functions.php';

		register_activation_hook( WC_ADMIN_PLUGIN_FILE, array( $this, 'on_activation' ) );
		register_deactivation_hook( WC_ADMIN_PLUGIN_FILE, array( $this, 'on_deactivation' ) );
		if ( did_action( 'plugins_loaded' ) ) {
			self::on_plugins_loaded();
		} else {
			// Make sure we hook into `plugins_loaded` before core's Automattic\WooCommerce\Package::init().
			// If core is network activated but we aren't, the packaged version of WooCommerce Admin will
			// attempt to use a data store that hasn't been loaded yet - because we've defined our constants here.
			// See: https://github.com/woocommerce/woocommerce-admin/issues/3869.
			add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), 9 );
		}
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
		Notes::clear_queued_actions();
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
		$this->define( 'WC_ADMIN_VERSION_NUMBER', '1.8.3' );
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
		API\Init::instance();
		ReportExporter::init();

		// CRUD classes.
		Notes::init();

		// Initialize category lookup.
		CategoryLookup::instance()->init();

		// Admin note providers.
		// @todo These should be bundled in the features/ folder, but loading them from there currently has a load order issue.
		new WooSubscriptionsNotes();
		new HistoricalData();
		new OrderMilestones();
		new TrackingOptIn();
		new WooCommercePayments();
		new InstallJPAndWCSPlugins();
		new DrawAttention();
		new HomeScreenFeedback();
		new SetUpAdditionalPaymentTypes();
		new TestCheckout();
		new SellingOnlineCourses();

		// Initialize RemoteInboxNotificationsEngine.
		RemoteInboxNotificationsEngine::init();
	}

	/**
	 * Set up our admin hooks and plugin loader.
	 */
	protected function hooks() {
		add_filter( 'woocommerce_admin_features', array( $this, 'replace_supported_features' ), 0 );

		Loader::get_instance();
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
				__( 'The WooCommerce Admin feature plugin requires <a href="%1$s">WooCommerce</a> %2$s or greater to be installed and active.', 'woocommerce' ),
				'https://wordpress.org/plugins/woocommerce/',
				$minimum_woocommerce_version
			);
		}

		if ( ! $wordpress_minimum_met ) {
			$errors[] = sprintf(
				/* translators: 1: URL of WordPress.org, 2: The minimum WordPress version number */
				__( 'The WooCommerce Admin feature plugin requires <a href="%1$s">WordPress</a> %2$s or greater to be installed and active.', 'woocommerce' ),
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
	public function has_satisfied_dependencies() {
		$dependency_errors = $this->get_dependency_errors();
		return 0 === count( $dependency_errors );
	}

	/**
	 * Deactivates this plugin.
	 */
	public function deactivate_self() {
		deactivate_plugins( plugin_basename( WC_ADMIN_PLUGIN_FILE ) );
		unset( $_GET['activate'] ); // phpcs:ignore CSRF ok.
	}

	/**
	 * Notify users of the plugin requirements.
	 */
	public function render_dependencies_notice() {
		$message = $this->get_dependency_errors();
		printf( '<div class="error"><p>%s</p></div>', implode( ' ', $message ) ); /* phpcs:ignore xss ok. */
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
	public function __wakeup() {
		die();
	}
}
