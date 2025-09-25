<?php
/**
 * Woo_Analytics_Trait
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Block_Scanner;
use Automattic\Jetpack\Connection\Manager as Jetpack_Connection;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Payment_Gateway;
use WC_Product;

/**
 * Common functionality for WooCommerce Analytics classes.
 */
trait Woo_Analytics_Trait {
	/**
	 * Saves whether the cart/checkout templates are in use based on WC Blocks version.
	 *
	 * @var bool true if the templates are in use.
	 */
	protected $cart_checkout_templates_in_use;

	/**
	 * The content of the cart page or where the cart page is ultimately derived from if using a template.
	 *
	 * @var string
	 */
	protected $cart_content_source = '';

	/**
	 * The content of the checkout page or where the cart page is ultimately derived from if using a template.
	 *
	 * @var string
	 */
	protected $checkout_content_source = '';

	/**
	 * Tracks any additional blocks loaded on the Cart page.
	 *
	 * @var array
	 */
	protected $additional_blocks_on_cart_page;

	/**
	 * Tracks any additional blocks loaded on the Checkout page.
	 *
	 * @var array
	 */
	protected $additional_blocks_on_checkout_page;

	/**
	 *  Locks Add to Cart Events Tracking in the current request avoiding duplications.
	 *  i.e. If update_cart and add_to_cart actions happens in the same request.
	 *
	 *  @var bool If true. Cart events are locked for the current request.
	 */
	protected $lock_add_to_cart_events = false;

	/**
	 * Format Cart Items or Order Items to an array
	 *
	 * @param array|WC_Order_Item[] $items Cart Items or Order Items.
	 */
	protected function format_items_to_json( $items ) {
		$products = array();

		foreach ( $items as $item ) {
			if ( $item instanceof WC_Order_Item_Product ) {
				$product = wc_get_product( $item->get_product_id() );
			} else {
				$product = $item['data'];
			}

			if ( ! $product || ! $product instanceof WC_Product ) {
				continue;
			}

			$data = $this->get_product_details( $product );

			if ( $item instanceof WC_Order_Item_Product ) {
				$data['pq'] = $item->get_quantity();
			} else {
				$data['pq'] = $item['quantity'];
			}
			$products[] = $data;
		}

		return wp_json_encode( $products );
	}

	/**
	 * Get Cart/Checkout page view shared data
	 */
	protected function get_cart_checkout_shared_data() {
		$cart = WC()->cart;

		$guest_checkout           = ucfirst( get_option( 'woocommerce_enable_guest_checkout', 'No' ) );
		$create_account           = ucfirst( get_option( 'woocommerce_enable_signup_and_login_from_checkout', 'No' ) );
		$delayed_account_creation = ucfirst( get_option( 'woocommerce_enable_delayed_account_creation', 'Yes' ) );

		$coupons     = $cart->get_coupons();
		$coupon_used = 0;
		if ( is_countable( $coupons ) ) {
			$coupon_used = count( $coupons ) ? 1 : 0;
		}

		$enabled_payment_options = array_filter(
			WC()->payment_gateways->get_available_payment_gateways(),
			function ( $payment_gateway ) {
				if ( ! $payment_gateway instanceof WC_Payment_Gateway ) {
					return false;
				}

				return $payment_gateway->is_available();
			}
		);

		$enabled_payment_options = array_keys( $enabled_payment_options );
		$shared_data             = array(
			'products'                 => $this->format_items_to_json( $cart->get_cart() ),
			'create_account'           => $create_account,
			'guest_checkout'           => $guest_checkout,
			'delayed_account_creation' => $delayed_account_creation,
			'express_checkout'         => 'null', // TODO: not solved yet.
			'shipping_options_count'   => 'null', // TODO: not solved yet.
			'coupon_used'              => $coupon_used,
			'payment_options'          => $enabled_payment_options,
		);

		return $shared_data;
	}

