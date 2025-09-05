<?php
/**
 * WooCommerce Cache Optimization Integration
 * 
 * This file integrates the cache optimization system into WooCommerce.
 * 
 * @package WooCommerce/Includes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize cache optimization when WooCommerce is loaded.
 */
add_action( 'woocommerce_loaded', 'wc_init_cache_optimization' );

/**
 * Initialize cache optimization.
 */
function wc_init_cache_optimization() {
	// Load the cache optimizer
	require_once WC_ABSPATH . 'includes/class-wc-cache-optimizer.php';
	
	// Initialize the optimizer
	WC_Cache_Optimizer::instance();
}

/**
 * Add cache optimization status to WooCommerce system status.
 */
add_filter( 'woocommerce_system_status_environment_rows', 'wc_add_cache_optimization_status' );

/**
 * Add cache optimization status to system status.
 *
 * @param array $rows System status rows.
 * @return array
 */
function wc_add_cache_optimization_status( $rows ) {
	$optimizer = WC_Cache_Optimizer::instance();
	$status = $optimizer->get_status();
	
	$rows[] = array(
		'name'    => __( 'Cache Optimization', 'woocommerce' ),
		'value'   => $status['enabled'] ? __( 'Enabled', 'woocommerce' ) : __( 'Disabled', 'woocommerce' ),
		'note'    => $status['enabled'] ? __( 'Cart cookies are optimized for better caching', 'woocommerce' ) : __( 'Standard cookie behavior', 'woocommerce' ),
	);
	
	return $rows;
}

/**
 * Add cache optimization notice to admin.
 */
add_action( 'admin_notices', 'wc_cache_optimization_admin_notice' );

/**
 * Show admin notice about cache optimization.
 */
function wc_cache_optimization_admin_notice() {
	// Only show on WooCommerce admin pages
	if ( ! isset( $_GET['page'] ) || strpos( $_GET['page'], 'woocommerce' ) !== 0 ) {
		return;
	}
	
	$optimizer = WC_Cache_Optimizer::instance();
	$status = $optimizer->get_status();
	
	if ( ! $status['enabled'] ) {
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'WooCommerce Cache Optimization', 'woocommerce' ); ?></strong> - 
				<?php esc_html_e( 'Enable cache optimization to improve site performance by reducing unnecessary cookies.', 'woocommerce' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-cache-optimization' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Configure', 'woocommerce' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}