<?php
declare( strict_types = 1);
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\StoreApi\Utilities\PaymentUtils;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\Validation;

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
	 * Chunks build folder.
	 *
	 * @var string
	 */
	protected $chunks_folder = 'checkout-blocks';

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		parent::initialize();
		add_action( 'rest_api_init', array( $this, 'register_settings' ) );
		add_action( 'wp_loaded', array( $this, 'register_patterns' ) );
		// This prevents the page redirecting when the cart is empty. This is so the editor still loads the page preview.
		add_filter(
			'woocommerce_checkout_redirect_empty_cart',
			function ( $redirect_empty_cart ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return isset( $_GET['_wp-find-template'] ) ? false : $redirect_empty_cart;
			}
		);

		add_action( 'save_post', array( $this, 'update_local_pickup_title' ), 10, 2 );
	}

	/**
	 * Dequeues the scripts added by WC Core to the Checkout page.
	 *
	 * @return void
	 */
	public function dequeue_woocommerce_core_scripts() {
		wp_dequeue_script( 'wc-checkout' );
		wp_dequeue_script( 'wc-password-strength-meter' );
		wp_dequeue_script( 'selectWoo' );
		wp_dequeue_style( 'select2' );
	}

	/**
	 * Exposes settings exposed by the checkout block.
	 */
	public function register_settings() {
		register_setting(
			'options',
			'woocommerce_checkout_phone_field',
			array(
				'type'         => 'object',
				'description'  => __( 'Controls the display of the phone field in checkout.', 'woocommerce' ),
				'label'        => __( 'Phone number', 'woocommerce' ),
				'show_in_rest' => array(
					'name'   => 'woocommerce_checkout_phone_field',
					'schema' => array(
						'type' => 'string',
						'enum' => array( 'optional', 'required', 'hidden' ),
					),
				),
				'default'      => CartCheckoutUtils::get_phone_field_visibility(),
			)
		);
		register_setting(
			'options',
			'woocommerce_checkout_company_field',
			array(
				'type'         => 'object',
				'description'  => __( 'Controls the display of the company field in checkout.', 'woocommerce' ),
				'label'        => __( 'Company', 'woocommerce' ),
				'show_in_rest' => array(
					'name'   => 'woocommerce_checkout_company_field',
					'schema' => array(
						'type' => 'string',
						'enum' => array( 'optional', 'required', 'hidden' ),
					),
				),
				'default'      => CartCheckoutUtils::get_company_field_visibility(),
			)
		);
		register_setting(
			'options',
			'woocommerce_checkout_address_2_field',
			array(
				'type'         => 'object',
				'description'  => __( 'Controls the display of the apartment (address_2) field in checkout.', 'woocommerce' ),
				'label'        => __( 'Address Line 2', 'woocommerce' ),
				'show_in_rest' => array(
					'name'   => 'woocommerce_checkout_address_2_field',
					'schema' => array(
						'type' => 'string',
						'enum' => array( 'optional', 'required', 'hidden' ),
					),
				),
				'default'      => CartCheckoutUtils::get_address_2_field_visibility(),
			)
		);
	}

	/**
	 * Register block pattern for Empty Cart Message to make it translatable.
	 */
	public function register_patterns() {
		register_block_pattern(
			'woocommerce/checkout-heading',
			array(
				'title'    => '',
				'inserter' => false,
				'content'  => '<!-- wp:heading {"align":"wide", "level":1} --><h1 class="wp-block-heading alignwide">' . esc_html__( 'Checkout', 'woocommerce' ) . '</h1><!-- /wp:heading -->',
			)
		);
	}

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
		$dependencies = [];

		// Load password strength meter script asynchronously if needed.
		if ( ! is_user_logged_in() && 'no' === get_option( 'woocommerce_registration_generate_password' ) ) {
			$dependencies[] = 'zxcvbn-async';
		}

		$checkout_fields = Package::container()->get( CheckoutFields::class );
		// Load schema parser asynchronously if we need it.
		if ( Validation::has_field_schema( $checkout_fields->get_additional_fields() ) ) {
			$dependencies[] = 'wc-schema-parser';
		}

		$script = [
			'handle'       => 'wc-' . $this->block_name . '-block-frontend',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name . '-frontend' ),
			'dependencies' => $dependencies,
		];
		return $key ? $script[ $key ] : $script;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return string[]
	 */
	protected function get_block_type_style() {
		return array_merge( parent::get_block_type_style(), [ 'wc-blocks-packages-style' ] );
	}

	/**
	 * Enqueue frontend assets for this block, just in time for rendering.
	 *
	 * @param array    $attributes  Any attributes that currently are available from the block.
	 * @param string   $content    The block content.
	 * @param WP_Block $block    The block object.
	 */
	protected function enqueue_assets( array $attributes, $content, $block ) {
		/**
		 * Fires before checkout block scripts are enqueued.
		 *
		 * @since 4.6.0
		 */
		do_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before' );
		parent::enqueue_assets( $attributes, $content, $block );
		/**
		 * Fires after checkout block scripts are enqueued.
		 *
		 * @since 4.6.0
		 */
		do_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after' );
	}

	/**
	 * Append frontend scripts when rendering the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {

		if ( $this->is_checkout_endpoint() ) {
			// Note: Currently the block only takes care of the main checkout form -- if an endpoint is set, refer to the
			// legacy shortcode instead and do not render block.
			return wp_is_block_theme() ? do_shortcode( '[woocommerce_checkout]' ) : '[woocommerce_checkout]';
		}

		// Dequeue the core scripts when rendering this block.
		add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_woocommerce_core_scripts' ), 20 );

		/**
		 * We need to check if $content has any templates from prior iterations of the block, in order to update to the latest iteration.
		 * We test the iteration version by searching for new blocks brought in by it.
		 * The blocks used for testing should be always available in the block (not removable by the user).
		 * Checkout i1's content was returning an empty div, with no data-block-name attribute
		 */
		$regex_for_empty_block = '/<div class="[a-zA-Z0-9_\- ]*wp-block-woocommerce-checkout[a-zA-Z0-9_\- ]*"><\/div>/mi';
		$has_i1_template       = preg_match( $regex_for_empty_block, $content );

		if ( $has_i1_template ) {
			// This fallback needs to match the default templates defined in our Blocks.
			$inner_blocks_html = '
				<div data-block-name="woocommerce/checkout-fields-block" class="wp-block-woocommerce-checkout-fields-block">
					<div data-block-name="woocommerce/checkout-express-payment-block" class="wp-block-woocommerce-checkout-express-payment-block"></div>
					<div data-block-name="woocommerce/checkout-contact-information-block" class="wp-block-woocommerce-checkout-contact-information-block"></div>
					<div data-block-name="woocommerce/checkout-shipping-address-block" class="wp-block-woocommerce-checkout-shipping-address-block"></div>
					<div data-block-name="woocommerce/checkout-billing-address-block" class="wp-block-woocommerce-checkout-billing-address-block"></div>
					<div data-block-name="woocommerce/checkout-shipping-methods-block" class="wp-block-woocommerce-checkout-shipping-methods-block"></div>
					<div data-block-name="woocommerce/checkout-payment-block" class="wp-block-woocommerce-checkout-payment-block"></div>
					<div data-block-name="woocommerce/checkout-additional-information-block" class="wp-block-woocommerce-checkout-additional-information-block"></div>' .
					( isset( $attributes['showOrderNotes'] ) && false === $attributes['showOrderNotes'] ? '' : '<div data-block-name="woocommerce/checkout-order-note-block" class="wp-block-woocommerce-checkout-order-note-block"></div>' ) .
					( isset( $attributes['showPolicyLinks'] ) && false === $attributes['showPolicyLinks'] ? '' : '<div data-block-name="woocommerce/checkout-terms-block" class="wp-block-woocommerce-checkout-terms-block"></div>' ) .
					'<div data-block-name="woocommerce/checkout-actions-block" class="wp-block-woocommerce-checkout-actions-block"></div>
				</div>
				<div data-block-name="woocommerce/checkout-totals-block" class="wp-block-woocommerce-checkout-totals-block">
					<div data-block-name="woocommerce/checkout-order-summary-block" class="wp-block-woocommerce-checkout-order-summary-block"></div>
				</div>
			';

			$content = str_replace( '</div>', $inner_blocks_html . '</div>', $content );
		}

		/**
		 * Checkout i3 added inner blocks for Order summary.
		 * We need to add them to Checkout i2 templates.
		 * The order needs to match the order in which these blocks were registered.
		 */
		$order_summary_with_inner_blocks = '$0
			<div data-block-name="woocommerce/checkout-order-summary-cart-items-block" class="wp-block-woocommerce-checkout-order-summary-cart-items-block"></div>
			<div data-block-name="woocommerce/checkout-order-summary-subtotal-block" class="wp-block-woocommerce-checkout-order-summary-subtotal-block"></div>
			<div data-block-name="woocommerce/checkout-order-summary-fee-block" class="wp-block-woocommerce-checkout-order-summary-fee-block"></div>
			<div data-block-name="woocommerce/checkout-order-summary-discount-block" class="wp-block-woocommerce-checkout-order-summary-discount-block"></div>
			<div data-block-name="woocommerce/checkout-order-summary-coupon-form-block" class="wp-block-woocommerce-checkout-order-summary-coupon-form-block"></div>
			<div data-block-name="woocommerce/checkout-order-summary-shipping-block" class="wp-block-woocommerce-checkout-order-summary-shipping-block"></div>
			<div data-block-name="woocommerce/checkout-order-summary-taxes-block" class="wp-block-woocommerce-checkout-order-summary-taxes-block"></div>
		';
		// Order summary subtotal block was added in i3, so we search for it to see if we have a Checkout i2 template.
		$regex_for_order_summary_subtotal = '/<div[^<]*?data-block-name="woocommerce\/checkout-order-summary-subtotal-block"[^>]*?>/mi';
		$regex_for_order_summary          = '/<div[^<]*?data-block-name="woocommerce\/checkout-order-summary-block"[^>]*?>/mi';
		$has_i2_template                  = ! preg_match( $regex_for_order_summary_subtotal, $content );

		if ( $has_i2_template ) {
			$content = preg_replace( $regex_for_order_summary, $order_summary_with_inner_blocks, $content );
		}

		/**
		 * Add the Local Pickup toggle to checkouts missing this forced template.
		 */
		$local_pickup_inner_blocks = '<div data-block-name="woocommerce/checkout-shipping-method-block" class="wp-block-woocommerce-checkout-shipping-method-block"></div>' . PHP_EOL . PHP_EOL . '<div data-block-name="woocommerce/checkout-pickup-options-block" class="wp-block-woocommerce-checkout-pickup-options-block"></div>' . PHP_EOL . PHP_EOL . '$0';
		$has_local_pickup_regex    = '/<div[^<]*?data-block-name="woocommerce\/checkout-shipping-method-block"[^>]*?>/mi';
		$has_local_pickup          = preg_match( $has_local_pickup_regex, $content );

		if ( ! $has_local_pickup ) {
			$shipping_address_block_regex = '/<div[^<]*?data-block-name="woocommerce\/checkout-shipping-address-block" class="wp-block-woocommerce-checkout-shipping-address-block"[^>]*?><\/div>/mi';
			$content                      = preg_replace( $shipping_address_block_regex, $local_pickup_inner_blocks, $content );
		}

		/**
		 * Add the Additional Information block to checkouts missing it.
		 */
		$additional_information_inner_blocks = '$0' . PHP_EOL . PHP_EOL . '<div data-block-name="woocommerce/checkout-additional-information-block" class="wp-block-woocommerce-checkout-additional-information-block"></div>' . PHP_EOL . PHP_EOL;
		$has_additional_information_regex    = '/<div[^<]*?data-block-name="woocommerce\/checkout-additional-information-block"[^>]*?>/mi';
		$has_additional_information_block    = preg_match( $has_additional_information_regex, $content );

		if ( ! $has_additional_information_block ) {
			$payment_block_regex = '/<div[^<]*?data-block-name="woocommerce\/checkout-payment-block" class="wp-block-woocommerce-checkout-payment-block"[^>]*?><\/div>/mi';
			$content             = preg_replace( $payment_block_regex, $additional_information_inner_blocks, $content );
		}

		return $content;
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
	 * Update the local pickup title in WooCommerce Settings when the checkout page containing a Checkout block is saved.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 * @return void
	 */
	public function update_local_pickup_title( $post_id, $post ) {

		// This is not a proper save action, maybe an autosave, so don't continue.
		if ( empty( $post->post_status ) || 'inherit' === $post->post_status ) {
			return;
		}

		// Check if we are editing the checkout page and that it contains a Checkout block.
		// Cast to string for Checkout page ID comparison because get_option can return it as a string, so better to compare both values as strings.
		if ( ! empty( $post->post_type ) && 'wp_template' !== $post->post_type && ( false === has_block( 'woocommerce/checkout', $post ) || (string) get_option( 'woocommerce_checkout_page_id' ) !== (string) $post_id ) ) {
			return;
		}

		if ( ( ! empty( $post->post_type ) && ! empty( $post->post_name ) && 'page-checkout' !== $post->post_name && 'wp_template' === $post->post_type ) || false === has_block( 'woocommerce/checkout', $post ) ) {
			return;
		}
		$pickup_location_settings = LocalPickupUtils::get_local_pickup_settings( 'edit' );

		if ( ! isset( $pickup_location_settings['title'] ) ) {
			return;
		}

		if ( empty( $post->post_content ) ) {
			return;
		}

		$post_blocks = parse_blocks( $post->post_content );
		$title       = $this->find_local_pickup_text_in_checkout_block( $post_blocks );

		// Set the title to be an empty string if it isn't a string. This will make it fall back to the default value of "Pickup".
		if ( ! is_string( $title ) ) {
			$title = '';
		}

		$pickup_location_settings['title'] = $title;
		update_option( 'woocommerce_pickup_location_settings', $pickup_location_settings );
	}

	/**
	 * Recurse through the blocks to find the shipping methods block, then get the value of the localPickupText attribute from it.
	 *
	 * @param array $blocks The block(s) to search for the local pickup text.
	 * @return null|string  The local pickup text if found, otherwise void.
	 */
	private function find_local_pickup_text_in_checkout_block( $blocks ) {
		if ( ! is_array( $blocks ) ) {
			return null;
		}
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) && 'woocommerce/checkout-shipping-method-block' === $block['blockName'] ) {
				if ( ! empty( $block['attrs']['localPickupText'] ) ) {
					return $block['attrs']['localPickupText'];
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$answer = $this->find_local_pickup_text_in_checkout_block( $block['innerBlocks'] );
				if ( $answer ) {
					return $answer;
				}
			}
		}
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

		$country_data    = CartCheckoutUtils::get_country_data();
		$address_formats = WC()->countries->get_address_formats();

		// Move the address format into the 'countryData' setting.
		// We need to skip 'default' because that's not a valid country.
		foreach ( $address_formats as $country_code => $format ) {
			if ( 'default' === $country_code ) {
				continue;
			}
			$country_data[ $country_code ]['format'] = $format;
		}

		$this->asset_data_registry->add( 'countryData', $country_data );
		$this->asset_data_registry->add( 'defaultAddressFormat', $address_formats['default'] );
		$this->asset_data_registry->add(
			'checkoutAllowsGuest',
			false === filter_var(
				wc()->checkout()->is_registration_required(),
				FILTER_VALIDATE_BOOLEAN
			)
		);
		$this->asset_data_registry->add(
			'checkoutAllowsSignup',
			filter_var(
				wc()->checkout()->is_registration_enabled(),
				FILTER_VALIDATE_BOOLEAN
			)
		);
		$this->asset_data_registry->add( 'checkoutShowLoginReminder', filter_var( get_option( 'woocommerce_enable_checkout_login_reminder' ), FILTER_VALIDATE_BOOLEAN ) );
		$this->asset_data_registry->add( 'displayCartPricesIncludingTax', 'incl' === get_option( 'woocommerce_tax_display_cart' ) );
		$this->asset_data_registry->add( 'displayItemizedTaxes', 'itemized' === get_option( 'woocommerce_tax_total_display' ) );
		$this->asset_data_registry->add( 'forcedBillingAddress', 'billing_only' === get_option( 'woocommerce_ship_to_destination' ) );
		$this->asset_data_registry->add( 'generatePassword', filter_var( get_option( 'woocommerce_registration_generate_password' ), FILTER_VALIDATE_BOOLEAN ) );
		$this->asset_data_registry->add( 'taxesEnabled', wc_tax_enabled() );
		$this->asset_data_registry->add( 'couponsEnabled', wc_coupons_enabled() );
		$this->asset_data_registry->add( 'shippingEnabled', wc_shipping_enabled() );
		$this->asset_data_registry->add( 'hasDarkEditorStyleSupport', current_theme_supports( 'dark-editor-style' ) );
		$this->asset_data_registry->register_page_id( isset( $attributes['cartPageId'] ) ? $attributes['cartPageId'] : 0 );
		$this->asset_data_registry->add( 'isBlockTheme', wp_is_block_theme() );
		$this->asset_data_registry->add( 'isCheckoutBlock', true );

		$pickup_location_settings = LocalPickupUtils::get_local_pickup_settings();
		$local_pickup_method_ids  = LocalPickupUtils::get_local_pickup_method_ids();

		$this->asset_data_registry->add( 'localPickupEnabled', $pickup_location_settings['enabled'] );
		$this->asset_data_registry->add( 'localPickupText', $pickup_location_settings['title'] );
		$this->asset_data_registry->add( 'localPickupCost', $pickup_location_settings['cost'] );
		$this->asset_data_registry->add( 'collectableMethodIds', $local_pickup_method_ids );
		$this->asset_data_registry->add( 'shippingMethodsExist', CartCheckoutUtils::shipping_methods_exist() > 0 );

		$is_block_editor = $this->is_block_editor();

		if ( $is_block_editor && ! $this->asset_data_registry->exists( 'localPickupLocations' ) ) {
			$this->asset_data_registry->add(
				'localPickupLocations',
				array_map(
					function ( $location ) {
						$location['formatted_address'] = wc()->countries->get_formatted_address( $location['address'], ', ' );
						return $location;
					},
					get_option( 'pickup_location_pickup_locations', array() )
				)
			);
		}

		if ( $is_block_editor && ! $this->asset_data_registry->exists( 'globalShippingMethods' ) ) {
			$shipping_methods           = WC()->shipping()->get_shipping_methods();
			$formatted_shipping_methods = array_reduce(
				$shipping_methods,
				function ( $acc, $method ) use ( $local_pickup_method_ids ) {
					if ( in_array( $method->id, $local_pickup_method_ids, true ) ) {
						return $acc;
					}
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
			$this->asset_data_registry->add( 'activeShippingZones', CartCheckoutUtils::get_shipping_zones() );
		}

		if ( $is_block_editor && ! $this->asset_data_registry->exists( 'globalPaymentMethods' ) ) {
			// These are used to show options in the sidebar. We want to get the full list of enabled payment methods,
			// not just the ones that are available for the current cart (which may not exist yet).
			$payment_methods           = PaymentUtils::get_enabled_payment_gateways();
			$formatted_payment_methods = array_reduce(
				$payment_methods,
				function ( $acc, $method ) {
					$acc[] = [
						'id'          => $method->id,
						'title'       => $method->get_method_title() !== '' ? $method->get_method_title() : $method->get_title(),
						'description' => $method->get_method_description() !== '' ? $method->get_method_description() : $method->get_description(),
					];
					return $acc;
				},
				[]
			);
			$this->asset_data_registry->add( 'globalPaymentMethods', $formatted_payment_methods );
		}

		if ( $is_block_editor && ! $this->asset_data_registry->exists( 'incompatibleExtensions' ) ) {
			if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) || ! function_exists( 'get_plugins' ) ) {
				return;
			}

			$declared_extensions     = \Automattic\WooCommerce\Utilities\FeaturesUtil::get_compatible_plugins_for_feature( 'cart_checkout_blocks' );
			$all_plugins             = \get_plugins(); // Note that `get_compatible_plugins_for_feature` calls `get_plugins` internally, so this is already in cache.
			$incompatible_extensions = array_reduce(
				$declared_extensions['incompatible'],
				function ( $acc, $item ) use ( $all_plugins ) {
					$plugin      = $all_plugins[ $item ] ?? null;
					$plugin_id   = $plugin['TextDomain'] ?? dirname( $item, 2 );
					$plugin_name = $plugin['Name'] ?? $plugin_id;
					$acc[]       = [
						'id'    => $plugin_id,
						'title' => $plugin_name,
					];
					return $acc;
				},
				[]
			);
			$this->asset_data_registry->add( 'incompatibleExtensions', $incompatible_extensions );
		}

		if ( ! is_admin() && ! WC()->is_rest_api_request() ) {
			$this->asset_data_registry->hydrate_api_request( '/wc/store/v1/cart' );
			$this->asset_data_registry->hydrate_data_from_api_request( 'checkoutData', '/wc/store/v1/checkout' );
			$this->hydrate_customer_payment_methods();
		}

		/**
		 * Fires after checkout block data is registered.
		 *
		 * @since 2.6.0
		 */
		do_action( 'woocommerce_blocks_checkout_enqueue_data' );
	}

	/**
	 * Get saved customer payment methods for use in checkout.
	 */
	protected function hydrate_customer_payment_methods() {
		$payment_methods = PaymentUtils::get_saved_payment_methods();

		if ( ! $payment_methods || $this->asset_data_registry->exists( 'customerPaymentMethods' ) ) {
			return;
		}

		$this->asset_data_registry->add(
			'customerPaymentMethods',
			is_array( $payment_methods ) ? $payment_methods['enabled'] : null
		);
	}

	/**
	 * Register script and style assets for the block type before it is registered.
	 *
	 * This registers the scripts; it does not enqueue them.
	 */
	protected function register_block_type_assets() {
		parent::register_block_type_assets();
		$chunks        = $this->get_chunks_paths( $this->chunks_folder );
		$vendor_chunks = $this->get_chunks_paths( 'vendors--checkout-blocks' );
		$shared_chunks = [ 'cart-blocks/cart-express-payment--checkout-blocks/express-payment-frontend' ];
		$this->register_chunk_translations( array_merge( $chunks, $vendor_chunks, $shared_chunks ) );
	}

	/**
	 * Get list of Checkout block & its inner-block types.
	 *
	 * @return array;
	 */
	public static function get_checkout_block_types() {
		return [
			'Checkout',
			'CheckoutActionsBlock',
			'CheckoutAdditionalInformationBlock',
			'CheckoutBillingAddressBlock',
			'CheckoutContactInformationBlock',
			'CheckoutExpressPaymentBlock',
			'CheckoutFieldsBlock',
			'CheckoutOrderNoteBlock',
			'CheckoutOrderSummaryBlock',
			'CheckoutOrderSummaryCartItemsBlock',
			'CheckoutOrderSummaryCouponFormBlock',
			'CheckoutOrderSummaryDiscountBlock',
			'CheckoutOrderSummaryFeeBlock',
			'CheckoutOrderSummaryShippingBlock',
			'CheckoutOrderSummarySubtotalBlock',
			'CheckoutOrderSummaryTaxesBlock',
			'CheckoutOrderSummaryTotalsBlock',
			'CheckoutPaymentBlock',
			'CheckoutShippingAddressBlock',
			'CheckoutShippingMethodsBlock',
			'CheckoutShippingMethodBlock',
			'CheckoutPickupOptionsBlock',
			'CheckoutTermsBlock',
			'CheckoutTotalsBlock',
		];
	}
}
