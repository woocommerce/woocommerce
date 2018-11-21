<?php
/**
 * Plugin Name: WooCommerce Gutenberg Products Block
 * Plugin URI: https://github.com/woocommerce/woocommerce-gutenberg-products-block
 * Description: WooCommerce Products block for the Gutenberg editor.
 * Version: 1.1.2
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain:  woocommerce
 * Domain Path:  /languages
 * WC requires at least: 3.3
 * WC tested up to: 3.5
 */

defined( 'ABSPATH' ) || die();

define( 'WGPB_VERSION', '1.1.2' );

define( 'WGPB_DEVELOPMENT_MODE', true );

/**
 * Load up the assets if Gutenberg is active.
 */
function wgpb_initialize() {
	$files_exist = file_exists( plugin_dir_path( __FILE__ ) . '/build/products-block.js' );

	if ( $files_exist && function_exists( 'register_block_type' ) ) {
		add_action( 'init', 'wgpb_register_products_block' );
		add_action( 'rest_api_init', 'wgpb_register_api_routes' );
		add_action( 'enqueue_block_editor_assets', 'wgpb_extra_gutenberg_scripts' );
	}

	if ( defined( 'WGPB_DEVELOPMENT_MODE' ) && WGPB_DEVELOPMENT_MODE && ! $files_exist ) {
		add_action( 'admin_notices', 'wgpb_plugins_notice' );
	}

}
add_action( 'woocommerce_loaded', 'wgpb_initialize' );

/**
 * Display a warning about building files.
 */
function wgpb_plugins_notice() {
	echo '<div class="error"><p>';
	echo __( 'WooCommerce Product Blocks development mode requires files to be built. From the plugin directory, run <code>npm install</code> to install dependencies, <code>npm run build</code> to build the files or <code>npm start</code> to build the files and watch for changes.', 'woocommerce' );
	echo '</p></div>';
}

/**
 * Register the Products block and its scripts.
 */
function wgpb_register_products_block() {
	register_block_type( 'woocommerce/products', array(
		'editor_script' => 'woocommerce-products-block-editor',
		'editor_style'  => 'woocommerce-products-block-editor',
	) );
}

/**
 * Register extra scripts needed.
 */
function wgpb_extra_gutenberg_scripts() {
	if ( ! function_exists( 'wc_get_theme_support' ) ) {
		return;
	}

	// @todo Remove this dependency (as it adds a separate react instance).
	wp_enqueue_script(
		'react-transition-group',
		plugins_url( 'assets/js/vendor/react-transition-group.js', __FILE__ ),
		array(),
		'2.2.1',
		true
	);

	wp_register_script(
		'woocommerce-products-category-block',
		plugins_url( 'build/product-category-block.js', __FILE__ ),
		array( 'wp-element', 'wp-blocks', 'wp-i18n' ),
		defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( plugin_dir_path( __FILE__ ) . '/build/product-category-block.js' ) : WGPB_VERSION,
		true
	);

	wp_register_script(
		'woocommerce-products-block-editor',
		plugins_url( 'build/products-block.js', __FILE__ ),
		array( 'wp-api-fetch', 'wp-element', 'wp-components', 'wp-blocks', 'wp-editor', 'wp-i18n', 'react-transition-group' ),
		defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( plugin_dir_path( __FILE__ ) . '/build/products-block.js' ) : WGPB_VERSION,
		true
	);

	$product_block_data = array(
		'min_columns' => wc_get_theme_support( 'product_grid::min_columns', 1 ),
		'max_columns' => wc_get_theme_support( 'product_grid::max_columns', 6 ),
		'default_columns' => wc_get_default_products_per_row(),
		'min_rows' => wc_get_theme_support( 'product_grid::min_rows', 1 ),
		'max_rows' => wc_get_theme_support( 'product_grid::max_rows', 6 ),
		'default_rows' => wc_get_default_product_rows_per_page(),
	);
	wp_localize_script( 'woocommerce-products-block-editor', 'wc_product_block_data', $product_block_data );

	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( 'woocommerce-products-category-block', 'woocommerce' );
	}

	wp_enqueue_script( 'woocommerce-products-block-editor' );
	wp_enqueue_script( 'woocommerce-products-category-block' );

	wp_enqueue_style(
		'woocommerce-products-block-editor',
		plugins_url( 'build/products-block.css', __FILE__ ),
		array( 'wp-edit-blocks' ),
		defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( plugin_dir_path( __FILE__ ) . '/build/products-block.css' ) : WGPB_VERSION
	);

	wp_enqueue_style(
		'woocommerce-products-category-block',
		plugins_url( 'build/product-category-block.css', __FILE__ ),
		array( 'wp-edit-blocks' ),
		defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( plugin_dir_path( __FILE__ ) . '/build/product-category-block.css' ) : WGPB_VERSION
	);
}

