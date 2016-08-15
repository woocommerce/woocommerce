<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Structured data's handler and generator using JSON-LD format.
 *
 * @class     WC_Structured_Data
 * @since     2.7.0
 * @version   2.7.0
 * @package   WooCommerce/Classes
 * @author    Clément Cazaud <opportus@gmail.com>
 */
class WC_Structured_Data {
	
	/**
	 * @var null|array $_data
	 */
	private $_data;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Generate data...
		add_action( 'woocommerce_before_main_content',    array( $this, 'generate_website_data' ),        30, 0 );
		add_action( 'woocommerce_breadcrumb',             array( $this, 'generate_breadcrumblist_data' ), 10, 1 );
		add_action( 'woocommerce_shop_loop',              array( $this, 'generate_product_data' ),        10, 0 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'generate_product_data' ),        60, 0 );
		add_action( 'woocommerce_review_meta',            array( $this, 'generate_review_data' ),         20, 1 );
		add_action( 'woocommerce_email_order_details',    array( $this, 'generate_order_data' ),          20, 3 );
		
		// Output structured data...
		add_action( 'woocommerce_email_order_details',    array( $this, 'output_structured_data' ),       30, 0 );
		add_action( 'wp_footer',                          array( $this, 'output_structured_data' ),       10, 0 );
	}

	/**
	 * Sets `$this->_data`.
	 *
	 * @param  array $data
	 * @param  bool  $reset (default: false)
	 * @return bool
	 */
	public function set_data( $data, $reset = false ) {
		if ( ! isset( $data['@type'] ) ) {
			return false;
		} elseif ( ! is_string( $data['@type'] ) ) {
			return false;
		}

		if ( $reset && isset( $this->_data ) ) {
			unset( $this->_data );
		}
		
		$this->_data[] = $data;

		return true;
	}
	
	/**
	 * Gets `$this->_data`.
	 *
	 * @return array $data
	 */
	public function get_data() {
		return $data = isset( $this->_data ) ? $this->_data : array();
	}

	/**
	 * Structures and returns data.
	 *
	 * List of types available by default for specific request:
	 *
	 * 'product',
	 * 'review',
	 * 'breadcrumblist',
	 * 'website',
	 * 'order',
	 *
	 * @param  bool|array|string $requested_types (default: false)
	 * @return array
	 */
	public function get_structured_data( $requested_types = false ) {
		if ( empty( $this->get_data() ) || ( $requested_types && ! is_array( $requested_types ) && ! is_string( $requested_types ) || is_null( $requested_types ) ) ) {
			return array();
		}

		foreach ( $this->get_data() as $value ) {
			$type = strtolower( $value['@type'] );
			$data[ $type ][] = $value;
		}

		foreach ( $data as $type => $value ) {
			$data[ $type ] = count( $value ) > 1 ? array( '@graph' => $value ) : $value[0];
			$data[ $type ] = apply_filters( 'woocommerce_structured_data_context', array( '@context' => 'http://schema.org/' ), $data, $type, $value ) + $data[ $type ];
		}

		if ( $requested_types ) {
			if ( is_string( $requested_types ) ) {
				$requested_types = array( $requested_types );
			}

			foreach ( $data as $type => $value ) {
				foreach ( $requested_types as $requested_type ) {
					if ( $requested_type === $type ) {
						$structured_data[] = $value;
					}
				}	
			}
		} else {
			foreach ( $data as $value ) {
				$structured_data[] = $value;
			}
		}

		if ( ! isset( $structured_data ) ) {
			return array();
		}

		$structured_data = count( $structured_data ) > 1 ?  array( '@graph' => $structured_data ) : $structured_data[0];

		return $structured_data;
	}

	/**
	 * Sanitizes, encodes and outputs structured data.
	 * 
	 * @uses   `wp_footer` action hook
	 * @uses   `woocommerce_email_order_details` action hook
	 * @param  bool|array|string $requested_types (default: true)
	 * @return bool
	 */
	public function output_structured_data( $requested_types = true ) {
		if ( $requested_types === true ) {
			$requested_types = apply_filters( 'woocommerce_structured_data_type_for_page', array(
				  is_shop() || is_product_category() || is_product() ? 'product'        : null,
				  is_shop() && is_front_page()                       ? 'website'        : null,
				  is_product()                                       ? 'review'         : null,
				! is_shop()                                          ? 'breadcrumblist' : null,
				                                                       'order',
			) );
		}

		if ( $structured_data = $this->sanitize_data( $this->get_structured_data( $requested_types ) ) ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $structured_data ) . '</script>';
			
			return true;
		} else {
			return false;
		}	
	}

	/**
	 * Sanitizes data.
	 *
	 * @param  array $data
	 * @return array
	 */
	public function sanitize_data( $data ) {
		if ( ! $data || ! is_array( $data ) ) {
			return array();
		}

		foreach ( $data as $key => $value ) {
			$sanitized_data[ sanitize_text_field( $key ) ] = is_array( $value ) ? $this->sanitize_data( $value ) : sanitize_text_field( $value );
		}

		return $sanitized_data;
	}

	/**
	 * Generates, sanitizes, encodes and outputs specific structured data type.
	 *
	 * @param  string $type
	 * @param  mixed  $object  (default: null)
	 * @param  mixed  $param_1 (default: null)
	 * @param  mixed  $param_2 (default: null)
	 * @param  mixed  $param_3 (default: null)
	 * @return bool
	 */
	public function generate_output_structured_data( $type, $object = null, $param_1 = null, $param_2 = null, $param_3 = null ) {
		if ( ! is_string( $type ) ) {
			return false;
		}
		
		$generate = 'generate_' . $type . '_data';

		if ( $this->$generate( $object, $param_1, $param_2, $param_3 ) ) {
			return $this->output_structured_data( $type );
		} else {
			return false;
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Generators
	|--------------------------------------------------------------------------
	|
	| Methods for generating specific structured data types:
	|
	| - Product
	| - Review
	| - BreadcrumbList
	| - WebSite
	| - Order
	|
	| The generated data is stored into `$this->_data`.
	| See the methods above for handling `$this->_data`.
	|
	*/

	/**
	 * Generates Product structured data.
	 *
	 * @uses   `woocommerce_single_product_summary` action hook
	 * @uses   `woocommerce_shop_loop` action hook
	 * @param  bool|object $product (default: false)
	 * @return bool
	 */
	public function generate_product_data( $product = false ) {
		if ( ! $product ) {
			global $product;
		}

		if ( ! is_object( $product ) ) {
			return false;
		}

		$variations = count( $product->get_children() ) > 1 ? $product->get_available_variations() : array( $product );

		foreach ( $variations as $variation ) {
			$product_variation = count( $variations ) > 1 ? wc_get_product( $variation['variation_id'] ) : $variation;
			
			$markup_offers[] = array(
				'@type'         => 'Offer',
				'priceCurrency' => get_woocommerce_currency(),
				'price'         => $product_variation->get_price(),
				'availability'  => 'http://schema.org/' . $stock = ( $product_variation->is_in_stock() ? 'InStock' : 'OutOfStock' ),
				'sku'           => $product_variation->get_sku(),
				'image'         => wp_get_attachment_url( $product_variation->get_image_id() ),
				'description'   => count( $variations ) > 1 ? $product_variation->get_variation_description() : '',
				'seller'        => array(
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name' ),
					'url'   => get_bloginfo( 'url' ),
				),
			);
		}
		
		$markup['@type']       = 'Product';
		$markup['@id']         = get_the_permalink();
		$markup['name']        = get_the_title();
		$markup['description'] = get_the_excerpt();
		$markup['url']         = get_the_permalink();
		$markup['offers']      = $markup_offers;
		
		if ( $product->get_rating_count() ) {
			$markup['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $product->get_average_rating(),
				'ratingCount' => $product->get_rating_count(),
				'reviewCount' => $product->get_review_count(),
			);
		}

		return $this->set_data( apply_filters( 'woocommerce_structured_data_product', $markup, $product ) );
	}

	/**
	 * Generates Review structured data.
	 *
	 * @uses   `woocommerce_review_meta` action hook
	 * @param  object $comment
	 * @return bool
	 */
	public function generate_review_data( $comment ) {
		if ( ! is_object( $comment ) ) {
			return false;
		}

		$markup['@type']         = 'Review';
		$markup['@id']           = get_the_permalink() . '#li-comment-' . get_comment_ID();
		$markup['datePublished'] = get_comment_date( 'c' );
		$markup['description']   = get_comment_text();
		$markup['itemReviewed']  = array(
			'@type' => 'Product',
			'name'  => get_the_title(),
		);
		$markup['reviewRating']  = array(
			'@type'       => 'rating',
			'ratingValue' => intval( get_comment_meta( $comment->comment_ID, 'rating', true ) ),
		);
		$markup['author']        = array(
			'@type' => 'Person',
			'name'  => get_comment_author(),
		);
		
		return $this->set_data( apply_filters( 'woocommerce_structured_data_review', $markup, $comment ) );
	}

	/**
	 * Generates BreadcrumbList structured data.
	 *
	 * @uses   `woocommerce_breadcrumb` action hook
	 * @param  array $breadcrumb
	 * @return bool|void
	 */
	public function generate_breadcrumblist_data( $breadcrumb ) {
		if ( ! is_array( $breadcrumb ) ) {
			return false;
		}
		
		if ( empty( $breadcrumb = $breadcrumb['breadcrumb'] ) ) {
			return;
		}

		$position = 1;

		foreach ( $breadcrumb as $key => $value ) {
			$markup_crumbs[] = array(
				'@type'    => 'ListItem',
				'position' => $position ++,
				'item'     => array(
					'@id'  => ! empty( $value[1] ) && sizeof( $breadcrumb ) !== $key + 1 ? $value[1] : '#',
					'name' => $value[0],
				),
			);
		}

		$markup['@type']           = 'BreadcrumbList';
		$markup['itemListElement'] = $markup_crumbs;

		return $this->set_data( apply_filters( 'woocommerce_structured_data_breadcrumblist', $markup, $breadcrumb ) );
	}

	/**
	 * Generates WebSite structured data.
	 *
	 * @uses  `woocommerce_before_main_content` action hook
	 * @return bool
	 */
	public function generate_website_data() {
		$markup['@type']           = 'WebSite';
		$markup['name']            = get_bloginfo( 'name' );
		$markup['url']             = get_bloginfo( 'url' );
		$markup['potentialAction'] = array(
			'@type'       => 'SearchAction',
			'target'      => get_bloginfo( 'url' ) . '/?s={search_term_string}&post_type=product',
			'query-input' => 'required name=search_term_string',
		);

		return $this->set_data( apply_filters( 'woocommerce_structured_data_website', $markup ) );
	}
	
	/**
	 * Generates Order structured data.
	 *
	 * @uses   `woocommerce_email_order_details` action hook
	 * @param  object    $order
	 * @param  bool	     $sent_to_admin (default: false)
	 * @param  bool	     $plain_text (default: false)
	 * @return bool|void
	 */
	public function generate_order_data( $order, $sent_to_admin = false, $plain_text = false ) {
		if ( ! is_object( $order ) ) {
			return false;
		}
		
		if ( $plain_text ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
				continue;
			}

			$product        = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
			$product_exists = is_object( $product );
			$is_visible     = $product_exists && $product->is_visible();
			$order_url      = $sent_to_admin ? admin_url( 'post.php?post=' . absint( $order->id ) . '&action=edit' ) : $order->get_view_order_url();

			$markup_offers[]  = array(
				'@type'              => 'Offer',
				'price'              => $order->get_line_subtotal( $item ),
				'priceCurrency'      => $order->get_currency(),
				'priceSpecification' => array(
					'price'            => $order->get_line_subtotal( $item ),
					'priceCurrency'    => $order->get_currency(),
					'eligibleQuantity' => array(
						'@type' => 'QuantitativeValue',
						'value' => apply_filters( 'woocommerce_email_order_item_quantity', $item['qty'], $item ),
					),
				),
				'itemOffered'        => array(
					'@type' => 'Product',
					'name'  => apply_filters( 'woocommerce_order_item_name', $item['name'], $item, $is_visible ),
					'sku'   => $product_exists ? $product->get_sku() : '',
					'image' => $product_exists ? wp_get_attachment_image_url( $product->get_image_id() ) : '',
					'url'   => $is_visible ? get_permalink( $product->get_id() ) : get_home_url(),
				),
				'seller'             => array(
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name' ),
					'url'   => get_bloginfo( 'url' ),
				),
			);
		}

		switch ( $order->get_status() ) {
			case 'pending':
				$order_status = 'http://schema.org/OrderPaymentDue';
				break;
			case 'processing':
				$order_status = 'http://schema.org/OrderProcessing';
				break;
			case 'on-hold':
				$order_status = 'http://schema.org/OrderProblem';
				break;
			case 'completed':
				$order_status = 'http://schema.org/OrderDelivered';
				break;
			case 'cancelled':
				$order_status = 'http://schema.org/OrderCancelled';
				break;
			case 'refunded':
				$order_status = 'http://schema.org/OrderReturned';
				break;
			case 'failed':
				$order_status = 'http://schema.org/OrderProblem';
				break;
		}

		$markup['@type']              = 'Order';
		$markup['orderStatus']        = $order_status;
		$markup['orderNumber']        = $order->get_order_number();
		$markup['orderDate']          = date( 'c', $order->get_date_created() );
		$markup['url']                = $order_url;
		$markup['acceptedOffer']      = $markup_offers;
		$markup['discount']           = $order->get_total_discount();
		$markup['discountCurrency']   = $order->get_currency();
		$markup['price']              = $order->get_total();
		$markup['priceCurrency']      = $order->get_currency();
		$markup['priceSpecification'] = array(
			'price'                 => $order->get_total(),
			'priceCurrency'         => $order->get_currency(),
			'valueAddedTaxIncluded' => true,
		);
		$markup['billingAddress']     = array(
			'@type'           => 'PostalAddress',
			'name'            => $order->get_formatted_billing_full_name(),
			'streetAddress'   => $order->get_billing_address_1(),
			'postalCode'      => $order->get_billing_postcode(),
			'addressLocality' => $order->get_billing_city(),
			'addressRegion'   => $order->get_billing_state(),
			'addressCountry'  => $order->get_billing_country(),
			'email'           => $order->get_billing_email(),
			'telephone'       => $order->get_billing_phone(),
		);
		$markup['customer']           = array(
			'@type' => 'Person',
			'name'  => $order->get_formatted_billing_full_name(),
		);
		$markup['merchant']           = array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => get_bloginfo( 'url' ),
		);
		$markup['potentialAction']    = array(
			'@type'  => 'ViewAction',
			'name'   => 'View Order',
			'url'    => $order_url,
			'target' => $order_url,
		);

		return $this->set_data( apply_filters( 'woocommerce_structured_data_order', $markup, $sent_to_admin, $order ), true );
	}
}
