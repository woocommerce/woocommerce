<?php
/**
 * WooCommerce Cache Optimizer
 * 
 * This class provides comprehensive cache optimization for WooCommerce,
 * including conditional cookie management and CDN-friendly configurations.
 * 
 * @package WooCommerce/Includes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Cache_Optimizer class.
 */
class WC_Cache_Optimizer {

	/**
	 * Single instance of the class.
	 *
	 * @var WC_Cache_Optimizer
	 */
	protected static $instance = null;

	/**
	 * Whether cache optimization is enabled.
	 *
	 * @var bool
	 */
	private $enabled = false;

	/**
	 * Configuration options.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Get single instance of the class.
	 *
	 * @return WC_Cache_Optimizer
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize the cache optimizer.
	 */
	private function init() {
		// Check if optimization should be enabled
		$this->enabled = $this->should_enable_optimization();
		
		if ( ! $this->enabled ) {
			return;
		}

		// Load configuration
		$this->load_options();

		// Initialize hooks
		$this->init_hooks();

		// Replace default cart session handler
		$this->replace_cart_session_handler();
	}

	/**
	 * Check if cache optimization should be enabled.
	 *
	 * @return bool
	 */
	private function should_enable_optimization() {
		// Enable by default, but allow filtering
		$enabled = apply_filters( 'woocommerce_cache_optimization_enabled', true );

		// Disable in admin
		if ( is_admin() ) {
			$enabled = false;
		}

		// Disable if explicitly disabled via constant
		if ( defined( 'WC_DISABLE_CACHE_OPTIMIZATION' ) && WC_DISABLE_CACHE_OPTIMIZATION ) {
			$enabled = false;
		}

		return $enabled;
	}

