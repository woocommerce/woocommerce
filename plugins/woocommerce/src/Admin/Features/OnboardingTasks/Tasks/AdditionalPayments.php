<?php


namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\Init;

/**
 * Payments Task
 */
class AdditionalPayments extends Payments {

	/**
	 * Used to cache is_complete() method result.
	 *
	 * @var null
	 */
	private $is_complete_result = null;

	/**
	 * Used to cache can_view() method result.
	 *
	 * @var null
	 */
	private $can_view_result = null;


	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'payments';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __(
			'Set up additional payment options',
			'woocommerce'
		);
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			'Choose payment providers and enable payment methods at checkout.',
			'woocommerce'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '2 minutes', 'woocommerce' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		if ( null === $this->is_complete_result ) {
			$this->is_complete_result = self::has_enabled_additional_gateways();
		}

		return $this->is_complete_result;
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		// Go ahead if either of the features are enabled.
		// If the payment-gateway-suggestions are disabled,
		// we are still good to go because we can use the default suggestions.
		if ( ! Features::is_enabled( 'reactify-classic-payments-settings' ) &&
			! Features::is_enabled( 'payment-gateway-suggestions' ) ) {
			// Hide task if both features are not enabled.
			return false;
		}

		if ( null !== $this->can_view_result ) {
			return $this->can_view_result;
		}

		// Always show task if the React-based Payments settings page is enabled and
		// there are any gateways enabled (i.e. the Payments task is complete).
		if ( Features::is_enabled( 'reactify-classic-payments-settings' ) &&
			self::has_gateways() ) {
			return true;
		}

		// Show task if WooPayments is connected or if there are any suggested gateways in other category enabled.
		// Note: For now, we rely on the old Payment Gateways Suggestions lists to determine the visibility of this task.
		// This will need to be updated to use the new Payment Extension Suggestions/ Payments Providers system.
		$this->can_view_result = (
			WooCommercePayments::is_connected() ||
			self::has_enabled_other_category_gateways()
		);

		// Early return if task is not visible.
		if ( ! $this->can_view_result ) {
			return false;
		}

		// Show task if there are any suggested gateways in additional category.
		$this->can_view_result = ! empty( self::get_suggestion_gateways( 'category_additional' ) );

		return $this->can_view_result;
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		// If the React-based Payments settings page is enabled, link to the new Payments settings page.
		if ( Features::is_enabled( 'reactify-classic-payments-settings' ) ) {
			// We auto-expand the "Other" section to show the additional payment methods.
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&other_pes_section=expanded' );
		}

		// Otherwise, link to the Payments task page.
		return admin_url( 'admin.php?page=wc-admin&task=payments' );
	}

	/**
	 * Check if the store has any enabled gateways in other category.
	 *
	 * @return bool
	 */
	private static function has_enabled_other_category_gateways() {
		$other_gateways     = self::get_suggestion_gateways( 'category_other' );
		$other_gateways_ids = wp_list_pluck( $other_gateways, 'id' );

		return self::has_enabled_gateways(
			function( $gateway ) use ( $other_gateways_ids ) {
				return in_array( $gateway->id, $other_gateways_ids, true );
			}
		);
	}

	/**
	 * Check if the store has any enabled gateways in additional category.
	 *
	 * @return bool
	 */
	private static function has_enabled_additional_gateways() {
		$additional_gateways     = self::get_suggestion_gateways( 'category_additional' );
		$additional_gateways_ids = wp_list_pluck( $additional_gateways, 'id' );

		return self::has_enabled_gateways(
			function( $gateway ) use ( $additional_gateways_ids ) {
				return 'yes' === $gateway->enabled
				&& in_array( $gateway->id, $additional_gateways_ids, true );
			}
		);
	}

	/**
	 * Check if the store has any enabled gateways based on the given criteria.
	 *
	 * @param callable|null $filter A callback function to filter the gateways.
	 * @return bool
	 */
	private static function has_enabled_gateways( $filter = null ) {
		$gateways         = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways = array_filter(
			$gateways,
			function( $gateway ) use ( $filter ) {
				if ( is_callable( $filter ) ) {
					return 'yes' === $gateway->enabled && call_user_func( $filter, $gateway );
				} else {
					return 'yes' === $gateway->enabled;
				}
			}
		);

		return ! empty( $enabled_gateways );
	}

	/**
	 * Get the list of gateways to suggest.
	 *
	 * @param string $filter_by Filter by category. "category_additional" or "category_other".
	 *
	 * @return array
	 */
	private static function get_suggestion_gateways( $filter_by = 'category_additional' ) {
		$country            = wc_get_base_location()['country'];
		$plugin_suggestions = Init::get_cached_or_default_suggestions();
		$plugin_suggestions = array_filter(
			$plugin_suggestions,
			function( $plugin ) use ( $country, $filter_by ) {
				if ( ! isset( $plugin->{$filter_by} ) || ! isset( $plugin->plugins[0] ) ) {
					return false;
				}
				return in_array( $country, $plugin->{$filter_by}, true );
			}
		);
		return $plugin_suggestions;
	}
}
