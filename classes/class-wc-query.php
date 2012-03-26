<?php
/**
 * Contains the query functions for WooCommerce which alter the front-end post queries and loops.
 *
 * @class 		WC_Query
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class WC_Query {
	
	var $unfiltered_product_ids 	= array(); 	// Unfiltered product ids (before layered nav etc)
	var $filtered_product_ids 		= array(); 	// Filtered product ids (after layered nav)
	var $post__in 					= array(); 	// Product id's that match the layered nav + price filter
	var $meta_query 				= ''; 		// The meta query for the page
	var $layered_nav_post__in 		= array(); 	// posts matching layered nav only
	var $layered_nav_product_ids 	= array();	// Stores posts matching layered nav, so price filter can find max price in view
	
	/** constructor */
	function __construct() {
		add_filter( 'pre_get_posts', array( &$this, 'pre_get_posts') );
		add_filter( 'the_posts', array( &$this, 'the_posts'), 11, 2 );
		add_filter( 'wp', array( &$this, 'remove_product_query') );
	}

	/**
	 * Hook into pre_get_posts to do the main product query
	 */
	function pre_get_posts( $q ) {
		global $woocommerce;

		// Only apply to product categories, the product post archive, the shop page, product tags, and product attribute taxonomies
	    if 	( 
	    		( ! $q->is_main_query() ) // Abort if this isn't the main query
	    		|| 
	    		( ! $q->is_post_type_archive( 'product' ) && ! $q->is_tax( array_merge( array('product_cat', 'product_tag'), $woocommerce->get_attribute_taxonomy_names() ) ) ) // Abort if we're not on a post type archive/prduct taxonomy
	    	) 
	    return;
	    
	    $this->product_query( $q );
	    
	    // We're on a shop page so queue the woocommerce_get_products_in_view function
	    add_action('wp', array( &$this, 'get_products_in_view' ), 2);
	    
	    // And remove the pre_get_posts hook
	    remove_filter( 'pre_get_posts', array( &$this, 'pre_get_posts') );
	}

	/**
	 * Hook into the_posts to do the main product query if needed - relevanssi compatibility
	 */
	function the_posts( $posts, $query = false ) {
		global $woocommerce;
		
	    if 	( 
	    		( ! $query ) // Abort if theres no query
	    		||
	    		( empty( $this->post__in ) ) // Abort if we're not filtering posts
	    		||
	    		( ! empty( $query->wc_query ) ) // Abort if this query is already done
	    		||
	    		( empty( $query->query_vars["s"] ) ) // Abort if this isn't a search query
	    		|| 
	    		( ! $query->is_post_type_archive( 'product' ) && ! $query->is_tax( array_merge( array('product_cat', 'product_tag'), $woocommerce->get_attribute_taxonomy_names() ) ) ) // Abort if we're not on a post type archive/prduct taxonomy
	    	) 
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
	 */
	function product_query( $q ) {
		
		// Meta query
		$meta_query = (array) $q->get( 'meta_query' );
	    $meta_query[] = $this->visibility_meta_query();
	    $meta_query[] = $this->stock_status_meta_query();
		
		// Ordering
		$ordering = $this->get_catalog_ordering_args();
		
		// Get a list of post id's which match the current filters set (in the layered nav and price filter)
		$post__in = array_unique(apply_filters('loop_shop_post_in', array()));
		
		// Ordering query vars
		$q->set( 'orderby', $ordering['orderby'] );
		$q->set( 'order', $ordering['order'] );
		if (isset($ordering['meta_key'])) $q->set( 'meta_key', $ordering['meta_key'] );
	
		// Query vars that affect posts shown
		if (!$q->is_tax( 'product_cat' ) && !$q->is_tax( 'product_tag' )) $q->set( 'post_type', 'product' );
		$q->set( 'meta_query', $meta_query );
	    $q->set( 'post__in', $post__in );
	    $q->set( 'posts_per_page', ($q->get('posts_per_page')) ? $q->get('posts_per_page') : apply_filters('loop_shop_per_page', get_option('posts_per_page') ) );
	    
	    // Set a special variable
	    $q->set( 'wc_query', true );
	    
	    // Store variables
	    $this->post__in = $post__in;
	    $this->meta_query = $meta_query;
	}

	/**
	 * Remove the query
	 */
	function remove_product_query() {
		remove_filter( 'pre_get_posts', array( &$this, 'pre_get_posts') );
	}
	
	/**
	 * Get an unpaginated list all product ID's (both filtered and unfiltered). Makes use of transients.
	 */
	function get_products_in_view() {
		global $wp_query;
		
		$unfiltered_product_ids = array();
		
		// Get WP Query for current page (without 'paged')
		$current_wp_query = $wp_query->query;
		unset($current_wp_query['paged']);
		
		// Generate a transient name based on current query
		$transient_name = 'wc_uf_pid_' . md5( http_build_query($current_wp_query) );
		$transient_name = (is_search()) ? $transient_name . '_s' : $transient_name;
		
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
						'no_found_rows' => true
					)
				)
			);
		
			set_transient( $transient_name, $unfiltered_product_ids );

		}
		
		// Store the variable
		$this->unfiltered_product_ids = $unfiltered_product_ids;
		
		// Also store filtered posts ids...
		if (sizeof($this->post__in)>0) :
			$this->filtered_product_ids = array_intersect( $this->unfiltered_product_ids, $this->post__in );
		else :
			$this->filtered_product_ids = $this->unfiltered_product_ids;
		endif;
		
		// And filtered post ids which just take layered nav into consideration (to find max price in the price widget)
		if ( sizeof( $this->layered_nav_post__in ) > 0 ) :
			$this->layered_nav_product_ids = array_intersect( $this->unfiltered_product_ids, $this->layered_nav_post__in );
		else :
			$this->layered_nav_product_ids = $this->unfiltered_product_ids;
		endif;
	}
	
	/**
	 * Returns an array of arguments for ordering products based on the selected values
	 */
	function get_catalog_ordering_args() {
		$current_order = (isset($_SESSION['orderby'])) ? $_SESSION['orderby'] : apply_filters('woocommerce_default_catalog_orderby', get_option('woocommerce_default_catalog_orderby'));
		
		switch ($current_order) :
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
			default :
				$orderby = 'title';
				$order = 'asc';
				$meta_key = '';
			break;
		endswitch;
		
		$args = array();
		
		$args['orderby'] = $orderby;
		$args['order'] = $order;
		if ($meta_key) $args['meta_key'] = $meta_key;
		
		return apply_filters('woocommerce_get_catalog_ordering_args', $args);
	}
	
	/**
	 * Returns a meta query to handle product visibility
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
	 */
	function stock_status_meta_query( $status = 'instock' ) {
		$meta_query = array();
		if (get_option('woocommerce_hide_out_of_stock_items')=='yes') :
			 $meta_query = array(
		        'key' 		=> '_stock_status',
				'value' 	=> $status,
				'compare' 	=> '='
		    );
		endif;
		return $meta_query;
	}
	
	/**
	 * Get a list of product id's which should be hidden from the frontend; useful for custom queries and loops. Makes use of transients.
	 */
	function get_hidden_product_ids() {
		
		$transient_name = (is_search()) ? 'wc_hidden_product_ids_search' : 'wc_hidden_product_ids';
		
		if ( false === ( $hidden_product_ids = get_transient( $transient_name ) ) ) {
			
			$meta_query = array();
			$meta_query[] = $this->visibility_meta_query( 'NOT IN' );
	    	$meta_query[] = $this->stock_status_meta_query( 'outofstock' );
			
			$hidden_product_ids = get_posts(array(
				'post_type' 	=> 'product',
				'numberposts' 	=> -1,
				'post_status' 	=> 'publish',
				'meta_query' 	=> $meta_query,
				'fields' 		=> 'ids',
				'no_found_rows' => true
			));
			
			set_transient( $transient_name, $hidden_product_ids );
		}
				
		return (array) $hidden_product_ids;
	}	
 
}