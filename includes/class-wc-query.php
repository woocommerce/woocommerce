<?php
/**
 * Contains the query functions for WooCommerce which alter the front-end post queries and loops
 *
 * @class 		WC_Query
 * @version		2.6.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Query Class.
 */
class WC_Query {

	/** @public array Query vars to add to wp */
	public $query_vars = array();

	/**
	 * Stores chosen attributes
	 * @var array
	 */
	private static $_chosen_attributes;

	/**
	 * Constructor for the query class. Hooks in methods.
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoints' ) );
		if ( ! is_admin() ) {
			add_action( 'wp_loaded', array( $this, 'get_errors' ), 20 );
			add_filter( 'query_vars', array( $this, 'add_query_vars'), 0 );
			add_action( 'parse_request', array( $this, 'parse_request'), 0 );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
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
		// Query vars to add to WP.
		$this->query_vars = array(
			// Checkout actions.
			'order-pay'          => get_option( 'woocommerce_checkout_pay_endpoint', 'order-pay' ),
			'order-received'     => get_option( 'woocommerce_checkout_order_received_endpoint', 'order-received' ),
			// My account actions.
			'orders'                     => get_option( 'woocommerce_myaccount_orders_endpoint', 'orders' ),
			'view-order'                 => get_option( 'woocommerce_myaccount_view_order_endpoint', 'view-order' ),
			'downloads'                  => get_option( 'woocommerce_myaccount_downloads_endpoint', 'downloads' ),
			'edit-account'               => get_option( 'woocommerce_myaccount_edit_account_endpoint', 'edit-account' ),
			'edit-address'               => get_option( 'woocommerce_myaccount_edit_address_endpoint', 'edit-address' ),
			'payment-methods'            => get_option( 'woocommerce_myaccount_payment_methods_endpoint', 'payment-methods' ),
			'lost-password'              => get_option( 'woocommerce_myaccount_lost_password_endpoint', 'lost-password' ),
			'customer-logout'            => get_option( 'woocommerce_logout_endpoint', 'customer-logout' ),
			'add-payment-method'         => get_option( 'woocommerce_myaccount_add_payment_method_endpoint', 'add-payment-method' ),
			'delete-payment-method'      => get_option( 'woocommerce_myaccount_delete_payment_method_endpoint', 'delete-payment-method' ),
			'set-default-payment-method' => get_option( 'woocommerce_myaccount_set_default_payment_method_endpoint', 'set-default-payment-method' ),
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
			case 'orders' :
				if ( ! empty( $wp->query_vars['orders'] ) ) {
					$title = sprintf( __( 'Orders (page %d)', 'woocommerce' ), intval( $wp->query_vars['orders'] ) );
				} else {
					$title = __( 'Orders', 'woocommerce' );
				}
			break;
			case 'view-order' :
				$order = wc_get_order( $wp->query_vars['view-order'] );
				$title = ( $order ) ? sprintf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ) : '';
			break;
			case 'downloads' :
				$title = __( 'Downloads', 'woocommerce' );
			break;
			case 'edit-account' :
				$title = __( 'Account Details', 'woocommerce' );
			break;
			case 'edit-address' :
				$title = __( 'Addresses', 'woocommerce' );
			break;
			case 'payment-methods' :
				$title = __( 'Payment Methods', 'woocommerce' );
			break;
			case 'add-payment-method' :
				$title = __( 'Add Payment Method', 'woocommerce' );
			break;
			case 'lost-password' :
				$title = __( 'Lost Password', 'woocommerce' );
			break;
			default :
				$title = apply_filters( 'woocommerce_endpoint_' . $endpoint . '_title', '' );
			break;
		}

		return $title;
	}

	/**
	 * Endpoint mask describing the places the endpoint should be added.
	 *
	 * @since 2.6.2
	 * @return int
	 */
	protected function get_endpoints_mask() {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$page_on_front     = get_option( 'page_on_front' );
			$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
			$checkout_page_id  = get_option( 'woocommerce_checkout_page_id' );

			if ( in_array( $page_on_front, array( $myaccount_page_id, $checkout_page_id ) ) ) {
				return EP_ROOT | EP_PAGES;
			}
		}

