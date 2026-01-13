<?php

declare( strict_types = 1);

use Automattic\Jetpack\Constants;

/**
 * WC_Brands class.
 *
 * Important: For internal use only by the Automattic\WooCommerce\Internal\Brands package.
 *
 * @version 9.5.0
 */
class WC_Brands {

	/**
	 * Template URL -- filterable.
	 *
	 * @var mixed|null
	 */
	public $template_url;

	/**
	 * __construct function.
	 */
	public function __construct() {
		$this->template_url = apply_filters( 'woocommerce_template_url', 'woocommerce/' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		add_action( 'plugins_loaded', array( $this, 'register_hooks' ), 11 );

		$this->register_shortcodes();
	}

	/**
	 * Register our hooks
	 */
	public function register_hooks() {
		add_action( 'woocommerce_register_taxonomy', array( __CLASS__, 'init_taxonomy' ) );
		add_action( 'widgets_init', array( $this, 'init_widgets' ) );

		if ( ! wp_is_block_theme() ) {
			add_filter( 'template_include', array( $this, 'template_loader' ) );
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'styles' ) );
		add_action( 'wp', array( $this, 'body_class' ) );

		add_action( 'woocommerce_product_meta_end', array( $this, 'show_brand' ) );
		add_filter( 'woocommerce_structured_data_product', array( $this, 'add_structured_data' ), 20 );

		// duplicate product brands.
		add_action( 'woocommerce_product_duplicate_before_save', array( $this, 'duplicate_store_temporary_brands' ), 10, 2 );
		add_action( 'woocommerce_new_product', array( $this, 'duplicate_add_product_brand_terms' ) );
		add_action( 'woocommerce_new_product', array( $this, 'invalidate_wc_layered_nav_counts_cache' ), 10, 0 );
		add_action( 'woocommerce_update_product', array( $this, 'invalidate_wc_layered_nav_counts_cache' ), 10, 0 );
		add_action( 'transition_post_status', array( $this, 'reset_layered_nav_counts_on_status_change' ), 10, 3 );

		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 11, 2 );

		add_action( 'woocommerce_archive_description', array( $this, 'brand_description' ) );

		add_filter( 'woocommerce_product_query_tax_query', array( $this, 'update_product_query_tax_query' ), 10, 1 );

