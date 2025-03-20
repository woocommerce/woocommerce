<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;

use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Utilities\ArrayUtil;
use WC_HTTPS;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * The payment gateway provider class to handle all payment gateways that don't have a dedicated class.
 *
 * Extend this class for introducing gateway-specific behavior.
 */
class PaymentGateway {

	/**
	 * Extract the payment gateway provider details from the object.
	 *
	 * @param WC_Payment_Gateway $gateway      The payment gateway object.
	 * @param int                $order        Optional. The order to assign.
	 *                                         Defaults to 0 if not provided.
	 * @param string             $country_code Optional. The country code for which the details are being gathered.
	 *                                         This should be a ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The payment gateway provider details.
	 */
	public function get_details( WC_Payment_Gateway $gateway, int $order = 0, string $country_code = '' ): array {
		$plugin_slug = $this->get_plugin_slug( $gateway );
		$plugin_file = $this->get_plugin_file( $gateway, $plugin_slug );

		return array(
			'id'          => $gateway->id,
			'_order'      => $order,
			'title'       => $this->get_title( $gateway ),
			'description' => $this->get_description( $gateway ),
			'icon'        => $this->get_icon( $gateway ),
			'supports'    => $this->get_supports_list( $gateway ),
			'state'       => array(
				'enabled'           => $this->is_enabled( $gateway ),
				'account_connected' => $this->is_account_connected( $gateway ),
				'needs_setup'       => $this->needs_setup( $gateway ),
				'test_mode'         => $this->is_in_test_mode( $gateway ),
				'dev_mode'          => $this->is_in_dev_mode( $gateway ),
			),
			'management'  => array(
				'_links' => array(
					'settings' => array(
						'href' => $this->get_settings_url( $gateway ),
					),
				),
			),
			'onboarding'  => array(
				'state'                       => array(
					'started'   => $this->is_onboarding_started( $gateway ),
					'completed' => $this->is_onboarding_completed( $gateway ),
					'test_mode' => $this->is_in_test_mode_onboarding( $gateway ),
				),
				'_links'                      => array(
					'onboard' => array(
						'href' => $this->get_onboarding_url( $gateway ),
					),
				),
				'recommended_payment_methods' => $this->get_recommended_payment_methods( $gateway, $country_code ),
			),
			'plugin'      => array(
				// Default to treating the payment gateway plugin as a WordPress.org plugin.
				'_type'  => PaymentProviders::EXTENSION_TYPE_WPORG,
				'slug'   => $plugin_slug,
				'file'   => $plugin_file,
				// The gateway underlying plugin is obviously active (aka the plugin code is running).
				'status' => PaymentProviders::EXTENSION_ACTIVE,
			),
		);
	}

	/**
	 * Get the provider title of the payment gateway.
	 *
	 * This is the intended gateway title to use throughout the WC admin. It should be short.
	 *
	 * Note: We don't allow HTML tags in the title. All HTML tags will be stripped, including their contents.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The provider title of the payment gateway.
	 */
	public function get_title( WC_Payment_Gateway $payment_gateway ): string {
		$title = wp_strip_all_tags( html_entity_decode( $payment_gateway->get_method_title() ?? '' ), true );

		// Truncate the title.
		return Utils::truncate_with_words( $title, 75 );
	}

	/**
	 * Get the provider description of the payment gateway.
	 *
	 * This is the intended gateway description to use throughout the WC admin. It should be short and to the point.
	 *
	 * Note: We don't allow HTML tags in the description. All HTML tags will be stripped, including their contents.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The provider description of the payment gateway.
	 */
	public function get_description( WC_Payment_Gateway $payment_gateway ): string {
		$description = wp_strip_all_tags( html_entity_decode( $payment_gateway->get_method_description() ?? '' ), true );

		// Truncate the description.
		return Utils::truncate_with_words( $description, 130, '…' );
	}

