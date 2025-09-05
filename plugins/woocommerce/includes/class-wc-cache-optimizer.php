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
	 * Track cookies that were blocked during this request.
	 *
	 * @var array
	 */
	private $blocked_cookies = array();

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
	 * Load configuration options from database with fallback to defaults.
	 */
	private function load_options() {
		// Default options
		$default_options = array(
			'optimize_cart_cookies' => true,
			'optimize_session_cookies' => false, // Keep session cookies for security
			'disable_cart_fragments_on_static_pages' => true,
			'cache_friendly_ajax' => true,
			'debug_mode' => false,
		);

		// Get options from database, merge with defaults
		$saved_options = get_option( 'woocommerce_cache_optimization_options', array() );
		$this->options = wp_parse_args( $saved_options, $default_options );

		// Allow filtering of the final options
		$this->options = apply_filters( 'woocommerce_cache_optimization_options', $this->options );
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

		// Add admin menu
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Initialize cache optimization for cart cookies.
	 */
	private function replace_cart_session_handler() {
		if ( ! $this->options['optimize_cart_cookies'] ) {
			return;
		}

		// Use the existing WooCommerce filter system to control cookie behavior
		add_filter( 'woocommerce_set_cookie_enabled', array( $this, 'maybe_disable_cart_cookies' ), 10, 5 );
	}

	/**
	 * Conditionally disable cart cookies based on context.
	 *
	 * @param bool   $enabled Whether cookie should be set.
	 * @param string $name    Cookie name.
	 * @param string $value   Cookie value.
	 * @param int    $expire  Cookie expiration.
	 * @param bool   $secure  Whether cookie is secure.
	 * @return bool
	 */
	public function maybe_disable_cart_cookies( $enabled, $name, $value, $expire, $secure ) {
		// Always allow session cookies (they're essential for security)
		if ( strpos( $name, 'wp_woocommerce_session_' ) === 0 ) {
			return $enabled;
		}

		// For cart cookies, check if we're in a context that requires them
		if ( in_array( $name, array( 'woocommerce_items_in_cart', 'woocommerce_cart_hash' ), true ) ) {
			$should_allow = $this->requires_cookies_for_context();
			
			// Track blocked cookies for debug info
			if ( ! $should_allow ) {
				$this->blocked_cookies[] = array(
					'name' => $name,
					'value' => $value,
					'reason' => 'Context does not require cookies',
					'timestamp' => time(),
				);
			}
			
			return $should_allow;
		}

		// Allow other cookies by default
		return $enabled;
	}

	/**
	 * Check if cookies are required for the current context.
	 *
	 * @return bool
	 */
	private function requires_cookies_for_context() {
		// Always require cookies for non-cacheable pages
		if ( $this->is_dynamic_page() ) {
			return true;
		}

		// Always require cookies for AJAX requests
		if ( wp_doing_ajax() ) {
			return true;
		}

		// Require cookies if user is logged in (they might have saved cart)
		if ( is_user_logged_in() ) {
			return true;
		}

		// Require cookies if there are existing cart cookies (user has items in cart)
		if ( isset( $_COOKIE['woocommerce_items_in_cart'] ) || isset( $_COOKIE['woocommerce_cart_hash'] ) ) {
			return true;
		}

		// Note: Product pages with add-to-cart buttons don't need cookies
		// because add-to-cart works via AJAX and cookies are set after the action

		// Default to not requiring cookies for better caching
		return false;
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

		$status = $this->get_status();
		$cookie_info = $this->get_cookie_debug_info();

		echo '<div id="wc-cache-debug" style="position: fixed; bottom: 10px; right: 10px; background: #000; color: #fff; padding: 10px; font-size: 12px; z-index: 9999; max-width: 400px; max-height: 80vh; overflow-y: auto;">';
		echo '<strong>WooCommerce Cache Debug:</strong><br><br>';
		
		// Show optimization status
		echo '<strong>Optimization Status:</strong><br>';
		foreach ( $status as $key => $value ) {
			if ( is_bool( $value ) ) {
				$value = $value ? 'Yes' : 'No';
			} elseif ( is_array( $value ) ) {
				$value = print_r( $value, true );
			}
			echo esc_html( $key ) . ': ' . esc_html( $value ) . '<br>';
		}
		
		echo '<br><strong>Cookie Information:</strong><br>';
		foreach ( $cookie_info as $key => $value ) {
			echo esc_html( $key ) . ': ' . esc_html( $value ) . '<br>';
		}
		
		echo '</div>';
	}

	/**
	 * Get detailed cookie debug information.
	 *
	 * @return array
	 */
	private function get_cookie_debug_info() {
		$info = array();
		
		// Check WooCommerce-specific cookies
		$wc_cookies = array(
			'woocommerce_items_in_cart',
			'woocommerce_cart_hash',
			'woocommerce_recently_viewed',
		);
		
		// Check session cookies
		$session_cookies = array();
		foreach ( $_COOKIE as $name => $value ) {
			if ( strpos( $name, 'wp_woocommerce_session_' ) === 0 ) {
				$session_cookies[] = $name;
			}
		}
		
		// Count total cookies
		$total_cookies = count( $_COOKIE );
		$wc_cookie_count = 0;
		$session_cookie_count = count( $session_cookies );
		
		// Check which WooCommerce cookies are present
		$present_wc_cookies = array();
		foreach ( $wc_cookies as $cookie ) {
			if ( isset( $_COOKIE[ $cookie ] ) ) {
				$present_wc_cookies[] = $cookie;
				$wc_cookie_count++;
			}
		}
		
		$info['Total Cookies'] = $total_cookies;
		$info['WC Cart Cookies'] = $wc_cookie_count . ' (' . implode( ', ', $present_wc_cookies ) . ')';
		$info['Session Cookies'] = $session_cookie_count . ' (' . implode( ', ', $session_cookies ) . ')';
		
		// Show cookie values (truncated for security)
		if ( ! empty( $present_wc_cookies ) ) {
			$info['Cart Cookie Values'] = '';
			foreach ( $present_wc_cookies as $cookie ) {
				$value = $_COOKIE[ $cookie ];
				$truncated = strlen( $value ) > 20 ? substr( $value, 0, 20 ) . '...' : $value;
				$info['Cart Cookie Values'] .= $cookie . ': ' . $truncated . '<br>';
			}
		}
		
		// Check if cookies were blocked by optimization
		$blocked_cookies = $this->get_blocked_cookies_info();
		if ( ! empty( $blocked_cookies ) ) {
			$info['Blocked Cookies'] = implode( ', ', $blocked_cookies );
		}
		
		return $info;
	}

	/**
	 * Get information about cookies that were blocked by optimization.
	 *
	 * @return array
	 */
	private function get_blocked_cookies_info() {
		$blocked = array();
		
		// Show actually blocked cookies from this request
		foreach ( $this->blocked_cookies as $blocked_cookie ) {
			$blocked[] = $blocked_cookie['name'] . ' (' . $blocked_cookie['reason'] . ')';
		}
		
		return $blocked;
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
		// Handle form submission
		if ( isset( $_POST['submit'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce_cache_optimization-options' ) ) {
			$this->save_admin_options();
		}

		$options = $this->options;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WooCommerce Cache Optimization', 'woocommerce' ); ?></h1>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'woocommerce_cache_optimization-options' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Optimize Cart Cookies', 'woocommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="woocommerce_cache_optimization_options[optimize_cart_cookies]" value="1" <?php checked( $options['optimize_cart_cookies'] ); ?> />
								<?php esc_html_e( 'Only set cart cookies when necessary for functionality', 'woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'This is the main optimization feature that prevents cart cookies from being set on static pages.', 'woocommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Disable Cart Fragments on Static Pages', 'woocommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="woocommerce_cache_optimization_options[disable_cart_fragments_on_static_pages]" value="1" <?php checked( $options['disable_cart_fragments_on_static_pages'] ); ?> />
								<?php esc_html_e( 'Disable cart fragments AJAX on pages that don\'t need them', 'woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Reduces unnecessary AJAX requests on static pages like blog posts and product listings.', 'woocommerce' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Debug Mode', 'woocommerce' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="woocommerce_cache_optimization_options[debug_mode]" value="1" <?php checked( $options['debug_mode'] ); ?> />
								<?php esc_html_e( 'Show debug information on frontend (admin only)', 'woocommerce' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Shows detailed cache optimization status and cookie information on the frontend for administrators.', 'woocommerce' ); ?></p>
						</td>
					</tr>
				</table>
				
				<?php submit_button(); ?>
			</form>

			<div class="card" style="max-width: 600px; margin-top: 20px;">
				<h3><?php esc_html_e( 'Current Status', 'woocommerce' ); ?></h3>
				<?php
				$status = $this->get_status();
				?>
				<p><strong><?php esc_html_e( 'Optimization:', 'woocommerce' ); ?></strong> <?php echo $status['enabled'] ? esc_html__( 'Enabled', 'woocommerce' ) : esc_html__( 'Disabled', 'woocommerce' ); ?></p>
				<p><strong><?php esc_html_e( 'Current Page Type:', 'woocommerce' ); ?></strong> <?php echo $status['dynamic_page'] ? esc_html__( 'Dynamic (requires cookies)', 'woocommerce' ) : esc_html__( 'Static (cacheable)', 'woocommerce' ); ?></p>
				<p><strong><?php esc_html_e( 'Cookies Required:', 'woocommerce' ); ?></strong> <?php echo $status['requires_cookies'] ? esc_html__( 'Yes', 'woocommerce' ) : esc_html__( 'No', 'woocommerce' ); ?></p>
				<?php if ( $status['blocked_cookies_count'] > 0 ) : ?>
					<p><strong><?php esc_html_e( 'Blocked Cookies:', 'woocommerce' ); ?></strong> <?php echo esc_html( $status['blocked_cookies_count'] ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save admin options.
	 */
	private function save_admin_options() {
		$options = array();
		
		// Sanitize and save options
		$options['optimize_cart_cookies'] = isset( $_POST['woocommerce_cache_optimization_options']['optimize_cart_cookies'] );
		$options['disable_cart_fragments_on_static_pages'] = isset( $_POST['woocommerce_cache_optimization_options']['disable_cart_fragments_on_static_pages'] );
		$options['debug_mode'] = isset( $_POST['woocommerce_cache_optimization_options']['debug_mode'] );
		
		// Keep other options unchanged
		$existing_options = get_option( 'woocommerce_cache_optimization_options', array() );
		$options = wp_parse_args( $options, $existing_options );
		
		update_option( 'woocommerce_cache_optimization_options', $options );
		
		// Reload options
		$this->load_options();
		
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully!', 'woocommerce' ) . '</p></div>';
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
			'requires_cookies' => $this->requires_cookies_for_context(),
			'is_ajax' => wp_doing_ajax(),
			'is_logged_in' => is_user_logged_in(),
			'has_cart_cookies' => isset( $_COOKIE['woocommerce_items_in_cart'] ) || isset( $_COOKIE['woocommerce_cart_hash'] ),
			'is_product_page' => is_product(),
			'blocked_cookies_count' => count( $this->blocked_cookies ),
		);
	}
}