<?php
/**
 * Contains the query functions for WooCommerce which alter the front-end post queries and loops
 *
 * @class 		WC_Query
 * @version		2.3.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Query' ) ) :

/**
 * WC_Query Class.
 */
class WC_Query {

	/** @public array Query vars to add to wp */
	public $query_vars = array();

	/** @public array Unfiltered product ids (before layered nav etc) */
	public $unfiltered_product_ids 	= array();

	/** @public array Filtered product ids (after layered nav) */
	public $filtered_product_ids 	= array();

	/** @public array Filtered product ids (after layered nav, per taxonomy) */
	public $filtered_product_ids_for_taxonomy 	= array();

	/** @public array Product IDs that match the layered nav + price filter */
	public $post__in 		= array();

	/** @public array|string The meta query for the page */
	public $meta_query 		= '';

	/** @public array Post IDs matching layered nav only */
	public $layered_nav_post__in 	= array();

	/** @public array Stores post IDs matching layered nav, so price filter can find max price in view */
	public $layered_nav_product_ids = array();

	/**
	 * Constructor for the query class. Hooks in methods.
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoints' ) );
		add_action( 'init', array( $this, 'layered_nav_init' ) );
		add_action( 'init', array( $this, 'price_filter_init' ) );

		if ( ! is_admin() ) {
			add_action( 'wp_loaded', array( $this, 'get_errors' ), 20 );
			add_filter( 'query_vars', array( $this, 'add_query_vars'), 0 );
			add_action( 'parse_request', array( $this, 'parse_request'), 0 );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			add_filter( 'the_posts', array( $this, 'the_posts' ), 11, 2 );
			add_action( 'wp', array( $this, 'remove_product_query' ) );
			add_action( 'wp', array( $this, 'remove_ordering_args' ) );
		}

		$this->init_query_vars();
	}

	/**
	 * Get any errors from querystring.
	 */
	public function get_errors() {
		if ( ! empty( $_GET['wc_error'] ) && ( $error = sanitize_text_field( $_GET['wc_error'] ) ) && ! wc_has_notice( $error, 'error' ) ) {
			wc_add_notice( $error, 'error' );
		}
	}

	/**
	 * Init query vars by loading options.
	 */
	public function init_query_vars() {
		// Query vars to add to WP
		$this->query_vars = array(
			// Checkout actions
			'order-pay'          => get_option( 'woocommerce_checkout_pay_endpoint', 'order-pay' ),
			'order-received'     => get_option( 'woocommerce_checkout_order_received_endpoint', 'order-received' ),

			// My account actions
			'view-order'         => get_option( 'woocommerce_myaccount_view_order_endpoint', 'view-order' ),
			'edit-account'       => get_option( 'woocommerce_myaccount_edit_account_endpoint', 'edit-account' ),
			'edit-address'       => get_option( 'woocommerce_myaccount_edit_address_endpoint', 'edit-address' ),
			'lost-password'      => get_option( 'woocommerce_myaccount_lost_password_endpoint', 'lost-password' ),
			'customer-logout'    => get_option( 'woocommerce_logout_endpoint', 'customer-logout' ),
			'add-payment-method' => get_option( 'woocommerce_myaccount_add_payment_method_endpoint', 'add-payment-method' ),
		);
	}

