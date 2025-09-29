<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Throwable;
use WC_HTTPS;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * The payment gateway provider class to handle all payment gateways that don't have a dedicated class.
 *
 * Extend this class for introducing gateway-specific behavior.
 */
class PaymentGateway {

	// This is the default onboarding type for all gateways.
	// It means that the payment extension will handle the onboarding.
	const ONBOARDING_TYPE_EXTERNAL = 'external';

	// This is the onboarding type for gateways that have a WooCommerce-tailored onboarding flow.
	// This might mean just having the payment methods select step in the WooCommerce settings.
	const ONBOARDING_TYPE_NATIVE = 'native';

	// This is the onboarding type for gateways that have a WooCommerce in-context onboarding flow.
	const ONBOARDING_TYPE_NATIVE_IN_CONTEXT = 'native_in_context';

	// Payment method categories to inform the UI about grouping or the emphasis of payment methods.
	const PAYMENT_METHOD_CATEGORY_PRIMARY   = 'primary';
	const PAYMENT_METHOD_CATEGORY_SECONDARY = 'secondary';

	/**
	 * Extract the payment gateway provider details from the object.
	 *
	 * @param WC_Payment_Gateway $gateway      The payment gateway object.
	 * @param int                $order        Optional. The order to assign.
	 *                                         Defaults to 0 if not provided.
	 * @param string             $country_code Optional. The country code for which the details are being gathered.
	 *                                         This should be an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The payment gateway provider details.
	 */
	public function get_details( WC_Payment_Gateway $gateway, int $order = 0, string $country_code = '' ): array {
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
				'type'                        => self::ONBOARDING_TYPE_EXTERNAL,
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
			'plugin'      => $this->get_plugin_details( $gateway ),
		);
	}

	/**
	 * Enhance this provider's payment extension suggestion with additional information.
	 *
	 * The details added do not require the payment extension to be active or a gateway instance.
	 *
	 * @param array $extension_suggestion The extension suggestion details.
	 *
	 * @return array The enhanced payment extension suggestion details.
	 */
	public function enhance_extension_suggestion( array $extension_suggestion ): array {
		if ( empty( $extension_suggestion['onboarding'] ) || ! is_array( $extension_suggestion['onboarding'] ) ) {
			$extension_suggestion['onboarding'] = array();
		}

		if ( ! isset( $extension_suggestion['onboarding']['type'] ) ) {
			$extension_suggestion['onboarding']['type'] = self::ONBOARDING_TYPE_EXTERNAL;
		}

		return $extension_suggestion;
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
		$title = $payment_gateway->get_method_title();
		// If we still couldn't get the WC admin title, fall back to the main title.
		if ( ! is_string( $title ) || empty( $title ) ) {
			$title = $payment_gateway->get_title();
		}
		// If we still couldn't get the title, return a default value.
		if ( ! is_string( $title ) || empty( $title ) ) {
			return esc_html__( 'Unknown', 'woocommerce' );
		}

		// No HTML tags allowed in the title.
		$title = wp_strip_all_tags( html_entity_decode( $title, ENT_QUOTES | ENT_SUBSTITUTE ), true );

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
		$description = $payment_gateway->get_method_description();
		// If we couldn't get the WC admin description, fall back to the main description.
		if ( ! is_string( $description ) || empty( $description ) ) {
			$description = $payment_gateway->get_description();
		}
		// If we still couldn't get the description, use an empty string since the description is not critical.
		if ( ! is_string( $description ) || empty( $description ) ) {
			return '';
		}

		// No HTML tags allowed in the description.
		$description = wp_strip_all_tags( html_entity_decode( $description, ENT_QUOTES | ENT_SUBSTITUTE ), true );

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
		if ( ! is_string( $icon_url ) || empty( $icon_url ) ) {
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
		try {
			return wc_string_to_bool( $payment_gateway->enabled ?? 'no' );
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway is enabled: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		// If we reach here, just assume that the gateway is not enabled.
		return false;
	}

	/**
	 * Check if the payment gateway needs setup.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway needs setup, false otherwise.
	 */
	public function needs_setup( WC_Payment_Gateway $payment_gateway ): bool {
		try {
			$needs_setup = wc_string_to_bool( $payment_gateway->needs_setup() );
			// If we get a true value, it means the gateway needs setup.
			if ( $needs_setup ) {
				return true;
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway needs setup: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		// If we get a false value, it might mean that it doesn't need setup,
		// but it can also mean that the gateway does not provide the information and just falls back to the default.
		// Check if there is a connected account, as that is the most common indicator of a setup.
		if ( ! $this->is_account_connected( $payment_gateway ) ) {
			return true;
		}

		// If we reach here, just assume that the gateway does not need setup.
		return false;
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
		try {
			// Try various gateway methods to check if the payment gateway is in test mode.
			if ( is_callable( array( $payment_gateway, 'is_test_mode' ) ) ) {
				return wc_string_to_bool( $payment_gateway->is_test_mode() );
			}
			if ( is_callable( array( $payment_gateway, 'is_in_test_mode' ) ) ) {
				return wc_string_to_bool( $payment_gateway->is_in_test_mode() );
			}

			// Try various gateway public properties to check if the payment gateway is in test mode.
			if ( isset( $payment_gateway->testmode ) ) {
				return wc_string_to_bool( $payment_gateway->testmode );
			}
			if ( isset( $payment_gateway->test_mode ) ) {
				return wc_string_to_bool( $payment_gateway->test_mode );
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

				$mode = strtolower( (string) $payment_gateway->get_option( 'mode', 'not_found' ) );
				if ( in_array( $mode, array( 'test', 'sandbox', 'dev' ), true ) ) {
					return true;
				} elseif ( in_array( $mode, array( 'live', 'production', 'prod' ), true ) ) {
					return false;
				}
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway is in test mode: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
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
		try {
			// Try various gateway methods to check if the payment gateway is in dev mode.
			if ( is_callable( array( $payment_gateway, 'is_dev_mode' ) ) ) {
				return wc_string_to_bool( $payment_gateway->is_dev_mode() );
			}
			if ( is_callable( array( $payment_gateway, 'is_in_dev_mode' ) ) ) {
				return wc_string_to_bool( $payment_gateway->is_in_dev_mode() );
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway is in dev mode: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		return false;
	}

	/**
	 * Check if the payment gateway has a payments processor account connected.
	 *
	 * Note: Be extra careful if you override this method and rely on needs_setup() since it could lead to an infinite loop.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway account is connected, false otherwise.
	 *              If the payment gateway does not provide the information, it will return true.
	 */
	public function is_account_connected( WC_Payment_Gateway $payment_gateway ): bool {
		try {
			if ( is_callable( array( $payment_gateway, 'is_account_connected' ) ) ) {
				return wc_string_to_bool( $payment_gateway->is_account_connected() );
			}

			if ( is_callable( array( $payment_gateway, 'is_connected' ) ) ) {
				return wc_string_to_bool( $payment_gateway->is_connected() );
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway account is connected: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
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
		try {
			if ( is_callable( array( $payment_gateway, 'is_onboarding_started' ) ) ) {
				return wc_string_to_bool( $payment_gateway->is_onboarding_started() );
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway onboarding started: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
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

		try {
			if ( is_callable( array( $payment_gateway, 'is_onboarding_completed' ) ) ) {
				return wc_string_to_bool( $payment_gateway->is_onboarding_completed() );
			}

			// Note: This is what WooPayments provides, but it should become standard.
			if ( is_callable( array( $payment_gateway, 'is_account_partially_onboarded' ) ) ) {
				return ! wc_string_to_bool( $payment_gateway->is_account_partially_onboarded() );
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway onboarding is completed: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		// Fall back to inferring this from having a connected account.
		return $this->is_account_connected( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in test mode onboarding (aka sandbox).
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode onboarding, false otherwise.
	 */
	public function is_in_test_mode_onboarding( WC_Payment_Gateway $payment_gateway ): bool {
		try {
			// Try various gateway methods to check if the payment gateway is in test mode onboarding.
			if ( is_callable( array( $payment_gateway, 'is_test_mode_onboarding' ) ) ) {
				return wc_string_to_bool( $payment_gateway->is_test_mode_onboarding() );
			}
			if ( is_callable( array( $payment_gateway, 'is_in_test_mode_onboarding' ) ) ) {
				return wc_string_to_bool( $payment_gateway->is_in_test_mode_onboarding() );
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway is in test mode onboarding: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
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
		try {
			if ( is_callable( array( $payment_gateway, 'get_settings_url' ) ) ) {
				$url = trim( (string) $payment_gateway->get_settings_url() );
				if ( ! empty( $url ) && ! wc_is_valid_url( $url ) ) {
					// Back-compat: normalize common relative admin URLs.
					$url = ltrim( $url, '/' );
					// Remove the '/wp-admin/' prefix if it exists.
					if ( 0 === strpos( $url, 'wp-admin/' ) ) {
						$url = substr( $url, strlen( 'wp-admin/' ) );
					}
					if ( 0 === strpos( $url, 'admin.php' ) || 0 === strpos( $url, '/admin.php' ) ) {
						$url = admin_url( ltrim( $url, '/' ) );
					}
				}
				if ( ! empty( $url ) && wc_is_valid_url( $url ) ) {
					return add_query_arg(
						array(
							'from' => Payments::FROM_PAYMENTS_SETTINGS,
						),
						$url
					);
				}
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to get gateway settings URL: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		// If we couldn't get a valid settings URL from the gateway, fall back to a general gateway settings URL.
		return Utils::wc_payments_settings_url(
			null,
			array(
				'section' => strtolower( $payment_gateway->id ),
				'from'    => Payments::FROM_PAYMENTS_SETTINGS,
			)
		);
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
		try {
			if ( is_callable( array( $payment_gateway, 'get_connection_url' ) ) ) {
				// If we received no return URL, we will set the WC Payments Settings page as the return URL.
				$return_url = ! empty( $return_url ) ? $return_url : admin_url( 'admin.php?page=wc-settings&tab=checkout&from=' . Payments::FROM_PROVIDER_ONBOARDING );

				return (string) $payment_gateway->get_connection_url( $return_url );
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to get gateway connection URL: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		// Fall back to pointing users to the payment gateway settings page to handle onboarding.
		return $this->get_settings_url( $payment_gateway );
	}

	/**
	 * Get the plugin details for a payment gateway.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return array The plugin details for the payment gateway.
	 */
	public function get_plugin_details( WC_Payment_Gateway $payment_gateway ): array {
		$entity_type = $this->get_containing_entity_type( $payment_gateway );

		return array(
			'_type'  => $entity_type,
			'slug'   => $this->get_plugin_slug( $payment_gateway ),
			// Only include the plugin file if the entity type is a regular plugin.
			// We don't want to try to change the state of must-use plugins or themes.
			'file'   => PaymentsProviders::EXTENSION_TYPE_WPORG === $entity_type ? $this->get_plugin_file( $payment_gateway ) : '',
			// The gateway's underlying plugin is obviously active (aka the code is running).
			'status' => PaymentsProviders::EXTENSION_ACTIVE,
		);
	}

	/**
	 * Get the source plugin slug of a payment gateway instance.
	 *
	 * It accounts for both regular and must-use plugins.
	 * If the gateway is registered through a theme, it will return the theme slug.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The plugin slug of the payment gateway.
	 *                Empty string if a plugin slug could not be determined.
	 */
	public function get_plugin_slug( WC_Payment_Gateway $payment_gateway ): string {
		global $wp_theme_directories;

		// If the payment gateway object has a `plugin_slug` property, use it.
		// This is useful for testing.
		if ( isset( $payment_gateway->plugin_slug ) ) {
			return (string) $payment_gateway->plugin_slug;
		}

		$gateway_class_filename = $this->get_class_filename( $payment_gateway );
		// Bail if we couldn't get the gateway class filename.
		if ( ! is_string( $gateway_class_filename ) ) {
			return '';
		}

		$entity_type = $this->get_containing_entity_type( $payment_gateway );
		// Bail if we couldn't determine the entity type.
		if ( PaymentsProviders::EXTENSION_TYPE_UNKNOWN === $entity_type ) {
			return '';
		}

		if ( PaymentsProviders::EXTENSION_TYPE_THEME === $entity_type ) {
			// Find the theme directory it is part of and extract the slug.
			// This accounts for both parent and child themes.
			if ( is_array( $wp_theme_directories ) ) {
				foreach ( $wp_theme_directories as $dir ) {
					if ( str_starts_with( $gateway_class_filename, $dir ) ) {
						return $this->extract_slug_from_path( substr( $gateway_class_filename, strlen( $dir ) ) );
					}
				}
			}

			// Bail if we couldn't find a match.
			return '';
		}

		// By this point, we know that the payment gateway is part of a plugin.
		// Extract the relative path of the class file to the plugins directory.
		// We account for both regular and must-use plugins.
		$gateway_class_plugins_path = trim( plugin_basename( $gateway_class_filename ), DIRECTORY_SEPARATOR );

		return $this->extract_slug_from_path( $gateway_class_plugins_path );
	}

	/**
	 * Get the corresponding plugin file of the payment gateway, without the .php extension.
	 *
	 * This is useful for using the WP API to change the state of the plugin (activate or deactivate).
	 * We remove the .php extension since the WP API expects plugin files without it.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 * @param string             $plugin_slug     Optional. The payment gateway plugin slug to use directly.
	 *
	 * @return string The plugin file corresponding to the payment gateway plugin. Does not include the .php extension.
	 *                In case of failures, it will return an empty string.
	 */
	public function get_plugin_file( WC_Payment_Gateway $payment_gateway, string $plugin_slug = '' ): string {
		// If the payment gateway object has a `plugin_file` property, use it.
		// This is useful for testing.
		if ( isset( $payment_gateway->plugin_file ) ) {
			$plugin_file = $payment_gateway->plugin_file;
			// Sanity check.
			if ( ! is_string( $plugin_file ) ) {
				return '';
			}
			// Remove the .php extension from the file path. The WP API expects it without it.
			return Utils::trim_php_file_extension( $plugin_file );
		}

		if ( empty( $plugin_slug ) ) {
			$plugin_slug = $this->get_plugin_slug( $payment_gateway );
		}

		// Bail if we couldn't determine the plugin slug.
		if ( empty( $plugin_slug ) ) {
			return '';
		}

		$plugin_file = PluginsHelper::get_plugin_path_from_slug( $plugin_slug );
		// Bail if we couldn't determine the plugin file.
		if ( ! is_string( $plugin_file ) || empty( $plugin_file ) ) {
			return '';
		}

		// Remove the .php extension from the file path. The WP API expects it without it.
		return Utils::trim_php_file_extension( $plugin_file );
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
	 *                                            This should be an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The recommended payment methods list for the payment gateway.
	 *               Empty array if there are none.
	 */
	public function get_recommended_payment_methods( WC_Payment_Gateway $payment_gateway, string $country_code = '' ): array {
		// Bail if the payment gateway does not implement the method.
		if ( ! is_callable( array( $payment_gateway, 'get_recommended_payment_methods' ) ) ) {
			return array();
		}

		try {
			// Get the "raw" recommended payment methods from the payment gateway.
			$recommended_pms = call_user_func_array(
				array( $payment_gateway, 'get_recommended_payment_methods' ),
				array( 'country_code' => $country_code ),
			);
			if ( ! is_array( $recommended_pms ) ) {
				// Bail if the recommended payment methods are not an array.
				return array();
			}
		} catch ( Throwable $e ) {
			// Log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to get recommended payment methods: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);

			return array();
		}

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
			// Default to enabled if not explicit.
			'enabled'     => wc_string_to_bool( $recommended_pm['enabled'] ?? true ),
			// Default to not required if not explicit.
			'required'    => wc_string_to_bool( $recommended_pm['required'] ?? false ),
			'title'       => sanitize_text_field( $recommended_pm['title'] ),
			'description' => '',
			'icon'        => '',
			'category'    => self::PAYMENT_METHOD_CATEGORY_PRIMARY, // Default to primary.
		);

		// If the payment method has a description, sanitize it before use.
		if ( ! empty( $recommended_pm['description'] ) ) {
			$standard_details['description'] = (string) $recommended_pm['description'];
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

		// If the payment method has a category, use it if it's one of the known categories.
		if ( ! empty( $recommended_pm['category'] ) &&
			in_array( $recommended_pm['category'], array( self::PAYMENT_METHOD_CATEGORY_PRIMARY, self::PAYMENT_METHOD_CATEGORY_SECONDARY ), true ) ) {
			$standard_details['category'] = $recommended_pm['category'];
		}

		return $standard_details;
	}

	/**
	 * Get the filename of the payment gateway class.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string|null The filename of the payment gateway class or null if it cannot be determined.
	 */
	private function get_class_filename( WC_Payment_Gateway $payment_gateway ): ?string {
		// If the payment gateway object has a `class_filename` property, use it.
		// It is only used in development environments (including when running tests).
		if ( isset( $payment_gateway->class_filename ) && in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
			$class_filename = $payment_gateway->class_filename;
		} else {
			try {
				$reflector      = new \ReflectionClass( get_class( $payment_gateway ) );
				$class_filename = $reflector->getFileName();
			} catch ( Throwable $e ) {
				// Bail but log so we can investigate.
				SafeGlobalFunctionProxy::wc_get_logger()->debug(
					'Failed to get gateway class filename: ' . $e->getMessage(),
					array(
						'gateway'   => $payment_gateway->id,
						'source'    => 'settings-payments',
						'exception' => $e,
					)
				);
				return null;
			}
		}

		// Bail if we couldn't get the gateway class filename.
		if ( ! is_string( $class_filename ) ) {
			return null;
		}

		return $class_filename;
	}

	/**
	 * Get the type of entity the payment gateway class is contained in.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The type of extension containing the payment gateway class.
	 */
	private function get_containing_entity_type( WC_Payment_Gateway $payment_gateway ): string {
		global $wp_plugin_paths, $wp_theme_directories;

		// If the payment gateway object has a `extension_type` property, use it.
		// This is useful for testing.
		if ( isset( $payment_gateway->extension_type ) ) {
			// Validate the extension type.
			if ( ! in_array(
				$payment_gateway->extension_type,
				array(
					PaymentsProviders::EXTENSION_TYPE_WPORG,
					PaymentsProviders::EXTENSION_TYPE_MU_PLUGIN,
					PaymentsProviders::EXTENSION_TYPE_THEME,
				),
				true
			) ) {
				return PaymentsProviders::EXTENSION_TYPE_UNKNOWN;
			}

			return $payment_gateway->extension_type;
		}

		$gateway_class_filename = $this->get_class_filename( $payment_gateway );
		// Bail if we couldn't get the gateway class filename.
		if ( ! is_string( $gateway_class_filename ) ) {
			return PaymentsProviders::EXTENSION_TYPE_UNKNOWN;
		}

		// Plugin paths logic closely matches the one in plugin_basename().
		// $wp_plugin_paths contains normalized paths.
		$file = wp_normalize_path( $gateway_class_filename );

		arsort( $wp_plugin_paths );
		// Account for symlinks in the plugin paths.
		foreach ( $wp_plugin_paths as $dir => $realdir ) {
			if ( str_starts_with( $file, $realdir ) ) {
				$gateway_class_filename = $dir . substr( $gateway_class_filename, strlen( $realdir ) );
			}
		}

		// Test for regular plugins.
		if ( str_starts_with( $gateway_class_filename, wp_normalize_path( WP_PLUGIN_DIR ) ) ) {
			// For now, all plugins are considered WordPress.org plugins.
			return PaymentsProviders::EXTENSION_TYPE_WPORG;
		}

		// Test for must-use plugins.
		if ( str_starts_with( $gateway_class_filename, wp_normalize_path( WPMU_PLUGIN_DIR ) ) ) {
			return PaymentsProviders::EXTENSION_TYPE_MU_PLUGIN;
		}

		// Check if it is part of a theme.
		if ( is_array( $wp_theme_directories ) ) {
			foreach ( $wp_theme_directories as $dir ) {
				// Check if the class file is in a theme directory.
				if ( str_starts_with( $gateway_class_filename, $dir ) ) {
					return PaymentsProviders::EXTENSION_TYPE_THEME;
				}
			}
		}

		// Default to an unknown type.
		return PaymentsProviders::EXTENSION_TYPE_UNKNOWN;
	}

	/**
	 * Extract the slug from a given path.
	 *
	 * It can be a directory or file path.
	 * This should be a relative path since the top-level directory or file name will be used as the slug.
	 *
	 * @param string $path The path to extract the slug from.
	 *
	 * @return string The slug extracted from the path.
	 */
	private function extract_slug_from_path( string $path ): string {
		$path = trim( $path );
		$path = trim( $path, DIRECTORY_SEPARATOR );

		// If the path is just a file name, use it as the slug.
		if ( false === strpos( $path, DIRECTORY_SEPARATOR ) ) {
			return Utils::trim_php_file_extension( $path );
		}

		$parts = explode( DIRECTORY_SEPARATOR, $path );
		// Bail if we couldn't get the parts.
		if ( ! is_array( $parts ) ) {
			return '';
		}

		return reset( $parts );
	}
}
