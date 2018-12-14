<?php
/**
 * Plugin Name: WooCommerce Blocks
 * Plugin URI: https://github.com/woocommerce/woocommerce-gutenberg-products-block
 * Description: WooCommerce blocks for the Gutenberg editor.
 * Version: 1.3.0-dev
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain:  woo-gutenberg-products-block
 * WC requires at least: 3.3
 * WC tested up to: 3.5
 *
 * @package WooCommerce\Blocks
 */

defined( 'ABSPATH' ) || die();

define( 'WGPB_VERSION', '1.2.0' );

define( 'WGPB_DEVELOPMENT_MODE', true );

/**
 * Load up the assets if Gutenberg is active.
 */
function wgpb_initialize() {
	$files_exist = file_exists( plugin_dir_path( __FILE__ ) . '/build/products-block.js' );

	if ( $files_exist && function_exists( 'register_block_type' ) ) {
		add_action( 'init', 'wgpb_register_products_block' );
		add_action( 'enqueue_block_editor_assets', 'wgpb_extra_gutenberg_scripts' );
	}

	if ( defined( 'WGPB_DEVELOPMENT_MODE' ) && WGPB_DEVELOPMENT_MODE && ! $files_exist ) {
		add_action( 'admin_notices', 'wgpb_plugins_notice' );
	}

	add_action( 'rest_api_init', 'wgpb_register_api_routes' );
}
add_action( 'woocommerce_loaded', 'wgpb_initialize' );

/**
 * Display a warning about building files.
 */
function wgpb_plugins_notice() {
	echo '<div class="error"><p>';
	esc_html_e( 'WooCommerce Product Blocks development mode requires files to be built. From the plugin directory, run <code>npm install</code> to install dependencies, <code>npm run build</code> to build the files or <code>npm start</code> to build the files and watch for changes.', 'woo-gutenberg-products-block' );
	echo '</p></div>';
}

/**
 * Register the Products block and its scripts.
 */
function wgpb_register_products_block() {
	register_block_type( 'woocommerce/products' );
	register_block_type( 'woocommerce/product-category' );
	register_block_type( 'woocommerce/product-best-sellers' );
	register_block_type( 'woocommerce/product-top-rated' );
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
		'woocommerce-blocks',
		plugins_url( 'build/blocks.js', __FILE__ ),
		array(
			'wp-api-fetch',
			'wp-blocks',
			'wp-components',
			'wp-compose',
			'wp-data',
			'wp-element',
			'wp-editor',
			'wp-i18n',
			'wp-url',
			'lodash',
		),
		defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( plugin_dir_path( __FILE__ ) . '/build/blocks.js' ) : WGPB_VERSION,
		true
	);

	wp_register_script(
		'woocommerce-products-block-editor',
		plugins_url( 'build/products-block.js', __FILE__ ),
		array( 'wp-api-fetch', 'wp-element', 'wp-components', 'wp-blocks', 'wp-editor', 'wp-i18n', 'react-transition-group' ),
		defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( plugin_dir_path( __FILE__ ) . '/build/products-block.js' ) : WGPB_VERSION,
		true
	);

	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( 'woocommerce-blocks', 'woo-gutenberg-products-block' );
	}

	wp_enqueue_script( 'woocommerce-products-block-editor' );
	wp_enqueue_script( 'woocommerce-blocks' );

	wp_enqueue_style(
		'woocommerce-products-block-editor',
		plugins_url( 'build/products-block.css', __FILE__ ),
		array( 'wp-edit-blocks' ),
		defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( plugin_dir_path( __FILE__ ) . '/build/products-block.css' ) : WGPB_VERSION
	);

	wp_enqueue_style(
		'woocommerce-blocks',
		plugins_url( 'build/blocks.css', __FILE__ ),
		array( 'wp-edit-blocks' ),
		defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( plugin_dir_path( __FILE__ ) . '/build/blocks.css' ) : WGPB_VERSION
	);

	add_action( 'admin_print_footer_scripts', 'wgpb_print_script_settings', 1 );
}

/**
 * Output the wcSettings global before printing any script tags.
 */
function wgpb_print_script_settings() {
	$code = get_woocommerce_currency();

	// Settings and variables can be passed here for access in the app.
	$settings = array(
		'adminUrl'   => admin_url(),
		'wcAssetUrl' => plugins_url( 'assets/', WC_PLUGIN_FILE ),
		'siteLocale' => esc_attr( get_bloginfo( 'language' ) ),
		'currency'   => array(
			'code'      => $code,
			'precision' => wc_get_price_decimals(),
			'symbol'    => get_woocommerce_currency_symbol( $code ),
		),
		'date'       => array(
			'dow' => get_option( 'start_of_week', 0 ),
		),
	);

	// Global settings used in each block.
	$block_settings = array(
		'min_columns'     => wc_get_theme_support( 'product_grid::min_columns', 1 ),
		'max_columns'     => wc_get_theme_support( 'product_grid::max_columns', 6 ),
		'default_columns' => wc_get_default_products_per_row(),
		'min_rows'        => wc_get_theme_support( 'product_grid::min_rows', 1 ),
		'max_rows'        => wc_get_theme_support( 'product_grid::max_rows', 6 ),
		'default_rows'    => wc_get_default_product_rows_per_page(),
	);
	?>
	<script type="text/javascript">
		var wcSettings = <?php echo wp_json_encode( $settings ); ?>;
		var wc_product_block_data = <?php echo wp_json_encode( $block_settings ); ?>;
	</script>
	<?php
}

/**
 * Register extra API routes with functionality specific for product blocks.
 */
function wgpb_register_api_routes() {
	include_once dirname( __FILE__ ) . '/includes/class-wgpb-products-controller.php';
	include_once dirname( __FILE__ ) . '/includes/class-wgpb-product-categories-controller.php';
	include_once dirname( __FILE__ ) . '/includes/class-wgpb-product-attributes-controller.php';
	include_once dirname( __FILE__ ) . '/includes/class-wgpb-product-attribute-terms-controller.php';

	$products = new WGPB_Products_Controller();
	$products->register_routes();

	$categories = new WGPB_Product_Categories_Controller();
	$categories->register_routes();

	$attributes = new WGPB_Product_Attributes_Controller();
	$attributes->register_routes();

	$attribute_terms = new WGPB_Product_Attribute_Terms_Controller();
	$attribute_terms->register_routes();
}

/**
 * Brings some extra required shortcode features from WC core 3.4+ to this feature plugin.
 *
 * @todo Remove this function when merging into core because it won't be necessary.
 *
 * @param array  $args WP_Query args.
 * @param array  $attributes Shortcode attributes.
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
			$args['tax_query'] = array(); // WPCS: slow query ok.
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
			$args['tax_query'] = array(); // WPCS: slow query ok.
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
