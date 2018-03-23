<?php
/**
 * Contains the query functions for WooCommerce which alter the front-end post queries and loops
 *
 * @version 3.2.0
 * @package WooCommerce\Classes
 * @author  Automattic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Query Class.
 */
class WC_Query {

	/**
	 * Query vars to add to wp.
	 *
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * Reference to the main product query on the page.
	 *
	 * @var array
	 */
	private static $product_query;

	/**
	 * Stores chosen attributes.
	 *
	 * @var array
	 */
	private static $_chosen_attributes;

	/**
	 * Constructor for the query class. Hooks in methods.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoints' ) );
		if ( ! is_admin() ) {
			add_action( 'wp_loaded', array( $this, 'get_errors' ), 20 );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
			add_action( 'parse_request', array( $this, 'parse_request' ), 0 );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			add_filter( 'the_posts', array( $this, 'remove_product_query_filters' ) );
			add_filter( 'get_pagenum_link', array( $this, 'remove_add_to_cart_pagination' ), 10, 1 );
		}
		$this->init_query_vars();
	}

	/**
	 * Get any errors from querystring.
	 */
	public function get_errors() {
		$error = ! empty( $_GET['wc_error'] ) ? sanitize_text_field( wp_unslash( $_GET['wc_error'] ) ) : ''; // WPCS: input var ok, CSRF ok.

		if ( $error && ! wc_has_notice( $error, 'error' ) ) {
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
			'order-pay'                  => get_option( 'woocommerce_checkout_pay_endpoint', 'order-pay' ),
			'order-received'             => get_option( 'woocommerce_checkout_order_received_endpoint', 'order-received' ),
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
	 *
	 * @param  string $endpoint Endpoint key.
	 * @return string
	 */
	public function get_endpoint_title( $endpoint ) {
		global $wp;

		switch ( $endpoint ) {
			case 'order-pay':
				$title = __( 'Pay for order', 'woocommerce' );
				break;
			case 'order-received':
				$title = __( 'Order received', 'woocommerce' );
				break;
			case 'orders':
				if ( ! empty( $wp->query_vars['orders'] ) ) {
					/* translators: %s: page */
					$title = sprintf( __( 'Orders (page %d)', 'woocommerce' ), intval( $wp->query_vars['orders'] ) );
				} else {
					$title = __( 'Orders', 'woocommerce' );
				}
				break;
			case 'view-order':
				$order = wc_get_order( $wp->query_vars['view-order'] );
				/* translators: %s: order number */
				$title = ( $order ) ? sprintf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ) : '';
				break;
			case 'downloads':
				$title = __( 'Downloads', 'woocommerce' );
				break;
			case 'edit-account':
				$title = __( 'Account details', 'woocommerce' );
				break;
			case 'edit-address':
				$title = __( 'Addresses', 'woocommerce' );
				break;
			case 'payment-methods':
				$title = __( 'Payment methods', 'woocommerce' );
				break;
			case 'add-payment-method':
				$title = __( 'Add payment method', 'woocommerce' );
				break;
			case 'lost-password':
				$title = __( 'Lost password', 'woocommerce' );
				break;
			default:
				$title = '';
				break;
		}

		return apply_filters( 'woocommerce_endpoint_' . $endpoint . '_title', $title, $endpoint );
	}

	/**
	 * Endpoint mask describing the places the endpoint should be added.
	 *
	 * @since 2.6.2
	 * @return int
	 */
	public function get_endpoints_mask() {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$page_on_front     = get_option( 'page_on_front' );
			$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
			$checkout_page_id  = get_option( 'woocommerce_checkout_page_id' );

			if ( in_array( $page_on_front, array( $myaccount_page_id, $checkout_page_id ), true ) ) {
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

		foreach ( $this->get_query_vars() as $key => $var ) {
			if ( ! empty( $var ) ) {
				add_rewrite_endpoint( $var, $mask );
			}
		}
	}

	/**
	 * Add query vars.
	 *
	 * @access public
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		foreach ( $this->get_query_vars() as $key => $var ) {
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
		return apply_filters( 'woocommerce_get_query_vars', $this->query_vars );
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

		// Map query vars to their keys, or get them if endpoints are not supported.
		foreach ( $this->get_query_vars() as $key => $var ) {
			if ( isset( $_GET[ $var ] ) ) { // WPCS: input var ok, CSRF ok.
				$wp->query_vars[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $var ] ) ); // WPCS: input var ok, CSRF ok.
			} elseif ( isset( $wp->query_vars[ $var ] ) ) {
				$wp->query_vars[ $key ] = $wp->query_vars[ $var ];
			}
		}
	}

	/**
	 * Are we currently on the front page?
	 *
	 * @param WP_Query $q Query instance.
	 * @return bool
	 */
	private function is_showing_page_on_front( $q ) {
		return $q->is_home() && 'page' === get_option( 'show_on_front' );
	}

	/**
	 * Is the front page a page we define?
	 *
	 * @param int $page_id Page ID.
	 * @return bool
	 */
	private function page_on_front_is( $page_id ) {
		return absint( get_option( 'page_on_front' ) ) === absint( $page_id );
	}

	/**
	 * Hook into pre_get_posts to do the main product query.
	 *
	 * @param WP_Query $q Query instance.
	 */
	public function pre_get_posts( $q ) {
		// We only want to affect the main query.
		if ( ! $q->is_main_query() ) {
			return;
		}

		// Fix for endpoints on the homepage.
		if ( $this->is_showing_page_on_front( $q ) && ! $this->page_on_front_is( $q->get( 'page_id' ) ) ) {
			$_query = wp_parse_args( $q->query );
			if ( ! empty( $_query ) && array_intersect( array_keys( $_query ), array_keys( $this->get_query_vars() ) ) ) {
				$q->is_page     = true;
				$q->is_home     = false;
				$q->is_singular = true;
				$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
				add_filter( 'redirect_canonical', '__return_false' );
			}
		}

		// When orderby is set, WordPress shows posts on the front-page. Get around that here.
		if ( $this->is_showing_page_on_front( $q ) && $this->page_on_front_is( wc_get_page_id( 'shop' ) ) ) {
			$_query = wp_parse_args( $q->query );
			if ( empty( $_query ) || ! array_diff( array_keys( $_query ), array( 'preview', 'page', 'paged', 'cpage', 'orderby' ) ) ) {
				$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
				$q->is_page = true;
				$q->is_home = false;

				// WP supporting themes show post type archive.
				if ( current_theme_supports( 'woocommerce' ) ) {
					$q->set( 'post_type', 'product' );
				} else {
					$q->is_singular = true;
				}
			}
		}

		// Fix product feeds.
		if ( $q->is_feed() && $q->is_post_type_archive( 'product' ) ) {
			$q->is_comment_feed = false;
		}

		// Special check for shops with the PRODUCT POST TYPE ARCHIVE on front.
		if ( current_theme_supports( 'woocommerce' ) && $q->is_page() && 'page' === get_option( 'show_on_front' ) && absint( $q->get( 'page_id' ) ) === wc_get_page_id( 'shop' ) ) {
			// This is a front-page shop.
			$q->set( 'post_type', 'product' );
			$q->set( 'page_id', '' );

			if ( isset( $q->query['paged'] ) ) {
				$q->set( 'paged', $q->query['paged'] );
			}

			// Define a variable so we know this is the front page shop later on.
			wc_maybe_define_constant( 'SHOP_IS_ON_FRONT', true );

			// Get the actual WP page to avoid errors and let us use is_front_page().
			// This is hacky but works. Awaiting https://core.trac.wordpress.org/ticket/21096.
			global $wp_post_types;

			$shop_page = get_post( wc_get_page_id( 'shop' ) );

			$wp_post_types['product']->ID         = $shop_page->ID;
			$wp_post_types['product']->post_title = $shop_page->post_title;
			$wp_post_types['product']->post_name  = $shop_page->post_name;
			$wp_post_types['product']->post_type  = $shop_page->post_type;
			$wp_post_types['product']->ancestors  = get_ancestors( $shop_page->ID, $shop_page->post_type );

			// Fix conditional Functions like is_front_page.
			$q->is_singular          = false;
			$q->is_post_type_archive = true;
			$q->is_archive           = true;
			$q->is_page              = true;

			// Remove post type archive name from front page title tag.
			add_filter( 'post_type_archive_title', '__return_empty_string', 5 );

			// Fix WP SEO.
			if ( class_exists( 'WPSEO_Meta' ) ) {
				add_filter( 'wpseo_metadesc', array( $this, 'wpseo_metadesc' ) );
				add_filter( 'wpseo_metakey', array( $this, 'wpseo_metakey' ) );
			}
		} elseif ( ! $q->is_post_type_archive( 'product' ) && ! $q->is_tax( get_object_taxonomies( 'product' ) ) ) {
			// Only apply to product categories, the product post archive, the shop page, product tags, and product attribute taxonomies.
			return;
		}

		$this->product_query( $q );
	}

	/**
	 * Pre_get_posts above may adjust the main query to add WooCommerce logic. When this query is done, we need to ensure
	 * all custom filters are removed.
	 *
	 * This is done here during the_posts filter. The input is not changed.
	 *
	 * @param array $posts Posts from WP Query.
	 * @return array
	 */
	public function remove_product_query_filters( $posts ) {
		$this->remove_ordering_args();
		return $posts;
	}

	/**
	 * WP SEO meta description.
	 *
	 * Hooked into wpseo_ hook already, so no need for function_exist.
	 *
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
	 * Query the products, applying sorting/ordering etc.
	 * This applies to the main WordPress loop.
	 *
	 * @param WP_Query $q Query instance.
	 */
	public function product_query( $q ) {
		if ( ! is_feed() ) {
			$ordering  = $this->get_catalog_ordering_args();
			$q->set( 'orderby', $ordering['orderby'] );
			$q->set( 'order', $ordering['order'] );

			if ( isset( $ordering['meta_key'] ) ) {
				$q->set( 'meta_key', $ordering['meta_key'] );
			}
		}

		// Query vars that affect posts shown.
		$q->set( 'meta_query', $this->get_meta_query( $q->get( 'meta_query' ), true ) );
		$q->set( 'tax_query', $this->get_tax_query( $q->get( 'tax_query' ), true ) );
		$q->set( 'wc_query', 'product_query' );
		$q->set( 'post__in', array_unique( (array) apply_filters( 'loop_shop_post_in', array() ) ) );

		// Work out how many products to query.
		$q->set( 'posts_per_page', $q->get( 'posts_per_page' ) ? $q->get( 'posts_per_page' ) : apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() ) );

		// Store reference to this query.
		self::$product_query = $q;

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
		remove_filter( 'posts_clauses', array( $this, 'order_by_price_asc_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'order_by_price_desc_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'order_by_popularity_post_clauses' ) );
		remove_filter( 'posts_clauses', array( $this, 'order_by_rating_post_clauses' ) );
	}

	/**
	 * Returns an array of arguments for ordering products based on the selected values.
	 *
	 * @param string $orderby Order by param.
	 * @param string $order Order param.
	 * @return array
	 */
	public function get_catalog_ordering_args( $orderby = '', $order = '' ) {
		// Get ordering from query string unless defined.
		if ( ! $orderby ) {
			$orderby_value = isset( $_GET['orderby'] ) ? wc_clean( (string) wp_unslash( $_GET['orderby'] ) ) : wc_clean( get_query_var( 'orderby' ) ); // WPCS: sanitization ok, input var ok.

			if ( ! $orderby_value ) {
				if ( is_search() ) {
					$orderby_value = 'relevance';
				} else {
					$orderby_value = apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ) );
				}
			}

			// Get order + orderby args from string.
			$orderby_value = explode( '-', $orderby_value );
			$orderby       = esc_attr( $orderby_value[0] );
			$order         = ! empty( $orderby_value[1] ) ? $orderby_value[1] : $order;
		}