		return EP_PAGES;
	}

	/**
	 * Add endpoints for query vars.
	 */
	public function add_endpoints() {
		$mask = $this->get_endpoints_mask();

		foreach ( $this->query_vars as $key => $var ) {
			if ( ! empty( $var ) ) {
				add_rewrite_endpoint( $var, $mask );
			}
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

		// Fix product feeds
		if ( $q->is_feed() && $q->is_post_type_archive( 'product' ) ) {
			$q->is_comment_feed = false;
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
			// This is hacky but works. Awaiting https://core.trac.wordpress.org/ticket/21096
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
		return WPSEO_Meta::get_value( 'metadesc', wc_get_page_id( 'shop' ) );
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
		return WPSEO_Meta::get_value( 'metakey', wc_get_page_id( 'shop' ) );
	}

	/**
	 * Query the products, applying sorting/ordering etc. This applies to the main wordpress loop.
	 *
	 * @param mixed $q
	 */
	public function product_query( $q ) {
		// Ordering query vars
		$ordering  = $this->get_catalog_ordering_args();
		$q->set( 'orderby', $ordering['orderby'] );
		$q->set( 'order', $ordering['order'] );
		if ( isset( $ordering['meta_key'] ) ) {
			$q->set( 'meta_key', $ordering['meta_key'] );
		}

		// Query vars that affect posts shown
		$q->set( 'meta_query', $this->get_meta_query( $q->get( 'meta_query' ) ) );
		$q->set( 'tax_query', $this->get_tax_query( $q->get( 'tax_query' ) ) );
		$q->set( 'posts_per_page', $q->get( 'posts_per_page' ) ? $q->get( 'posts_per_page' ) : apply_filters( 'loop_shop_per_page', get_option( 'posts_per_page' ) ) );
		$q->set( 'wc_query', 'product_query' );
		$q->set( 'post__in', array_unique( (array) apply_filters( 'loop_shop_post_in', array() ) ) );

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
	 * Returns an array of arguments for ordering products based on the selected values.
	 *
	 * @access public
	 * @return array
	 */
	public function get_catalog_ordering_args( $orderby = '', $order = '' ) {
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
	 * WP Core doens't let us change the sort direction for invidual orderby params - https://core.trac.wordpress.org/ticket/17065.
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
		$args['where']  .= " AND ( $wpdb->commentmeta.meta_key = 'rating' OR $wpdb->commentmeta.meta_key IS null ) ";
		$args['join']   .= "
			LEFT OUTER JOIN $wpdb->comments ON($wpdb->posts.ID = $wpdb->comments.comment_post_ID)
			LEFT JOIN $wpdb->commentmeta ON($wpdb->comments.comment_ID = $wpdb->commentmeta.comment_id)
		";
		$args['orderby'] = "average_rating DESC, $wpdb->posts.post_date DESC";
		$args['groupby'] = "$wpdb->posts.ID";

		return $args;
	}

	/**
	 * Appends meta queries to an array.
	 *
	 * @param  array $meta_query
	 * @return array
	 */
	public function get_meta_query( $meta_query = array() ) {
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}

		$meta_query['visibility']    = $this->visibility_meta_query();
		$meta_query['stock_status']  = $this->stock_status_meta_query();
		$meta_query['price_filter']  = $this->price_filter_meta_query();
		$meta_query['rating_filter'] = $this->rating_filter_meta_query();

		return array_filter( apply_filters( 'woocommerce_product_query_meta_query', $meta_query, $this ) );
	}

	/**
	 * Return a meta query for filtering by price.
	 * @return array
	 */
	private function price_filter_meta_query() {
		if ( isset( $_GET['max_price'] ) || isset( $_GET['min_price'] ) ) {
			$min = isset( $_GET['min_price'] ) ? floatval( $_GET['min_price'] ) : 0;
			$max = isset( $_GET['max_price'] ) ? floatval( $_GET['max_price'] ) : 9999999999;

			/**
			 * Adjust if the store taxes are not displayed how they are stored.
			 * Max is left alone because the filter was already increased.
			 * Kicks in when prices excluding tax are displayed including tax.
			 */
			if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
				$tax_classes = array_merge( array( '' ), WC_Tax::get_tax_classes() );
				$class_min   = $min;

				foreach ( $tax_classes as $tax_class ) {
					if ( $tax_rates = WC_Tax::get_rates( $tax_class ) ) {
						$class_min = $min - WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $min, $tax_rates ) );
					}
				}

				$min = $class_min;
			}

			return array(
				'key'          => '_price',
				'value'        => array( $min, $max ),
				'compare'      => 'BETWEEN',
				'type'         => 'DECIMAL',
				'price_filter' => true,
			);
		}
		return array();
	}

	/**
	 * Return a meta query for filtering by rating.
	 * @return array
	 */
	public function rating_filter_meta_query() {
		return isset( $_GET['min_rating'] ) ? array(
			'key'           => '_wc_average_rating',
			'value'         => isset( $_GET['min_rating'] ) ? floatval( $_GET['min_rating'] ) : 0,
			'compare'       => '>=',
			'type'          => 'DECIMAL',
			'rating_filter' => true,
		) : array();
	}

	/**
	 * Returns a meta query to handle product visibility.
	 * @param string $compare (default: 'IN')
	 * @return array
	 */
	public function visibility_meta_query( $compare = 'IN' ) {
		return array(
			'key'     => '_visibility',
			'value'   => is_search() ? array( 'visible', 'search' ) : array( 'visible', 'catalog' ),
			'compare' => $compare,
		);
	}

	/**
	 * Returns a meta query to handle product stock status.
	 *
	 * @access public
	 * @param string $status (default: 'instock')
	 * @return array
	 */
	public function stock_status_meta_query( $status = 'instock' ) {
		return 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ? array(
			'key' 		=> '_stock_status',
			'value' 	=> $status,
			'compare' 	=> '=',
		) : array();
	}

	/**
	 * Appends tax queries to an array.
	 * @param array $tax_query
	 * @return array
	 */
	public function get_tax_query( $tax_query = array() ) {
		if ( ! is_array( $tax_query ) ) {
			$tax_query = array();
		}

		// Layered nav filters on terms
		if ( $_chosen_attributes = $this->get_layered_nav_chosen_attributes() ) {
			foreach ( $_chosen_attributes as $taxonomy => $data ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $data['terms'],
					'operator' => 'and' === $data['query_type'] ? 'AND' : 'IN',
					'include_children' => false,
				);
			}
		}

		return array_filter( apply_filters( 'woocommerce_product_query_tax_query', $tax_query, $this ) );
	}

	/**
	 * Get the tax query which was used by the main query.
	 * @return array
	 */
	public static function get_main_tax_query() {
		global $wp_the_query;

		$args      = $wp_the_query->query_vars;
		$tax_query = isset( $args['tax_query'] ) ? $args['tax_query'] : array();

		if ( ! empty( $args['taxonomy'] ) && ! empty( $args['term'] ) ) {
			$tax_query[ $args['taxonomy'] ] = array(
				'taxonomy' => $args['taxonomy'],
				'terms'    => array( $args['term'] ),
				'field'    => 'slug',
			);
		}

		if ( ! empty( $args['product_cat'] ) ) {
			$tax_query[ 'product_cat' ] = array(
				'taxonomy' => 'product_cat',
				'terms'    => array( $args['product_cat'] ),
				'field'    => 'slug',
			);
		}

		if ( ! empty( $args['product_tag'] ) ) {
			$tax_query[ 'product_tag' ] = array(
				'taxonomy' => 'product_tag',
				'terms'    => array( $args['product_tag'] ),
				'field'    => 'slug',
			);
		}

		return $tax_query;
	}

	/**
	 * Get the meta query which was used by the main query.
	 * @return array
	 */
	public static function get_main_meta_query() {
		global $wp_the_query;

		$args       = $wp_the_query->query_vars;
		$meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();

		return $meta_query;
	}

	/**
	 * Based on WP_Query::parse_search
	 */
	public static function get_main_search_query_sql() {
		global $wp_the_query, $wpdb;

		$args         = $wp_the_query->query_vars;
		$search_terms = isset( $args['search_terms'] ) ? $args['search_terms'] : array();
		$sql          = array();

		foreach ( $search_terms as $term ) {
			// Terms prefixed with '-' should be excluded.
			$include = '-' !== substr( $term, 0, 1 );

			if ( $include ) {
				$like_op  = 'LIKE';
				$andor_op = 'OR';
			} else {
				$like_op  = 'NOT LIKE';
				$andor_op = 'AND';
				$term     = substr( $term, 1 );
			}

			$like  = '%' . $wpdb->esc_like( $term ) . '%';
			$sql[] = $wpdb->prepare( "(($wpdb->posts.post_title $like_op %s) $andor_op ($wpdb->posts.post_excerpt $like_op %s) $andor_op ($wpdb->posts.post_content $like_op %s))", $like, $like, $like );
		}

		if ( ! empty( $sql ) && ! is_user_logged_in() ) {
			$sql[] = "($wpdb->posts.post_password = '')";
		}

		return implode( ' AND ', $sql );
	}

	/**
	 * Layered Nav Init.
	 */
	public static function get_layered_nav_chosen_attributes() {
		if ( ! is_array( self::$_chosen_attributes ) ) {
			self::$_chosen_attributes = array();

			if ( $attribute_taxonomies = wc_get_attribute_taxonomies() ) {
				foreach ( $attribute_taxonomies as $tax ) {
					$attribute    = wc_sanitize_taxonomy_name( $tax->attribute_name );
					$taxonomy     = wc_attribute_taxonomy_name( $attribute );
					$filter_terms = ! empty( $_GET[ 'filter_' . $attribute ] ) ? explode( ',', wc_clean( $_GET[ 'filter_' . $attribute ] ) ) : array();

					if ( empty( $filter_terms ) || ! taxonomy_exists( $taxonomy ) ) {
						continue;
					}

					$query_type = ! empty( $_GET[ 'query_type_' . $attribute ] ) && in_array( $_GET[ 'query_type_' . $attribute ], array( 'and', 'or' ) ) ? wc_clean( $_GET[ 'query_type_' . $attribute ] ) : '';
					self::$_chosen_attributes[ $taxonomy ]['terms']      = array_map( 'sanitize_title', $filter_terms ); // Ensures correct encoding
					self::$_chosen_attributes[ $taxonomy ]['query_type'] = $query_type ? $query_type : apply_filters( 'woocommerce_layered_nav_default_query_type', 'and' );
				}
			}
		}
		return self::$_chosen_attributes;
	}

	/**
	 * @deprecated 2.6.0
	 */
	public function layered_nav_init() {
		_deprecated_function( 'layered_nav_init', '2.6', '' );
	}

	/**
	 * Get an unpaginated list all product ID's (both filtered and unfiltered). Makes use of transients.
	 * @deprecated 2.6.0 due to performance concerns
	 */
	public function get_products_in_view() {
		_deprecated_function( 'get_products_in_view', '2.6', '' );
	}

	/**
	 * Layered Nav post filter.
	 * @deprecated 2.6.0 due to performance concerns
	 */
	public function layered_nav_query( $filtered_posts ) {
		_deprecated_function( 'layered_nav_query', '2.6', '' );
	}
}
