<?php
/**
 * Contains the query functions for WooCommerce which alter the front-end post queries and loops.
 *
 * @class 		WC_Query
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Query {

	/** @var array Unfiltered product ids (before layered nav etc) */
	var $unfiltered_product_ids 	= array();

	/** @var array Filtered product ids (after layered nav) */
	var $filtered_product_ids 		= array();

	/** @var array Product IDs that match the layered nav + price filter */
	var $post__in 					= array();

	/** @var array The meta query for the page */
	var $meta_query 				= '';

	/** @var array Post IDs matching layered nav only */
	var $layered_nav_post__in 		= array();

	/** @var array Stores post IDs matching layered nav, so price filter can find max price in view */
	var $layered_nav_product_ids 	= array();

	/**
	 * Constructor for the query class. Hooks in methods.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		add_filter( 'pre_get_posts', array( &$this, 'pre_get_posts') );
		add_filter( 'the_posts', array( &$this, 'the_posts'), 11, 2 );
		add_filter( 'wp', array( &$this, 'remove_product_query') );
	}


	/**
	 * Hook into pre_get_posts to do the main product query
	 *
	 * @access public
	 * @param mixed $q query object
	 * @return void
	 */
	function pre_get_posts( $q ) {
		global $woocommerce;

		// We only want to affect the main query
		if ( ! $q->is_main_query() )
			return;

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
	       		add_filter( 'wpseo_metadesc', array( &$this, 'wpseo_metadesc' ) );
	       		add_filter( 'wpseo_metakey', array( &$this, 'wpseo_metakey' ) );
	       	}

		} else {

			// Only apply to product categories, the product post archive, the shop page, product tags, and product attribute taxonomies
		    if 	( ! $q->is_post_type_archive( 'product' ) && ! $q->is_tax( array_merge( array('product_cat', 'product_tag'), $woocommerce->get_attribute_taxonomy_names() ) ) )
		   		return;

		}

	    $this->product_query( $q );

	    // We're on a shop page so queue the woocommerce_get_products_in_view function
	    add_action( 'wp', array( &$this, 'get_products_in_view' ), 2);

	    // And remove the pre_get_posts hook
	    $this->remove_product_query();
	}
	

	/**
	 * wpseo_metadesc function.
	 * 
	 * @access public
	 * @param mixed $meta
	 * @return void
	 */
	function wpseo_metadesc() {
		return wpseo_get_value( 'metadesc', woocommerce_get_page_id('shop') );
	}
	
	
	/**
	 * wpseo_metakey function.
	 * 
	 * @access public
	 * @return void
	 */
	function wpseo_metakey() {
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
	function the_posts( $posts, $query = false ) {
		global $woocommerce;

		// Abort if theres no query
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
		if 	( ! $query->is_post_type_archive( 'product' ) && ! $query->is_tax( array_merge( array('product_cat', 'product_tag'), $woocommerce->get_attribute_taxonomy_names() ) ) )
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
	function product_query( $q ) {

		// Meta query
		$meta_query = (array) $q->get( 'meta_query' );
	    $meta_query[] = $this->visibility_meta_query();
	    $meta_query[] = $this->stock_status_meta_query();

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
	    $this->post__in = $post__in;
	    $this->meta_query = $meta_query;

	    do_action( 'woocommerce_product_query', $q, $this );
	}


	/**
	 * Remove the query
	 *
	 * @access public
	 * @return void
	 */
	function remove_product_query() {
		remove_filter( 'pre_get_posts', array( &$this, 'pre_get_posts') );
	}


	/**
	 * Get an unpaginated list all product ID's (both filtered and unfiltered). Makes use of transients.
	 *
	 * @access public
	 * @return void
	 */
	function get_products_in_view() {
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
	function get_catalog_ordering_args() {
		$current_order = ( isset( $_SESSION['orderby'] ) ) ? $_SESSION['orderby'] : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );

		switch ( $current_order ) {
			case 'date' :
				$orderby = 'date';
				$order = 'desc';
				$meta_key = '';
			break;
			case 'price' :
				$orderby = 'meta_value_num';
				$order = 'asc';
				$meta_key = '_price';
			break;
			case 'title' :
				$orderby = 'title';
				$order = 'asc';
				$meta_key = '';
			break;
			default :
				$orderby = 'menu_order title';
				$order = 'asc';
				$meta_key = '';
			break;
		}

		$args = array();

		$args['orderby'] = $orderby;
		$args['order'] = $order;
		if ($meta_key)
			$args['meta_key'] = $meta_key;

		return apply_filters('woocommerce_get_catalog_ordering_args', $args );
	}


	/**
	 * Returns a meta query to handle product visibility
	 *
	 * @access public
	 * @param string $compare (default: 'IN')
	 * @return array
	 */
	function visibility_meta_query( $compare = 'IN' ) {
		if ( is_search() ) $in = array( 'visible', 'search' ); else $in = array( 'visible', 'catalog' );

	    $meta_query = array(
	        'key' => '_visibility',
	        'value' => $in,
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
	function stock_status_meta_query( $status = 'instock' ) {
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