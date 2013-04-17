<?php
/**
 * Contains the query functions for WooCommerce which alter the front-end post queries and loops.
 *
 * @class 		WC_Query
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Query {

	/** @public array Unfiltered product ids (before layered nav etc) */
	public $unfiltered_product_ids 	= array();

	/** @public array Filtered product ids (after layered nav) */
	public $filtered_product_ids 	= array();

	/** @public array Product IDs that match the layered nav + price filter */
	public $post__in 		= array();

	/** @public array The meta query for the page */
	public $meta_query 		= '';

	/** @public array Post IDs matching layered nav only */
	public $layered_nav_post__in 	= array();

	/** @public array Stores post IDs matching layered nav, so price filter can find max price in view */
	public $layered_nav_product_ids = array();

	/**
	 * Constructor for the query class. Hooks in methods.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_filter( 'the_posts', array( $this, 'the_posts' ), 11, 2 );
		add_filter( 'wp', array( $this, 'remove_product_query' ) );
	}

	/**
	 * Hook into pre_get_posts to do the main product query
	 *
	 * @access public
	 * @param mixed $q query object
	 * @return void
	 */
	public function pre_get_posts( $q ) {
		global $woocommerce;

		// We only want to affect the main query
		if ( ! $q->is_main_query() )
			return;

		// When orderby is set, WordPress shows posts. Get around that here.
		if ( $q->is_home() && 'page' == get_option('show_on_front') && get_option('page_on_front') == woocommerce_get_page_id('shop') ) {
			$_query = wp_parse_args( $q->query );
			if ( empty( $_query ) || ! array_diff( array_keys( $_query ), array( 'preview', 'page', 'paged', 'cpage', 'orderby' ) ) ) {
				$q->is_page = true;
				$q->is_home = false;
				$q->set( 'page_id', get_option('page_on_front') );
				$q->set( 'post_type', 'product' );
			}
		}

		// Special check for shops with the product archive on front
		if ( $q->is_page() && 'page' == get_option( 'show_on_front' ) && $q->get('page_id') == woocommerce_get_page_id('shop') ) {

			// This is a front-page shop
			$q->set( 'post_type', 'product' );
			$q->set( 'page_id', '' );
			if ( isset( $q->query['paged'] ) )
				$q->set( 'paged', $q->query['paged'] );

			// Define a variable so we know this is the front page shop later on
			define( 'SHOP_IS_ON_FRONT', true );

			// Get the actual WP page to avoid errors and let us use is_front_page()
			// This is hacky but works. Awaiting http://core.trac.wordpress.org/ticket/21096
			global $wp_post_types;

			$shop_page 	= get_post( woocommerce_get_page_id('shop') );
			$q->is_page = true;

			$wp_post_types['product']->ID 			= $shop_page->ID;
			$wp_post_types['product']->post_title 	= $shop_page->post_title;
			$wp_post_types['product']->post_name 	= $shop_page->post_name;

			// Fix conditional Functions like is_front_page
			$q->is_singular = false;
			$q->is_post_type_archive = true;
			$q->is_archive = true;

			// Fix WP SEO
			if ( function_exists( 'wpseo_get_value' ) ) {
				add_filter( 'wpseo_metadesc', array( $this, 'wpseo_metadesc' ) );
				add_filter( 'wpseo_metakey', array( $this, 'wpseo_metakey' ) );
			}

		} else {

			// Only apply to product categories, the product post archive, the shop page, product tags, and product attribute taxonomies
		    if 	( ! $q->is_post_type_archive( 'product' ) && ! $q->is_tax( get_object_taxonomies( 'product' ) ) )
		   		return;

		}

		$this->product_query( $q );

		if ( is_search() ) {
		    add_filter( 'posts_where', array( $this, 'search_post_excerpt' ) );
		    add_filter( 'wp', array( $this, 'remove_posts_where' ) );
		}

		// We're on a shop page so queue the woocommerce_get_products_in_view function
		add_action( 'wp', array( $this, 'get_products_in_view' ), 2);

		// And remove the pre_get_posts hook
		$this->remove_product_query();
	}

	/**
	 * search_post_excerpt function.
	 *
	 * @access public
	 * @param string $where (default: '')
	 * @return string (modified where clause)
	 */
	public function search_post_excerpt( $where = '' ) {
		global $wp_the_query;

		// If this is not a WC Query, do not modify the query
		if ( empty( $wp_the_query->query_vars['wc_query'] ) )
		    return $where;

		$where = preg_replace(
		    "/post_title\s+LIKE\s*(\'[^\']+\')/",
		    "post_title LIKE $1) OR (post_excerpt LIKE $1", $where );

		return $where;
	}

	/**
	 * wpseo_metadesc function.
	 *
	 * @access public
	 * @param mixed $meta
	 * @return void
	 */
	public function wpseo_metadesc() {
		return wpseo_get_value( 'metadesc', woocommerce_get_page_id('shop') );
	}


	/**
	 * wpseo_metakey function.
	 *
	 * @access public
	 * @return void
	 */
	public function wpseo_metakey() {
		return wpseo_get_value( 'metakey', woocommerce_get_page_id('shop') );
	}


	/**
	 * Hook into the_posts to do the main product query if needed - relevanssi compatibility
	 *
	 * @access public
	 * @param mixed $posts
	 * @param bool $query (default: false)
	 * @return void
	 */
	public function the_posts( $posts, $query = false ) {
		global $woocommerce;

		// Abort if there's no query
		if ( ! $query )
			return $posts;

		// Abort if we're not filtering posts
		if ( empty( $this->post__in ) )
			return $posts;

		// Abort if this query has already been done
		if ( ! empty( $query->wc_query ) )
			return $posts;

		// Abort if this isn't a search query
		if ( empty( $query->query_vars["s"] ) )
			return $posts;

		// Abort if we're not on a post type archive/product taxonomy
		if 	( ! $query->is_post_type_archive( 'product' ) && ! $query->is_tax( get_object_taxonomies( 'product' ) ) )
	   		return $posts;

		$filtered_posts = array();
		$queried_post_ids = array();

		foreach ( $posts as $post ) {
		    if ( in_array( $post->ID, $this->post__in ) ) {
			    $filtered_posts[] = $post;
			    $queried_post_ids[] = $post->ID;
		    }
		}

		$query->posts = $filtered_posts;
		    $query->post_count = count( $filtered_posts );

		    // Ensure filters are set
		    $this->unfiltered_product_ids = $queried_post_ids;
		    $this->filtered_product_ids = $queried_post_ids;

		    if ( sizeof( $this->layered_nav_post__in ) > 0 ) {
			    $this->layered_nav_product_ids = array_intersect( $this->unfiltered_product_ids, $this->layered_nav_post__in );
		    } else {
			    $this->layered_nav_product_ids = $this->unfiltered_product_ids;
		    }

		return $filtered_posts;
	}


	/**
	 * Query the products, applying sorting/ordering etc. This applies to the main wordpress loop
	 *
	 * @access public
	 * @param mixed $q
	 * @return void
	 */
	public function product_query( $q ) {

		// Meta query
		$meta_query = $this->get_meta_query( $q->get( 'meta_query' ) );

		// Ordering
		$ordering = $this->get_catalog_ordering_args();

		// Get a list of post id's which match the current filters set (in the layered nav and price filter)
		$post__in = array_unique( apply_filters( 'loop_shop_post_in', array() ) );

		// Ordering query vars
		$q->set( 'orderby', $ordering['orderby'] );
		$q->set( 'order', $ordering['order'] );
		if ( isset( $ordering['meta_key'] ) )
			$q->set( 'meta_key', $ordering['meta_key'] );

		// Query vars that affect posts shown
		if ( ! $q->is_tax( 'product_cat' ) && ! $q->is_tax( 'product_tag' ) )
			$q->set( 'post_type', 'product' );
		$q->set( 'meta_query', $meta_query );
		$q->set( 'post__in', $post__in );
		$q->set( 'posts_per_page', $q->get( 'posts_per_page' ) ? $q->get( 'posts_per_page' ) : apply_filters( 'loop_shop_per_page', get_option( 'posts_per_page' ) ) );

		// Set a special variable
		$q->set( 'wc_query', true );

		// Store variables
		$this->post__in   = $post__in;
		$this->meta_query = $meta_query;

		do_action( 'woocommerce_product_query', $q, $this );
	}


	/**
	 * Remove the query
	 *
	 * @access public
	 * @return void
	 */
	public function remove_product_query() {
		remove_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
	}

	/**
	 * Remove the posts_where filter
	 *
	 * @access public
	 * @return void
	 */
	public function remove_posts_where() {
		remove_filter( 'posts_where', array( $this, 'search_post_excerpt' ) );
	}


	/**
	 * Get an unpaginated list all product ID's (both filtered and unfiltered). Makes use of transients.
	 *
	 * @access public
	 * @return void
	 */
	public function get_products_in_view() {
		global $wp_the_query;

		$unfiltered_product_ids = array();

		// Get main query
		$current_wp_query = $wp_the_query->query;

		// Get WP Query for current page (without 'paged')
		unset( $current_wp_query['paged'] );

		// Generate a transient name based on current query
		$transient_name = 'wc_uf_pid_' . md5( http_build_query( $current_wp_query ) );
		$transient_name = ( is_search() ) ? $transient_name . '_s' : $transient_name;

		if ( false === ( $unfiltered_product_ids = get_transient( $transient_name ) ) ) {

		    // Get all visible posts, regardless of filters
		    $unfiltered_product_ids = get_posts(
				array_merge(
					$current_wp_query,
					array(
						'post_type' 	=> 'product',
						'numberposts' 	=> -1,
						'post_status' 	=> 'publish',
						'meta_query' 	=> $this->meta_query,
						'fields' 		=> 'ids',
						'no_found_rows' => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false
					)
				)
			);

			set_transient( $transient_name, $unfiltered_product_ids );
		}

		// Store the variable
		$this->unfiltered_product_ids = $unfiltered_product_ids;

		// Also store filtered posts ids...
		if ( sizeof( $this->post__in ) > 0 )
			$this->filtered_product_ids = array_intersect( $this->unfiltered_product_ids, $this->post__in );
		else
			$this->filtered_product_ids = $this->unfiltered_product_ids;

		// And filtered post ids which just take layered nav into consideration (to find max price in the price widget)
		if ( sizeof( $this->layered_nav_post__in ) > 0 )
			$this->layered_nav_product_ids = array_intersect( $this->unfiltered_product_ids, $this->layered_nav_post__in );
		else
			$this->layered_nav_product_ids = $this->unfiltered_product_ids;
	}


	/**
	 * Returns an array of arguments for ordering products based on the selected values
	 *
	 * @access public
	 * @return array
	 */
	public function get_catalog_ordering_args( $orderby = '', $order = '' ) {
		global $woocommerce;

		// Get ordering from query string unless defined
		if ( ! $orderby ) {
			$orderby_value = isset( $_GET['orderby'] ) ? woocommerce_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );

			// Get order + orderby args from string
			$orderby_value = explode( '-', $orderby_value );
			$orderby       = esc_attr( $orderby_value[0] );
			$order         = ! empty( $orderby_value[1] ) ? $orderby_value[1] : $order;
		}

		$orderby = strtolower( $orderby );
		$order   = strtoupper( $order );

		$args = array();

		switch ( $orderby ) {
			case 'date' :
				$args['orderby']  = 'date';
				$args['order']    = $order == 'ASC' ? 'ASC' : 'DESC';
				$args['meta_key'] = '';
			break;
			case 'price' :
				$args['orderby']  = 'meta_value_num';
				$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
				$args['meta_key'] = '_price';
			break;
			case 'popularity' :
				$args['orderby']  = 'meta_value_num';
				$args['order']    = $order == 'ASC' ? 'ASC' : 'DESC';
				$args['meta_key'] = 'total_sales';
			break;
			case 'rating' :
				$args['orderby']  = 'menu_order title';
				$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
				$args['meta_key'] = '';

				add_filter( 'posts_clauses', array( $this, 'order_by_rating_post_clauses' ) );
			break;
			case 'title' :
				$args['orderby']  = 'title';
				$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
				$args['meta_key'] = '';
			break;
			// default - menu_order
			default :
				$args['orderby']  = 'menu_order title';
				$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
				$args['meta_key'] = '';
			break;
		}

		return apply_filters( 'woocommerce_get_catalog_ordering_args', $args );
	}

	/**
	 * order_by_rating_post_clauses function.
	 *
	 * @access public
	 * @param array $args
	 * @return array
	 */
	public function order_by_rating_post_clauses( $args ) {

		global $wpdb;

		$args['fields'] .= ", AVG( $wpdb->commentmeta.meta_value ) as average_rating ";

		$args['where'] .= " AND ( $wpdb->commentmeta.meta_key = 'rating' OR $wpdb->commentmeta.meta_key IS null ) ";

		$args['join'] .= "
			LEFT OUTER JOIN $wpdb->comments ON($wpdb->posts.ID = $wpdb->comments.comment_post_ID)
			LEFT JOIN $wpdb->commentmeta ON($wpdb->comments.comment_ID = $wpdb->commentmeta.comment_id)
		";

		$args['orderby'] = "average_rating DESC";

		$args['groupby'] = "$wpdb->posts.ID";

		return $args;
	}

	/**
	 * Appends meta queries to an array.
	 *
	 * @access public
	 * @return void
	 */
	public function get_meta_query( $meta_query = array() ) {
		if ( ! is_array( $meta_query ) )
			$meta_query = array();

		$meta_query[] = $this->visibility_meta_query();
		$meta_query[] = $this->stock_status_meta_query();

		return array_filter( $meta_query );
	}

	/**
	 * Returns a meta query to handle product visibility
	 *
	 * @access public
	 * @param string $compare (default: 'IN')
	 * @return array
	 */
	public function visibility_meta_query( $compare = 'IN' ) {
		if ( is_search() )
			$in = array( 'visible', 'search' );
		else
			$in = array( 'visible', 'catalog' );

		$meta_query = array(
		    'key'     => '_visibility',
		    'value'   => $in,
		    'compare' => $compare
		);

		return $meta_query;
	}

	/**
	 * Returns a meta query to handle product stock status
	 *
	 * @access public
	 * @param string $status (default: 'instock')
	 * @return array
	 */
	public function stock_status_meta_query( $status = 'instock' ) {
		$meta_query = array();
		if ( get_option( 'woocommerce_hide_out_of_stock_items' ) == 'yes' ) {
			 $meta_query = array(
		        'key' 		=> '_stock_status',
				'value' 	=> $status,
				'compare' 	=> '='
		    );
		}
		return $meta_query;
	}

}