<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Checkout class.
 *
 * @internal
 */
class Checkout extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout';

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return array|string;
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name ),
			'dependencies' => [ 'wc-blocks' ],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string
	 */
	protected function get_block_type_script( $key = null ) {
		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block-frontend',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name . '-frontend' ),
			'dependencies' => [],
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 */
	protected function enqueue_assets( array $attributes ) {
		do_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before' );
		parent::enqueue_assets( $attributes );
		do_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after' );
	}

	/**
	 * Append frontend scripts when rendering the block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content ) {
		if ( $this->is_checkout_endpoint() ) {
			// Note: Currently the block only takes care of the main checkout form -- if an endpoint is set, refer to the
			// legacy shortcode instead and do not render block.
			return '[woocommerce_checkout]';
		}

		// Deregister core checkout scripts and styles.
		wp_dequeue_script( 'wc-checkout' );
		wp_dequeue_script( 'wc-password-strength-meter' );
		wp_dequeue_script( 'selectWoo' );
		wp_dequeue_style( 'select2' );

		// If the content is empty, we may have transformed from an older checkout block. Insert the default list of blocks.
		$regex_for_empty_block = '/<div class="[a-zA-Z0-9_\- ]*wp-block-woocommerce-checkout[a-zA-Z0-9_\- ]*"><\/div>/mi';

		$is_empty = preg_match( $regex_for_empty_block, $content );

		if ( $is_empty ) {
			$inner_blocks_html = '<div data-block-name="woocommerce/checkout-fields-block" class="wp-block-woocommerce-checkout-fields-block"></div><div data-block-name="woocommerce/checkout-totals-block" class="wp-block-woocommerce-checkout-totals-block"></div>';

			$content = str_replace( '</div>', $inner_blocks_html . '</div>', $content );
		}

		return $this->inject_html_data_attributes( $content, $attributes );
	}

	/**
	 * Check if we're viewing a checkout page endpoint, rather than the main checkout page itself.
	 *
	 * @return boolean
	 */
	protected function is_checkout_endpoint() {
		return is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );

		$this->asset_data_registry->add(
			'allowedCountries',
			function() {
				return $this->deep_sort_with_accents( WC()->countries->get_allowed_countries() );
			},
			true
		);
		$this->asset_data_registry->add(
			'allowedStates',
			function() {
				return $this->deep_sort_with_accents( WC()->countries->get_allowed_country_states() );
			},
			true
		);
		$this->asset_data_registry->add(
			'shippingCountries',
			function() {
				return $this->deep_sort_with_accents( WC()->countries->get_shipping_countries() );
			},
			true
		);
		$this->asset_data_registry->add(
			'shippingStates',
			function() {
				return $this->deep_sort_with_accents( WC()->countries->get_shipping_country_states() );
			},
			true
		);
		$this->asset_data_registry->add(
			'countryLocale',
			function() {
				// Merge country and state data to work around https://github.com/woocommerce/woocommerce/issues/28944.
				$country_locale = wc()->countries->get_country_locale();
				$states         = wc()->countries->get_states();

				foreach ( $states as $country => $states ) {
					if ( empty( $states ) ) {
						$country_locale[ $country ]['state']['required'] = false;
						$country_locale[ $country ]['state']['hidden']   = true;
					}
				}
				return $country_locale;
			},
			true
		);
		$this->asset_data_registry->add( 'baseLocation', wc_get_base_location(), true );
		$this->asset_data_registry->add(
			'checkoutAllowsGuest',
			false === filter_var(
				WC()->checkout()->is_registration_required(),
				FILTER_VALIDATE_BOOLEAN
			),
			true
		);
		$this->asset_data_registry->add(
			'checkoutAllowsSignup',
			filter_var(
				WC()->checkout()->is_registration_enabled(),
				FILTER_VALIDATE_BOOLEAN
			),
			true
		);
		$this->asset_data_registry->add( 'checkoutShowLoginReminder', filter_var( get_option( 'woocommerce_enable_checkout_login_reminder' ), FILTER_VALIDATE_BOOLEAN ), true );
		$this->asset_data_registry->add( 'displayCartPricesIncludingTax', 'incl' === get_option( 'woocommerce_tax_display_cart' ), true );
		$this->asset_data_registry->add( 'displayItemizedTaxes', 'itemized' === get_option( 'woocommerce_tax_total_display' ), true );
		$this->asset_data_registry->add( 'taxesEnabled', wc_tax_enabled(), true );
		$this->asset_data_registry->add( 'couponsEnabled', wc_coupons_enabled(), true );
		$this->asset_data_registry->add( 'shippingEnabled', wc_shipping_enabled(), true );
		$this->asset_data_registry->add( 'hasDarkEditorStyleSupport', current_theme_supports( 'dark-editor-style' ), true );
		$this->asset_data_registry->register_page_id( isset( $attributes['cartPageId'] ) ? $attributes['cartPageId'] : 0 );

		$is_block_editor = $this->is_block_editor();

		// Hydrate the following data depending on admin or frontend context.
		if ( $is_block_editor && ! $this->asset_data_registry->exists( 'shippingMethodsExist' ) ) {
			$methods_exist = wc_get_shipping_method_count( false, true ) > 0;
			$this->asset_data_registry->add( 'shippingMethodsExist', $methods_exist );
		}

		if ( $is_block_editor && ! $this->asset_data_registry->exists( 'globalShippingMethods' ) ) {
			$shipping_methods           = WC()->shipping()->get_shipping_methods();
			$formatted_shipping_methods = array_reduce(
				$shipping_methods,
				function( $acc, $method ) {
					if ( $method->supports( 'settings' ) ) {
						$acc[] = [
							'id'          => $method->id,
							'title'       => $method->method_title,
							'description' => $method->method_description,
						];
					}
					return $acc;
				},
				[]
			);
			$this->asset_data_registry->add( 'globalShippingMethods', $formatted_shipping_methods );
		}

		if ( $is_block_editor && ! $this->asset_data_registry->exists( 'activeShippingZones' ) && class_exists( '\WC_Shipping_Zones' ) ) {
			$shipping_zones             = \WC_Shipping_Zones::get_zones();
			$formatted_shipping_zones   = array_reduce(
				$shipping_zones,
				function( $acc, $zone ) {
					$acc[] = [
						'id'          => $zone['id'],
						'title'       => $zone['zone_name'],
						'description' => $zone['formatted_zone_location'],
					];
					return $acc;
				},
				[]
			);
			$formatted_shipping_zones[] = [
				'id'          => 0,
				'title'       => __( 'International', 'woocommerce' ),
				'description' => __( 'Locations outside all other zones', 'woocommerce' ),
			];
			$this->asset_data_registry->add( 'activeShippingZones', $formatted_shipping_zones );
		}

		if ( $is_block_editor && ! $this->asset_data_registry->exists( 'globalPaymentMethods' ) ) {
			$payment_methods           = WC()->payment_gateways->payment_gateways();
			$formatted_payment_methods = array_reduce(
				$payment_methods,
				function( $acc, $method ) {
					if ( 'yes' === $method->enabled ) {
						$acc[] = [
							'id'          => $method->id,
							'title'       => $method->method_title,
							'description' => $method->method_description,
						];
					}
					return $acc;
				},
				[]
			);
			$this->asset_data_registry->add( 'globalPaymentMethods', $formatted_payment_methods );
		}

		if ( ! is_admin() && ! WC()->is_rest_api_request() ) {
			$this->hydrate_from_api();
			$this->hydrate_customer_payment_methods();
		}

		do_action( 'woocommerce_blocks_checkout_enqueue_data' );
	}

	/**
	 * Are we currently on the admin block editor screen?
	 */
	protected function is_block_editor() {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}
		$screen = get_current_screen();

		return $screen && $screen->is_block_editor();
	}

	/**
	 * Removes accents from an array of values, sorts by the values, then returns the original array values sorted.
	 *
	 * @param array $array Array of values to sort.
	 * @return array Sorted array.
	 */
	protected function deep_sort_with_accents( $array ) {
		if ( ! is_array( $array ) || empty( $array ) ) {
			return $array;
		}

		if ( is_array( reset( $array ) ) ) {
			return array_map( [ $this, 'deep_sort_with_accents' ], $array );
		}

		$array_without_accents = array_map( 'remove_accents', array_map( 'wc_strtolower', array_map( 'html_entity_decode', $array ) ) );
		asort( $array_without_accents );
		return array_replace( $array_without_accents, $array );
	}

	/**
	 * Get customer payment methods for use in checkout.
	 */
	protected function hydrate_customer_payment_methods() {
		if ( ! is_user_logged_in() || $this->asset_data_registry->exists( 'customerPaymentMethods' ) ) {
			return;
		}
		add_filter( 'woocommerce_payment_methods_list_item', [ $this, 'include_token_id_with_payment_methods' ], 10, 2 );
		$this->asset_data_registry->add(
			'customerPaymentMethods',
			wc_get_customer_saved_methods_list( get_current_user_id() )
		);
		remove_filter( 'woocommerce_payment_methods_list_item', [ $this, 'include_token_id_with_payment_methods' ], 10, 2 );
	}

	/**
	 * Hydrate the checkout block with data from the API.
	 */
	protected function hydrate_from_api() {
		// Print existing notices now, otherwise they are caught by the Cart
		// Controller and converted to exceptions.
		wc_print_notices();

		add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
		$this->asset_data_registry->hydrate_api_request( '/wc/store/cart' );
		$this->asset_data_registry->hydrate_api_request( '/wc/store/checkout' );
		remove_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
	}

	/**
	 * Callback for woocommerce_payment_methods_list_item filter to add token id
	 * to the generated list.
	 *
	 * @param array     $list_item The current list item for the saved payment method.
	 * @param \WC_Token $token     The token for the current list item.
	 *
	 * @return array The list item with the token id added.
	 */
	public static function include_token_id_with_payment_methods( $list_item, $token ) {
		$list_item['tokenId'] = $token->get_id();
		$brand                = ! empty( $list_item['method']['brand'] ) ?
			strtolower( $list_item['method']['brand'] ) :
			'';
		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- need to match on translated value from core.
		if ( ! empty( $brand ) && esc_html__( 'Credit card', 'woocommerce' ) !== $brand ) {
			$list_item['method']['brand'] = wc_get_credit_card_type_label( $brand );
		}
		return $list_item;
	}
}
