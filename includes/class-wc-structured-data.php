<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Structured data's handler and generator using JSON-LD format.
 *
 * @class     WC_Structured_Data
 * @version   2.7.0
 * @package   WooCommerce/Classes
 * @category  Class
 * @author    Clement Cazaud
 */
class WC_Structured_Data {
	
	/**
	 * @var array Partially structured data from `generate_*` methods
	 */
	private $_data;

	/**
	 * @var array Structured data
	 */
	private $_structured_data;

	/**
	 * Constructor.
	*/
	public function __construct() {
		// Generate data...
		add_action( 'woocommerce_before_main_content', array( $this, 'generate_shop_data' ), 30 );
		add_action( 'woocommerce_breadcrumb', array( $this, 'generate_breadcrumb_data' ), 10, 1 );
		add_action( 'woocommerce_before_shop_loop_item', array( $this, 'generate_product_category_data' ), 20 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'generate_product_data' ), 60 );
		add_action( 'woocommerce_review_meta', array( $this, 'generate_product_review_data' ), 20, 1 );
		add_action( 'woocommerce_email_order_details', array( $this, 'generate_email_order_data' ), 20, 4 );
		// Enqueue structured data...
		add_action( 'woocommerce_email_order_details', array( $this, 'enqueue_data' ), 30 );
		add_action( 'wp_footer', array( $this, 'enqueue_data' ) );
	}

	/**
	 * Sets `$this->_data` after `$json` validation.
	 *
	 * @param  array $json Partially structured data from `generate_*` methods
	 * @param  bool  $overwrite (default: false)
	 * @return bool `false` if invalid `$json`, otherwise `true`
	 */
	public function set_data( $json, $overwrite = false ) {
		if ( ! is_array( $json ) || ! array_key_exists( '@type', $json ) ) {
			return false;
		}

		if ( $overwrite && isset( $this->_data ) ) {
			unset( $this->_data );
		}
		
		$this->_data[] = $json;

		return true;
	}
	
	/**
	 * Gets `$this->_data`.
	 *
	 * @return array $data Or empty array if `$this->_data` is not set
	 */
	public function get_data() {
		$data = isset( $this->_data ) ? $this->_data : array();
			
		return $data;
	}

	/**
	 * Sets `$this->_structured_data`.
	 *
	 * @return bool `false` if there is no `$this->_data` to structure, otherwise `true`
	 */
	public function set_structured_data() {
		if ( ! isset( $this->_data ) ) {
			return false;
		}

		foreach ( $this->get_data() as $value ) {
			$type = $value['@type'];

			switch ( $type ) {
				case 'MusicAlbum':
				case 'SoftwareApplication':
					$type = 'Product';
					break;
			}

			$data[ $type ][] = $value;
		}

		foreach ( $data as $type => $value ) {
			if ( count( $value ) > 1 ) {
				$data[ $type ] = array( '@graph' => $value );
			} else {
				$data[ $type ] = $value[0];
			}

			$data[ $type ] = apply_filters( 'woocommerce_structured_data_context', array( '@context' => 'http://schema.org/' ), $data, $type, $value ) + $data[ $type ];
		}

		$this->_structured_data = $data;

		return true;
	}

	/**
	 * Gets `$this->_structured_data` after `$requested_types` validation.
	 *
	 * @param  mixed $requested_types bool|array (default: false) Array of requested types
	 * @return array $structured_data Or empty array if there is no structured data or if `$requested_types` is not valid
	 */
	public function get_structured_data( $requested_types = false ) {
		if ( ! $this->set_structured_data() ) {
			return array();
		} elseif ( $requested_types && ! is_array( $requested_types ) ) {
			return array();
		}

		if ( $requested_types ) {
			foreach ( $this->_structured_data as $type => $value ) {
				foreach ( $requested_types as $requested_type ) {
					if ( $requested_type === $type ) {
						$structured_data[] = $value;
					}
				}
			}
		} else {
			foreach ( $this->_structured_data as $value ) {
				$structured_data[] = $value;
			}
		}

		if ( count( $structured_data ) > 1 ) {
			return $structured_data = array( '@graph' => $structured_data );
		} else {
			return $structured_data[0];
		}
	}

	/**
	 * Sanitizes, encodes and echoes structured data.
	 * Hooked into the `wp_footer` action hook.
	 * Hooked into the `woocommerce_email_order_details` action hook.
	 */
	public function enqueue_data( $requested_types = false ) {
		if ( $structured_data = $this->sanitize_data( $this->get_structured_data( $requested_types ) ) ) {
			// Testing/Debugging
			//echo json_encode( $structured_data, JSON_UNESCAPED_SLASHES );
			
			echo '<script type="application/ld+json">' . wp_json_encode( $structured_data ) . '</script>';
		}
	}

	/**
	 * Sanitizes structured data.
	 *
	 * @param  array $data
	 * @return array $sanitized_data Or empty array if there is no data to sanitize
	 */
	public function sanitize_data( $data ) {
		if ( ! $data ) {
			return array();
		}

		foreach ( $data as $key => $value ) {
			$sanitized_data[ sanitize_text_field( $key ) ] = is_array( $value ) ? $this->sanitize_data( $value ) : sanitize_text_field( $value );
		}

		return $sanitized_data;
	}

	/**
	 * Generates structured data for product categories.
	 * Hooked into the `woocommerce_before_shop_loop_item` action hook.
	 */
	public function generate_product_category_data() {
		if ( ! is_product_category() && ! is_shop() ) {
			return;
		}
		
		$this->generate_product_data();
	}
	
	/**
	 * Generates structured data for single products.
	 * Hooked into the `woocommerce_single_product_summary` action hook.
	 */
	public function generate_product_data() {
		global $product;

		if ( $is_multi_variation = count( $product->get_children() ) > 1 ? true : false ) {
			$variations = $product->get_available_variations();
		} else {
			$variations = array( null );
		}

		foreach ( $variations as $variation ) {
			$product_variation = $is_multi_variation ? wc_get_product( $variation['variation_id'] ) : $product;
			
			$json_offers[] = array(
				'@type'         => 'Offer',
				'priceCurrency' => get_woocommerce_currency(),
				'price'         => $product_variation->get_price(),
				'availability'  => 'http://schema.org/' . $stock = ( $product_variation->is_in_stock() ? 'InStock' : 'OutOfStock' ),
				'sku'           => $product_variation->get_sku(),
				'image'         => wp_get_attachment_url( $product_variation->get_image_id() ),
				'description'   => $is_multi_variation ? $product_variation->get_variation_description() : '',
				'seller'        => array(
					'@type' => 'Organization',
					'name'  => get_bloginfo( 'name' ),
					'url'   => get_bloginfo( 'url' ),
				),
			);
		}
		
		if ( $product->is_downloadable() ) {
			switch ( $product->download_type ) {
				case 'application' :
					$type = "SoftwareApplication";
					break;
				case 'music' :
					$type = "MusicAlbum";
					break;
				default :
					$type = "Product";
					break;
			}
		} else {
			$type = "Product";
		}

		$json['@type']       = $type;
		$json['@id']         = get_the_permalink();
		$json['name']        = get_the_title();
		$json['description'] = get_the_excerpt();
		$json['url']         = get_the_permalink();
		$json['offers']      = $json_offers;
		
		if ( $product->get_rating_count() ) {
			$json['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $product->get_average_rating(),
				'ratingCount' => $product->get_rating_count(),
				'reviewCount' => $product->get_review_count(),
			);
		}

		$this->set_data( apply_filters( 'woocommerce_structured_data_product', $json, $product ) );
	}

	/**
	 * Generates structured data for product reviews.
	 * Hooked into the `woocommerce_review_meta` action hook.
	 *
	 * @param object $comment
	 */
	public function generate_product_review_data( $comment ) {

		$json['@type']         = 'Review';
		$json['@id']           = get_the_permalink() . '#li-comment-' . get_comment_ID();
		$json['datePublished'] = get_comment_date( 'c' );
		$json['description']   = get_comment_text();
		$json['itemReviewed']  = array(
			'@type' => 'Product',
			'name'  => get_the_title(),
		);
		$json['reviewRating']  = array(
			'@type'       => 'rating',
			'ratingValue' => intval( get_comment_meta( $comment->comment_ID, 'rating', true ) ),
		);
		$json['author']        = array(
			'@type'       => 'Person',
			'name'        => get_comment_author(),
		);
		
		$this->set_data( apply_filters( 'woocommerce_structured_data_product_review', $json, $comment ) );
	}

	/**
	 * Generates structured data for the breadcrumb.
	 * Hooked into the `woocommerce_breadcrumb` action hook.
	 *
	 * @param array $args
	 */
	public function generate_breadcrumb_data( $args ) {
		if ( empty( $args['breadcrumb'] ) ) {
			return;
		}

		$breadcrumb = $args['breadcrumb'];
		$position   = 1;

		foreach ( $breadcrumb as $key => $value ) {
			if ( ! empty( $value[1] ) && sizeof( $breadcrumb ) !== $key + 1 ) {
				$json_crumbs_item = array(
					'@id'  => $value[1],
					'name' => $value[0],
				);
			} else {
				$json_crumbs_item = array(
					'name' => $value[0]
				);
			}

			$json_crumbs[] = array(
				'@type'    => 'ListItem',
				'position' => $position ++,
				'item'     => $json_crumbs_item,
			);
		}

		$json['@type']           = 'BreadcrumbList';
		$json['itemListElement'] = $json_crumbs;

		$this->set_data( apply_filters( 'woocommerce_structured_data_breadcrumb', $json, $breadcrumb ) );
	}

	/**
	 * Generates structured data related to the shop.
	 * Hooked into the `woocommerce_before_main_content` action hook.
	 */
	public function generate_shop_data() {
		if ( ! is_shop() || ! is_front_page() ) {
			return;
		}

		$json['@type']           = 'WebSite';
		$json['name']            = get_bloginfo( 'name' );
		$json['url']             = get_bloginfo( 'url' );
		$json['potentialAction'] = array(
			'@type'       => 'SearchAction',
			'target'      => get_bloginfo( 'url' ) . '/?s={search_term_string}&post_type=product',
			'query-input' => 'required name=search_term_string',
		);

		$this->set_data( apply_filters( 'woocommerce_structured_data_shop', $json ) );
	}
	
	/**
	 * Generates structured data for the email order.
	 * Hooked into the `woocommerce_email_order_details` action hook.
	 * 
	 * @param mixed $order
	 * @param bool	$sent_to_admin (default: false)
	 * @param bool	$plain_text (default: false)
	 */
	public function generate_email_order_data( $order, $sent_to_admin = false, $plain_text = false ) {
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

			$json_offers[]  = array(
				'@type'              => 'Offer',
				'price'              => $order->get_line_subtotal( $item ),
				'priceCurrency'      => $order->get_currency(),
				'priceSpecification' => array(
					'price'                 => $order->get_line_subtotal( $item ),
					'priceCurrency'         => $order->get_currency(),
					'eligibleQuantity'      => array(
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

		$json['@type']              = 'Order';
		$json['orderStatus']        = $order_status;
		$json['orderNumber']        = $order->get_order_number();
		$json['orderDate']          = date( 'c', $order->get_date_created() );
		$json['url']                = $order_url;
		$json['acceptedOffer']      = $json_offers;
		$json['discount']           = $order->get_total_discount();
		$json['discountCurrency']   = $order->get_currency();
		$json['price']              = $order->get_total();
		$json['priceCurrency']      = $order->get_currency();
		$json['priceSpecification'] = array(
			'price'                 => $order->get_total(),
			'priceCurrency'         => $order->get_currency(),
			'valueAddedTaxIncluded' => true,
		);
		$json['billingAddress']     = array(
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
		$json['customer']           = array(
			'@type' => 'Person',
			'name'  => $order->get_formatted_billing_full_name(),
		);
		$json['merchant']           = array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => get_bloginfo( 'url' ),
		);
		$json['potentialAction']    = array(
			'@type'  => 'ViewAction',
			'name'   => 'View Order',
			'url'    => $order_url,
			'target' => $order_url,
		);

		$this->set_data( apply_filters( 'woocommerce_structured_data_email_order', $json, $sent_to_admin, $order ), true );
	}
}
