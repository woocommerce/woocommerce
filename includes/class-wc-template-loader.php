<?php
/**
 * Template Loader
 *
 * @class 		WC_Template
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		Automattic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Template_Loader.
 */
class WC_Template_Loader {

	/**
	 * Store the shop page ID.
	 *
	 * @var integer
	 */
	private static $shop_page_id = 0;

	/**
	 * Store whether we're processing a product inside the_content filter.
	 *
	 * @var boolean
	 */
	private static $in_content_filter = false;

	/**
	 * Hook in methods.
	 */
	public static function init() {
		self::$shop_page_id = wc_get_page_id( 'shop' );

		// Supported themes.
		if ( current_theme_supports( 'woocommerce' ) ) {
			add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
			add_filter( 'comments_template', array( __CLASS__, 'comments_template_loader' ) );
		} else {
			// Unsupported themes.
			add_action( 'template_redirect', array( __CLASS__, 'unsupported_theme_init' ) );
		}
	}

	/**
	 * Hook in methods to enhance the unsupported theme experience on Shop and Product pages.
	 *
	 * @since 3.3.0
	 */
	public static function unsupported_theme_init() {
		if ( self::$shop_page_id || is_product() ) {
			add_filter( 'the_content', array( __CLASS__, 'unsupported_theme_content_filter' ), 10 );
			add_filter( 'the_title', array( __CLASS__, 'unsupported_theme_title_filter' ), 10, 2 );
			add_filter( 'post_thumbnail_html', array( __CLASS__, 'unsupported_theme_single_featured_image_filter' ) );
			add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'unsupported_theme_remove_review_tab' ) );
			add_filter( 'comments_number', '__return_empty_string' );