	/**
	 * Gets the content of the cart/checkout page or where the cart/checkout page is ultimately derived from if using a template.
	 * This method sets the class properties $checkout_content_source and $cart_content_source.
	 *
	 * @return void Does not return, but sets class properties.
	 */
	public function find_cart_checkout_content_sources() {

		/**
		 * The steps we take to find the content are:
		 * 1. Check the transient, if that contains content and is not expired, return that.
		 * 2. Check if the cart/checkout templates are in use. If *not in use*, get the content from the pages and
		 *    return it, there is no need to dig further.
		 * 3. If the templates *are* in use, check if the `page-content-wrapper` block is in use. If so, get the content
		 *    from the pages (same as step 2) and return it.
		 * 4. If the templates are in use but `page-content-wrapper` is not, then get the content directly from the
		 *    template and return it.
		 * 5. At the end of each step, assign the found content to the relevant class properties and save them in a
		 *    transient with a 1-day lifespan. This will prevent us from having to do this work on every page load.
		 */

		$cart_checkout_content_cache_transient_name = 'jetpack_woocommerce_analytics_cart_checkout_content_sources';

		$transient_value = get_transient( $cart_checkout_content_cache_transient_name );

		if (
			false !== $transient_value &&
			! empty( $transient_value['checkout_content_source'] ) &&
			! empty( $transient_value['cart_content_source'] )
		) {
			$this->cart_content_source     = $transient_value['cart_content_source'];
			$this->checkout_content_source = $transient_value['checkout_content_source'];
			return;
		}

		$this->cart_checkout_templates_in_use = wp_is_block_theme()
			&& class_exists( '\Automattic\WooCommerce\Blocks\Package' )
			&& version_compare( \Automattic\WooCommerce\Blocks\Package::get_version(), '10.6.0', '>=' );

		// Cart/Checkout *pages* are in use if the templates are not in use. Return their content and do nothing else.
		if ( ! $this->cart_checkout_templates_in_use ) {
			$cart_page     = get_post( wc_get_page_id( 'cart' ) );
			$checkout_page = get_post( wc_get_page_id( 'checkout' ) );

			if ( $cart_page && isset( $cart_page->post_content ) ) {
				$this->cart_content_source = $cart_page->post_content;
			}

			if ( $checkout_page && isset( $checkout_page->post_content ) ) {
				$this->checkout_content_source = $checkout_page->post_content;
			}

			set_transient(
				$cart_checkout_content_cache_transient_name,
				array(
					'cart_content_source'     => $this->cart_content_source,
					'checkout_content_source' => $this->checkout_content_source,
				),
				DAY_IN_SECONDS
			);
			return;
		}

		// We are in a Block theme - so we need to find out if the templates are being used.
		if ( function_exists( 'get_block_template' ) ) {
			$checkout_template = get_block_template( 'woocommerce/woocommerce//page-checkout' );
			$cart_template     = get_block_template( 'woocommerce/woocommerce//page-cart' );

			if ( ! $checkout_template ) {
				$checkout_template = get_block_template( 'woocommerce/woocommerce//checkout' );
			}

			if ( ! $cart_template ) {
				$cart_template = get_block_template( 'woocommerce/woocommerce//cart' );
			}
		}

		if ( ! empty( $checkout_template->content ) ) {
			// Checkout template is in use, but we need to see if the page-content-wrapper is in use, or if the template is being used directly.
			$this->checkout_content_source = $checkout_template->content;
			$is_using_page_content         = str_contains( $checkout_template->content, '<!-- wp:woocommerce/page-content-wrapper {"page":"checkout"}' );

			if ( $is_using_page_content ) {
				// The page-content-wrapper is in use, so we need to get the page content.
				$checkout_page = get_post( wc_get_page_id( 'checkout' ) );

				if ( $checkout_page && isset( $checkout_page->post_content ) ) {
					$this->checkout_content_source = $checkout_page->post_content;
				}
			}
		}

		if ( ! empty( $cart_template->content ) ) {
			// Cart template is in use, but we need to see if the page-content-wrapper is in use, or if the template is being used directly.
			$this->cart_content_source = $cart_template->content;
			$is_using_page_content     = str_contains( $cart_template->content, '<!-- wp:woocommerce/page-content-wrapper {"page":"cart"}' );

			if ( $is_using_page_content ) {
				// The page-content-wrapper is in use, so we need to get the page content.
				$cart_page = get_post( wc_get_page_id( 'cart' ) );

				if ( $cart_page && isset( $cart_page->post_content ) ) {
					$this->cart_content_source = $cart_page->post_content;
				}
			}
		}

		set_transient(
			$cart_checkout_content_cache_transient_name,
			array(
				'cart_content_source'     => $this->cart_content_source,
				'checkout_content_source' => $this->checkout_content_source,
			),
			DAY_IN_SECONDS
		);
	}

