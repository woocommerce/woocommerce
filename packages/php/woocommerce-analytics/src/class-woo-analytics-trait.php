<?php
/**
 * Woo_Analytics_Trait
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

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

		$guest_checkout = ucfirst( get_option( 'woocommerce_enable_guest_checkout', 'No' ) );
		$create_account = ucfirst( get_option( 'woocommerce_enable_signup_and_login_from_checkout', 'No' ) );

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
		$cart_total              = wc_prices_include_tax() ? $cart->get_cart_contents_total() + $cart->get_cart_contents_tax() : $cart->get_cart_contents_total();
		$shared_data             = array(
			'products'               => $this->format_items_to_json( $cart->get_cart() ),
			'create_account'         => $create_account,
			'guest_checkout'         => $guest_checkout,
			'express_checkout'       => 'null', // TODO: not solved yet.
			'products_count'         => $cart->get_cart_contents_count(),
			'order_value'            => $cart_total,
			'shipping_options_count' => 'null', // TODO: not solved yet.
			'coupon_used'            => $coupon_used,
			'payment_options'        => $enabled_payment_options,
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
		$site_info          = array(
			'blog_id'                            => Jetpack_Connection::get_site_id(),
			'store_id'                           => defined( '\\WC_Install::STORE_ID_OPTION' ) ? get_option( \WC_Install::STORE_ID_OPTION ) : false,
			'ui'                                 => $this->get_user_id(),
			'url'                                => home_url(),
			'woo_version'                        => WC()->version,
			'store_admin'                        => in_array( array( 'administrator', 'shop_manager' ), wp_get_current_user()->roles, true ) ? 1 : 0,
			'device'                             => wp_is_mobile() ? 'mobile' : 'desktop',
			'template_used'                      => $this->cart_checkout_templates_in_use ? '1' : '0',
			'additional_blocks_on_cart_page'     => $this->additional_blocks_on_cart_page,
			'additional_blocks_on_checkout_page' => $this->additional_blocks_on_checkout_page,
			'store_currency'                     => get_woocommerce_currency(),
		);
		$cart_checkout_info = $this->get_cart_checkout_info();
		return array_merge( $site_info, $cart_checkout_info );
	}

	/**
	 * Render tracks event properties as string of JavaScript object props.
	 *
	 * @param  array $properties Array of key/value pairs.
	 * @return string String of the form "key1: value1, key2: value2, " (etc).
	 */
	private function render_properties_as_js( $properties ) {
		$js_args_string = '';
		foreach ( $properties as $key => $value ) {
			if ( is_array( $value ) ) {
				$js_args_string = $js_args_string . "'$key': " . wp_json_encode( $value ) . ',';
			} else {
				$js_args_string = $js_args_string . "'$key': '" . esc_js( $value ) . "', ";
			}
		}
		return $js_args_string;
	}

	/**
	 * Record an event with optional product and custom properties.
	 *
	 * @param string  $event_name The name of the event to record.
	 * @param array   $properties Optional array of (key => value) event properties.
	 * @param integer $product_id The id of the product relating to the event.
	 *
	 * @return string|void
	 */
	public function record_event( $event_name, $properties = array(), $product_id = null ) {
		$js = $this->process_event_properties( $event_name, $properties, $product_id );
		wc_enqueue_js( "_wca.push({$js});" );
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
	 * Compose event properties.
	 *
	 * @param string  $event_name The name of the event to record.
	 * @param array   $properties Optional array of (key => value) event properties.
	 * @param integer $product_id Optional id of the product relating to the event.
	 *
	 * @return string|void
	 */
	public function process_event_properties( $event_name, $properties = array(), $product_id = null ) {

		// Only set product details if we have a product id.
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product instanceof WC_Product ) {
				return;
			}
			$product_details = $this->get_product_details( $product );
		}

		/**
		 * Allow defining custom event properties in WooCommerce Analytics.
		 *
		 * @module woocommerce-analytics
		 *
		 * @since 12.5
		 *
		 * @param array $all_props Array of event props to be filtered.
		 */
		$all_props = apply_filters(
			'jetpack_woocommerce_analytics_event_props',
			array_merge(
				$this->get_common_properties(), // We put this here to allow override of common props.
				$properties
			)
		);

		$js = "{'_en': '" . esc_js( $event_name ) . "'";

		if ( isset( $product_details ) ) {
				$all_props = array_merge( $all_props, $product_details );
		}

		$js .= ',' . $this->render_properties_as_js( $all_props ) . '}';

		return $js;
	}

	/**
	 * Get the current user id
	 *
	 * @return int
	 */
	public function get_user_id() {
		if ( is_user_logged_in() ) {
			$blogid = Jetpack_Connection::get_site_id();
			$userid = get_current_user_id();
			return $blogid . ':' . $userid;
		}
		return 'null';
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

		$parsed_blocks = parse_blocks( $content );
		$other_blocks  = array_filter(
			$parsed_blocks,
			function ( $block ) use ( $cart_or_checkout ) {
				if ( ! isset( $block['blockName'] ) ) {
					return false;
				}

				if ( 'woocommerce/classic-shortcode' === $block['blockName'] ) {
					return false;
				}

				if ( 'core/shortcode' === $block['blockName'] ) {
					return false;
				}

				if ( 'checkout' === $cart_or_checkout && 'woocommerce/checkout' !== $block['blockName'] ) {
					return true;
				}

				if ( 'cart' === $cart_or_checkout && 'woocommerce/cart' !== $block['blockName'] ) {
					return true;
				}

				return false;
			}
		);

		$all_inner_blocks = array();

		// Loop over each "block group". In templates the blocks are grouped up.
		foreach ( $other_blocks as $block ) {

			// This check is necessary because sometimes this is null when using templates.
			if ( ! empty( $block['blockName'] ) ) {
				$all_inner_blocks[] = $block['blockName'];
			}

			if ( ! isset( $block['innerBlocks'] ) || ! is_array( $block['innerBlocks'] ) || 0 === count( $block['innerBlocks'] ) ) {
				continue;
			}

			foreach ( $block['innerBlocks'] as $inner_content ) {
				$all_inner_blocks = array_merge( $all_inner_blocks, $this->get_inner_blocks( $inner_content ) );
			}
		}
		set_transient( $additional_blocks_on_page_transient_name, $all_inner_blocks, DAY_IN_SECONDS );
		return $all_inner_blocks;
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
		$classic_shortcode_presence = str_contains( $content, '<!-- wp:woocommerce/classic-shortcode' );

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
		$classic_shortcode_presence = str_contains( $content, '<!-- wp:woocommerce/classic-shortcode' );

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
}