/**
 * Output the wcSettings global before printing any script tags.
 */
function wgpb_print_script_settings() {
	$code = get_woocommerce_currency();

	// Settings and variables can be passed here for access in the app.
	$settings = array(
		'adminUrl'         => admin_url(),
		'wcAssetUrl'       => plugins_url( 'assets/', WC_PLUGIN_FILE ),
		'siteLocale'       => esc_attr( get_bloginfo( 'language' ) ),
		'currency'         => array(
			'code'      => $code,
			'precision' => wc_get_price_decimals(),
			'symbol'    => get_woocommerce_currency_symbol( $code ),
		),
		'date'             => array(
			'dow' => get_option( 'start_of_week', 0 ),
		),
	);
	?>
	<script type="text/javascript">
		var wcSettings = <?php echo json_encode( $settings ); ?>;
	</script>
	<?php
}
add_action( 'admin_print_footer_scripts', 'wgpb_print_script_settings', 1 );

/**
 * Register extra API routes with functionality not available in WC core yet.
 *
 * @todo Remove this function when merging into core because it won't be necessary.
 */
function wgpb_register_api_routes() {
	include_once( dirname( __FILE__ ) . '/includes/class-wgpb-products-controller.php' );
	$controller = new WGPB_Products_Controller();
	$controller->register_routes();
}

/**
 * Brings some extra required shortcode features from WC core 3.4+ to this feature plugin.
 *
 * @todo Remove this function when merging into core because it won't be necessary.
 *
 * @param array $args WP_Query args.
 * @param array $attributes Shortcode attributes.
 * @param string $type Type of shortcode currently processing.
 */
function wgpb_extra_shortcode_features( $args, $attributes, $type ) {
	if ( 'products' !== $type ) {
		return $args;
	}

	// Enable term ids in the category shortcode.
	if ( ! empty( $attributes['category'] ) ) {
		$categories = array_map( 'sanitize_title', explode( ',', $attributes['category'] ) );
		$field      = 'slug';

		if ( empty( $args['tax_query'] ) ) {
			$args['tax_query'] = array();
		}

		// Unset old category tax query.
		foreach ( $args['tax_query'] as $index => $tax_query ) {
			if ( 'product_cat' === $tax_query['taxonomy'] ) {
				unset( $args['tax_query'][ $index ] );
			}
		}

		if ( is_numeric( $categories[0] ) ) {
			$categories = array_map( 'absint', $categories );
			$field      = 'term_id';
		}
		$args['tax_query'][] = array(
			'taxonomy' => 'product_cat',
			'terms'    => $categories,
			'field'    => $field,
			'operator' => $attributes['cat_operator'],
		);
	}

	// Enable term ids in the attributes shortcode and just-attribute queries.
	if ( ! empty( $attributes['attribute'] ) || ! empty( $attributes['terms'] ) ) {
		$taxonomy = strstr( $attributes['attribute'], 'pa_' ) ? sanitize_title( $attributes['attribute'] ) : 'pa_' . sanitize_title( $attributes['attribute'] );
		$terms    = $attributes['terms'] ? array_map( 'sanitize_title', explode( ',', $attributes['terms'] ) ) : array();
		$field    = 'slug';

		if ( empty( $args['tax_query'] ) ) {
			$args['tax_query'] = array();
		}

		// Unset old attribute tax query.
		foreach ( $args['tax_query'] as $index => $tax_query ) {
			if ( $taxonomy === $tax_query['taxonomy'] ) {
				unset( $args['tax_query'][ $index ] );
			}
		}

		if ( $terms && is_numeric( $terms[0] ) ) {
			$terms = array_map( 'absint', $terms );
			$field = 'term_id';
		}

		// If no terms were specified get all products that are in the attribute taxonomy.
		if ( ! $terms ) {
			$terms = get_terms(
				array(
					'taxonomy' => $taxonomy,
					'fields'   => 'ids',
				)
			);
			$field = 'term_id';
		}

		$args['tax_query'][] = array(
			'taxonomy' => $taxonomy,
			'terms'    => $terms,
			'field'    => $field,
			'operator' => $attributes['terms_operator'],
		);
	}

	return $args;
}
add_filter( 'woocommerce_shortcode_products_query', 'wgpb_extra_shortcode_features', 10, 3 );