		$orderby = strtolower( $orderby );
		$order   = strtoupper( $order );
		$args    = array(
			'orderby'  => $orderby,
			'order'    => ( 'DESC' === $order ) ? 'DESC' : 'ASC',
			'meta_key' => '', // @codingStandardsIgnoreLine
		);

		switch ( $orderby ) {
			case 'menu_order':
				$args['orderby']  = 'menu_order title';
				break;
			case 'title':
				$args['orderby'] = 'title';
				$args['order']   = ( 'DESC' === $order ) ? 'DESC' : 'ASC';
				break;
			case 'relevance':
				$args['orderby'] = 'relevance';
				$args['order']   = 'DESC';
				break;
			case 'rand':
				$args['orderby'] = 'rand'; // @codingStandardsIgnoreLine
				break;
			case 'date':
				$args['orderby'] = 'date ID';
				$args['order']   = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
				break;
			case 'price':
				if ( 'DESC' === $order ) {
					add_filter( 'posts_clauses', array( $this, 'order_by_price_desc_post_clauses' ) );
				} else {
					add_filter( 'posts_clauses', array( $this, 'order_by_price_asc_post_clauses' ) );
				}
				break;
			case 'popularity':
				$args['meta_key'] = 'total_sales'; // @codingStandardsIgnoreLine

				// Sorting handled later though a hook.
				add_filter( 'posts_clauses', array( $this, 'order_by_popularity_post_clauses' ) );
				break;
			case 'rating':
				$args['meta_key'] = '_wc_average_rating'; // @codingStandardsIgnoreLine
				$args['orderby']  = array(
					'meta_value_num' => 'DESC',
					'ID'             => 'ASC',
				);
				break;
		}