	/**
	 * Get the provider icon URL of the payment gateway.
	 *
	 * We expect to receive a URL to an image file.
	 * If the gateway provides an <img> tag or a list of them, we will fall back to the default payments icon.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The provider icon URL of the payment gateway.
	 */
	public function get_icon( WC_Payment_Gateway $payment_gateway ): string {
		$icon_url = $payment_gateway->icon ?? '';
		if ( ! is_string( $icon_url ) ) {
			$icon_url = '';
		}

		$icon_url = trim( $icon_url );

		// Test if it actually is a URL as some gateways put an <img> tag or a list of them.
		if ( ! wc_is_valid_url( $icon_url ) ) {
			// Fall back to the default payments icon.
			return plugins_url( 'assets/images/icons/default-payments.svg', WC_PLUGIN_FILE );
		}

		return WC_HTTPS::force_https_url( $icon_url );
	}

	/**
	 * Get the provider supports list of the payment gateway.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string[] The provider supports list of the payment gateway.
	 */
	public function get_supports_list( WC_Payment_Gateway $payment_gateway ): array {
		$supports_list = $payment_gateway->supports ?? array();
		if ( ! is_array( $supports_list ) ) {
			return array();
		}

		// Sanitize the list to ensure it only contains a list of key-like strings.
		$sanitized_list = array();
		foreach ( $supports_list as $support ) {
			if ( ! is_string( $support ) ) {
				continue;
			}

			$sanitized_list[] = sanitize_key( $support );
		}

		// Ensure the list contains unique values and re-indexed.
		return array_values( array_unique( $sanitized_list ) );
	}

