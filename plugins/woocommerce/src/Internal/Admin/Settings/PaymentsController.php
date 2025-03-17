<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentExtensionSuggestions;
use Exception;
use WooCommerce\Admin\Experimental_Abtest;

defined( 'ABSPATH' ) || exit;
/**
 * Payments settings controller class.
 *
 * Use this class for hooks and actions related to the Payments settings page.
 */
class PaymentsController {

	/**
	 * The payment service.
	 *
	 * @var Payments
	 */
	private Payments $payments;

	/**
	 * Register hooks.
	 */
	public function register() {
		// Filter the feature config to allow the experiment to have effect.
		// Use a priority of 9 to ensure that the filter runs before the user-set feature values
		// are applied by the WC Beta Tester plugin.
		// This way we allow users to control the feature flag via the WC Beta Tester plugin and disregard the experiment.
		// @see plugins/woocommerce-beta-tester/plugin.php.
		add_filter( 'woocommerce_admin_get_feature_config', array( $this, 'filter_feature_config_experiment' ), 9 );

		// Because we gate the hooking based on a feature flag,
		// we need to delay the registration until the 'woocommerce_init' hook.
		// Otherwise, we end up in an infinite loop.
		add_action( 'woocommerce_init', array( $this, 'delayed_register' ) );
	}

	/**
	 * Filter the feature flags list to modify the new Payments Settings page feature based on the experiment.
	 *
	 * @param array $features The feature flags list.
	 *
	 * @return array The updated feature flags list.
	 */
	public function filter_feature_config_experiment( $features ) {
		// If the feature flag is not present or has been disabled, don't do anything.
		if ( empty( $features['reactify-classic-payments-settings'] ) ) {
			return $features;
		}

		// Transient key to handle the experiment failure.
		$transient_key = 'wc_experiment_failure_woocommerce_payment_settings_2025_v2';

		// Try to get cached result first.
		$cached_result = get_transient( $transient_key );

		// If we have a cache entry that indicates an error, disable the feature.
		if ( 'error' === $cached_result ) {
			return array_merge(
				$features,
				array(
					'reactify-classic-payments-settings' => false,
				)
			);
		}

		try {
			$in_treatment = Experimental_Abtest::in_treatment( 'woocommerce_payment_settings_2025_v2' );
		} catch ( \Exception $e ) {
			// If the experiment fails, set a transient to avoid repeated failures and set the flag to false.
			$in_treatment = false;
			set_transient( $transient_key, 'error', HOUR_IN_SECONDS );
		}

		// If the feature flag is enabled, but the user is NOT in the experiment treatment group, disable the feature.
		if ( ! $in_treatment ) {
			return array_merge(
				$features,
				array(
					'reactify-classic-payments-settings' => false,
				)
			);
		}

		return $features;
	}

