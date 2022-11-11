<?php
namespace Automattic\WooCommerce\Blocks\Shipping;

use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;

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
		// @todo This should be moved inline for the settings page only.
		$this->asset_data_registry->add(
			'pickupLocationSettings',
			get_option( 'woocommerce_pickup_location_settings', [] ),
			true
		);
		$this->asset_data_registry->add(
			'pickupLocations',
			function() {
				$locations = get_option( 'pickup_location_pickup_locations', [] );
				$formatted = [];
				foreach ( $locations as $location ) {
					$formatted[] = [
						'name'    => $location['name'],
						'address' => $location['address'],
						'details' => $location['details'],
						'enabled' => wc_string_to_bool( $location['enabled'] ),
					];
				}
				return $formatted;
			},
			true
		);
		if ( is_admin() ) {
			$this->asset_data_registry->add(
				'countryStates',
				function() {
					return WC()->countries->get_states();
				},
				true
			);
		}
		add_action( 'rest_api_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'woocommerce_load_shipping_methods', array( $this, 'register_shipping_methods' ) );
		add_filter( 'woocommerce_shipping_packages', array( $this, 'filter_shipping_packages' ) );
		add_filter( 'woocommerce_local_pickup_methods', array( $this, 'filter_local_pickup_methods' ) );
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'pickup_location_customer_tax_location' ) );
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
	 * Load admin scripts.
	 */
	public function admin_scripts() {
		$this->asset_api->register_script( 'wc-shipping-method-pickup-location', 'build/wc-shipping-method-pickup-location.js', [], true );
	}

	/**
	 * Registers the local pickup method for blocks.
	 */
	public function register_shipping_methods() {
		$pickup = new PickupLocation();
		wc()->shipping->register_shipping_method( $pickup );
	}

	/**
	 * Disable local pickup if multiple packages are present.
	 *
	 * @param array $packages Array of shipping packages.
	 * @return array
	 */
	public function filter_shipping_packages( $packages ) {
		if ( 1 === count( $packages ) ) {
			return $packages;
		}

		foreach ( $packages as $package_id => $package ) {
			$package_rates = $package['rates'];

			foreach ( $package_rates as $rate_id => $rate ) {
				$method_id = $rate->get_method_id();

				if ( 'pickup_location' === $method_id ) {
					unset( $packages[ $package_id ]['rates'][ $rate_id ] );
				}
			}
		}

		return $packages;
	}

	/**
	 * Declares the Pickup Location shipping method as being a Local Pickup method.
	 *
	 * @param array $methods Shipping method ids.
	 * @return array
	 */
	public function filter_local_pickup_methods( $methods ) {
		$methods[] = 'pickup_location';
		return $methods;
	}

	/**
	 * Filter the location used for taxes based on the chosen pickup location.
	 *
	 * @param array $address Location args.
	 * @return array
	 */
	public function pickup_location_customer_tax_location( $address ) {
		// We only need to select from the first package, since pickup_location only supports a single package.
		$chosen_method          = current( WC()->session->get( 'chosen_shipping_methods', array() ) ) ?? '';
		$chosen_method_id       = explode( ':', $chosen_method )[0];
		$chosen_method_instance = explode( ':', $chosen_method )[1] ?? 0;

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		if ( $chosen_method_id && true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && 'pickup_location' === $chosen_method_id ) {
			$pickup_locations = get_option( 'pickup_location_pickup_locations', [] );

			if ( ! empty( $pickup_locations[ $chosen_method_instance ] ) && ! empty( $pickup_locations[ $chosen_method_instance ]['address']['country'] ) ) {
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
}