	/**
	 * Check if the payment gateway is enabled.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is enabled, false otherwise.
	 */
	public function is_enabled( WC_Payment_Gateway $payment_gateway ): bool {
		return filter_var( $payment_gateway->enabled, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Check if the payment gateway needs setup.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway needs setup, false otherwise.
	 */
	public function needs_setup( WC_Payment_Gateway $payment_gateway ): bool {
		return filter_var( $payment_gateway->needs_setup(), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Try to determine if the payment gateway is in test mode.
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode, false otherwise.
	 */
	public function is_in_test_mode( WC_Payment_Gateway $payment_gateway ): bool {
		// Try various gateway methods to check if the payment gateway is in test mode.
		if ( is_callable( array( $payment_gateway, 'is_test_mode' ) ) ) {
			return filter_var( $payment_gateway->is_test_mode(), FILTER_VALIDATE_BOOLEAN );
		}
		if ( is_callable( array( $payment_gateway, 'is_in_test_mode' ) ) ) {
			return filter_var( $payment_gateway->is_in_test_mode(), FILTER_VALIDATE_BOOLEAN );
		}

		// Try various gateway option entries to check if the payment gateway is in test mode.
		if ( is_callable( array( $payment_gateway, 'get_option' ) ) ) {
			$test_mode = filter_var( $payment_gateway->get_option( 'test_mode', 'not_found' ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
			if ( ! is_null( $test_mode ) ) {
				return $test_mode;
			}

			$test_mode = filter_var( $payment_gateway->get_option( 'testmode', 'not_found' ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
			if ( ! is_null( $test_mode ) ) {
				return $test_mode;
			}
		}

		return false;
	}

	/**
	 * Try to determine if the payment gateway is in dev mode.
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in dev mode, false otherwise.
	 */
	public function is_in_dev_mode( WC_Payment_Gateway $payment_gateway ): bool {
		// Try various gateway methods to check if the payment gateway is in dev mode.
		if ( is_callable( array( $payment_gateway, 'is_dev_mode' ) ) ) {
			return filter_var( $payment_gateway->is_dev_mode(), FILTER_VALIDATE_BOOLEAN );
		}
		if ( is_callable( array( $payment_gateway, 'is_in_dev_mode' ) ) ) {
			return filter_var( $payment_gateway->is_in_dev_mode(), FILTER_VALIDATE_BOOLEAN );
		}

		return false;
	}

	/**
	 * Check if the payment gateway has a payments processor account connected.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway account is connected, false otherwise.
	 *              If the payment gateway does not provide the information, it will return true.
	 */
	public function is_account_connected( WC_Payment_Gateway $payment_gateway ): bool {
		if ( is_callable( array( $payment_gateway, 'is_account_connected' ) ) ) {
			return filter_var( $payment_gateway->is_account_connected(), FILTER_VALIDATE_BOOLEAN );
		}

		if ( is_callable( array( $payment_gateway, 'is_connected' ) ) ) {
			return filter_var( $payment_gateway->is_connected(), FILTER_VALIDATE_BOOLEAN );
		}

		// Fall back to assuming that it is connected. This is the safest option.
		return true;
	}

	/**
	 * Check if the payment gateway has started the onboarding process.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway has started the onboarding process, false otherwise.
	 *              If the payment gateway does not provide the information,
	 *              it will infer it from having a connected account.
	 */
	public function is_onboarding_started( WC_Payment_Gateway $payment_gateway ): bool {
		if ( is_callable( array( $payment_gateway, 'is_onboarding_started' ) ) ) {
			return filter_var( $payment_gateway->is_onboarding_started(), FILTER_VALIDATE_BOOLEAN );
		}

		// Fall back to inferring this from having a connected account.
		return $this->is_account_connected( $payment_gateway );
	}

	/**
	 * Check if the payment gateway has completed the onboarding process.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway has completed the onboarding process, false otherwise.
	 *              If the payment gateway does not provide the information,
	 *              it will infer it from having a connected account.
	 */
	public function is_onboarding_completed( WC_Payment_Gateway $payment_gateway ): bool {
		// Sanity check: If the onboarding has not started, it cannot be completed.
		if ( ! $this->is_onboarding_started( $payment_gateway ) ) {
			return false;
		}

		if ( is_callable( array( $payment_gateway, 'is_onboarding_completed' ) ) ) {
			return filter_var( $payment_gateway->is_onboarding_completed(), FILTER_VALIDATE_BOOLEAN );
		}

		// Note: This is what WooPayments provides, but it should become standard.
		if ( is_callable( array( $payment_gateway, 'is_account_partially_onboarded' ) ) ) {
			return ! filter_var( $payment_gateway->is_account_partially_onboarded(), FILTER_VALIDATE_BOOLEAN );
		}

		// Fall back to inferring this from having a connected account.
		return $this->is_account_connected( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in test mode onboarding (aka sandbox or test-drive).
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode onboarding, false otherwise.
	 */
	public function is_in_test_mode_onboarding( WC_Payment_Gateway $payment_gateway ): bool {
		// Try various gateway methods to check if the payment gateway is in test mode onboarding.
		if ( is_callable( array( $payment_gateway, 'is_test_mode_onboarding' ) ) ) {
			return filter_var( $payment_gateway->is_test_mode_onboarding(), FILTER_VALIDATE_BOOLEAN );
		}
		if ( is_callable( array( $payment_gateway, 'is_in_test_mode_onboarding' ) ) ) {
			return filter_var( $payment_gateway->is_in_test_mode_onboarding(), FILTER_VALIDATE_BOOLEAN );
		}

		return false;
	}

	/**
	 * Get the settings URL for a payment gateway.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The settings URL for the payment gateway.
	 */
	public function get_settings_url( WC_Payment_Gateway $payment_gateway ): string {
		if ( is_callable( array( $payment_gateway, 'get_settings_url' ) ) ) {
			return $payment_gateway->get_settings_url();
		}

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $payment_gateway->id ) );
	}

	/**
	 * Get the onboarding URL for the payment gateway.
	 *
	 * This URL should start or continue the onboarding process.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 * @param string             $return_url      Optional. The URL to return to after onboarding.
	 *                                            This will likely get attached to the onboarding URL.
	 *
	 * @return string The onboarding URL for the payment gateway.
	 */
	public function get_onboarding_url( WC_Payment_Gateway $payment_gateway, string $return_url = '' ): string {
		if ( is_callable( array( $payment_gateway, 'get_connection_url' ) ) ) {
			// If we received no return URL, we will set the WC Payments Settings page as the return URL.
			$return_url = ! empty( $return_url ) ? $return_url : admin_url( 'admin.php?page=wc-settings&tab=checkout' );

			return $payment_gateway->get_connection_url( $return_url );
		}

		// Fall back to pointing users to the payment gateway settings page to handle onboarding.
		return $this->get_settings_url( $payment_gateway );
	}

	/**
	 * Get the source plugin slug of a payment gateway instance.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The plugin slug of the payment gateway.
	 */
	public function get_plugin_slug( WC_Payment_Gateway $payment_gateway ): string {
		// If the payment gateway object has a `plugin_slug` property, use it.
		// This is useful for testing.
		if ( isset( $payment_gateway->plugin_slug ) ) {
			return $payment_gateway->plugin_slug;
		}

		try {
			$reflector = new \ReflectionClass( get_class( $payment_gateway ) );
		} catch ( \ReflectionException $e ) {
			// Bail if we can't get the class details.
			return '';
		}

		$gateway_class_filename = $reflector->getFileName();
		// Determine the gateway's plugin directory from the class path.
		$gateway_class_path = trim( dirname( plugin_basename( $gateway_class_filename ) ), DIRECTORY_SEPARATOR );
		if ( false === strpos( $gateway_class_path, DIRECTORY_SEPARATOR ) ) {
			// The gateway class file is in the root of the plugin's directory.
			$plugin_slug = $gateway_class_path;
		} else {
			$plugin_slug = explode( DIRECTORY_SEPARATOR, $gateway_class_path )[0];
		}

		return $plugin_slug;
	}

	/**
	 * Get the plugin file of payment gateway, without the .php extension.
	 *
	 * This is useful for the WP API, which expects the plugin file without the .php extension.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 * @param string             $plugin_slug     Optional. The payment gateway plugin slug to use directly.
	 *
	 * @return string The plugin file corresponding to the payment gateway plugin. Does not include the .php extension.
	 */
	public function get_plugin_file( WC_Payment_Gateway $payment_gateway, string $plugin_slug = '' ): string {
		// If the payment gateway object has a `plugin_file` property, use it.
		// This is useful for testing.
		if ( isset( $payment_gateway->plugin_file ) ) {
			$plugin_file = $payment_gateway->plugin_file;
			// Remove the .php extension from the file path. The WP API expects it without it.
			if ( ! empty( $plugin_file ) && str_ends_with( $plugin_file, '.php' ) ) {
				$plugin_file = substr( $plugin_file, 0, - 4 );
			}

			return $plugin_file;
		}

		if ( empty( $plugin_slug ) ) {
			$plugin_slug = $this->get_plugin_slug( $payment_gateway );
		}

		// Bail if we couldn't determine the plugin slug.
		if ( empty( $plugin_slug ) ) {
			return '';
		}

		$plugin_file = PluginsHelper::get_plugin_path_from_slug( $plugin_slug );
		// Remove the .php extension from the file path. The WP API expects it without it.
		if ( ! empty( $plugin_file ) && str_ends_with( $plugin_file, '.php' ) ) {
			$plugin_file = substr( $plugin_file, 0, - 4 );
		}

		return $plugin_file;
	}

	/**
	 * Try and determine a list of recommended payment methods for a payment gateway.
	 *
	 * This data is not always available, and it is up to the payment gateway to provide it.
	 * This is not a definitive list of payment methods that the gateway supports.
	 * The data is aimed at helping the user understand what payment methods are recommended for the gateway
	 * and potentially help them make a decision on which payment methods to enable.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 * @param string             $country_code    Optional. The country code for which to get recommended payment methods.
	 *                                            This should be a ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The recommended payment methods list for the payment gateway.
	 *               Empty array if there are none.
	 */
	public function get_recommended_payment_methods( WC_Payment_Gateway $payment_gateway, string $country_code = '' ): array {
		// Bail if the payment gateway does not implement the method.
		if ( ! is_callable( array( $payment_gateway, 'get_recommended_payment_methods' ) ) ) {
			return array();
		}

		// Get the "raw" recommended payment methods from the payment gateway.
		$recommended_pms = call_user_func_array(
			array( $payment_gateway, 'get_recommended_payment_methods' ),
			array( 'country_code' => $country_code ),
		);

		// Validate the received list items.
		$recommended_pms = array_filter(
			$recommended_pms,
			array( $this, 'validate_recommended_payment_method' )
		);

		// Sort the list.
		$recommended_pms = $this->sort_recommended_payment_methods( $recommended_pms );

		// Extract, standardize, and sanitize the details for each recommended payment method.
		$standardized_pms = array();
		foreach ( $recommended_pms as $index => $recommended_pm ) {
			// Use the index as the order since we sorted (and normalized) the list earlier.
			$standardized_pms[] = $this->standardize_recommended_payment_method( $recommended_pm, $index );
		}

		return $standardized_pms;
	}

	/**
	 * Validate a recommended payment method entry.
	 *
	 * @param mixed $recommended_pm The recommended payment method entry to validate.
	 *
	 * @return bool True if the recommended payment method entry is valid, false otherwise.
	 */
	protected function validate_recommended_payment_method( $recommended_pm ): bool {
		// We require at least `id` and `title`.
		return is_array( $recommended_pm ) &&
				! empty( $recommended_pm['id'] ) &&
				! empty( $recommended_pm['title'] );
	}

	/**
	 * Sort the recommended payment methods.
	 *
	 * @param array $recommended_pms The recommended payment methods list to sort.
	 *
	 * @return array The sorted recommended payment methods list.
	 *               List keys are not preserved.
	 */
	protected function sort_recommended_payment_methods( array $recommended_pms ): array {
		// Sort the recommended payment methods by order/priority, if available.
		usort(
			$recommended_pms,
			function ( $a, $b ) {
				// `order` takes precedence over `priority`.
				// Entries that don't have the order/priority are placed at the end.
				return array( ( $a['order'] ?? PHP_INT_MAX ), ( $a['priority'] ?? PHP_INT_MAX ) ) <=> array( ( $b['order'] ?? PHP_INT_MAX ), ( $b['priority'] ?? PHP_INT_MAX ) );
			}
		);

		return array_values( $recommended_pms );
	}

	/**
	 * Standardize a recommended payment method entry.
	 *
	 * @param array $recommended_pm The recommended payment method entry to standardize.
	 * @param int   $order          Optional. The order of the recommended payment method.
	 *                              Defaults to 0 if not provided.
	 *
	 * @return array The standardized recommended payment method entry.
	 */
	protected function standardize_recommended_payment_method( array $recommended_pm, int $order = 0 ): array {
		$standard_details = array(
			'id'          => sanitize_key( $recommended_pm['id'] ),
			'_order'      => $order,
			'enabled'     => (bool) ( $recommended_pm['enabled'] ?? true ), // Default to enabled if not explicit.
			'required'    => (bool) ( $recommended_pm['required'] ?? false ), // Default to not required if not explicit.
			'title'       => sanitize_text_field( $recommended_pm['title'] ),
			'description' => '',
			'icon'        => '',
		);

		// If the payment method has a description, sanitize it before use.
		if ( ! empty( $recommended_pm['description'] ) ) {
			$standard_details['description'] = $recommended_pm['description'];
			// Make sure that if we have HTML tags, we only allow stylistic tags and anchors.
			if ( preg_match( '/<[^>]+>/', $standard_details['description'] ) ) {
				// Only allow stylistic tags with a few modifications.
				$allowed_tags = wp_kses_allowed_html( 'data' );
				$allowed_tags = array_merge(
					$allowed_tags,
					array(
						'a' => array(
							'href'   => true,
							'target' => true,
						),
					)
				);

				$standard_details['description'] = wp_kses( $standard_details['description'], $allowed_tags );
			}
		}

		// If the payment method has an icon, try to use it.
		if ( ! empty( $recommended_pm['icon'] ) && wc_is_valid_url( $recommended_pm['icon'] ) ) {
			$standard_details['icon'] = sanitize_url( $recommended_pm['icon'] );
		}

		return $standard_details;
	}
}
