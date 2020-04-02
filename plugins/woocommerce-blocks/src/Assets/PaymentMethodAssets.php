<?php
/**
 * Handles assets for registered payment types.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\Assets;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

/**
 * PaymentMethodAssets class.
 *
 * @since $VID:$
 */
class PaymentMethodAssets {

	/**
	 * Reference to the PaymentMethodRegistry instance
	 *
	 * @var PaymentMethodRegistry
	 */
	private $payment_method_registry;

	/**
	 * An instance of the AssetDataRegistry
	 *
	 * @var AssetDataRegistry
	 */
	private $asset_registry;

	/**
	 * Constructor for class
	 *
	 * @param PaymentMethodRegistry $payment_method_registry An instance of Payment Method Registry.
	 * @param AssetDataRegistry     $asset_registry  Used for registering data to pass along to the request.
	 */
	public function __construct( PaymentMethodRegistry $payment_method_registry, AssetDataRegistry $asset_registry ) {
		$this->payment_method_registry = $payment_method_registry;
		$this->asset_registry          = $asset_registry;
	}

	/**
	 * Gets an array of all registered payment method script handles.
	 *
	 * @return string[]
	 */
	public function get_all_registered_payment_method_script_handles() {
		$script_handles  = [];
		$payment_methods = $this->payment_method_registry->get_all_registered();

		foreach ( $payment_methods as $payment_method ) {
			if ( ! $payment_method->is_active() ) {
				continue;
			}
			$script_handles = array_merge(
				$script_handles,
				is_admin() ? $payment_method->get_payment_method_script_handles_for_admin() : $payment_method->get_payment_method_script_handles()
			);
		}

		return array_unique( array_filter( $script_handles ) );
	}

	/**
	 * Adds data from payment methods to the asset registry.
	 */
	public function register_payment_method_data() {
		$payment_methods = $this->payment_method_registry->get_all_registered();

		foreach ( $payment_methods as $payment_method ) {
			if ( ! $payment_method->is_active() ) {
				continue;
			}
			$asset_data_key = $payment_method->get_name() . '_data';

			if ( ! $this->asset_registry->exists( $asset_data_key ) ) {
				$this->asset_registry->add( $asset_data_key, $payment_method->get_payment_method_data() );
			}
		}
	}
}
