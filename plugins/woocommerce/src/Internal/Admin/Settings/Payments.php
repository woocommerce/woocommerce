<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentExtensionSuggestions as ExtensionSuggestions;
use Exception;

defined( 'ABSPATH' ) || exit;
/**
 * Payments settings service class.
 */
class Payments {

	const USER_PAYMENTS_NOX_PROFILE_KEY = 'woocommerce_payments_nox_profile';

	const SUGGESTIONS_CONTEXT = 'wc_settings_payments';

	/**
	 * The payment providers service.
	 *
	 * @var PaymentProviders
	 */
	private PaymentProviders $providers;

	/**
	 * The payment extension suggestions service.
	 *
	 * @var ExtensionSuggestions
	 */
	private ExtensionSuggestions $extension_suggestions;

	/**
	 * Initialize the class instance.
	 *
	 * @param PaymentProviders     $payment_providers             The payment providers service.
	 * @param ExtensionSuggestions $payment_extension_suggestions The payment extension suggestions service.
	 *
	 * @internal
	 */
	final public function init( PaymentProviders $payment_providers, ExtensionSuggestions $payment_extension_suggestions ): void {
		$this->providers             = $payment_providers;
		$this->extension_suggestions = $payment_extension_suggestions;
	}

	/**
	 * Get the payment provider details list for the settings page.
	 *
	 * @param string $location The location for which the providers are being determined.
	 *                         This is a ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The payment providers details list.
	 * @throws Exception If there are malformed or invalid suggestions.
	 */
	public function get_payment_providers( string $location ): array {
		$payment_gateways = $this->providers->get_payment_gateways();
		$suggestions      = array();

		$providers_order_map = $this->providers->get_order_map();

		$payment_providers = array();

		// Only include suggestions if the requesting user can install plugins.
		if ( current_user_can( 'install_plugins' ) ) {
			$suggestions = $this->providers->get_extension_suggestions( $location, self::SUGGESTIONS_CONTEXT );
		}
		// If we have preferred suggestions, add them to the providers list.
		if ( ! empty( $suggestions['preferred'] ) ) {
			// Sort them by priority, ASC.
			usort(
				$suggestions['preferred'],
				function ( $a, $b ) {
					return $a['_priority'] <=> $b['_priority'];
				}
			);
			$added_to_top = 0;
			foreach ( $suggestions['preferred'] as $suggestion ) {
				$suggestion_order_map_id = $this->providers->get_suggestion_order_map_id( $suggestion['id'] );
				// Determine the suggestion's order value.
				// If we don't have an order for it, add it to the top but keep the relative order (PSP first, APM second).
				if ( ! isset( $providers_order_map[ $suggestion_order_map_id ] ) ) {
					$providers_order_map = Utils::order_map_add_at_order( $providers_order_map, $suggestion_order_map_id, $added_to_top );
					++$added_to_top;
				}

				// Change suggestion details to align it with a regular payment gateway.
				$suggestion['_suggestion_id'] = $suggestion['id'];
				$suggestion['id']             = $suggestion_order_map_id;
				$suggestion['_type']          = PaymentProviders::TYPE_SUGGESTION;
				$suggestion['_order']         = $providers_order_map[ $suggestion_order_map_id ];
				unset( $suggestion['_priority'] );

				$payment_providers[] = $suggestion;
			}
		}

		foreach ( $payment_gateways as $payment_gateway ) {
			// Determine the gateway's order value.
			// If we don't have an order for it, add it to the end.
			if ( ! isset( $providers_order_map[ $payment_gateway->id ] ) ) {
				$providers_order_map = Utils::order_map_add_at_order( $providers_order_map, $payment_gateway->id, count( $payment_providers ) );
			}

			$payment_providers[] = $this->providers->get_payment_gateway_details(
				$payment_gateway,
				$providers_order_map[ $payment_gateway->id ],
				$location
			);
		}

		// Add offline payment methods group entry if we have offline payment methods.
		if ( in_array( PaymentProviders::TYPE_OFFLINE_PM, array_column( $payment_providers, '_type' ), true ) ) {
			// Determine the item's order value.
			// If we don't have an order for it, add it to the end.
			if ( ! isset( $providers_order_map[ PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP ] ) ) {
				$providers_order_map = Utils::order_map_add_at_order( $providers_order_map, PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP, count( $payment_providers ) );
			}

			$payment_providers[] = array(
				'id'          => PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
				'_type'       => PaymentProviders::TYPE_OFFLINE_PMS_GROUP,
				'_order'      => $providers_order_map[ PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP ],
				'title'       => __( 'Take offline payments', 'woocommerce' ),
				'description' => __( 'Accept payments offline using multiple different methods. These can also be used to test purchases.', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/payment_methods/cod.svg', WC_PLUGIN_FILE ),
				// The offline PMs (and their group) are obviously from WooCommerce, and WC is always active.
				'plugin'      => array(
					'_type'  => 'wporg',
					'slug'   => 'woocommerce',
					'file'   => '', // This pseudo-provider should have no use for the plugin file.
					'status' => PaymentProviders::EXTENSION_ACTIVE,
				),
				'management'  => array(
					'_links' => array(
						'settings' => array(
							'href' => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=offline' ),
						),
					),
				),
			);
		}

		// Determine the final, standardized providers order map.
		$providers_order_map = $this->providers->enhance_order_map( $providers_order_map );
		// Enforce the order map on all providers, just in case.
		foreach ( $payment_providers as $key => $provider ) {
			$payment_providers[ $key ]['_order'] = $providers_order_map[ $provider['id'] ];
		}
		// NOTE: For now, save it back to the DB. This is temporary until we have a better way to handle this!
		$this->providers->save_order_map( $providers_order_map );

		// Sort the payment providers by order, ASC.
		usort(
			$payment_providers,
			function ( $a, $b ) {
				return $a['_order'] <=> $b['_order'];
			}
		);

		return $payment_providers;
	}

	/**
	 * Get the payment extension suggestions for the given location.
	 *
	 * @param string $location The location for which the suggestions are being fetched.
	 *
	 * @return array[] The payment extension suggestions for the given location, split into preferred and other.
	 * @throws Exception If there are malformed or invalid suggestions.
	 */
	public function get_payment_extension_suggestions( string $location ): array {
		return $this->providers->get_extension_suggestions( $location, self::SUGGESTIONS_CONTEXT );
	}

	/**
	 * Get the payment extension suggestions categories details.
	 *
	 * @return array The payment extension suggestions categories.
	 */
	public function get_payment_extension_suggestion_categories(): array {
		return $this->providers->get_extension_suggestion_categories();
	}

	/**
	 * Get the business location country code for the Payments settings.
	 *
	 * @return string The ISO 3166-1 alpha-2 country code to use for the overall business location.
	 *                If the user didn't set a location, the WC base location country code is used.
	 */
	public function get_country(): string {
		$user_nox_meta = get_user_meta( get_current_user_id(), self::USER_PAYMENTS_NOX_PROFILE_KEY, true );
		if ( ! empty( $user_nox_meta['business_country_code'] ) ) {
			return $user_nox_meta['business_country_code'];
		}

		return WC()->countries->get_base_country();
	}

	/**
	 * Set the business location country for the Payments settings.
	 *
	 * @param string $location The country code. This should be a ISO 3166-1 alpha-2 country code.
	 */
	public function set_country( string $location ): bool {
		$user_payments_nox_profile = get_user_meta( get_current_user_id(), self::USER_PAYMENTS_NOX_PROFILE_KEY, true );

		if ( empty( $user_payments_nox_profile ) ) {
			$user_payments_nox_profile = array();
		} else {
			$user_payments_nox_profile = maybe_unserialize( $user_payments_nox_profile );
		}
		$user_payments_nox_profile['business_country_code'] = $location;

		return false !== update_user_meta( get_current_user_id(), self::USER_PAYMENTS_NOX_PROFILE_KEY, $user_payments_nox_profile );
	}

	/**
	 * Update the payment providers order map.
	 *
	 * @param array $order_map The new order for payment providers.
	 *
	 * @return bool True if the payment providers ordering was successfully updated, false otherwise.
	 */
	public function update_payment_providers_order_map( array $order_map ): bool {
		return $this->providers->update_payment_providers_order_map( $order_map );
	}

	/**
	 * Hide a payment extension suggestion.
	 *
	 * @param string $id The ID of the payment extension suggestion to hide.
	 *
	 * @return bool True if the suggestion was successfully hidden, false otherwise.
	 * @throws Exception If the suggestion ID is invalid.
	 */
	public function hide_payment_extension_suggestion( string $id ): bool {
		return $this->providers->hide_extension_suggestion( $id );
	}

	/**
	 * Dismiss a payment extension suggestion incentive.
	 *
	 * @param string $suggestion_id The suggestion ID.
	 * @param string $incentive_id  The incentive ID.
	 * @param string $context       Optional. The context in which the incentive should be dismissed.
	 *                              Default is to dismiss the incentive in all contexts.
	 *
	 * @return bool True if the incentive was not previously dismissed and now it is.
	 *              False if the incentive was already dismissed or could not be dismissed.
	 * @throws Exception If the incentive could not be dismissed due to an error.
	 */
	public function dismiss_extension_suggestion_incentive( string $suggestion_id, string $incentive_id, string $context = 'all' ): bool {
		return $this->extension_suggestions->dismiss_incentive( $incentive_id, $suggestion_id, $context );
	}
}
