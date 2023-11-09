<?php
namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\BlockTemplatesController;
use Automattic\WooCommerce\Blocks\Package;
use WC_Tracks;

/**
 * Service class to integrate Blocks with the Jetpack WooCommerce Analytics extension,
 */
class JetpackWooCommerceAnalytics {
	/**
	 * Instance of the asset API.
	 *
	 * @var AssetApi
	 */
	protected $asset_api;

	/**
	 * Instance of the asset data registry.
	 *
	 * @var AssetDataRegistry
	 */
	protected $asset_data_registry;

	/**
	 * Instance of the block templates controller.
	 *
	 * @var BlockTemplatesController
	 */
	protected $block_templates_controller;

	/**
	 * Whether the required Jetpack WooCommerce Analytics classes are available.
	 *
	 * @var bool
	 */
	protected $is_compatible;

	/**
	 * Constructor.
	 *
	 * @param AssetApi                 $asset_api Instance of the asset API.
	 * @param AssetDataRegistry        $asset_data_registry Instance of the asset data registry.
	 * @param BlockTemplatesController $block_templates_controller Instance of the block templates controller.
	 */
	public function __construct( AssetApi $asset_api, AssetDataRegistry $asset_data_registry, BlockTemplatesController $block_templates_controller ) {
		$this->asset_api                  = $asset_api;
		$this->asset_data_registry        = $asset_data_registry;
		$this->block_templates_controller = $block_templates_controller;
	}

	/**
	 * Hook into WP.
	 */
	public function init() {
		add_action( 'init', array( $this, 'check_compatibility' ) );
		add_action( 'rest_pre_serve_request', array( $this, 'track_local_pickup' ), 10, 4 );

		$is_rest = wc()->is_rest_api_request();
		if ( ! $is_rest ) {
			add_action( 'init', array( $this, 'init_if_compatible' ), 20 );
		}
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
	 * Gather relevant product information. Taken from Jetpack WooCommerce Analytics Module.
	 *
	 * @param \WC_Product $product product.
	 * @return array
	 */
	public function get_product_details( $product ) {
		return array(
			'id'       => $product->get_id(),
			'name'     => $product->get_title(),
			'category' => $this->get_product_categories_concatenated( $product ),
			'price'    => $product->get_price(),
			'type'     => $product->get_type(),
		);
	}

	/**
	 * Save the order received page view event properties to the asset data registry. The front end will consume these
	 * later.
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return void
	 */
	public function output_order_received_page_view_properties( $order_id ) {
		$order        = wc_get_order( $order_id );
		$product_data = wp_json_encode(
			array_map(
				function( $item ) {
					$product         = wc_get_product( $item->get_product_id() );
					$product_details = $this->get_product_details( $product );
					return array(
						'pi' => $product_details['id'],
						'pq' => $item->get_quantity(),
						'pt' => $product_details['type'],
						'pn' => $product_details['name'],
						'pc' => $product_details['category'],
						'pp' => $product_details['price'],
					);
				},
				$order->get_items()
			)
		);

		$properties             = $this->get_cart_checkout_info();
		$properties['products'] = $product_data;
		$this->asset_data_registry->add( 'wc-blocks-jetpack-woocommerce-analytics_order_received_properties', $properties );
	}

	/**
	 * Check compatibility with Jetpack WooCommerce Analytics.
	 *
	 * @return void
	 */
	public function check_compatibility() {
		// Require Jetpack WooCommerce Analytics to be available.
		$this->is_compatible = class_exists( 'Jetpack_WooCommerce_Analytics_Universal', false ) &&
								class_exists( 'Jetpack_WooCommerce_Analytics', false ) &&
								\Jetpack_WooCommerce_Analytics::should_track_store();
	}

	/**
	 * Initialize if compatible.
	 */
	public function init_if_compatible() {
		if ( ! $this->is_compatible ) {
			return;
		}
		$this->register_assets();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_script_data' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'output_order_received_page_view_properties' ) );
	}

	/**
	 * Register scripts.
	 */
	public function register_assets() {
		if ( ! $this->is_compatible ) {
			return;
		}
		$asset_file = include Package::get_path() . 'build/wc-blocks-jetpack-woocommerce-analytics.asset.php';
		if ( is_array( $asset_file['dependencies'] ) ) {
			$this->asset_api->register_script( 'wc-blocks-jetpack-woocommerce-analytics', 'build/wc-blocks-jetpack-woocommerce-analytics.js', array_merge( array( 'wc-blocks' ), $asset_file['dependencies'] ) );
		}
	}

	/**
	 * Enqueue the Google Tag Manager script if prerequisites are met.
	 */
	public function enqueue_scripts() {
		// Additional check here before finally enqueueing the scripts. Done late here because checking these earlier fails.
		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}
		wp_enqueue_script( 'wc-blocks-jetpack-woocommerce-analytics' );
	}