	/**
	 * Delayed hook registration.
	 */
	public function delayed_register() {
		// Don't do anything if the feature is not enabled.
		if ( ! Features::is_enabled( 'reactify-classic-payments-settings' ) ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_filter( 'woocommerce_admin_shared_settings', array( $this, 'preload_settings' ) );
		add_filter( 'woocommerce_admin_allowed_promo_notes', array( $this, 'add_allowed_promo_notes' ) );
	}

	/**
	 * Initialize the class instance.
	 *
	 * @param Payments $payments The payments service.
	 *
	 * @internal
	 */
	final public function init( Payments $payments ): void {
		$this->payments = $payments;
	}

	/**
	 * Adds the Payments top-level menu item.
	 */
	public function add_menu() {
		global $menu;

		// The WooPayments plugin must not be active.
		// When active, WooPayments will own the Payments menu item since it is the native Woo payments solution.
		if ( $this->is_woopayments_active() ) {
			return;
		}

		$menu_title = esc_html__( 'Payments', 'woocommerce' );
		$menu_icon  = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4NTIiIGhlaWdodD0iNjg0Ij48cGF0aCBmaWxsPSIjYTJhYWIyIiBkPSJNODIgODZ2NTEyaDY4NFY4NlptMCA1OThjLTQ4IDAtODQtMzgtODQtODZWODZDLTIgMzggMzQgMCA4MiAwaDY4NGM0OCAwIDg0IDM4IDg0IDg2djUxMmMwIDQ4LTM2IDg2LTg0IDg2em0zODQtNTU2djQ0aDg2djg0SDM4MnY0NGgxMjhjMjQgMCA0MiAxOCA0MiA0MnYxMjhjMCAyNC0xOCA0Mi00MiA0MmgtNDR2NDRoLTg0di00NGgtODZ2LTg0aDE3MHYtNDRIMzM4Yy0yNCAwLTQyLTE4LTQyLTQyVjIxNGMwLTI0IDE4LTQyIDQyLTQyaDQ0di00NHoiLz48L3N2Zz4=';
		// Link to the Payments settings page.
		$menu_path = 'admin.php?page=wc-settings&tab=checkout';

		add_menu_page(
			$menu_title,
			$menu_title,
			'manage_woocommerce', // Capability required to see the menu item.
			$menu_path,
			null,
			$menu_icon,
			56, // Position after WooCommerce Product menu item.
		);

		// If there are providers with active incentive, add a notice badge to the Payments menu item.
		if ( $this->store_has_providers_with_incentive() ) {
			$badge = ' <span class="wcpay-menu-badge awaiting-mod count-1"><span class="plugin-count">1</span></span>';
			foreach ( $menu as $index => $menu_item ) {
				// Only add the badge markup if not already present and the menu item is the Payments menu item.
				if ( 0 === strpos( $menu_item[0], $menu_title )
					&& $menu_path === $menu_item[2]
					&& false === strpos( $menu_item[0], $badge ) ) {

					$menu[ $index ][0] .= $badge; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

					// One menu item with a badge is more than enough.
					break;
				}
			}
		}
	}

	/**
	 * Preload settings to make them available to the Payments settings page frontend logic.
	 *
	 * Added keys will be available in the window.wcSettings.admin object.
	 *
	 * @param array $settings The settings array.
	 *
	 * @return array Settings array with additional settings added.
	 */
	public function preload_settings( array $settings ): array {
		// We only preload settings in the WP admin.
		if ( ! is_admin() ) {
			return $settings;
		}

		// Add the business location country to the settings.
		if ( ! isset( $settings[ Payments::USER_PAYMENTS_NOX_PROFILE_KEY ] ) ) {
			$settings[ Payments::USER_PAYMENTS_NOX_PROFILE_KEY ] = array();
		}
		$settings[ Payments::USER_PAYMENTS_NOX_PROFILE_KEY ]['business_country_code'] = $this->payments->get_country();

		return $settings;
	}

	/**
	 * Adds promo note IDs to the list of allowed ones.
	 *
	 * @param array $promo_notes Allowed promo note IDs.
	 *
	 * @return array The updated list of allowed promo note IDs.
	 */
	public function add_allowed_promo_notes( array $promo_notes = array() ): array {
		try {
			$providers = $this->payments->get_payment_providers( $this->payments->get_country() );
		} catch ( Exception $e ) {
			// In case of an error, bail.
			return $promo_notes;
		}

		// Add all incentive promo IDs to the allowed promo notes list.
		foreach ( $providers as $provider ) {
			if ( ! empty( $provider['_incentive']['promo_id'] ) ) {
				$promo_notes[] = $provider['_incentive']['promo_id'];
			}
		}

		return $promo_notes;
	}

	/**
	 * Check if the store has any enabled gateways (including offline payment methods).
	 *
	 * @return bool True if the store has any enabled gateways, false otherwise.
	 */
	private function store_has_enabled_gateways(): bool {
		$gateways         = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways = array_filter(
			$gateways,
			function ( $gateway ) {
				return 'yes' === $gateway->enabled;
			}
		);

		return ! empty( $enabled_gateways );
	}

	/**
	 * Check if the store has any payment providers that have an active incentive.
	 *
	 * @return bool True if the store has providers with an active incentive.
	 */
	private function store_has_providers_with_incentive(): bool {
		try {
			$providers = $this->payments->get_payment_providers( $this->payments->get_country() );
		} catch ( Exception $e ) {
			// In case of an error, just return false.
			return false;
		}

		// Go through the providers and check if any of them have a "prominently" visible incentive (i.e., modal or banner).
		foreach ( $providers as $provider ) {
			// We check to see if the incentive was dismissed in the banner context.
			// In case an incentive uses the modal surface also (like the WooPayments Switch incentive),
			// we rely on the fact that the modal falls back to the banner, once dismissed.
			if ( ! empty( $provider['_incentive'] ) &&
				( empty( $provider['_incentive']['_dismissals'] ) ||
					! in_array( 'wc_settings_payments__banner', $provider['_incentive']['_dismissals'], true )
				)
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the WooPayments plugin is active.
	 *
	 * @return boolean
	 */
	private function is_woopayments_active(): bool {
		return class_exists( '\WC_Payments' );
	}
}