	/**
	 * Default event properties which should be included with all events.
	 *
	 * @return array Array of standard event props.
	 */
	public function get_common_properties() {
		$site_info = array(
			'blog_id'        => Jetpack_Connection::get_site_id(),
			'store_id'       => defined( '\\WC_Install::STORE_ID_OPTION' ) ? get_option( \WC_Install::STORE_ID_OPTION ) : false,
			'ui'             => $this->get_user_id(),
			'url'            => home_url(),
			'woo_version'    => WC()->version,
			'wp_version'     => get_bloginfo( 'version' ),
			'store_admin'    => in_array( array( 'administrator', 'shop_manager' ), wp_get_current_user()->roles, true ) ? 1 : 0,
			'device'         => wp_is_mobile() ? 'mobile' : 'desktop',
			'store_currency' => get_woocommerce_currency(),
			'timezone'       => wp_timezone_string(),
			'is_guest'       => ( $this->get_user_id() === null ) ? 1 : 0,
		);

		/**
		 * Allow defining custom event properties in WooCommerce Analytics.
		 *
		 * @module woocommerce-analytics
		 *
		 * @since 12.5
		 *
		 * @param array $properties Array of event props to be filtered.
		 */
		$properties = apply_filters(
			'jetpack_woocommerce_analytics_event_props',
			$site_info
		);

		return $properties;
	}