	/**
	 * Enqueue the Google Tag Manager script if prerequisites are met.
	 */
	public function register_script_data() {
		$this->asset_data_registry->add( 'wc-blocks-jetpack-woocommerce-analytics_cart_checkout_info', $this->get_cart_checkout_info() );
	}

	/**
	 * Get the current user id
	 *
	 * @return int
	 */
	private function get_user_id() {
		if ( is_user_logged_in() ) {
			$blogid = \Jetpack::get_option( 'id' );
			$userid = get_current_user_id();
			return $blogid . ':' . $userid;
		}
		return 'null';
	}

	/**
	 * Default event properties which should be included with all events.
	 *
	 * @return array Array of standard event props.
	 */
	public function get_common_properties() {
		if ( ! class_exists( 'Jetpack' ) || ! is_callable( array( 'Jetpack', 'get_option' ) ) ) {
			return array();
		}
		return array(
			'blog_id'     => \Jetpack::get_option( 'id' ),
			'ui'          => $this->get_user_id(),
			'url'         => home_url(),
			'woo_version' => WC()->version,
		);
	}

	/**
	 * Get info about the cart & checkout pages, in particular whether the store is using shortcodes or Gutenberg blocks.
	 * This info is cached in a transient.
	 *
	 * @return array
	 */
	public function get_cart_checkout_info() {
		$transient_name = 'woocommerce_blocks_jetpack_woocommerce_analytics_cart_checkout_info_cache';

		$info = get_transient( $transient_name );

		// Return cached data early to prevent additional processing, the transient lasts for 1 day.
		if ( false !== $info ) {
			return $info;
		}

		$cart_template        = null;
		$checkout_template    = null;
		$cart_template_id     = null;
		$checkout_template_id = null;
		$templates            = $this->block_templates_controller->get_block_templates( array( 'cart', 'checkout', 'page-checkout', 'page-cart' ) );
		$guest_checkout       = ucfirst( get_option( 'woocommerce_enable_guest_checkout', 'No' ) );
		$create_account       = ucfirst( get_option( 'woocommerce_enable_signup_and_login_from_checkout', 'No' ) );

		foreach ( $templates as $template ) {
			if ( 'cart' === $template->slug || 'page-cart' === $template->slug ) {
				$cart_template_id = ( $template->id );
				continue;
			}
			if ( 'checkout' === $template->slug || 'page-checkout' === $template->slug ) {
				$checkout_template_id = ( $template->id );
			}
		}

		// Get the template and its contents from the IDs we found above.
		if ( function_exists( 'get_block_template' ) ) {
			$cart_template     = get_block_template( $cart_template_id );
			$checkout_template = get_block_template( $checkout_template_id );
		}

		if ( function_exists( 'gutenberg_get_block_template' ) ) {
			$cart_template     = get_block_template( $cart_template_id );
			$checkout_template = get_block_template( $checkout_template_id );
		}

		// Something failed with the template retrieval, return early with 0 values rather than let a warning appear.
		if ( ! $cart_template || ! $checkout_template ) {
			return array(
				'cart_page_contains_cart_block'         => 0,
				'cart_page_contains_cart_shortcode'     => 0,
				'checkout_page_contains_checkout_block' => 0,
				'checkout_page_contains_checkout_shortcode' => 0,
			);
		}

		// Update the info transient with data we got from the templates, if the site isn't using WC Blocks we
		// won't be doing this so no concern about overwriting.
		// Sites that load this code will be loading it on a page using the relevant block, but we still need to check
		// the other page to see if it's using the block or shortcode.
		$info = array(
			'cart_page_contains_cart_block'             => str_contains( $cart_template->content, '<!-- wp:woocommerce/cart' ),
			'cart_page_contains_cart_shortcode'         => str_contains( $cart_template->content, '[woocommerce_cart]' ),
			'checkout_page_contains_checkout_block'     => str_contains( $checkout_template->content, '<!-- wp:woocommerce/checkout' ),
			'checkout_page_contains_checkout_shortcode' => str_contains( $checkout_template->content, '[woocommerce_checkout]' ),
			'additional_blocks_on_cart_page'            => $this->get_additional_blocks(
				$cart_template->content,
				array( 'woocommerce/cart' )
			),
			'additional_blocks_on_checkout_page'        => $this->get_additional_blocks(
				$checkout_template->content,
				array( 'woocommerce/checkout' )
			),
			'device'                                    => wp_is_mobile() ? 'mobile' : 'desktop',
			'guest_checkout'                            => 'Yes' === $guest_checkout ? 'Yes' : 'No',
			'create_account'                            => 'Yes' === $create_account ? 'Yes' : 'No',
			'store_currency'                            => get_woocommerce_currency(),
		);
		set_transient( $transient_name, $info, DAY_IN_SECONDS );
		return array_merge( $this->get_common_properties(), $info );
	}