	/**
	 * Load configuration options.
	 */
	private function load_options() {
		$this->options = apply_filters( 'woocommerce_cache_optimization_options', array(
			'optimize_cart_cookies' => true,
			'optimize_session_cookies' => false, // Keep session cookies for security
			'disable_cart_fragments_on_static_pages' => true,
			'cache_friendly_ajax' => true,
			'debug_mode' => false,
		) );
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Add cache-friendly headers
		add_action( 'wp', array( $this, 'add_cache_headers' ), 5 );

		// Optimize cart fragments
		if ( $this->options['disable_cart_fragments_on_static_pages'] ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_disable_cart_fragments' ) );
		}

		// Add debug information
		if ( $this->options['debug_mode'] ) {
			add_action( 'wp_footer', array( $this, 'add_debug_info' ) );
		}

		// Add admin settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Replace the default cart session handler with optimized version.
	 */
	private function replace_cart_session_handler() {
		if ( ! $this->options['optimize_cart_cookies'] ) {
			return;
		}

		// Hook into WooCommerce initialization
		add_action( 'woocommerce_init', array( $this, 'replace_cart_session' ), 5 );
	}

	/**
	 * Replace cart session handler.
	 */
	public function replace_cart_session() {
		if ( ! class_exists( 'WC_Cache_Optimized_Cart_Session' ) ) {
			require_once WC_ABSPATH . 'includes/class-wc-cache-optimized-cart-session.php';
		}

		// Replace the cart session
		remove_action( 'woocommerce_cart_loaded_from_session', array( WC()->cart, 'get_cart_from_session' ) );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'load_optimized_cart_session' ) );
	}

	/**
	 * Load optimized cart session.
	 */
	public function load_optimized_cart_session() {
		// Create optimized session handler
		$optimized_session = new WC_Cache_Optimized_Cart_Session( WC()->cart );
		
		// Replace the session in the cart object
		WC()->cart->session = $optimized_session;
	}

	/**
	 * Add cache-friendly headers.
	 */
	public function add_cache_headers() {
		// Only add headers if we're not on a dynamic page
		if ( $this->is_dynamic_page() ) {
			return;
		}

		// Add cache-friendly headers
		if ( ! headers_sent() ) {
			header( 'Cache-Control: public, max-age=3600' ); // Cache for 1 hour
			header( 'Vary: Cookie' ); // Tell CDN to vary on cookies
		}
	}

	/**
	 * Check if current page is dynamic (requires real-time data).
	 *
	 * @return bool
	 */
	private function is_dynamic_page() {
		// Dynamic pages that should not be cached
		$dynamic_pages = array(
			'cart',
			'checkout',
			'my-account',
			'order-received',
			'order-pay',
		);

		foreach ( $dynamic_pages as $page ) {
			if ( is_wc_endpoint_url( $page ) || is_page( $page ) ) {
				return true;
			}
		}

		// Check if user is logged in
		if ( is_user_logged_in() ) {
			return true;
		}

		// Check if cart has items
		if ( ! WC()->cart->is_empty() ) {
			return true;
		}

		return false;
	}

	/**
	 * Maybe disable cart fragments on static pages.
	 */
	public function maybe_disable_cart_fragments() {
		// Don't disable on dynamic pages
		if ( $this->is_dynamic_page() ) {
			return;
		}

		// Don't disable on product pages (they need add-to-cart functionality)
		if ( is_product() ) {
			return;
		}

		// Disable cart fragments script
		wp_dequeue_script( 'wc-cart-fragments' );
		wp_deregister_script( 'wc-cart-fragments' );
	}

	/**
	 * Add debug information to footer.
	 */
	public function add_debug_info() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = array(
			'Cache Optimization' => $this->enabled ? 'Enabled' : 'Disabled',
			'Dynamic Page' => $this->is_dynamic_page() ? 'Yes' : 'No',
			'Cart Empty' => WC()->cart->is_empty() ? 'Yes' : 'No',
			'User Logged In' => is_user_logged_in() ? 'Yes' : 'No',
		);

		if ( WC()->cart->session && method_exists( WC()->cart->session, 'get_cache_optimization_status' ) ) {
			$status = array_merge( $status, WC()->cart->session->get_cache_optimization_status() );
		}

		echo '<div id="wc-cache-debug" style="position: fixed; bottom: 10px; right: 10px; background: #000; color: #fff; padding: 10px; font-size: 12px; z-index: 9999;">';
		echo '<strong>WooCommerce Cache Debug:</strong><br>';
		foreach ( $status as $key => $value ) {
			echo esc_html( $key ) . ': ' . esc_html( $value ) . '<br>';
		}
		echo '</div>';
	}

	/**
	 * Register admin settings.
	 */
	public function register_settings() {
		register_setting( 'woocommerce_cache_optimization', 'woocommerce_cache_optimization_options' );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Cache Optimization', 'woocommerce' ),
			__( 'Cache Optimization', 'woocommerce' ),
			'manage_woocommerce',
			'wc-cache-optimization',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Admin page callback.
	 */
	public function admin_page() {
		$options = get_option( 'woocommerce_cache_optimization_options', $this->options );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WooCommerce Cache Optimization', 'woocommerce' ); ?></h1>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'woocommerce_cache_optimization' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Optimize Cart Cookies', 'woocommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="woocommerce_cache_optimization_options[optimize_cart_cookies]" value="1" <?php checked( $options['optimize_cart_cookies'] ); ?> />
								<?php esc_html_e( 'Only set cart cookies when necessary for functionality', 'woocommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Disable Cart Fragments on Static Pages', 'woocommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="woocommerce_cache_optimization_options[disable_cart_fragments_on_static_pages]" value="1" <?php checked( $options['disable_cart_fragments_on_static_pages'] ); ?> />
								<?php esc_html_e( 'Disable cart fragments AJAX on pages that don\'t need them', 'woocommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Debug Mode', 'woocommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="woocommerce_cache_optimization_options[debug_mode]" value="1" <?php checked( $options['debug_mode'] ); ?> />
								<?php esc_html_e( 'Show debug information on frontend (admin only)', 'woocommerce' ); ?>
							</label>
						</td>
					</tr>
				</table>
				
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get optimization status.
	 *
	 * @return array
	 */
	public function get_status() {
		return array(
			'enabled' => $this->enabled,
			'options' => $this->options,
			'dynamic_page' => $this->is_dynamic_page(),
		);
	}
}