<?php
/**
 * General store tracking actions.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use Automattic\Jetpack\Constants;
use WC_Order;
use WC_Product;

/**
 * Filters and Actions added to Store pages to perform analytics.
 */
class Universal {
	/**
	 * Trait to handle common analytics functions.
	 */
	use Woo_Analytics_Trait;

	/**
	 * Constructor.
	 */
	public function init_hooks() {
		$this->find_cart_checkout_content_sources();
		$this->additional_blocks_on_cart_page     = $this->get_additional_blocks_on_page( 'cart' );
		$this->additional_blocks_on_checkout_page = $this->get_additional_blocks_on_page( 'checkout' );

		// Capture search
		add_action( 'template_redirect', array( $this, 'capture_search_query' ), 11 );

		// Capture cart events.
		add_action( 'woocommerce_add_to_cart', array( $this, 'capture_add_to_cart' ), 10, 6 );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'capture_cart_quantity_update' ), 10, 4 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'capture_remove_from_cart' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'remove_from_cart_attributes' ), 10, 2 );

		// Checkout.
		// Send events after checkout template (shortcode).
		add_action( 'woocommerce_after_checkout_form', array( $this, 'checkout_process' ) );
		// Send events after checkout block.
		add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after', array( $this, 'checkout_process' ) );

		// order processed.
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'order_process' ), 10, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'order_process' ), 10, 1 );

		add_filter( 'woocommerce_checkout_posted_data', array( $this, 'save_checkout_post_data' ), 10, 1 );

		add_action( 'woocommerce_created_customer', array( $this, 'capture_created_customer' ), 10, 2 );

		add_action( 'woocommerce_created_customer', array( $this, 'capture_post_checkout_created_customer' ), 10, 2 );

		// single product page view.
		add_action( 'woocommerce_after_single_product', array( $this, 'capture_product_view' ) );

		// order confirmed page view
		add_action( 'woocommerce_thankyou', array( $this, 'capture_order_confirmation_view' ), 10, 1 );

		// checkout page view
		add_action( 'wp_footer', array( $this, 'capture_checkout_view' ), 11 );

		// cart page view
		add_action( 'wp_footer', array( $this, 'capture_cart_view' ), 11 );

		// Enqueue events to track.
		add_action( 'wp_footer', array( $this, 'inject_analytics_data' ), 999 );
	}

	/**
	 * Inject analytics data into the window object
	 */
	public function inject_analytics_data() {
		$is_clickhouse_enabled     = Features::is_clickhouse_enabled();
		$is_proxy_tracking_enabled = Features::is_proxy_tracking_enabled();
		// When proxy tracking is enabled, we don't need to send the common properties to the client.
		$common_properties = $is_proxy_tracking_enabled ? array() : $this->get_common_properties();
		?>
		<script type="text/javascript">
			(function() {
				window.wcAnalytics = window.wcAnalytics || {};
				const wcAnalytics = window.wcAnalytics;

				// Set the assets URL for webpack to find the split assets.
				wcAnalytics.assets_url = '<?php echo esc_url( plugins_url( '../build/', __DIR__ . '/class-woocommerce-analytics.php' ) ); ?>';

				// Set the REST API tracking endpoint URL.
				wcAnalytics.trackEndpoint = '<?php echo esc_url( rest_url( 'woocommerce-analytics/v1/track' ) ); ?>';

				// Set common properties for all events.
				wcAnalytics.commonProps = <?php echo wp_json_encode( $common_properties, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ); ?>;

				// Set the event queue.
				wcAnalytics.eventQueue = <?php echo wp_json_encode( WC_Analytics_Tracking::get_event_queue(), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ); ?>;

				// Features.
				wcAnalytics.features = {
					ch: <?php echo $is_clickhouse_enabled ? 'true' : 'false'; ?>,
					sessionTracking: <?php echo $is_clickhouse_enabled ? 'true' : 'false'; ?>,
					proxy: <?php echo $is_proxy_tracking_enabled ? 'true' : 'false'; ?>,
				};

				wcAnalytics.breadcrumbs = <?php echo wp_json_encode( $this->get_breadcrumb_titles(), JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ); ?>;

				// Page context flags.
				wcAnalytics.pages = {
					isAccountPage: <?php echo is_account_page() ? 'true' : 'false'; ?>,
					isCart: <?php echo is_cart() ? 'true' : 'false'; ?>,
				};
			})();
		</script>
		<?php
	}

	/**
	 * Capture remove from cart events in mini-cart and cart blocks
	 *
	 * @param string   $cart_item_key The cart item removed.
	 * @param \WC_Cart $cart The cart.
	 *
	 * @return void
	 */
	public function capture_remove_from_cart( $cart_item_key, $cart ) {
		$item = $cart->removed_cart_contents[ $cart_item_key ] ?? null;

		WC_Analytics_Tracking::record_event(
			'remove_from_cart',
			$this->get_cart_checkout_event_properties(
				array(
					'pi' => (int) $item['product_id'],
					'pq' => (int) $item['quantity'],
				)
			)
		);
	}

	/**
	 * Capture remove/add from cart events using the Cart Controller
	 *
	 * @param string   $cart_item_key The cart item updated.
	 * @param int      $quantity Contains the new quantity of the item.
	 * @param int      $old_quantity Contains the old quantity of the item.
	 * @param \WC_Cart $cart The cart.
	 *
	 * @return void
	 */
	public function capture_cart_quantity_update( $cart_item_key, $quantity, $old_quantity, $cart ) {
		$product_id = $cart->cart_contents[ $cart_item_key ]['product_id'];
		if ( $quantity > $old_quantity ) {
			WC_Analytics_Tracking::record_event(
				'add_to_cart',
				$this->get_cart_checkout_event_properties(
					array(
						'pi' => $product_id,
						'pq' => $quantity,
					)
				)
			);
			$this->lock_add_to_cart_events = true;
			return;
		}

		if ( $quantity < $old_quantity ) {
			WC_Analytics_Tracking::record_event(
				'remove_from_cart',
				$this->get_cart_checkout_event_properties(
					array(
						'pi' => $product_id,
						'pq' => $quantity,
					)
				)
			);
			return;
		}
	}

	/**
	 * Adds the product ID to the remove product link (for use by remove_from_cart above) if not present
	 *
	 * @param string $url Full HTML a tag of the link to remove an item from the cart.
	 * @param string $key Unique Key ID for a cart item.
	 *
	 * @return string
	 */
	public function remove_from_cart_attributes( $url, $key ) {
		if ( str_contains( $url, 'data-product_id' ) ) {
			return $url;
		}

		$item    = WC()->cart->get_cart_item( $key );
		$product = $item['data'];

		$new_attributes = sprintf(
			'" data-product_id="%s">',
			esc_attr( $product->get_id() )
		);

		$url = str_replace( '">', $new_attributes, $url );
		return $url;
	}

	/**
	 * Get the selected shipping option for a cart item. If the name cannot be found in the options table, the method's
	 * ID will be used.
	 *
	 * @param string $cart_item_key the cart item key.
	 *
	 * @return mixed|bool
	 */
	public function get_shipping_option_for_item( $cart_item_key ) {
		$packages         = wc()->shipping()->get_packages();
		$selected_options = wc()->session->get( 'chosen_shipping_methods' );

		if ( ! is_array( $packages ) || ! is_array( $selected_options ) ) {
			return false;
		}

		foreach ( $packages as $package_id => $package ) {

			if ( ! isset( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
				return false;
			}

			foreach ( $package['contents'] as $package_item ) {
				if ( ! isset( $package_item['key'] ) || $package_item['key'] !== $cart_item_key || ! isset( $selected_options[ $package_id ] ) ) {
					continue;
				}
				$selected_rate_id = $selected_options[ $package_id ];
				$method_key_id    = sanitize_text_field( str_replace( ':', '_', $selected_rate_id ) );
				$option_name      = 'woocommerce_' . $method_key_id . '_settings';
				$option_value     = get_option( $option_name );
				$title            = '';
				if ( is_array( $option_value ) && isset( $option_value['title'] ) ) {
					$title = $option_value['title'];
				}
				if ( ! $title ) {
					return $selected_rate_id;
				}
				return $title;
			}
		}

		return false;
	}

	/**
	 * On the Checkout page, trigger an event for each product in the cart
	 */
	public function checkout_process() {
		global $post;
		$checkout_page_id    = wc_get_page_id( 'checkout' );
		$cart                = WC()->cart->get_cart();
		$is_in_checkout_page = isset( $post->ID ) && $checkout_page_id === $post->ID ? 'Yes' : 'No';
		$session             = WC()->session;
		if ( is_object( $session ) ) {
			$session->set( 'checkout_page_used', 'Yes' === $is_in_checkout_page );
			$session->save_data();
		}

		foreach ( $cart as $cart_item_key => $cart_item ) {
			/**
			 * This filter is already documented in woocommerce/templates/cart/cart.php
			 */
			$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			if ( ! $product || ! $product instanceof WC_Product ) {
				continue;
			}

			$data = $this->get_cart_checkout_shared_data();

			$data['from_checkout'] = $is_in_checkout_page;

			if ( ! empty( $data['products'] ) ) {
				unset( $data['products'] );
			}

			if ( ! empty( $data['shipping_options_count'] ) ) {
				unset( $data['shipping_options_count'] );
			}

			$data['pq'] = $cart_item['quantity'];
			$this->enqueue_event( 'product_checkout', $this->get_cart_checkout_event_properties( $data ), $product->get_id() );
		}
	}

	/**
	 * After the order processed, fire an event for each item in the order
	 *
	 * @param string|WC_Order $order_id_or_order Order Id or Order object.
	 */
	public function order_process( $order_id_or_order ) {
		if ( is_string( $order_id_or_order ) ) {
			$order = wc_get_order( $order_id_or_order );
		} else {
			$order = $order_id_or_order;
		}

		if (
			! $order
			|| ! $order instanceof WC_Order
		) {
			return;
		}

		$payment_option = $order->get_payment_method();

		if ( is_object( WC()->session ) ) {
			$create_account     = true === WC()->session->get( 'wc_checkout_createaccount_used' ) ? 'Yes' : 'No';
			$checkout_page_used = true === WC()->session->get( 'checkout_page_used' ) ? 'Yes' : 'No';

		} else {
			$create_account     = 'No';
			$checkout_page_used = 'No';
		}

		$delayed_account_creation = ucfirst( get_option( 'woocommerce_enable_delayed_account_creation', 'Yes' ) );

		$guest_checkout = $order->get_user() ? 'No' : 'Yes';

		$express_checkout = 'null';
		// When the payment option is woocommerce_payment
		// See if Google Pay or Apple Pay was used.
		if ( 'woocommerce_payments' === $payment_option ) {
			$payment_option_title = $order->get_payment_method_title();
			if ( 'Google Pay (WooCommerce Payments)' === $payment_option_title ) {
				$express_checkout = array( 'google_pay' );
			} elseif ( 'Apple Pay (WooCommerce Payments)' === $payment_option_title ) {
				$express_checkout = array( 'apple_pay' );
			}
		}

		$checkout_page_contains_checkout_block     = '0';
		$checkout_page_contains_checkout_shortcode = '0';

		$order_source = $order->get_created_via();
		if ( 'store-api' === $order_source ) {
			$checkout_page_contains_checkout_block = '1';
		} elseif ( 'checkout' === $order_source ) {
			$checkout_page_contains_checkout_shortcode = '1';
		}

		// loop through products in the order and queue a purchase event.
		foreach ( $order->get_items() as $order_item ) {
			// @phan-suppress-next-line PhanUndeclaredMethod -- Checked before being called. See also https://github.com/phan/phan/issues/1204.
			$product_id = is_callable( array( $order_item, 'get_product_id' ) ) ? $order_item->get_product_id() : -1;

			$order_items       = $order->get_items();
			$order_items_count = 0;
			if ( is_array( $order_items ) ) {
				$order_items_count = count( $order_items );
			}
			$order_coupons       = $order->get_coupons();
			$order_coupons_count = 0;
			if ( is_array( $order_coupons ) ) {
				$order_coupons_count = count( $order_coupons );
			}

			WC_Analytics_Tracking::record_event(
				'product_purchase',
				$this->get_cart_checkout_event_properties(
					array(
						'oi'                       => $order->get_order_number(),
						'pi'                       => $product_id,
						'pq'                       => $order_item->get_quantity(),
						'payment_option'           => $payment_option,
						'create_account'           => $create_account,
						'guest_checkout'           => $guest_checkout,
						'delayed_account_creation' => $delayed_account_creation,
						'express_checkout'         => $express_checkout,
						'coupon_used'              => $order_coupons_count,
						'products_count'           => $order_items_count,
						'order_value'              => $order->get_subtotal(),
						'order_total'              => $order->get_total(),
						'total_discount'           => $order->get_discount_total(),
						'total_taxes'              => $order->get_total_tax(),
						'total_shipping'           => $order->get_shipping_total(),
						'from_checkout'            => $checkout_page_used,
						'checkout_page_contains_checkout_block' => $checkout_page_contains_checkout_block,
						'checkout_page_contains_checkout_shortcode' => $checkout_page_contains_checkout_shortcode,
					)
				)
			);
		}
	}
	/**
	 * Gets the inner blocks of a block.
	 *
	 * @param array $inner_blocks The inner blocks.
	 *
	 * @return array
	 */
	private function get_inner_blocks( $inner_blocks ) {
		$block_names = array();
		if ( ! empty( $inner_blocks['blockName'] ) ) {
			$block_names[] = $inner_blocks['blockName'];
		}
		if ( isset( $inner_blocks['innerBlocks'] ) && is_array( $inner_blocks['innerBlocks'] ) ) {
			$block_names = array_merge( $block_names, $this->get_inner_blocks( $inner_blocks['innerBlocks'] ) );
		}
		return $block_names;
	}

	/**
	 * Track adding items to the cart.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $product_id Product added to cart.
	 * @param int    $quantity Quantity added to cart.
	 * @param int    $variation_id Product variation.
	 * @param array  $variation Variation attributes..
	 * @param array  $cart_item_data Other cart data.
	 */
	public function capture_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( $this->lock_add_to_cart_events ) {
			return;
		}
		WC_Analytics_Tracking::record_event(
			'add_to_cart',
			$this->get_cart_checkout_event_properties(
				array(
					'pi' => $product_id,
					'pq' => $quantity,
				)
			)
		);
	}

	/**
	 * Get the event properties for the cart and checkout events.
	 *
	 * @param array $event_properties Event properties.
	 */
	public function get_cart_checkout_event_properties( $event_properties = array() ) {
		if ( isset( $event_properties['pq'] ) ) {
			$event_properties['pq'] = 0 === $event_properties['pq'] ? 1 : $event_properties['pq'];
			$event_properties['pq'] = (string) $event_properties['pq'];
		}
		$product         = isset( $event_properties['pi'] ) ? wc_get_product( $event_properties['pi'] ) : null;
		$product_details = $product instanceof WC_Product ? $this->get_product_details( $product ) : array();

		$checkout_cart_details = array(
			'template_used'                      => $this->cart_checkout_templates_in_use ? '1' : '0',
			'additional_blocks_on_cart_page'     => $this->additional_blocks_on_cart_page,
			'additional_blocks_on_checkout_page' => $this->additional_blocks_on_checkout_page,
			'order_value'                        => $this->get_cart_subtotal(),
			'order_total'                        => $this->get_cart_total(),
			'total_tax'                          => $this->get_cart_taxes(),
			'total_discount'                     => $this->get_total_discounts(),
			'total_shipping'                     => $this->get_cart_shipping_total(),
			'products_count'                     => $this->get_cart_items_count(),
		);
		$cart_checkout_info    = $this->get_cart_checkout_info();

		$event_properties = array_merge( $product_details, $checkout_cart_details, $cart_checkout_info, $event_properties ); // event properties should be last to allow for overrides

		return $event_properties;
	}

	/**
	 * Save create account post data to be used in $this->order_process.
	 *
	 * @param array|null $data Post data from the checkout page.
	 *
	 * @return array|null
	 */
	public function save_checkout_post_data( ?array $data ) {
		if ( is_object( WC()->session ) && ! empty( $data['createaccount'] ) ) {
			WC()->session->set( 'wc_checkout_createaccount_used', true );
			WC()->session->save_data();
		}
		return $data;
	}

	/**
	 * Capture the create account event. Similar to save_checkout_post_data but works with Store API.
	 *
	 * @param int   $customer_id Customer ID.
	 * @param array $new_customer_data New customer data.
	 */
	public function capture_created_customer( $customer_id, $new_customer_data ) {
		$session = WC()->session;
		if (
			is_object( $session )
			&& is_array( $new_customer_data )
			&& ! empty( $new_customer_data['source'] )
		) {
			if ( str_contains( $new_customer_data['source'], 'store-api' ) ) {
				$session->set( 'wc_checkout_createaccount_used', true );
				$session->save_data();
			}
		}
	}

	/**
	 * Capture the post checkout create account event.
	 *
	 * @param int   $customer_id Customer ID.
	 * @param array $new_customer_data New customer data.
	 */
	public function capture_post_checkout_created_customer( $customer_id, $new_customer_data ) {
		if (
			is_array( $new_customer_data )
			&& ! empty( $new_customer_data['source'] )
			&& str_contains( $new_customer_data['source'], 'delayed-account-creation' )
		) {

			$checkout_page_used                        = true === WC()->session->get( 'checkout_page_used' ) ? 'Yes' : 'No';
			$checkout_page_contains_checkout_block     = '1';
			$checkout_page_contains_checkout_shortcode = '0';

			$this->enqueue_event(
				'post_account_creation',
				$this->get_cart_checkout_event_properties(
					array(
						'from_checkout' => $checkout_page_used,
						'checkout_page_contains_checkout_block' => $checkout_page_contains_checkout_block,
						'checkout_page_contains_checkout_shortcode' => $checkout_page_contains_checkout_shortcode,
					)
				)
			);
		}
	}

	/**
	 * Capture a search event.
	 */
	public function capture_search_query() {
		if ( is_search() ) {
			global $wp_query;
			$this->enqueue_event(
				'search',
				array(
					'search_query' => $wp_query->get( 's' ),
					'qty'          => $wp_query->found_posts,
				)
			);
		}
	}

	/**
	 * Track the cart page view
	 */
	public function capture_cart_view() {
		global $post;
		$cart_page_id = wc_get_page_id( 'cart' );

		$is_cart = $cart_page_id && is_page( $cart_page_id )
			|| wc_post_content_has_shortcode( 'woocommerce_cart' )
			|| has_block( 'woocommerce/cart', $post )
			|| apply_filters( 'woocommerce_is_cart', false )
			|| Constants::is_defined( 'WOOCOMMERCE_CART' )
			|| is_cart();

		if ( ! $is_cart ) {
			return;
		}

		$this->enqueue_event(
			'cart_view',
			$this->get_cart_checkout_event_properties(
				$this->get_cart_checkout_shared_data()
			)
		);
	}

	/**
	 * Track a product page view
	 */
	public function capture_product_view() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$this->enqueue_event(
			'product_view',
			array(),
			$product->get_id()
		);
	}

	/**
	 * Track the order confirmation page view
	 */
	public function capture_order_confirmation_view() {
		$order_id = absint( get_query_var( 'order-received' ) );
		if ( ! $order_id ) {
			return;
		}

		if ( ! is_order_received_page() ) {
			return;
		}

		$order = wc_get_order( $order_id );

		$order_source                              = $order->get_created_via();
		$checkout_page_contains_checkout_block     = '0';
		$checkout_page_contains_checkout_shortcode = '0';

		if ( 'store-api' === $order_source ) {
			$checkout_page_contains_checkout_block = '1';
		} elseif ( 'checkout' === $order_source ) {
			$checkout_page_contains_checkout_shortcode = '1';
		}

		$coupons     = $order->get_coupons();
		$coupon_used = 0;
		if ( is_countable( $coupons ) ) {
			$coupon_used = count( $coupons ) ? 1 : 0;
		}

		if ( is_object( WC()->session ) ) {
			$create_account     = true === WC()->session->get( 'wc_checkout_createaccount_used' ) ? 'Yes' : 'No';
			$checkout_page_used = true === WC()->session->get( 'checkout_page_used' ) ? 'Yes' : 'No';
		} else {
			$create_account     = 'No';
			$checkout_page_used = 'No';
		}

		$delayed_account_creation = ucfirst( get_option( 'woocommerce_enable_delayed_account_creation', 'Yes' ) );
		$this->enqueue_event(
			'order_confirmation_view',
			$this->get_cart_checkout_event_properties(
				array(
					'coupon_used'              => $coupon_used,
					'create_account'           => $create_account,
					'express_checkout'         => 'null', // TODO: not solved yet.
					'guest_checkout'           => $order->get_customer_id() ? 'No' : 'Yes',
					'delayed_account_creation' => $delayed_account_creation,
					'oi'                       => $order->get_id(),
					'order_value'              => $order->get_subtotal(),
					'order_total'              => $order->get_total(),
					'products_count'           => $order->get_item_count(),
					'total_discount'           => $order->get_discount_total(),
					'total_shipping'           => $order->get_shipping_total(),
					'total_tax'                => $order->get_total_tax(),
					'payment_option'           => $order->get_payment_method(),
					'products'                 => $this->format_items_to_json( $order->get_items() ),
					'order_note'               => $order->get_customer_note(),
					'shipping_option'          => $order->get_shipping_method(),
					'from_checkout'            => $checkout_page_used,
					'checkout_page_contains_checkout_block' => $checkout_page_contains_checkout_block,
					'checkout_page_contains_checkout_shortcode' => $checkout_page_contains_checkout_shortcode,
				)
			)
		);
	}

	/**
	 * Track the checkout page view
	 */
	public function capture_checkout_view() {
		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$checkout_page_id = wc_get_page_id( 'checkout' );

		$is_checkout = $checkout_page_id && is_page( $checkout_page_id )
			|| wc_post_content_has_shortcode( 'woocommerce_checkout' )
			|| has_block( 'woocommerce/checkout', $post )
			|| apply_filters( 'woocommerce_is_checkout', false )
			|| Constants::is_defined( 'WOOCOMMERCE_CHECKOUT' );

		if ( ! $is_checkout ) {
			return;
		}

		$is_in_checkout_page                       = isset( $post->ID ) && $checkout_page_id === $post->ID ? 'Yes' : 'No';
		$checkout_page_contains_checkout_block     = '0';
		$checkout_page_contains_checkout_shortcode = '1';

		$session = WC()->session;
		if ( is_object( $session ) ) {
			$session->set( 'checkout_page_used', true );
			$session->save_data();
			$draft_order_id = $session->get( 'store_api_draft_order', 0 );
			if ( $draft_order_id ) {
				$checkout_page_contains_checkout_block     = '1';
				$checkout_page_contains_checkout_shortcode = '0';
			}
		}

		// Order received page is also a checkout page, so we need to bail out if we are on that page.
		if ( is_order_received_page() ) {
			return;
		}

		$this->enqueue_event(
			'checkout_view',
			$this->get_cart_checkout_event_properties(
				array_merge(
					$this->get_cart_checkout_shared_data(),
					array(
						'from_checkout' => $is_in_checkout_page,
						'checkout_page_contains_checkout_block' => $checkout_page_contains_checkout_block,
						'checkout_page_contains_checkout_shortcode' => $checkout_page_contains_checkout_shortcode,
					)
				)
			)
		);
	}
}