		return apply_filters( 'woocommerce_get_catalog_ordering_args', $args );
	}

	/**
	 * Handle numeric price sorting.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_price_asc_post_clauses( $args ) {
		global $wpdb, $wp_query;

		if ( isset( $wp_query->queried_object, $wp_query->queried_object->term_taxonomy_id, $wp_query->queried_object->taxonomy ) && is_a( $wp_query->queried_object, 'WP_Term' ) ) {
			$search_within_terms   = get_term_children( $wp_query->queried_object->term_taxonomy_id, $wp_query->queried_object->taxonomy );
			$search_within_terms[] = $wp_query->queried_object->term_taxonomy_id;
			$args['join'] .= " INNER JOIN (
				SELECT post_id, min( meta_value+0 ) price
				FROM $wpdb->postmeta
				INNER JOIN (
					SELECT $wpdb->term_relationships.object_id
					FROM $wpdb->term_relationships
					WHERE 1=1
					AND $wpdb->term_relationships.term_taxonomy_id IN (" . implode( ',', array_map( 'absint', $search_within_terms ) ) . ")
				) as products_within_terms ON $wpdb->postmeta.post_id = products_within_terms.object_id
				WHERE meta_key='_price' GROUP BY post_id ) as price_query ON $wpdb->posts.ID = price_query.post_id ";
		} else {
			$args['join']    .= " INNER JOIN ( SELECT post_id, min( meta_value+0 ) price FROM $wpdb->postmeta WHERE meta_key='_price' GROUP BY post_id ) as price_query ON $wpdb->posts.ID = price_query.post_id ";
		}
		$args['orderby'] = " price_query.price ASC, $wpdb->posts.ID ASC ";
		return $args;
	}

	/**
	 * Handle numeric price sorting.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_price_desc_post_clauses( $args ) {
		global $wpdb, $wp_query;

		if ( isset( $wp_query->queried_object, $wp_query->queried_object->term_taxonomy_id, $wp_query->queried_object->taxonomy ) && is_a( $wp_query->queried_object, 'WP_Term' ) ) {
			$search_within_terms   = get_term_children( $wp_query->queried_object->term_taxonomy_id, $wp_query->queried_object->taxonomy );
			$search_within_terms[] = $wp_query->queried_object->term_taxonomy_id;
			$args['join'] .= " INNER JOIN (
				SELECT post_id, max( meta_value+0 ) price
				FROM $wpdb->postmeta
				INNER JOIN (
					SELECT $wpdb->term_relationships.object_id
					FROM $wpdb->term_relationships
					WHERE 1=1
					AND $wpdb->term_relationships.term_taxonomy_id IN (" . implode( ',', array_map( 'absint', $search_within_terms ) ) . ")
				) as products_within_terms ON $wpdb->postmeta.post_id = products_within_terms.object_id
				WHERE meta_key='_price' GROUP BY post_id ) as price_query ON $wpdb->posts.ID = price_query.post_id ";
		} else {
			$args['join'] .= " INNER JOIN ( SELECT post_id, max( meta_value+0 ) price FROM $wpdb->postmeta WHERE meta_key='_price' GROUP BY post_id ) as price_query ON $wpdb->posts.ID = price_query.post_id ";
		}

		$args['orderby'] = " price_query.price DESC, $wpdb->posts.ID DESC ";
		return $args;
	}

	/**
	 * WP Core doens't let us change the sort direction for individual orderby params - https://core.trac.wordpress.org/ticket/17065.
	 *
	 * This lets us sort by meta value desc, and have a second orderby param.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function order_by_popularity_post_clauses( $args ) {
		global $wpdb;
		$args['orderby'] = "$wpdb->postmeta.meta_value+0 DESC, $wpdb->posts.post_date DESC";
		return $args;
	}

	/**
	 * Appends meta queries to an array.
	 *
	 * @param  array $meta_query Meta query.
	 * @param  bool  $main_query If is main query.
	 * @return array
	 */
	public function get_meta_query( $meta_query = array(), $main_query = false ) {
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}
		$meta_query['price_filter']  = $this->price_filter_meta_query();
		return array_filter( apply_filters( 'woocommerce_product_query_meta_query', $meta_query, $this ) );
	}

	/**
	 * Appends tax queries to an array.
	 *
	 * @param  array $tax_query  Tax query.
	 * @param  bool  $main_query If is main query.
	 * @return array
	 */
	public function get_tax_query( $tax_query = array(), $main_query = false ) {
		if ( ! is_array( $tax_query ) ) {
			$tax_query = array(
				'relation' => 'AND',
			);
		}

		// Layered nav filters on terms.
		if ( $main_query ) {
			foreach ( $this->get_layered_nav_chosen_attributes() as $taxonomy => $data ) {
				$tax_query[] = array(
					'taxonomy'         => $taxonomy,
					'field'            => 'slug',
					'terms'            => $data['terms'],
					'operator'         => 'and' === $data['query_type'] ? 'AND' : 'IN',
					'include_children' => false,
				);
			}
		}

		$product_visibility_terms  = wc_get_product_visibility_term_ids();
		$product_visibility_not_in = array( is_search() && $main_query ? $product_visibility_terms['exclude-from-search'] : $product_visibility_terms['exclude-from-catalog'] );

		// Hide out of stock products.
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$product_visibility_not_in[] = $product_visibility_terms['outofstock'];
		}

		// Filter by rating.
		if ( isset( $_GET['rating_filter'] ) ) { // WPCS: input var ok, CSRF ok.
			$rating_filter = array_filter( array_map( 'absint', explode( ',', $_GET['rating_filter'] ) ) ); // WPCS: input var ok, CSRF ok, Sanitization ok.
			$rating_terms  = array();
			for ( $i = 1; $i <= 5; $i ++ ) {
				if ( in_array( $i, $rating_filter, true ) && isset( $product_visibility_terms[ 'rated-' . $i ] ) ) {
					$rating_terms[] = $product_visibility_terms[ 'rated-' . $i ];
				}
			}
			if ( ! empty( $rating_terms ) ) {
				$tax_query[] = array(
					'taxonomy'      => 'product_visibility',
					'field'         => 'term_taxonomy_id',
					'terms'         => $rating_terms,
					'operator'      => 'IN',
					'rating_filter' => true,
				);
			}
		}

		if ( ! empty( $product_visibility_not_in ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $product_visibility_not_in,
				'operator' => 'NOT IN',
			);
		}

		return array_filter( apply_filters( 'woocommerce_product_query_tax_query', $tax_query, $this ) );
	}

	/**
	 * Return a meta query for filtering by price.
	 *
	 * @return array
	 */
	private function price_filter_meta_query() {
		if ( isset( $_GET['max_price'] ) || isset( $_GET['min_price'] ) ) { // WPCS: input var ok, CSRF ok.
			$meta_query = wc_get_min_max_price_meta_query( $_GET ); // WPCS: input var ok, CSRF ok.
			$meta_query['price_filter'] = true;

			return $meta_query;
		}

		return array();
	}

	/**
	 * Get the main query which product queries ran against.
	 *
	 * @return array
	 */
	public static function get_main_query() {
		return self::$product_query;
	}

	/**
	 * Get the tax query which was used by the main query.
	 *
	 * @return array
	 */
	public static function get_main_tax_query() {
		$tax_query = isset( self::$product_query->tax_query, self::$product_query->tax_query->queries ) ? self::$product_query->tax_query->queries : array();

		return $tax_query;
	}

	/**
	 * Get the meta query which was used by the main query.
	 *
	 * @return array
	 */
	public static function get_main_meta_query() {
		$args       = self::$product_query->query_vars;
		$meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();

		return $meta_query;
	}

	/**
	 * Based on WP_Query::parse_search
	 */
	public static function get_main_search_query_sql() {
		global $wpdb;

		$args         = self::$product_query->query_vars;
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
			$sql[] = $wpdb->prepare( "(($wpdb->posts.post_title $like_op %s) $andor_op ($wpdb->posts.post_excerpt $like_op %s) $andor_op ($wpdb->posts.post_content $like_op %s))", $like, $like, $like ); // unprepared SQL ok.
		}

		if ( ! empty( $sql ) && ! is_user_logged_in() ) {
			$sql[] = "($wpdb->posts.post_password = '')";
		}

		return implode( ' AND ', $sql );
	}

	/**
	 * Get an array of attributes and terms selected with the layered nav widget.
	 *
	 * @return array
	 */
	public static function get_layered_nav_chosen_attributes() {
		if ( ! is_array( self::$_chosen_attributes ) ) {
			self::$_chosen_attributes = array();
			$attribute_taxonomies     = wc_get_attribute_taxonomies();

			if ( ! empty( $attribute_taxonomies ) ) {
				foreach ( $attribute_taxonomies as $tax ) {
					$attribute    = wc_sanitize_taxonomy_name( $tax->attribute_name );
					$taxonomy     = wc_attribute_taxonomy_name( $attribute );
					$filter_terms = ! empty( $_GET[ 'filter_' . $attribute ] ) ? explode( ',', wc_clean( wp_unslash( $_GET[ 'filter_' . $attribute ] ) ) ) : array(); // WPCS: sanitization ok, input var ok.

					if ( empty( $filter_terms ) || ! taxonomy_exists( $taxonomy ) ) {
						continue;
					}

					$query_type = ! empty( $_GET[ 'query_type_' . $attribute ] ) && in_array( $_GET[ 'query_type_' . $attribute ], array( 'and', 'or' ), true ) ? wc_clean( wp_unslash( $_GET[ 'query_type_' . $attribute ] ) ) : ''; // WPCS: sanitization ok, input var ok.
					self::$_chosen_attributes[ $taxonomy ]['terms']      = array_map( 'sanitize_title', $filter_terms ); // Ensures correct encoding.
					self::$_chosen_attributes[ $taxonomy ]['query_type'] = $query_type ? $query_type : apply_filters( 'woocommerce_layered_nav_default_query_type', 'and' );
				}
			}
		}
		return self::$_chosen_attributes;
	}

	/**
	 * Remove the add-to-cart param from pagination urls.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public function remove_add_to_cart_pagination( $url ) {
		return remove_query_arg( 'add-to-cart', $url );
	}

	// @codingStandardsIgnoreStart
	/**
	 * Order by rating post clauses.
	 *
	 * @deprecated 3.0.0
	 * @param array $args
	 * @return array
	 */
	public function order_by_rating_post_clauses( $args ) {
		global $wpdb;

		wc_deprecated_function( 'order_by_rating_post_clauses', '3.0' );

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
	 * Return a meta query for filtering by rating.
	 *
	 * @deprecated 3.0.0 Replaced with taxonomy.
	 * @return array
	 */
	public function rating_filter_meta_query() {
		return array();
	}

	/**
	 * Returns a meta query to handle product visibility.
	 *
	 * @deprecated 3.0.0 Replaced with taxonomy.
	 * @param string $compare (default: 'IN')
	 * @return array
	 */
	public function visibility_meta_query( $compare = 'IN' ) {
		return array();
	}

	/**
	 * Returns a meta query to handle product stock status.
	 *
	 * @deprecated 3.0.0 Replaced with taxonomy.
	 * @param string $status (default: 'instock')
	 * @return array
	 */
	public function stock_status_meta_query( $status = 'instock' ) {
		return array();
	}

	/**
	 * Layered nav init.
	 *
	 * @deprecated 2.6.0
	 */
	public function layered_nav_init() {
		wc_deprecated_function( 'layered_nav_init', '2.6' );
	}

	/**
	 * Get an unpaginated list all product IDs (both filtered and unfiltered). Makes use of transients.
	 *
	 * @deprecated 2.6.0 due to performance concerns
	 */
	public function get_products_in_view() {
		wc_deprecated_function( 'get_products_in_view', '2.6' );
	}

	/**
	 * Layered Nav post filter.
	 *
	 * @deprecated 2.6.0 due to performance concerns
	 *
	 * @param mixed $deprecated Deprecated.
	 */
	public function layered_nav_query( $deprecated ) {
		wc_deprecated_function( 'layered_nav_query', '2.6' );
	}

	/**
	 * Search post excerpt.
	 *
	 * @deprecated 3.2.0 - Not needed anymore since WordPress 4.5.
	 */
	public function search_post_excerpt( $where = '' ) {
		wc_deprecated_function( 'WC_Query::search_post_excerpt', '3.2.0', 'Excerpt added to search query by default since WordPress 4.5.' );
		return $where;
	}

	/**
	 * Remove the posts_where filter.
	 * @deprecated 3.2.0 - Nothing to remove anymore because search_post_excerpt() is deprecated.
	 */
	public function remove_posts_where() {
		wc_deprecated_function( 'WC_Query::remove_posts_where', '3.2.0', 'Nothing to remove anymore because search_post_excerpt() is deprecated.' );
	}
	// @codingStandardsIgnoreEnd
}