			if ( is_product() ) {
				remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
				remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
				add_theme_support( 'wc-product-gallery-zoom' );
				add_theme_support( 'wc-product-gallery-lightbox' );
				add_theme_support( 'wc-product-gallery-slider' );
			}
		}
	}

	/**
	 * Get information about the current shop page view.
	 *
	 * @since 3.3.0
	 * @return array
	 */
	private static function get_current_shop_view_args() {
		return (object) array(
			'page'    => absint( max( 1, absint( get_query_var( 'paged' ) ) ) ),
			'columns' => wc_get_default_products_per_row(),
			'rows'    => wc_get_default_product_rows_per_page(),
		);
	}

	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. woocommerce looks for theme.
	 * overrides in /theme/woocommerce/ by default.
	 *
	 * For beginners, it also looks for a woocommerce.php template first. If the user adds.
	 * this to the theme (containing a woocommerce() inside) this will be used for all.
	 * woocommerce templates.
	 *
	 * @param string $template Template to load.
	 * @return string
	 */
	public static function template_loader( $template ) {
		if ( is_embed() ) {
			return $template;
		}

		if ( $default_file = self::get_template_loader_default_file() ) {
			/**
			 * Filter hook to choose which files to find before WooCommerce does it's own logic.
			 *
			 * @since 3.0.0
			 * @var array
			 */
			$search_files = self::get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );

			if ( ! $template || WC_TEMPLATE_DEBUG_MODE ) {
				$template = WC()->plugin_path() . '/templates/' . $default_file;
			}
		}

		return $template;
	}

	/**
	 * Get the default filename for a template.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	private static function get_template_loader_default_file() {
		if ( is_singular( 'product' ) ) {
			$default_file = 'single-product.php';
		} elseif ( is_product_taxonomy() ) {
			$term = get_queried_object();

			if ( is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) {
				$default_file = 'taxonomy-' . $term->taxonomy . '.php';
			} else {
				$default_file = 'archive-product.php';
			}
		} elseif ( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) {
			$default_file = current_theme_supports( 'woocommerce' ) ? 'archive-product.php' : '';
		} else {
			$default_file = '';
		}
		return $default_file;
	}

	/**
	 * Get an array of filenames to search for a given template.
	 *
	 * @since  3.0.0
	 * @param  string $default_file The default file name.
	 * @return string[]
	 */
	private static function get_template_loader_files( $default_file ) {
		$search_files   = apply_filters( 'woocommerce_template_loader_files', array(), $default_file );
		$search_files[] = 'woocommerce.php';

		if ( is_page_template() ) {
			$search_files[] = get_page_template_slug();
		}

		if ( is_product_taxonomy() ) {
			$term   = get_queried_object();
			$search_files[] = 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$search_files[] = WC()->template_path() . 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$search_files[] = 'taxonomy-' . $term->taxonomy . '.php';
			$search_files[] = WC()->template_path() . 'taxonomy-' . $term->taxonomy . '.php';
		}

		$search_files[] = $default_file;
		$search_files[] = WC()->template_path() . $default_file;

		return array_unique( $search_files );
	}

	/**
	 * Load comments template.
	 *
	 * @param string $template template to load.
	 * @return string
	 */
	public static function comments_template_loader( $template ) {
		if ( get_post_type() !== 'product' ) {
			return $template;
		}

		$check_dirs = array(
			trailingslashit( get_stylesheet_directory() ) . WC()->template_path(),
			trailingslashit( get_template_directory() ) . WC()->template_path(),
			trailingslashit( get_stylesheet_directory() ),
			trailingslashit( get_template_directory() ),
			trailingslashit( WC()->plugin_path() ) . 'templates/',
		);

		if ( WC_TEMPLATE_DEBUG_MODE ) {
			$check_dirs = array( array_pop( $check_dirs ) );
		}

		foreach ( $check_dirs as $dir ) {
			if ( file_exists( trailingslashit( $dir ) . 'single-product-reviews.php' ) ) {
				return trailingslashit( $dir ) . 'single-product-reviews.php';
			}
		}
	}

	/**
	 * Filter the title and insert WooCommerce content on the shop page.
	 *
	 * For non-WC themes, this will setup the main shop page to be shortcode based to improve default appearance.
	 *
	 * @since 3.3.0
	 * @param string $title Existing title.
	 * @param int    $id ID of the post being filtered.
	 * @return string
	 */
	public static function unsupported_theme_title_filter( $title, $id ) {
		if ( ! current_theme_supports( 'woocommerce' ) && is_page( self::$shop_page_id ) && $id === self::$shop_page_id ) {
			$args         = self::get_current_shop_view_args();
			$title_suffix = array();

			if ( $args->page > 1 ) {
				$title_suffix[] = sprintf( esc_html__( 'Page %d', 'woocommerce' ), $args->page );
			}

			if ( $title_suffix ) {
				$title = $title . ' &ndash; ' . implode( ', ', $title_suffix );
			}
		}
		return $title;
	}

	/**
	 * Filter the content and insert WooCommerce content on the shop page.
	 *
	 * For non-WC themes, this will setup the main shop page to be shortcode based to improve default appearance.
	 *
	 * @since 3.3.0
	 * @param string $content Existing post content.
	 * @return string
	 */
	public static function unsupported_theme_content_filter( $content ) {
		global $wp_query;

		if ( current_theme_supports( 'woocommerce' ) || ! is_main_query() ) {
			return $content;
		}

		self::$in_content_filter = true;

		// Remove the filter we're in to avoid nested calls.
		remove_filter( 'the_content', array( __CLASS__, 'the_content_filter' ) );

		// Unsupported theme shop page.
		if ( is_page( self::$shop_page_id ) ) {
			$args      = self::get_current_shop_view_args();
			$shortcode = new WC_Shortcode_Products(
				array_merge(
					wc()->query->get_catalog_ordering_args(),
					array(
						'page'     => $args->page,
						'columns'  => $args->columns,
						'rows'     => $args->rows,
						'orderby'  => '',
						'order'    => '',
						'paginate' => true,
						'cache'    => false,
					)
				),
			'products' );

			// Allow queries to run e.g. layered nav.
			add_action( 'pre_get_posts', array( wc()->query, 'product_query' ) );

			$content = $content . $shortcode->get_content();

			// Remove actions and self to avoid nested calls.
			remove_action( 'pre_get_posts', array( wc()->query, 'product_query' ) );

			// Unsupported theme product page.
		} elseif ( is_product() ) {
			$content = do_shortcode( '[product_page id="' . get_the_ID() . '" show_title=0]' );
		}

		self::$in_content_filter = false;

		return $content;
	}

	/**
	 * Prevent the main featured image on product pages because there will be another featured image
	 * in the gallery.
	 *
	 * @since 3.3.0
	 * @param string $html Img element HTML.
	 * @return string
	 */
	public static function unsupported_theme_single_featured_image_filter( $html ) {
		if ( self::$in_content_filter || ! is_product() || ! is_main_query() ) {
			return $html;
		}

		return '';
	}

	/**
	 * Remove the Review tab and just use the regular comment form.
	 *
	 * @param array $tabs Tab info.
	 * @return array
	 */
	public static function unsupported_theme_remove_review_tab( $tabs ) {
		unset( $tabs['reviews'] );
		return $tabs;
	}
}

add_action( 'init', array( 'WC_Template_Loader', 'init' ) );