		// REST API.
		add_action( 'rest_api_init', array( $this, 'rest_api_register_routes' ) );
		add_action( 'woocommerce_rest_insert_product', array( $this, 'rest_api_maybe_set_brands' ), 10, 2 );
		add_filter( 'woocommerce_rest_prepare_product', array( $this, 'rest_api_prepare_brands_to_product' ), 10, 2 ); // WC 2.6.x.
		add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'rest_api_prepare_brands_to_product' ), 10, 2 ); // WC 3.x.
		add_action( 'woocommerce_rest_insert_product', array( $this, 'rest_api_add_brands_to_product' ), 10, 3 ); // WC 2.6.x.
		add_action( 'woocommerce_rest_insert_product_object', array( $this, 'rest_api_add_brands_to_product' ), 10, 3 ); // WC 3.x.
		add_filter( 'woocommerce_rest_product_object_query', array( $this, 'rest_api_filter_products_by_brand' ), 10, 2 );
		add_filter( 'rest_product_collection_params', array( $this, 'rest_api_product_collection_params' ), 10, 2 );

		// Layered nav widget compatibility.
		add_filter( 'woocommerce_layered_nav_term_html', array( $this, 'woocommerce_brands_update_layered_nav_link' ), 10, 4 );

		// Filter the list of taxonomies overridden for the original term count.
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'recount_after_stock_change' ) );
		add_action( 'woocommerce_update_options_products_inventory', array( $this, 'recount_all_brands' ) );

		// Product Editor compatibility.
		add_action( 'woocommerce_layout_template_after_instantiation', array( $this, 'wc_brands_on_block_template_register' ), 10, 3 );

		// Block theme integration.
		add_filter( 'hooked_block_types', array( $this, 'hook_product_brand_block' ), 10, 4 );
		add_filter( 'hooked_block_core/post-terms', array( $this, 'configure_product_brand_block' ), 10, 5 );
	}

	/**
	 * Recount the brands after the stock amount changes.
	 *
	 * @param int $product_id Product ID.
	 */
	public function recount_after_stock_change( $product_id ) {
		if ( 'yes' !== get_option( 'woocommerce_hide_out_of_stock_items' ) || empty( $product_id ) ) {
			return;
		}

		$product_terms = get_the_terms( $product_id, 'product_brand' );

		if ( ! $product_terms ) {
			return;
		}

		if ( wp_defer_term_counting() ) {
			// When deferring term counts, we're using the built in handling of `wp_update_term_count()` to deal with the deferring
			// and, though, this will cause both the standard and stock based counts to be rerun, it is still more efficient
			// in cases where deferred term counting was warranted.
			$product_terms = get_the_terms( $product_id, 'product_brand' );
			if ( is_array( $product_terms ) ) {
				wp_update_term_count( array_column( $product_terms, 'term_taxonomy_id' ), 'product_brand' );
			}
			return;
		}

		$product_brands = array();

		foreach ( $product_terms as $term ) {
			$product_brands[ $term->term_id ] = $term->parent;
		}

		_wc_term_recount( $product_brands, get_taxonomy( 'product_brand' ), false, false );
	}

	/**
	 * Recount all brands.
	 */
	public function recount_all_brands() {
		$product_brands = get_terms(
			array(
				'taxonomy'   => 'product_brand',
				'hide_empty' => false,
				'fields'     => 'id=>parent',
			)
		);
		_wc_term_recount( $product_brands, get_taxonomy( 'product_brand' ), true, false );
	}

	/**
	 * Update the main product fetch query to filter by selected brands.
	 *
	 * @param array $tax_query array of current taxonomy filters.
	 *
	 * @return array
	 */
	public function update_product_query_tax_query( array $tax_query ) {
		if ( isset( $_GET['filter_product_brand'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$filter_product_brand = wc_clean( wp_unslash( $_GET['filter_product_brand'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$brands_filter        = array_filter( array_map( 'absint', explode( ',', $filter_product_brand ) ) );

			if ( $brands_filter ) {
				$tax_query[] = array(
					'taxonomy' => 'product_brand',
					'terms'    => $brands_filter,
					'operator' => 'IN',
				);
			}
		}

		return $tax_query;
	}

	/**
	 * Filter to allow product_brand in the permalinks for products.
	 *
	 * @param string  $permalink The existing permalink URL.
	 * @param WP_Post $post The post.
	 * @return string
	 */
	public function post_type_link( $permalink, $post ) {
		// Abort if post is not a product.
		if ( 'product' !== $post->post_type ) {
			return $permalink;
		}

		if ( empty( $permalink ) ) {
			return $permalink;
		}

		// Abort early if the placeholder rewrite tag isn't in the generated URL.
		if ( false === strpos( $permalink, '%' ) ) {
			return $permalink;
		}

		// Get the custom taxonomy terms in use by this post.
		$terms = get_the_terms( $post->ID, 'product_brand' );

		// If no terms are assigned to this post, use a string instead (can't leave the placeholder there).
		$product_brand = _x( 'uncategorized', 'slug', 'woocommerce' );

		if ( is_array( $terms ) && ! empty( $terms ) ) {
			// Replace the placeholder rewrite tag with the first term's slug.
			$first_term = array_shift( $terms );
			if ( $first_term instanceof WP_Term ) {
				$product_brand = $first_term->slug;
			}
		}

		$find = array(
			'%product_brand%',
		);

		$replace = array(
			$product_brand,
		);

		$replace = array_map( 'sanitize_title', $replace );

		$permalink = str_replace( $find, $replace, $permalink );

		return $permalink;
	}

	/**
	 * Adds filter for introducing CSS classes.
	 */
	public function body_class() {
		if ( is_tax( 'product_brand' ) ) {
			add_filter( 'body_class', array( $this, 'add_body_class' ) );
		}
	}

	/**
	 * Adds classes to brand taxonomy pages.
	 *
	 * @param array $classes Classes array.
	 */
	public function add_body_class( $classes ) {
		$classes[] = 'woocommerce';
		$classes[] = 'woocommerce-page';
		return $classes;
	}

	/**
	 * Enqueues styles.
	 */
	public function styles() {
		if ( ! $this->should_load_brands_styles() ) {
			return;
		}

		$version = Constants::get_constant( 'WC_VERSION' );
		wp_enqueue_style( 'brands-styles', WC()->plugin_url() . '/assets/css/brands.css', array(), $version );
	}

	/**
	 * Determines if brands styles should be loaded on the current page.
	 *
	 * @since 10.4.0
	 * @return bool
	 */
	private function should_load_brands_styles() {
		global $post;

		// Should load on brand taxonomy archive pages.
		if ( is_tax( 'product_brand' ) ) {
			return true;
		}

		// Should load on single product pages that have brands assigned.
		if ( is_singular( 'product' ) && has_term( '', 'product_brand' ) ) {
			return true;
		}

		// Check if any brand shortcodes are present in the content.
		if ( $post && ! empty( $post->post_content ) ) {
			$brand_shortcodes = array(
				'brand_products',
				'product_brand',
				'product_brand_list',
				'product_brand_thumbnails',
				'product_brand_thumbnails_description',
			);

			foreach ( $brand_shortcodes as $shortcode ) {
				if ( has_shortcode( $post->post_content, $shortcode ) ) {
					return true;
				}
			}
		}

		// Check if any brand widgets are active.
		if ( is_active_widget( false, false, 'wc_brands_brand_description' ) ||
			is_active_widget( false, false, 'woocommerce_brand_nav' ) ||
			is_active_widget( false, false, 'wc_brands_brand_thumbnails' ) ) {
			return true;
		}

		/**
		 * Filter whether brands styles should be loaded.
		 *
		 * @since 10.4.0
		 *
		 * @param bool $should_load Whether to load brands styles.
		 */
		return apply_filters( 'woocommerce_should_load_brands_styles', false );
	}

	/**
	 * Initializes brand taxonomy.
	 */
	public static function init_taxonomy() {
		// Get the custom brand permalink slug, or use the default translatable slug.
		$slug = get_option( 'woocommerce_brand_permalink', '' );

		if ( '' === $slug ) {
			$slug = __( 'brand', 'woocommerce' );
		}

		register_taxonomy(
			'product_brand',
			array( 'product' ),
			/**
			 * Filter the brand taxonomy.
			 *
			 * @since 9.4.0
			 *
			 * @param array $args Args.
			 */
			apply_filters(
				'register_taxonomy_product_brand',
				array(
					'hierarchical'          => true,
					'update_count_callback' => '_wc_term_recount',
					'label'                 => __( 'Brands', 'woocommerce' ),
					'labels'                => array(
						'name'              => __( 'Brands', 'woocommerce' ),
						'singular_name'     => __( 'Brand', 'woocommerce' ),
						'template_name'     => _x( 'Products by Brand', 'Template name', 'woocommerce' ),
						'search_items'      => __( 'Search Brands', 'woocommerce' ),
						'all_items'         => __( 'All Brands', 'woocommerce' ),
						'parent_item'       => __( 'Parent Brand', 'woocommerce' ),
						'parent_item_colon' => __( 'Parent Brand:', 'woocommerce' ),
						'edit_item'         => __( 'Edit Brand', 'woocommerce' ),
						'update_item'       => __( 'Update Brand', 'woocommerce' ),
						'add_new_item'      => __( 'Add New Brand', 'woocommerce' ),
						'new_item_name'     => __( 'New Brand Name', 'woocommerce' ),
						'not_found'         => __( 'No Brands Found', 'woocommerce' ),
						'no_terms'          => __( 'No brands', 'woocommerce' ),
						'back_to_items'     => __( '&larr; Go to Brands', 'woocommerce' ),
					),

					'show_ui'               => true,
					'show_admin_column'     => true,
					'show_in_nav_menus'     => true,
					'show_in_rest'          => true,
					'capabilities'          => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),

					'rewrite'               => array(
						'slug'         => $slug,
						'with_front'   => false,
						'hierarchical' => true,
					),
				)
			)
		);
	}

	/**
	 * Initializes brand widgets.
	 */
	public function init_widgets() {
		// Include.
		require_once WC()->plugin_path() . '/includes/widgets/class-wc-widget-brand-description.php';
		require_once WC()->plugin_path() . '/includes/widgets/class-wc-widget-brand-nav.php';
		require_once WC()->plugin_path() . '/includes/widgets/class-wc-widget-brand-thumbnails.php';

		// Register.
		register_widget( 'WC_Widget_Brand_Description' );
		register_widget( 'WC_Widget_Brand_Nav' );
		register_widget( 'WC_Widget_Brand_Thumbnails' );
	}

	/**
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. woocommerce looks for theme
	 * overides in /theme/woocommerce/ by default
	 *
	 * For beginners, it also looks for a woocommerce.php template first. If the user adds
	 * this to the theme (containing a woocommerce() inside) this will be used for all
	 * woocommerce templates.
	 *
	 * @param string $template Template.
	 */
	public function template_loader( $template ) {
		$find = array( 'woocommerce.php' );
		$file = '';

		if ( is_tax( 'product_brand' ) ) {

			$term = get_queried_object();

			$file   = 'taxonomy-' . $term->taxonomy . '.php';
			$find[] = 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$find[] = $this->template_url . 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$find[] = $file;
			$find[] = $this->template_url . $file;

		}

		if ( $file ) {
			$template = locate_template( $find );
			if ( ! $template ) {
				$template = WC()->plugin_path() . '/templates/brands/' . $file;
			}
		}

		return $template;
	}

	/**
	 * Displays brand description.
	 */
	public function brand_description() {
		if ( 'yes' !== get_option( 'wc_brands_show_description' ) ) {
			return;
		}

		if ( ! is_tax( 'product_brand' ) ) {
			return;
		}

		if ( ! get_query_var( 'term' ) ) {
			return;
		}

		$thumbnail = '';

		$term      = get_term_by( 'slug', get_query_var( 'term' ), 'product_brand' );
		$thumbnail = wc_get_brand_thumbnail_url( $term->term_id, 'full' );

		wc_get_template(
			'brand-description.php',
			array(
				'thumbnail' => $thumbnail,
			),
			'woocommerce',
			WC()->plugin_path() . '/templates/brands/'
		);
	}

	/**
	 * Displays brand.
	 */
	public function show_brand() {
		global $post;

		if ( is_singular( 'product' ) ) {
			$terms       = get_the_terms( $post->ID, 'product_brand' );
			$brand_count = is_array( $terms ) ? count( $terms ) : 0;

			$taxonomy = get_taxonomy( 'product_brand' );
			$labels   = $taxonomy->labels;

			/* translators: %s - Label name */
			$brand_output = wc_get_brands( $post->ID, ', ', ' <span class="posted_in">' . sprintf( _n( '%s: ', '%s: ', $brand_count, 'woocommerce' ), $labels->singular_name, $labels->name ), '</span>' );

			/**
			 * Filter the brand output in product meta.
			 *
			 * @since 9.8.0
			 *
			 * @param string $brand_output The HTML output for brands.
			 * @param array  $terms        Array of brand term objects.
			 * @param int    $post_id      The product ID.
			 */
			echo apply_filters( 'woocommerce_product_brands_output', $brand_output, $terms, $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}

	/**
	 * Add structured data to product page.
	 *
	 * @param  array $markup Markup.
	 * @return array $markup
	 */
	public function add_structured_data( $markup ) {
		global $post;

		if ( ! is_array( $markup ) ) {
			$markup = array();
		}

		if ( array_key_exists( 'brand', $markup ) ) {
			return $markup;
		}

		$brands = get_the_terms( $post->ID, 'product_brand' );

		if ( ! empty( $brands ) && is_array( $brands ) ) {
			// Can only return one brand, so pick the first.
			$brand_thumbnail = wc_get_brand_thumbnail_url( $brands[0]->term_id, 'full' );
			$markup['brand'] = array(
				'@type' => 'Brand',
				'name'  => $brands[0]->name,
			);
			if ( $brand_thumbnail ) {
				$markup['brand']['logo'] = $brand_thumbnail;
			}
		}

		return $markup;
	}

	/**
	 * Registers shortcodes.
	 */
	public function register_shortcodes() {
		add_shortcode( 'product_brand', array( $this, 'output_product_brand' ) );
		add_shortcode( 'product_brand_thumbnails', array( $this, 'output_product_brand_thumbnails' ) );
		add_shortcode( 'product_brand_thumbnails_description', array( $this, 'output_product_brand_thumbnails_description' ) );
		add_shortcode( 'product_brand_list', array( $this, 'output_product_brand_list' ) );
		add_shortcode( 'brand_products', array( $this, 'output_brand_products' ) );
	}

	/**
	 * Displays product brand.
	 *
	 * @param array $atts Attributes from the shortcode.
	 * @return string The generated output.
	 */
	public function output_product_brand( $atts ) {
		global $post;

		$args = shortcode_atts(
			array(
				'width'   => '',
				'height'  => '',
				'class'   => 'aligncenter',
				'post_id' => '',
			),
			$atts
		);

		if ( ! $args['post_id'] && ! $post ) {
			return '';
		}

		if ( ! $args['post_id'] ) {
			$args['post_id'] = $post->ID;
		}

		$brands = wp_get_post_terms( $args['post_id'], 'product_brand', array( 'fields' => 'ids' ) );

		if ( is_wp_error( $brands ) ) {
			return '';
		}

		// Bail early if we don't have any brands registered.
		if ( 0 === count( $brands ) ) {
			return '';
		}

		ob_start();

		foreach ( $brands as $brand ) {
			$thumbnail = wc_get_brand_thumbnail_url( $brand );
			if ( empty( $thumbnail ) ) {
				continue;
			}

			$args['thumbnail'] = $thumbnail;
			$args['term']      = get_term_by( 'id', $brand, 'product_brand' );

			if ( $args['width'] || $args['height'] ) {
				$args['width']  = ! empty( $args['width'] ) ? $args['width'] : 'auto';
				$args['height'] = ! empty( $args['height'] ) ? $args['height'] : 'auto';
			}

			wc_get_template(
				'shortcodes/single-brand.php',
				$args,
				'woocommerce',
				WC()->plugin_path() . '/templates/brands/'
			);
		}

		return ob_get_clean();
	}

	/**
	 * Displays product brand list.
	 *
	 * @param array $atts Attributes from the shortcode.
	 * @return string
	 */
	public function output_product_brand_list( $atts ) {
		$args = shortcode_atts(
			array(
				'show_top_links'    => true,
				'show_empty'        => true,
				'show_empty_brands' => false,
			),
			$atts
		);

		$show_top_links    = $args['show_top_links'];
		$show_empty        = $args['show_empty'];
		$show_empty_brands = $args['show_empty_brands'];

		if ( 'false' === $show_top_links ) {
			$show_top_links = false;
		}

		if ( 'false' === $show_empty ) {
			$show_empty = false;
		}

		if ( 'false' === $show_empty_brands ) {
			$show_empty_brands = false;
		}

		$product_brands = array();
        //phpcs:disable
		$terms          = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => ( $show_empty_brands ? false : true ) ) );
		$alphabet       = apply_filters( 'woocommerce_brands_list_alphabet', range( 'a', 'z' ) );
		$numbers        = apply_filters( 'woocommerce_brands_list_numbers', '0-9' );

		foreach ( $terms as $term ) {
			$term_letter = $this->get_brand_name_first_character( $term->name );

			// Allow a locale to be set for ctype_alpha().
			if ( has_filter( 'woocommerce_brands_list_locale' ) ) {
				setLocale( LC_CTYPE, apply_filters( 'woocommerce_brands_list_locale', 'en_US.UTF-8' ) );
			}

			if ( ctype_alpha( $term_letter ) ) {

				foreach ( $alphabet as $i ) {
					if ( $i == $term_letter ) {
						$product_brands[ $i ][] = $term;
						break;
					}
				}
			} else {
				$product_brands[ $numbers ][] = $term;
			}
		}

		ob_start();

		wc_get_template(
			'shortcodes/brands-a-z.php',
			array(
				'terms'          => $terms,
				'index'          => array_merge( $alphabet, array( $numbers ) ),
				'product_brands' => $product_brands,
				'show_empty'     => $show_empty,
				'show_top_links' => $show_top_links,
			),
			'woocommerce',
			WC()->plugin_path() . '/templates/brands/'
		);

		return ob_get_clean();
	}

	/**
	 * Get the first letter of the brand name, returning lowercase and without accents.
	 *
	 * @param string $name
	 *
	 * @return string
	 * @since  9.4.0
	 */
	private function get_brand_name_first_character( $name ) {
		// Convert to lowercase and remove accents.
		$clean_name = strtolower( sanitize_title( $name ) );
		// Return the first letter of the name.
		return substr( $clean_name, 0, 1 );
	}

	/**
	 * Displays brand thumbnails.
	 *
	 * @param mixed $atts
	 * @return void
	 */
	public function output_product_brand_thumbnails( $atts ) {
		$args = shortcode_atts(
			array(
				'show_empty'    => true,
				'columns'       => 4,
				'hide_empty'    => 0,
				'orderby'       => 'name',
				'exclude'       => '',
				'number'        => '',
				'fluid_columns' => false,
			),
			$atts
		);

		$exclude = array_map( 'intval', explode( ',', $args['exclude'] ) );
		$order   = 'name' === $args['orderby'] ? 'asc' : 'desc';

		if ( 'true' === $args['show_empty'] ) {
			$hide_empty = false;
		} else {
			$hide_empty = true;
		}

		$brands = get_terms(
			'product_brand',
			array(
				'hide_empty' => $hide_empty,
				'orderby'    => $args['orderby'],
				'exclude'    => $exclude,
				'number'     => $args['number'],
				'order'      => $order,
			)
		);

		if ( ! $brands ) {
			return;
		}

		ob_start();

		wc_get_template(
			'widgets/brand-thumbnails.php',
			array(
				'brands'        => $brands,
				'columns'       => is_numeric( $args['columns'] ) ? intval( $args['columns'] ) : 4,
				'fluid_columns' => wp_validate_boolean( $args['fluid_columns'] ),
			),
			'woocommerce',
			WC()->plugin_path() . '/templates/brands/'
		);

		return ob_get_clean();
	}

	/**
	 * Displays brand thumbnails description.
	 *
	 * @param mixed $atts
	 * @return void
	 */
	public function output_product_brand_thumbnails_description( $atts ) {
		$args = shortcode_atts(
			array(
				'show_empty' => true,
				'columns'    => 1,
				'hide_empty' => 0,
				'orderby'    => 'name',
				'exclude'    => '',
				'number'     => '',
			),
			$atts
		);

		$exclude = array_map( 'intval', explode( ',', $args['exclude'] ) );
		$order   = 'name' === $args['orderby'] ? 'asc' : 'desc';

		if ( 'true' === $args['show_empty'] ) {
			$hide_empty = false;
		} else {
			$hide_empty = true;
		}

		$brands = get_terms(
			'product_brand',
			array(
				'hide_empty' => $hide_empty,
				'orderby'    => $args['orderby'],
				'exclude'    => $exclude,
				'number'     => $args['number'],
				'order'      => $order,
			)
		);

		if ( ! $brands ) {
			return;
		}

		ob_start();

		wc_get_template(
			'widgets/brand-thumbnails-description.php',
			array(
				'brands'  => $brands,
				'columns' => $args['columns'],
			),
			'woocommerce',
			WC()->plugin_path() . '/templates/brands/'
		);

		return ob_get_clean();
	}

	/**
	 * Displays brand products.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function output_brand_products( $atts ) {
		if ( empty( $atts['brand'] ) ) {
			return '';
		}

		// Add the brand attributes and query arguments.
		add_filter( 'shortcode_atts_brand_products', array( __CLASS__, 'add_brand_products_shortcode_atts' ), 10, 4 );
		add_filter( 'woocommerce_shortcode_products_query', array( __CLASS__, 'get_brand_products_query_args' ), 10, 3 );

		$shortcode = new WC_Shortcode_Products( $atts, 'brand_products' );

		// Remove the brand attributes and query arguments.
		remove_filter( 'shortcode_atts_brand_products', array( __CLASS__, 'add_brand_products_shortcode_atts' ), 10 );
		remove_filter( 'woocommerce_shortcode_products_query', array( __CLASS__, 'get_brand_products_query_args' ), 10 );

		return $shortcode->get_content();
	}

	/**
	 * Adds the taxonomy query to the WooCommerce products shortcode query arguments.
	 *
	 * @param array  $query_args
	 * @param array  $attributes
	 * @param string $type
	 *
	 * @return array
	 */
	public static function get_brand_products_query_args( $query_args, $attributes, $type ) {
		if ( 'brand_products' !== $type || empty( $attributes['brand'] ) ) {
			return $query_args;
		}

		$query_args['tax_query'][] = array(
			'taxonomy' => 'product_brand',
			'terms'    => array_map( 'sanitize_title', explode( ',', $attributes['brand'] ) ),
			'field'    => 'slug',
			'operator' => 'IN',
		);

		return $query_args;
	}

	/**
	 * Adds the "brand" attribute to the list of WooCommerce products shortcode attributes.
	 *
	 * @param array  $out       The output array of shortcode attributes.
	 * @param array  $pairs     The supported attributes and their defaults.
	 * @param array  $atts      The user defined shortcode attributes.
	 * @param string $shortcode The shortcode name.
	 *
	 * @return array The output array of shortcode attributes.
	 */
	public static function add_brand_products_shortcode_atts( $out, $pairs, $atts, $shortcode ) {
		$out['brand'] = array_key_exists( 'brand', $atts ) ? $atts['brand'] : '';

		return $out;
	}

	/**
	 * Register REST API route for /products/brands.
	 *
	 * @since 9.4.0
	 *
	 * @return void
	 */
	public function rest_api_register_routes() {
		require_once WC()->plugin_path() . '/includes/rest-api/Controllers/Version2/class-wc-rest-product-brands-v2-controller.php';
		require_once WC()->plugin_path() . '/includes/rest-api/Controllers/Version3/class-wc-rest-product-brands-controller.php';

		$controllers = array(
			'WC_REST_Product_Brands_V2_Controller',
			'WC_REST_Product_Brands_Controller'
		);

		foreach ( $controllers as $controller ) {
			( new $controller() )->register_routes();
		}
	}

	/**
	 * Maybe set brands when requesting PUT /products/<id>.
	 *
	 * @since 9.4.0
	 *
	 * @param WP_Post         $post    Post object
	 * @param WP_REST_Request $request Request object
	 *
	 * @return void
	 */
	public function rest_api_maybe_set_brands( $post, $request ) {
		if ( isset( $request['brands'] ) && is_array( $request['brands'] ) ) {
			$terms = array_map( 'absint', $request['brands'] );
			wp_set_object_terms( $post->ID, $terms, 'product_brand' );
		}
	}

	/**
	 * Prepare brands in product response.
	 *
	 * @param WP_REST_Response $response   The response object.
	 * @param WP_Post|WC_Data  $post       Post object or WC object.
	 * @version 9.4.0
	 * @return WP_REST_Response
	 */
	public function rest_api_prepare_brands_to_product( $response, $post ) {
		$post_id = is_callable( array( $post, 'get_id' ) ) ? $post->get_id() : ( ! empty( $post->ID ) ? $post->ID : null );

		if ( empty( $response->data['brands'] ) ) {
			$terms = array();

			foreach ( wp_get_post_terms( $post_id, 'product_brand' ) as $term ) {
				$terms[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}

			$response->data['brands'] = $terms;
		}

		return $response;
	}

	/**
	 * Add brands in product response.
	 *
	 * @param WC_Data         $product   Inserted product object.
	 * @param WP_REST_Request $request   Request object.
	 * @param boolean         $creating  True when creating object, false when updating.
	 * @version 9.4.0
	 */
	public function rest_api_add_brands_to_product( $product, $request, $creating = true ) {
		$product_id = is_callable( array( $product, 'get_id' ) ) ? $product->get_id() : ( ! empty( $product->ID ) ? $product->ID : null );
		$params     = $request->get_params();
		$brands     = isset( $params['brands'] ) ? $params['brands'] : array();

		if ( ! empty( $brands ) ) {
			if ( is_array( $brands[0] ) && array_key_exists( 'id', $brands[0] ) ) {
				$brands = array_map(
					function ( $brand ) {
						return absint( $brand['id'] );
					},
					$brands
				);
			} else {
				$brands = array_map( 'absint', $brands );
			}
			wp_set_object_terms( $product_id, $brands, 'product_brand' );
		}
	}

	/**
	 * Filters products by taxonomy product_brand.
	 *
	 * @param array           $args    Request args.
	 * @param WP_REST_Request $request Request data.
	 * @return array Request args.
	 * @version 9.4.0
	 */
	public function rest_api_filter_products_by_brand( $args, $request ) {
		if ( ! empty( $request['brand'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'product_brand',
				'field'    => 'term_id',
				'terms'    => $request['brand'],
			);
		}

		return $args;
	}

	/**
	 * Documents additional query params for collections of products.
	 *
	 * @param array        $params JSON Schema-formatted collection parameters.
	 * @param WP_Post_Type $post_type   Post type object.
	 * @return array JSON Schema-formatted collection parameters.
	 * @version 9.4.0
	 */
	public function rest_api_product_collection_params( $params, $post_type ) {
		$params['brand'] = array(
			'description'       => __( 'Limit result set to products assigned a specific brand ID.', 'woocommerce' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Injects Brands filters into layered nav links.
	 *
	 * @param  string $term_html Original link html.
	 * @param  mixed  $term      Term that is currently added.
	 * @param  string $link      Original layered nav item link.
	 * @param  number $count     Number of items in that filter.
	 * @return string            Term html.
	 * @version 9.4.0
	 */
	public function woocommerce_brands_update_layered_nav_link( $term_html, $term, $link, $count ) {
		if ( empty( $_GET['filter_product_brand'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $term_html;
		}

		$filter_product_brand = wc_clean( wp_unslash( $_GET['filter_product_brand'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_attributes   = array_map( 'intval', explode( ',', $filter_product_brand ) );
		$current_values       = ! empty( $current_attributes ) ? $current_attributes : array();
		$link                 = add_query_arg(
			array(
				'filtering'            => '1',
				'filter_product_brand' => implode( ',', $current_values ),
			),
			wp_specialchars_decode( $link )
		);
		$term_html            = '<a rel="nofollow" href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . '</a>';
		$term_html           .= ' ' . apply_filters( 'woocommerce_layered_nav_count', '<span class="count">(' . absint( $count ) . ')</span>', $count, $term );
		return $term_html;
	}

	/**
	 * Temporarily tag a post with meta before it is saved in order
	 * to allow us to be able to use the meta when the product is saved to add
	 * the brands when an ID has been generated.
	 *
	 *
	 * @param WC_Product $duplicate
	 * @return WC_Product $original
	 */
	public function duplicate_store_temporary_brands( $duplicate, $original ) {
		$terms = get_the_terms( $original->get_id(), 'product_brand' );
		if ( ! is_array( $terms ) ) {
			return;
		}

		$ids = array();
		foreach ( $terms as $term ) {
			$ids[] = $term->term_id;
		}
		$duplicate->add_meta_data( 'duplicate_temp_brand_ids', $ids );
	}

	/**
	 * After product was added check if there are temporary brands and
	 * add them officially and remove the temporary brands.
	 *
	 * @since 9.4.0
	 *
	 * @param int $product_id
	 */
	public function duplicate_add_product_brand_terms( $product_id ) {
		$product = wc_get_product( $product_id );
		// Bail if product isn't found.
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$term_ids = $product->get_meta( 'duplicate_temp_brand_ids' );
		if ( empty( $term_ids ) ) {
			return;
		}
		$term_taxonomy_ids = wp_set_object_terms( $product_id, $term_ids, 'product_brand' );
		$product->delete_meta_data( 'duplicate_temp_brand_ids' );
		$product->save();
	}

	/**
	 * Invalidates the layered nav counts cache.
	 *
	 * @return void
	 */
	public function invalidate_wc_layered_nav_counts_cache() {
		$taxonomy = 'product_brand';
		delete_transient( 'wc_layered_nav_counts_' . sanitize_title( $taxonomy ) );
	}

	/**
	 * Reset Layered Nav cached counts on product status change.
	 *
	 * @param $new_status
	 * @param $old_status
	 * @param $post
	 *
	 * @return void
	 */
	function reset_layered_nav_counts_on_status_change( $new_status, $old_status, $post ) {
		if ( $post->post_type === 'product' && $old_status !== $new_status ) {
			$this->invalidate_wc_layered_nav_counts_cache();
		}
	}

	/**
	 * Add a new block to the template.
	 *
	 * @param string                 $template_id Template ID.
	 * @param string                 $template_area Template area.
	 * @param BlockTemplateInterface $template Template instance.
	 */
	public function wc_brands_on_block_template_register( $template_id, $template_area, $template ) {

		if ( 'simple-product' === $template->get_id() ) {
			$section = $template->get_section_by_id( 'product-catalog-section' );
			if ( $section !== null ) {
				$section->add_block(
					array(
						'id'         => 'woocommerce-brands-select',
						'blockName'  => 'woocommerce/product-taxonomy-field',
						'order'      => 15,
						'attributes' => array(
							'label'       => __( 'Brands', 'woocommerce-brands' ),
							'createTitle' => __( 'Create new brand', 'woocommerce-brands' ),
							'slug'        => 'product_brand',
							'property'    => 'brands',
						),
					)
				);
			}
		}
	}

	/**
	 * Hooks the product brand terms block into single product templates.
	 *
	 * @param array $hooked_block_types The array of hooked block types.
	 * @param int $relative_position The relative position of the hooked block.
	 * @param string $anchor_block_type The type of anchor block.
	 * @param WP_Block_Template $context The context of the block.
	 *
	 * @return array The array of hooked block types.
	 */
	public function hook_product_brand_block( $hooked_block_types, $relative_position, $anchor_block_type, $context ) {

		// Only add the block as the last child of the product meta block
		if ( 'woocommerce/product-meta' === $anchor_block_type &&
			 'last_child' === $relative_position &&
			 $context instanceof WP_Block_Template &&
			 'single-product' === $context->slug ) {

				remove_action( 'woocommerce_product_meta_end', array( $this, 'show_brand' ) );

				// Check if the template already has a product brand block
				if ( ! $this->template_already_has_brand_block( $context ) ) {
					// Simply add the core/post-terms block type
					$hooked_block_types[] = 'core/post-terms';
				}
		}

		return $hooked_block_types;
	}

	/**
	 * Check if the template already contains a product brand block.
	 *
	 * @param WP_Block_Template $template The template object.
	 *
	 * @return boolean True if template contains a brand block.
	 */
	private function template_already_has_brand_block( $template ) {
		if ( ! $template || empty( $template->content ) ) {
			return false;
		}

		return strpos( $template->content, '<!-- wp:post-terms {"term":"product_brand"' ) !== false;
	}

	/**
	 * Configures the attributes for the hooked product brand terms block.
	 *
	 * @param array $parsed_hooked_block The parsed hooked block.
	 * @param string $hooked_block_type The type of hooked block.
	 * @param int $relative_position The relative position of the hooked block.
	 * @param array $parsed_anchor_block The parsed anchor block.
	 * @param WP_Block_Template $context The context of the block.
	 *
	 * @return array The parsed hooked block.
	 */
	public function configure_product_brand_block( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context ) {
		if ( is_null( $parsed_hooked_block ) ) {
			return $parsed_hooked_block;
		}

		if ( 'core/post-terms' === $hooked_block_type &&
			 'last_child' === $relative_position &&
			 'woocommerce/product-meta' === $parsed_anchor_block['blockName'] &&
			 empty( $parsed_anchor_block['attrs'] ) ) {

			$parsed_hooked_block['attrs'] = array(
				'term'	 => 'product_brand',
				'prefix' => __( 'Brands: ', 'woocommerce' ),
			);
		}

		return $parsed_hooked_block;
	}
}

$GLOBALS['WC_Brands'] = new WC_Brands();