	/**
	 * Get page title for an endpoint.
	 * @param  string
	 * @return string
	 */
	public function get_endpoint_title( $endpoint ) {
		global $wp;

		switch ( $endpoint ) {
			case 'order-pay' :
				$title = __( 'Pay for Order', 'woocommerce' );
			break;
			case 'order-received' :
				$title = __( 'Order Received', 'woocommerce' );
			break;
			case 'view-order' :
				$order = wc_get_order( $wp->query_vars['view-order'] );
				$title = ( $order ) ? sprintf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ) : '';
			break;
			case 'edit-account' :
				$title = __( 'Edit Account Details', 'woocommerce' );
			break;
			case 'edit-address' :
				$title = __( 'Edit Address', 'woocommerce' );
			break;
			case 'add-payment-method' :
				$title = __( 'Add Payment Method', 'woocommerce' );
			break;
			case 'lost-password' :
				$title = __( 'Lost Password', 'woocommerce' );
			break;
			default :
				$title = '';
			break;
		}
		return $title;
	}

	/**
	 * Add endpoints for query vars.
	 */
	public function add_endpoints() {
		foreach ( $this->query_vars as $key => $var ) {
			add_rewrite_endpoint( $var, EP_ROOT | EP_PAGES );
		}
	}

	/**
	 * Add query vars.
	 *
	 * @access public
	 * @param array $vars
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		foreach ( $this->query_vars as $key => $var ) {
			$vars[] = $key;
		}

		return $vars;
	}

	/**
	 * Get query vars.
	 *
	 * @return array
	 */
	public function get_query_vars() {
		return $this->query_vars;
	}

	/**
	 * Get query current active query var.
	 *
	 * @return string
	 */
	public function get_current_endpoint() {
		global $wp;
		foreach ( $this->get_query_vars() as $key => $value ) {
			if ( isset( $wp->query_vars[ $key ] ) ) {
				return $key;
			}
		}
		return '';
	}

	/**
	 * Parse the request and look for query vars - endpoints may not be supported.
	 */
	public function parse_request() {
		global $wp;

		// Map query vars to their keys, or get them if endpoints are not supported
		foreach ( $this->query_vars as $key => $var ) {
			if ( isset( $_GET[ $var ] ) ) {
				$wp->query_vars[ $key ] = $_GET[ $var ];
			}

			elseif ( isset( $wp->query_vars[ $var ] ) ) {
				$wp->query_vars[ $key ] = $wp->query_vars[ $var ];
			}
		}
	}

	/**
	 * Hook into pre_get_posts to do the main product query.
	 *
	 * @param mixed $q query object
	 */
	public function pre_get_posts( $q ) {
		// We only want to affect the main query
		if ( ! $q->is_main_query() ) {
			return;
		}

		// Fix for verbose page rules
		if ( $GLOBALS['wp_rewrite']->use_verbose_page_rules && isset( $q->queried_object->ID ) && $q->queried_object->ID === wc_get_page_id( 'shop' ) ) {
			$q->set( 'post_type', 'product' );
			$q->set( 'page', '' );
			$q->set( 'pagename', '' );

			// Fix conditional Functions
			$q->is_archive           = true;
			$q->is_post_type_archive = true;
			$q->is_singular          = false;
			$q->is_page              = false;
		}

		// Fix for endpoints on the homepage
		if ( $q->is_home() && 'page' === get_option( 'show_on_front' ) && absint( get_option( 'page_on_front' ) ) !== absint( $q->get( 'page_id' ) ) ) {
			$_query = wp_parse_args( $q->query );
			if ( ! empty( $_query ) && array_intersect( array_keys( $_query ), array_keys( $this->query_vars ) ) ) {
				$q->is_page     = true;
				$q->is_home     = false;
				$q->is_singular = true;
				$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
				add_filter( 'redirect_canonical', '__return_false' );
			}
		}

		// When orderby is set, WordPress shows posts. Get around that here.
		if ( $q->is_home() && 'page' === get_option( 'show_on_front' ) && absint( get_option( 'page_on_front' ) ) === wc_get_page_id( 'shop' ) ) {
			$_query = wp_parse_args( $q->query );
			if ( empty( $_query ) || ! array_diff( array_keys( $_query ), array( 'preview', 'page', 'paged', 'cpage', 'orderby' ) ) ) {
				$q->is_page = true;
				$q->is_home = false;
				$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
				$q->set( 'post_type', 'product' );
			}
		}

		// Special check for shops with the product archive on front
		if ( $q->is_page() && 'page' === get_option( 'show_on_front' ) && absint( $q->get( 'page_id' ) ) === wc_get_page_id( 'shop' ) ) {

			// This is a front-page shop
			$q->set( 'post_type', 'product' );
			$q->set( 'page_id', '' );

			if ( isset( $q->query['paged'] ) ) {
				$q->set( 'paged', $q->query['paged'] );
			}

			// Define a variable so we know this is the front page shop later on
			define( 'SHOP_IS_ON_FRONT', true );

			// Get the actual WP page to avoid errors and let us use is_front_page()
			// This is hacky but works. Awaiting http://core.trac.wordpress.org/ticket/21096
			global $wp_post_types;

			$shop_page 	= get_post( wc_get_page_id( 'shop' ) );

			$wp_post_types['product']->ID 			= $shop_page->ID;
			$wp_post_types['product']->post_title 	= $shop_page->post_title;
			$wp_post_types['product']->post_name 	= $shop_page->post_name;
			$wp_post_types['product']->post_type    = $shop_page->post_type;
			$wp_post_types['product']->ancestors    = get_ancestors( $shop_page->ID, $shop_page->post_type );

			// Fix conditional Functions like is_front_page
			$q->is_singular          = false;
			$q->is_post_type_archive = true;
			$q->is_archive           = true;
			$q->is_page              = true;

			// Remove post type archive name from front page title tag
			add_filter( 'post_type_archive_title', '__return_empty_string', 5 );

			// Fix WP SEO
			if ( class_exists( 'WPSEO_Meta' ) ) {
				add_filter( 'wpseo_metadesc', array( $this, 'wpseo_metadesc' ) );
				add_filter( 'wpseo_metakey', array( $this, 'wpseo_metakey' ) );
			}

		// Only apply to product categories, the product post archive, the shop page, product tags, and product attribute taxonomies
		} elseif ( ! $q->is_post_type_archive( 'product' ) && ! $q->is_tax( get_object_taxonomies( 'product' ) ) ) {
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
	 * Search post excerpt.
	 *
	 * @access public
	 * @param string $where (default: '')
	 * @return string (modified where clause)
	 */
	public function search_post_excerpt( $where = '' ) {
		global $wp_the_query;

		// If this is not a WC Query, do not modify the query
		if ( empty( $wp_the_query->query_vars['wc_query'] ) || empty( $wp_the_query->query_vars['s'] ) )
			return $where;

		$where = preg_replace(
			"/post_title\s+LIKE\s*(\'\%[^\%]+\%\')/",
			"post_title LIKE $1) OR (post_excerpt LIKE $1", $where );

		return $where;
	}

	/**
	 * WP SEO meta description.
	 *
	 * Hooked into wpseo_ hook already, so no need for function_exist.
	 *
	 * @access public
	 * @return string
	 */
	public function wpseo_metadesc() {
		return WPSEO_Meta::get_value( 'metadesc', wc_get_page_id('shop') );
	}

	/**
	 * WP SEO meta key.
	 *
	 * Hooked into wpseo_ hook already, so no need for function_exist.
	 *
	 * @access public
	 * @return string
	 */
	public function wpseo_metakey() {
		return WPSEO_Meta::get_value( 'metakey', wc_get_page_id('shop') );
	}

	/**
	 * Hook into the_posts to do the main product query if needed - relevanssi compatibility.
	 *
	 * @access public
	 * @param array $posts
	 * @param WP_Query|bool $query (default: false)
	 * @return array
	 */
	public function the_posts( $posts, $query = false ) {
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

		$filtered_posts   = array();
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
		$this->filtered_product_ids   = $queried_post_ids;

		if ( sizeof( $this->layered_nav_post__in ) > 0 ) {
			$this->layered_nav_product_ids = array_intersect( $this->unfiltered_product_ids, $this->layered_nav_post__in );
		} else {
			$this->layered_nav_product_ids = $this->unfiltered_product_ids;
		}

		return $filtered_posts;
	}


	/**
	 * Query the products, applying sorting/ordering etc. This applies to the main wordpress loop.
	 *
	 * @param mixed $q
	 */
	public function product_query( $q ) {

		// Meta query
		$meta_query = $this->get_meta_query( $q->get( 'meta_query' ) );

		// Ordering
		$ordering   = $this->get_catalog_ordering_args();

		// Get a list of post id's which match the current filters set (in the layered nav and price filter)
		$post__in   = array_unique( apply_filters( 'loop_shop_post_in', array() ) );

		// Ordering query vars
		$q->set( 'orderby', $ordering['orderby'] );
		$q->set( 'order', $ordering['order'] );
		if ( isset( $ordering['meta_key'] ) ) {
			$q->set( 'meta_key', $ordering['meta_key'] );
		}

		// Query vars that affect posts shown
		$q->set( 'meta_query', $meta_query );
		$q->set( 'post__in', $post__in );
		$q->set( 'posts_per_page', $q->get( 'posts_per_page' ) ? $q->get( 'posts_per_page' ) : apply_filters( 'loop_shop_per_page', get_option( 'posts_per_page' ) ) );

		// Set a special variable
		$q->set( 'wc_query', 'product_query' );

		// Store variables
		$this->post__in   = $post__in;
		$this->meta_query = $meta_query;

		do_action( 'woocommerce_product_query', $q, $this );
	}


	/**
	 * Remove the query.
	 */
	public function remove_product_query() {
		remove_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
	}

	/**
	 * Remove ordering queries.
	 */
	public function remove_ordering_args() {
		remove_filter( 'posts_clauses', array( $this, 'order_by_popularity_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'order_by_rating_post_clauses' ) );
	}

	/**
	 * Remove the posts_where filter.
	 */
	public function remove_posts_where() {
		remove_filter( 'posts_where', array( $this, 'search_post_excerpt' ) );
	}


	/**
	 * Get an unpaginated list all product ID's (both filtered and unfiltered). Makes use of transients.
	 */
	public function get_products_in_view() {
		global $wp_the_query;

		// Get main query
		$current_wp_query = $wp_the_query->query;

		// Get WP Query for current page (without 'paged')
		unset( $current_wp_query['paged'] );

		// Generate a transient name based on current query
		$transient_name = 'wc_uf_pid_' . md5( http_build_query( $current_wp_query ) . WC_Cache_Helper::get_transient_version( 'product_query' ) );
		$transient_name = ( is_search() ) ? $transient_name . '_s' : $transient_name;

		if ( false === ( $unfiltered_product_ids = get_transient( $transient_name ) ) ) {

			// Get all visible posts, regardless of filters
			$unfiltered_args = array_merge(
				$current_wp_query,
				array(
					'post_type'              => 'product',
					'numberposts'            => -1,
					'post_status'            => 'publish',
					'meta_query'             => $this->meta_query,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'pagename'               => '',
					'wc_query'               => 'get_products_in_view'
				)
			);

			$unfiltered_product_ids = apply_filters( 'woocommerce_unfiltered_product_ids', get_posts( $unfiltered_args ), $unfiltered_args );

			set_transient( $transient_name, $unfiltered_product_ids, DAY_IN_SECONDS * 30 );
		}

		// Store the variable
		$this->unfiltered_product_ids = $unfiltered_product_ids;

		// Also store filtered posts ids...
		if ( sizeof( $this->post__in ) > 0 ) {
			$this->filtered_product_ids = array_intersect( $this->unfiltered_product_ids, $this->post__in );
		} else {
			$this->filtered_product_ids = $this->unfiltered_product_ids;
		}

		// And filtered post ids which just take layered nav into consideration (to find max price in the price widget)
		if ( sizeof( $this->layered_nav_post__in ) > 0 ) {
			$this->layered_nav_product_ids = array_intersect( $this->unfiltered_product_ids, $this->layered_nav_post__in );
		} else {
			$this->layered_nav_product_ids = $this->unfiltered_product_ids;
		}
	}


	/**
	 * Returns an array of arguments for ordering products based on the selected values.
	 *
	 * @access public
	 * @return array
	 */
	public function get_catalog_ordering_args( $orderby = '', $order = '' ) {
		global $wpdb;

		// Get ordering from query string unless defined
		if ( ! $orderby ) {
			$orderby_value = isset( $_GET['orderby'] ) ? wc_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );

			// Get order + orderby args from string
			$orderby_value = explode( '-', $orderby_value );
			$orderby       = esc_attr( $orderby_value[0] );
			$order         = ! empty( $orderby_value[1] ) ? $orderby_value[1] : $order;
		}

		$orderby = strtolower( $orderby );
		$order   = strtoupper( $order );
		$args    = array();

		// default - menu_order
		$args['orderby']  = 'menu_order title';
		$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
		$args['meta_key'] = '';

		switch ( $orderby ) {
			case 'rand' :
				$args['orderby']  = 'rand';
			break;
			case 'date' :
				$args['orderby']  = 'date ID';
				$args['order']    = $order == 'ASC' ? 'ASC' : 'DESC';
			break;
			case 'price' :
				$args['orderby']  = "meta_value_num ID";
				$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
				$args['meta_key'] = '_price';
			break;
			case 'popularity' :
				$args['meta_key'] = 'total_sales';

				// Sorting handled later though a hook
				add_filter( 'posts_clauses', array( $this, 'order_by_popularity_post_clauses' ) );
			break;
			case 'rating' :
				// Sorting handled later though a hook
				add_filter( 'posts_clauses', array( $this, 'order_by_rating_post_clauses' ) );
			break;
			case 'title' :
				$args['orderby']  = 'title';
				$args['order']    = $order == 'DESC' ? 'DESC' : 'ASC';
			break;
		}

		return apply_filters( 'woocommerce_get_catalog_ordering_args', $args );
	}

	/**
	 * WP Core doens't let us change the sort direction for invidual orderby params - http://core.trac.wordpress.org/ticket/17065.
	 *
	 * This lets us sort by meta value desc, and have a second orderby param.
	 *
	 * @access public
	 * @param array $args
	 * @return array
	 */
	public function order_by_popularity_post_clauses( $args ) {
		global $wpdb;

		$args['orderby'] = "$wpdb->postmeta.meta_value+0 DESC, $wpdb->posts.post_date DESC";

		return $args;
	}

	/**
	 * Order by rating post clauses.
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

		$args['orderby'] = "average_rating DESC, $wpdb->posts.post_date DESC";

		$args['groupby'] = "$wpdb->posts.ID";

		return $args;
	}

	/**
	 * Appends meta queries to an array.
	 * @access public
	 * @param array $meta_query
	 * @return array
	 */
	public function get_meta_query( $meta_query = array() ) {
		if ( ! is_array( $meta_query ) )
			$meta_query = array();

		$meta_query[] = $this->visibility_meta_query();
		$meta_query[] = $this->stock_status_meta_query();

		return array_filter( $meta_query );
	}

	/**
	 * Returns a meta query to handle product visibility.
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
	 * Returns a meta query to handle product stock status.
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

	/**
	 * Layered Nav Init.
	 */
	public function layered_nav_init( ) {

		if ( apply_filters( 'woocommerce_is_layered_nav_active', is_active_widget( false, false, 'woocommerce_layered_nav', true ) ) && ! is_admin() ) {

			global $_chosen_attributes;

			$_chosen_attributes = array();

			$attribute_taxonomies = wc_get_attribute_taxonomies();
			if ( $attribute_taxonomies ) {
				foreach ( $attribute_taxonomies as $tax ) {

					$attribute       = wc_sanitize_taxonomy_name( $tax->attribute_name );
					$taxonomy        = wc_attribute_taxonomy_name( $attribute );
					$name            = 'filter_' . $attribute;
					$query_type_name = 'query_type_' . $attribute;

					if ( ! empty( $_GET[ $name ] ) && taxonomy_exists( $taxonomy ) ) {

						$_chosen_attributes[ $taxonomy ]['terms'] = explode( ',', $_GET[ $name ] );

						if ( empty( $_GET[ $query_type_name ] ) || ! in_array( strtolower( $_GET[ $query_type_name ] ), array( 'and', 'or' ) ) )
							$_chosen_attributes[ $taxonomy ]['query_type'] = apply_filters( 'woocommerce_layered_nav_default_query_type', 'and' );
						else
							$_chosen_attributes[ $taxonomy ]['query_type'] = strtolower( $_GET[ $query_type_name ] );

					}
				}
			}

			add_filter('loop_shop_post_in', array( $this, 'layered_nav_query' ) );
		}
	}

	/**
	 * Layered Nav post filter.
	 *
	 * @param array $filtered_posts
	 * @return array
	 */
	public function layered_nav_query( $filtered_posts ) {
		global $_chosen_attributes;

		if ( sizeof( $_chosen_attributes ) > 0 ) {

			$matched_products   = array(
				'and' => array(),
				'or'  => array()
			);
			$filtered_attribute = array(
				'and' => false,
				'or'  => false
			);

			foreach ( $_chosen_attributes as $attribute => $data ) {
				$matched_products_from_attribute = array();
				$filtered = false;

				if ( sizeof( $data['terms'] ) > 0 ) {
					foreach ( $data['terms'] as $value ) {

						$args = array(
							'post_type' 	=> 'product',
							'numberposts' 	=> -1,
							'post_status' 	=> 'publish',
							'fields' 		=> 'ids',
							'no_found_rows' => true,
							'tax_query' => array(
								array(
									'taxonomy' 	=> $attribute,
									'terms' 	=> $value,
									'field' 	=> 'term_id'
								)
							)
						);

						$post_ids = apply_filters( 'woocommerce_layered_nav_query_post_ids', get_posts( $args ), $args, $attribute, $value );

						if ( ! is_wp_error( $post_ids ) ) {

							if ( sizeof( $matched_products_from_attribute ) > 0 || $filtered ) {
								$matched_products_from_attribute = $data['query_type'] == 'or' ? array_merge( $post_ids, $matched_products_from_attribute ) : array_intersect( $post_ids, $matched_products_from_attribute );
							} else {
								$matched_products_from_attribute = $post_ids;
							}

							$filtered = true;
						}
					}
				}

				if ( sizeof( $matched_products[ $data['query_type'] ] ) > 0 || $filtered_attribute[ $data['query_type'] ] === true ) {
					$matched_products[ $data['query_type'] ] = ( $data['query_type'] == 'or' ) ? array_merge( $matched_products_from_attribute, $matched_products[ $data['query_type'] ] ) : array_intersect( $matched_products_from_attribute, $matched_products[ $data['query_type'] ] );
				} else {
					$matched_products[ $data['query_type'] ] = $matched_products_from_attribute;
				}

				$filtered_attribute[ $data['query_type'] ] = true;

				$this->filtered_product_ids_for_taxonomy[ $attribute ] = $matched_products_from_attribute;
			}

			// Combine our AND and OR result sets
			if ( $filtered_attribute['and'] && $filtered_attribute['or'] )
				$results = array_intersect( $matched_products[ 'and' ], $matched_products[ 'or' ] );
			else
				$results = array_merge( $matched_products[ 'and' ], $matched_products[ 'or' ] );

			if ( $filtered ) {

				WC()->query->layered_nav_post__in   = $results;
				WC()->query->layered_nav_post__in[] = 0;

				if ( sizeof( $filtered_posts ) == 0 ) {
					$filtered_posts   = $results;
					$filtered_posts[] = 0;
				} else {
					$filtered_posts   = array_intersect( $filtered_posts, $results );
					$filtered_posts[] = 0;
				}

			}
		}
		return (array) $filtered_posts;
	}

	/**
	 * Price filter Init.
	 */
	public function price_filter_init() {
		if ( apply_filters( 'woocommerce_is_price_filter_active', is_active_widget( false, false, 'woocommerce_price_filter', true ) ) && ! is_admin() ) {

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( 'wc-jquery-ui-touchpunch', WC()->plugin_url() . '/assets/js/jquery-ui-touch-punch/jquery-ui-touch-punch' . $suffix . '.js', array( 'jquery-ui-slider' ), WC_VERSION, true );
			wp_register_script( 'wc-price-slider', WC()->plugin_url() . '/assets/js/frontend/price-slider' . $suffix . '.js', array( 'jquery-ui-slider', 'wc-jquery-ui-touchpunch' ), WC_VERSION, true );

			wp_localize_script( 'wc-price-slider', 'woocommerce_price_slider_params', array(
				'currency_symbol' 	=> get_woocommerce_currency_symbol(),
				'currency_pos'      => get_option( 'woocommerce_currency_pos' ),
				'min_price'			=> isset( $_GET['min_price'] ) ? esc_attr( $_GET['min_price'] ) : '',
				'max_price'			=> isset( $_GET['max_price'] ) ? esc_attr( $_GET['max_price'] ) : ''
			) );

			add_filter( 'loop_shop_post_in', array( $this, 'price_filter' ) );
		}
	}

	/**
	 * Price Filter post filter.
	 *
	 * @param array $filtered_posts
	 * @return array
	 */
	public function price_filter( $filtered_posts = array() ) {
		global $wpdb;

		if ( isset( $_GET['max_price'] ) || isset( $_GET['min_price'] ) ) {

			$matched_products = array();
			$min              = isset( $_GET['min_price'] ) ? floatval( $_GET['min_price'] ) : 0;
			$max              = isset( $_GET['max_price'] ) ? floatval( $_GET['max_price'] ) : 9999999999;

			// If displaying prices in the shop including taxes, but prices don't include taxes..
			if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
				$tax_classes = array_merge( array( '' ), WC_Tax::get_tax_classes() );

				foreach ( $tax_classes as $tax_class ) {
					$tax_rates = WC_Tax::get_rates( $tax_class );
					$min_class = $min - WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $min, $tax_rates ) );
					$max_class = $max - WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $max, $tax_rates ) );

					$matched_products_query = apply_filters( 'woocommerce_price_filter_results', $wpdb->get_results( $wpdb->prepare( "
						SELECT DISTINCT ID, post_parent, post_type FROM {$wpdb->posts}
						INNER JOIN {$wpdb->postmeta} pm1 ON ID = pm1.post_id
						INNER JOIN {$wpdb->postmeta} pm2 ON ID = pm2.post_id
						WHERE post_type IN ( 'product', 'product_variation' )
						AND post_status = 'publish'
						AND pm1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_meta_keys', array( '_price' ) ) ) ) . "')
						AND pm1.meta_value BETWEEN %f AND %f
						AND pm2.meta_key = '_tax_class'
						AND pm2.meta_value = %s
					", $min_class, $max_class, sanitize_title( $tax_class ) ), OBJECT_K ), $min_class, $max_class );

					if ( $matched_products_query ) {
						foreach ( $matched_products_query as $product ) {
							if ( $product->post_type == 'product' ) {
								$matched_products[] = $product->ID;
							}
							if ( $product->post_parent > 0 ) {
								$matched_products[] = $product->post_parent;
							}
						}
					}
				}
			} else {
				$matched_products_query = apply_filters( 'woocommerce_price_filter_results', $wpdb->get_results( $wpdb->prepare( "
					SELECT DISTINCT ID, post_parent, post_type FROM {$wpdb->posts}
					INNER JOIN {$wpdb->postmeta} pm1 ON ID = pm1.post_id
					WHERE post_type IN ( 'product', 'product_variation' )
					AND post_status = 'publish'
					AND pm1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_meta_keys', array( '_price' ) ) ) ) . "')
					AND pm1.meta_value BETWEEN %d AND %d
				", $min, $max ), OBJECT_K ), $min, $max );

				if ( $matched_products_query ) {
					foreach ( $matched_products_query as $product ) {
						if ( $product->post_type == 'product' ) {
							$matched_products[] = $product->ID;
						}
						if ( $product->post_parent > 0 ) {
							$matched_products[] = $product->post_parent;
						}
					}
				}
			}

			$matched_products = array_unique( $matched_products );

			// Filter the id's
			if ( 0 === sizeof( $filtered_posts ) ) {
				$filtered_posts = $matched_products;
			} else {
				$filtered_posts = array_intersect( $filtered_posts, $matched_products );
			}
			$filtered_posts[] = 0;
		}

		return (array) $filtered_posts;
	}

}

endif;

return new WC_Query();