	/**
	 * Get the additional blocks used in a post or template.
	 *
	 * @param string $content The post content.
	 * @param array  $exclude The blocks to exclude.
	 *
	 * @return array The additional blocks.
	 */
	private function get_additional_blocks( $content, $exclude = array() ) {
		$parsed_blocks = parse_blocks( $content );
		return $this->get_nested_blocks( $parsed_blocks, $exclude );
	}

	/**
	 * Get the nested blocks from a block array.
	 *
	 * @param array    $blocks  The blocks array to find nested blocks inside.
	 * @param string[] $exclude Blocks to exclude, won't find nested blocks within any of the supplied blocks.
	 *
	 * @return array
	 */
	private function get_nested_blocks( $blocks, $exclude = array() ) {
		if ( ! is_array( $blocks ) ) {
			return array();
		}
		$additional_blocks = array();
		foreach ( $blocks as $block ) {
			if ( ! isset( $block['blockName'] ) ) {
				continue;
			}
			if ( in_array( $block['blockName'], $exclude, true ) ) {
				continue;
			}
			if ( is_array( $block['innerBlocks'] ) ) {
				$additional_blocks = array_merge( $additional_blocks, self::get_nested_blocks( $block['innerBlocks'], $exclude ) );
			}
			$additional_blocks[] = $block['blockName'];
		}
		return $additional_blocks;
	}

	/**
	 * Track local pickup settings changes via Store API
	 *
	 * @param bool              $served Whether the request has already been served.
	 * @param \WP_REST_Response $result The response object.
	 * @param \WP_REST_Request  $request The request object.
	 * @return bool
	 */
	public function track_local_pickup( $served, $result, $request ) {
		if ( '/wp/v2/settings' !== $request->get_route() ) {
			return $served;
		}
		// Param name here comes from the show_in_rest['name'] value when registering the setting.
		if ( ! $request->get_param( 'pickup_location_settings' ) && ! $request->get_param( 'pickup_locations' ) ) {
			return $served;
		}

		if ( ! $this->is_compatible ) {
			return $served;
		}

		$event_name = 'local_pickup_save_changes';

		$settings  = $request->get_param( 'pickup_location_settings' );
		$locations = $request->get_param( 'pickup_locations' );

		$data = array(
			'local_pickup_enabled'     => 'yes' === $settings['enabled'] ? true : false,
			'title'                    => __( 'Local Pickup', 'woo-gutenberg-products-block' ) === $settings['title'],
			'price'                    => '' === $settings['cost'] ? true : false,
			'cost'                     => '' === $settings['cost'] ? 0 : $settings['cost'],
			'taxes'                    => $settings['tax_status'],
			'total_pickup_locations'   => count( $locations ),
			'pickup_locations_enabled' => count(
				array_filter(
					$locations,
					function( $location ) {
						return $location['enabled']; }
				)
			),
		);

		WC_Tracks::record_event( $event_name, $data );

		return $served;
	}
}