	/**
	 * Enqueue an event with optional product and custom properties.
	 *
	 * @param string       $event_name The name of the event to record.
	 * @param array        $properties Optional array of (key => value) event properties.
	 * @param integer|null $product_id The id of the product relating to the event.
	 *
	 * @return void
	 */
	public function enqueue_event( $event_name, $properties = array(), $product_id = null ) {
		// Only set product details if we have a product id.
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product instanceof WC_Product ) {
				return;
			}
			$product_details = $this->get_product_details( $product );
		}

		$event_properties = array_merge( $product_details ?? array(), $properties );

		WC_Analytics_Tracking::add_event_to_queue( $event_name, $event_properties );
	}

	/**
	 * Gather relevant product information
	 *
	 * @param \WC_Product $product product.
	 * @return array
	 */
	public function get_product_details( $product ) {
		return array(
			'pi' => $product->get_id(),
			'pn' => $product->get_title(),
			'pc' => $this->get_product_categories_concatenated( $product ),
			'pp' => $product->get_price(),
			'pt' => $product->get_type(),
		);
	}

	/**
	 * Gets product categories or varation attributes as a formatted concatenated string
	 *
	 * @param object $product WC_Product.
	 * @return string
	 */
	public function get_product_categories_concatenated( $product ) {

		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$variation_data = $product->is_type( 'variation' ) ? wc_get_product_variation_attributes( $product->get_id() ) : '';
		if ( is_array( $variation_data ) && ! empty( $variation_data ) ) {
			$line = wc_get_formatted_variation( $variation_data, true );
		} else {
			$out        = array();
			$categories = get_the_terms( $product->get_id(), 'product_cat' );
			if ( $categories ) {
				foreach ( $categories as $category ) {
					$out[] = $category->name;
				}
			}
			$line = implode( '/', $out );
		}
		return $line;
	}

	/**
	 * Get the current user id
	 *
	 * @return string|null
	 */
	public function get_user_id() {
		if ( is_user_logged_in() ) {
			$blogid = Jetpack_Connection::get_site_id();
			$userid = get_current_user_id();
			return $blogid . ':' . $userid;
		}
		return null;
	}

	/**
	 * Gets the IDs of additional blocks on the Cart/Checkout pages or templates.
	 *
	 * @param string $cart_or_checkout Whether to get blocks on the cart or checkout page.
	 * @return array All inner blocks on the page.
	 */
	public function get_additional_blocks_on_page( $cart_or_checkout = 'cart' ) {
		$additional_blocks_on_page_transient_name = 'jetpack_woocommerce_analytics_additional_blocks_on_' . $cart_or_checkout . '_page';
		$additional_blocks_on_page                = get_transient( $additional_blocks_on_page_transient_name );

		if ( false !== $additional_blocks_on_page ) {
			return $additional_blocks_on_page;
		}

		$content = $this->cart_content_source;

		if ( 'checkout' === $cart_or_checkout ) {
			$content = $this->checkout_content_source;
		}

		$blocks_to_ignore = array(
			'woocommerce/classic-shortcode',
			'core/shortcode',
			'checkout' === $cart_or_checkout ? 'woocommerce/checkout' : 'woocommerce/cart',
		);

		$scanner = Block_Scanner::create( $content );
		if ( ! $scanner ) {
			return array();
		}

		$found_blocks        = array();
		$ignored_block_depth = 0; // Count how many ignored blocks we're nested inside.

		while ( $scanner->next_delimiter() ) {
			$type             = $scanner->get_delimiter_type();
			$block_type       = $scanner->get_block_type();
			$is_ignored_block = in_array( $block_type, $blocks_to_ignore, true );

			switch ( $type ) {
				case Block_Scanner::OPENER:
					// If this is an ignored block, increase our nesting depth.
					if ( $is_ignored_block ) {
						++$ignored_block_depth;
					}

					// Only collect blocks that are not inside any ignored block.
					if ( 0 === $ignored_block_depth ) {
						$found_blocks[] = $block_type;
					}
					break;

				case Block_Scanner::CLOSER:
					// If this closes an ignored block, decrease our nesting depth.
					if ( $is_ignored_block && $ignored_block_depth > 0 ) {
						--$ignored_block_depth;
					}
					break;

				case Block_Scanner::VOID:
					// Void blocks: only collect if we're not inside an ignored block and this isn't an ignored block itself.
					if ( 0 === $ignored_block_depth && ! $is_ignored_block ) {
						$found_blocks[] = $block_type;
					}
					break;
			}
		}

		set_transient( $additional_blocks_on_page_transient_name, $found_blocks, DAY_IN_SECONDS );
		return $found_blocks;
	}

	/**
	 * Gets an array containing the block or shortcode use properties for the Cart page.
	 *
	 * @return array            An array containing the block or shortcode use properties for the Cart page.
	 */
	public function get_cart_page_block_usage() {
		$new_info = array();

		$content                    = $this->cart_content_source;
		$block_presence             = str_contains( $content, '<!-- wp:woocommerce/cart' );
		$shortcode_presence         = str_contains( $content, '[woocommerce_cart]' );
		$classic_shortcode_presence = str_contains( $content, '<!-- wp:woocommerce/classic-shortcode {"shortcode":"cart"}' );

		$new_info['cart_page_contains_cart_block']     = $block_presence ? '1' : '0';
		$new_info['cart_page_contains_cart_shortcode'] = $shortcode_presence || $classic_shortcode_presence ? '1' : '0';
		return $new_info;
	}

	/**
	 * Gets an array containing the block or shortcode use properties for the Checkout page.
	 *
	 * @return array                An array containing the block or shortcode use properties for the Checkout page.
	 */
	public function get_checkout_page_block_usage() {
		$new_info = array();

		$content                    = $this->checkout_content_source;
		$block_presence             = str_contains( $content, '<!-- wp:woocommerce/checkout' );
		$shortcode_presence         = str_contains( $content, '[woocommerce_checkout]' );
		$classic_shortcode_presence = str_contains( $content, '<!-- wp:woocommerce/classic-shortcode {"shortcode":"checkout"}' );

		$new_info['checkout_page_contains_checkout_block']     = $block_presence ? '1' : '0';
		$new_info['checkout_page_contains_checkout_shortcode'] = $shortcode_presence || $classic_shortcode_presence ? '1' : '0';
		return $new_info;
	}

	/**
	 * Get info about the cart & checkout pages, in particular
	 * whether the store is using shortcodes or Gutenberg blocks.
	 * This info is cached in a transient.
	 *
	 * Note: similar code is in a WooCommerce core PR:
	 * https://github.com/woocommerce/woocommerce/pull/25932
	 *
	 * @return array
	 */
	public function get_cart_checkout_info() {
		$info = array_merge(
			$this->get_cart_page_block_usage(),
			$this->get_checkout_page_block_usage()
		);
		return $info;
	}

	/**
	 * Search a specific post for text content.
	 *
	 * Note: similar code is in a WooCommerce core PR:
	 * https://github.com/woocommerce/woocommerce/pull/25932
	 *
	 * @param integer $post_id The id of the post to search.
	 * @param string  $text    The text to search for.
	 * @return integer 1 if post contains $text (otherwise 0).
	 */
	public function post_contains_text( $post_id, $text ) {
		global $wpdb;

		// Search for the text anywhere in the post.
		$wildcarded = "%{$text}%";

		// No better way to search post content without having filters expanding blocks.
		// This is already cached up in the parent function.
		$result = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"
				SELECT COUNT( * ) FROM {$wpdb->prefix}posts
				WHERE ID=%d
				AND {$wpdb->prefix}posts.post_content LIKE %s
				",
				array( $post_id, $wildcarded )
			)
		);

		return ( '0' !== $result ) ? 1 : 0;
	}

	/**
	 * Get the cart total
	 *
	 * @return float
	 */
	public function get_cart_total() {
		$cart = WC()->cart;
		if ( $cart === null ) {
			return 0;
		}
		return $cart->get_total( 'tracking' );
	}

	/**
	 * Get the cart subtotal
	 *
	 * @return float
	 */
	public function get_cart_subtotal() {
		$cart = WC()->cart;
		if ( $cart === null ) {
			return 0;
		}
		return $cart->get_subtotal();
	}

	/**
	 * Get the cart shipping total
	 *
	 * @return float
	 */
	public function get_cart_shipping_total() {
		$cart = WC()->cart;
		if ( $cart === null ) {
			return 0;
		}
		return $cart->get_shipping_total();
	}

	/**
	 * Get the cart taxes
	 *
	 * @return float
	 */
	public function get_cart_taxes() {
		$cart = WC()->cart;
		if ( $cart === null ) {
			return 0;
		}
		return $cart->get_taxes_total();
	}

	/**
	 * Get the cart discount total
	 *
	 * @return float
	 */
	public function get_total_discounts() {
		$cart = WC()->cart;
		if ( $cart === null ) {
			return 0;
		}
		return $cart->get_discount_total();
	}

	/**
	 * Get number of items in the cart
	 *
	 * @return int
	 */
	public function get_cart_items_count() {
		$cart = WC()->cart;
		if ( $cart === null ) {
			return 0;
		}
		return $cart->get_cart_contents_count();
	}

	/**
	 * Retrieves the breadcrumb trail as an array of page titles.
	 *
	 * This function attempts to generate a hierarchical breadcrumb trail for the current page or post.
	 * - For the front page, it returns "Home".
	 * - For WooCommerce product, category, or tag pages, it uses the WooCommerce breadcrumb generator and prepends the shop page title if needed.
	 * - For regular pages, it builds the breadcrumb from the page's ancestors, ordered from top-level to current.
	 * - For all other cases, it returns the current page's title.
	 *
	 * @return array The breadcrumb trail as an array of titles.
	 */
	private function get_breadcrumb_titles() {
		if ( is_front_page() ) {
			return array( __( 'Home', 'woocommerce-analytics' ) );
		}

		if ( class_exists( '\WC_Breadcrumb' ) ) {
			$breadcrumb = new \WC_Breadcrumb();
			$crumbs     = $breadcrumb->generate();
			$titles     = wp_list_pluck( $crumbs, 0 );

			if ( is_product() || is_product_category() || is_product_tag() ) {
				$titles = $this->prepend_shop_page_title( $titles );
			}

			if ( ! empty( $titles ) ) {
				return $titles;
			}
		}

		// If it's a page, get the hierarchical title.
		if ( is_page() ) {
			$titles    = array();
			$page_id   = get_queried_object_id();
			$ancestors = get_post_ancestors( $page_id );
			// Reverse the ancestors to get the top-level first.
			$ancestors = array_reverse( $ancestors );

			foreach ( $ancestors as $ancestor ) {
				$titles[] = get_the_title( $ancestor );
			}
			$titles[] = get_the_title( $page_id );

			return $titles;
		}

		return array( get_the_title() );
	}

	/**
	 * Prepend the shop page title if it's not already present.
	 *
	 * @param array $titles The titles to prepend the shop page title to.
	 * @return array The titles with the shop page title prepended.
	 */
	private function prepend_shop_page_title( array $titles ) {
		$shop_page_id = wc_get_page_id( 'shop' );
		if ( ! $shop_page_id ) {
			return $titles;
		}

		$shop_page_title = get_the_title( $shop_page_id );

		if ( ! $shop_page_title || ( ! empty( $titles ) && $titles[0] === $shop_page_title ) ) {
			return $titles;
		}

		return array_merge( array( $shop_page_title ), $titles );
	}
}
