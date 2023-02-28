<?php
namespace Automattic\WooCommerce\Blocks\Shipping;

use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils;
use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * ShippingController class.
 *
 * @internal
 */
class ShippingController {
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
	 * Constructor.
	 *
	 * @param AssetApi          $asset_api Instance of the asset API.
	 * @param AssetDataRegistry $asset_data_registry Instance of the asset data registry.
	 */
	public function __construct( AssetApi $asset_api, AssetDataRegistry $asset_data_registry ) {
		$this->asset_api           = $asset_api;
		$this->asset_data_registry = $asset_data_registry;
	}

	/**
	 * Initialization method.
	 */
	public function init() {
		if ( is_admin() ) {
			$this->asset_data_registry->add(
				'countryStates',
				function() {
					return WC()->countries->get_states();
				},
				true
			);
		}
		$this->asset_data_registry->add( 'collectableMethodIds', array( 'Automattic\WooCommerce\StoreApi\Utilities\LocalPickupUtils', 'get_local_pickup_method_ids' ), true );
		add_action( 'rest_api_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'hydrate_client_settings' ] );
		add_action( 'woocommerce_load_shipping_methods', array( $this, 'register_local_pickup' ) );
		add_filter( 'woocommerce_local_pickup_methods', array( $this, 'register_local_pickup_method' ) );
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'filter_taxable_address' ) );
		add_filter( 'woocommerce_shipping_packages', array( $this, 'filter_shipping_packages' ) );
		add_filter( 'pre_update_option_woocommerce_pickup_location_settings', array( $this, 'flush_cache' ) );
		add_filter( 'pre_update_option_pickup_location_pickup_locations', array( $this, 'flush_cache' ) );
	}

	/**
	 * Register Local Pickup settings for rest api.
	 */
	public function register_settings() {
		register_setting(
			'options',
			'woocommerce_pickup_location_settings',
			[
				'type'         => 'object',
				'description'  => 'WooCommerce Local Pickup Method Settings',
				'default'      => [],
				'show_in_rest' => [
					'name'   => 'pickup_location_settings',
					'schema' => [
						'type'       => 'object',
						'properties' => array(
							'enabled'    => [
								'description' => __( 'If enabled, this method will appear on the block based checkout.', 'woo-gutenberg-products-block' ),
								'type'        => 'string',
								'enum'        => [ 'yes', 'no' ],
							],
							'title'      => [
								'description' => __( 'This controls the title which the user sees during checkout.', 'woo-gutenberg-products-block' ),
								'type'        => 'string',
							],
							'tax_status' => [
								'description' => __( 'If a cost is defined, this controls if taxes are applied to that cost.', 'woo-gutenberg-products-block' ),
								'type'        => 'string',
								'enum'        => [ 'taxable', 'none' ],
							],
							'cost'       => [
								'description' => __( 'Optional cost to charge for local pickup.', 'woo-gutenberg-products-block' ),
								'type'        => 'string',
							],
						),
					],
				],
			]
		);
		register_setting(
			'options',
			'pickup_location_pickup_locations',
			[
				'type'         => 'array',
				'description'  => 'WooCommerce Local Pickup Locations',
				'default'      => [],
				'show_in_rest' => [
					'name'   => 'pickup_locations',
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => array(
								'name'    => [
									'type' => 'string',
								],
								'address' => [
									'type'       => 'object',
									'properties' => array(
										'address_1' => [
											'type' => 'string',
										],
										'city'      => [
											'type' => 'string',
										],
										'state'     => [
											'type' => 'string',
										],
										'postcode'  => [
											'type' => 'string',
										],
										'country'   => [
											'type' => 'string',
										],
									),
								],
								'details' => [
									'type' => 'string',
								],
								'enabled' => [
									'type' => 'boolean',
								],
							),
						],
					],
				],
			]
		);
	}

	/**
	 * Hydrate client settings
	 */
	public function hydrate_client_settings() {
		$locations = get_option( 'pickup_location_pickup_locations', [] );

		$formatted_pickup_locations = [];
		foreach ( $locations as $location ) {
			$formatted_pickup_locations[] = [
				'name'    => $location['name'],
				'address' => $location['address'],
				'details' => $location['details'],
				'enabled' => wc_string_to_bool( $location['enabled'] ),
			];
		}

		$has_legacy_pickup = false;

		// Get all shipping zones.
		$shipping_zones              = \WC_Shipping_Zones::get_zones( 'admin' );
		$international_shipping_zone = new \WC_Shipping_Zone( 0 );

		// Loop through each shipping zone.
		foreach ( $shipping_zones as $shipping_zone ) {
			// Get all registered rates for this shipping zone.
			$shipping_methods = $shipping_zone['shipping_methods'];
			// Loop through each registered rate.
			foreach ( $shipping_methods as $shipping_method ) {
				if ( 'local_pickup' === $shipping_method->id && 'yes' === $shipping_method->enabled ) {
					$has_legacy_pickup = true;
					break 2;
				}
			}
		}

		foreach ( $international_shipping_zone->get_shipping_methods( true ) as $shipping_method ) {
			if ( 'local_pickup' === $shipping_method->id ) {
				$has_legacy_pickup = true;
				break;
			}
		}

		$settings = array(
			'pickupLocationSettings' => get_option( 'woocommerce_pickup_location_settings', [] ),
			'pickupLocations'        => $formatted_pickup_locations,
			'readonlySettings'       => array(
				'hasLegacyPickup' => $has_legacy_pickup,
				'storeCountry'    => WC()->countries->get_base_country(),
			),
		);

		wp_add_inline_script(
			'wc-shipping-method-pickup-location',
			sprintf(
				'var hydratedScreenSettings = %s;',
				wp_json_encode( $settings )
			),
			'before'
		);
	}
	/**
	 * Load admin scripts.
	 */
	public function admin_scripts() {
		$this->asset_api->register_script( 'wc-shipping-method-pickup-location', 'build/wc-shipping-method-pickup-location.js', [], true );
	}

	/**
	 * Registers the Local Pickup shipping method used by the Checkout Block.
	 */
	public function register_local_pickup() {
		$checkout_page_id = wc_get_page_id( 'checkout' );
		if ( $checkout_page_id && has_block( 'woocommerce/checkout', $checkout_page_id ) ) {
			wc()->shipping->register_shipping_method( new PickupLocation() );
		}
	}

	/**
	 * Declares the Pickup Location shipping method as a Local Pickup method for WooCommerce.
	 *
	 * @param array $methods Shipping method ids.
	 * @return array
	 */
	public function register_local_pickup_method( $methods ) {
		$methods[] = 'pickup_location';
		return $methods;
	}

	/**
	 * Everytime we save or update local pickup settings, we flush the shipping
	 * transient group.
	 *
	 * @param array $settings The setting array we're saving.
	 * @return array $settings The setting array we're saving.
	 */
	public function flush_cache( $settings ) {
		\WC_Cache_Helper::get_transient_version( 'shipping', true );
		return $settings;
	}
	/**
	 * Filter the location used for taxes based on the chosen pickup location.
	 *
	 * @param array $address Location args.
	 * @return array
	 */
	public function filter_taxable_address( $address ) {

		if ( null === WC()->session ) {
			return $address;
		}
		// We only need to select from the first package, since pickup_location only supports a single package.
		$chosen_method          = current( WC()->session->get( 'chosen_shipping_methods', array() ) ) ?? '';
		$chosen_method_id       = explode( ':', $chosen_method )[0];
		$chosen_method_instance = explode( ':', $chosen_method )[1] ?? 0;

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		if ( $chosen_method_id && true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && in_array( $chosen_method_id, LocalPickupUtils::get_local_pickup_method_ids(), true ) ) {
			$pickup_locations = get_option( 'pickup_location_pickup_locations', [] );
			$pickup_location  = $pickup_locations[ $chosen_method_instance ] ?? [];

			if ( isset( $pickup_location['address'], $pickup_location['address']['country'] ) && ! empty( $pickup_location['address']['country'] ) ) {
				$address = array(
					$pickup_locations[ $chosen_method_instance ]['address']['country'],
					$pickup_locations[ $chosen_method_instance ]['address']['state'],
					$pickup_locations[ $chosen_method_instance ]['address']['postcode'],
					$pickup_locations[ $chosen_method_instance ]['address']['city'],
				);
			}
		}

		return $address;
	}

	/**
	 * Local Pickup requires all packages to support local pickup. This is because the entire order must be picked up
	 * so that all packages get the same tax rates applied during checkout.
	 *
	 * If a shipping package does not support local pickup (e.g. if disabled by an extension), this filters the option
	 * out for all packages. This will in turn disable the "pickup" toggle in Block Checkout.
	 *
	 * @param array $packages Array of shipping packages.
	 * @return array
	 */
	public function filter_shipping_packages( $packages ) {
		// Check all packages for an instance of a collectable shipping method.
		$valid_packages = array_filter(
			$packages,
			function( $package ) {
				$shipping_method_ids = ArrayUtil::select( $package['rates'] ?? [], 'get_method_id', ArrayUtil::SELECT_BY_OBJECT_METHOD );
				return ! empty( array_intersect( LocalPickupUtils::get_local_pickup_method_ids(), $shipping_method_ids ) );
			}
		);

		// Remove pickup location from rates arrays.
		if ( count( $valid_packages ) !== count( $packages ) ) {
			$packages = array_map(
				function( $package ) {
					$package['rates'] = array_filter(
						$package['rates'],
						function( $rate ) {
							return ! in_array( $rate->get_method_id(), LocalPickupUtils::get_local_pickup_method_ids(), true );
						}
					);
					return $package;
				},
				$packages
			);
		}

		return $packages;
	}
}
