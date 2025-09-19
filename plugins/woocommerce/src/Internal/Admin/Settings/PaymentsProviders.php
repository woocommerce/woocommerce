<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Affirm;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\AfterpayClearpay;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Airwallex;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\AmazonPay;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Antom;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Eway;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\GoCardless;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\HelioPay;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Klarna;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\KlarnaCheckout;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\MercadoPago;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Mollie;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Monei;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\NexiCheckout;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Payfast;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\PaymentGateway;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Paymob;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Payoneer;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\PayPal;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Paystack;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Paytrail;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\PayUIndia;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Razorpay;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Stripe;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Tilopay;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Visa;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Vivacom;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WCCore;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentsExtensionSuggestions as ExtensionSuggestions;
use Exception;
use WC_Payment_Gateway;
use WC_Gateway_BACS;
use WC_Gateway_Cheque;
use WC_Gateway_COD;
use WC_Gateway_Paypal;

defined( 'ABSPATH' ) || exit;

/**
 * Payments Providers class.
 *
 * @internal
 */
class PaymentsProviders {

	public const TYPE_GATEWAY           = 'gateway';
	public const TYPE_OFFLINE_PM        = 'offline_pm';
	public const TYPE_OFFLINE_PMS_GROUP = 'offline_pms_group';
	public const TYPE_SUGGESTION        = 'suggestion';

	public const OFFLINE_METHODS = array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID, WC_Gateway_COD::ID );

	public const EXTENSION_NOT_INSTALLED = 'not_installed';
	public const EXTENSION_INSTALLED     = 'installed';
	public const EXTENSION_ACTIVE        = 'active';

	// For providers that are delivered through a plugin available on the WordPress.org repository.
	public const EXTENSION_TYPE_WPORG = 'wporg';
	// For providers that are delivered through a must-use plugin.
	public const EXTENSION_TYPE_MU_PLUGIN = 'mu_plugin';
	// For providers that are delivered through a theme.
	public const EXTENSION_TYPE_THEME = 'theme';
	// For providers that are delivered through an unknown mechanism.
	public const EXTENSION_TYPE_UNKNOWN = 'unknown';

	public const PROVIDERS_ORDER_OPTION         = 'woocommerce_gateway_order';
	public const SUGGESTION_ORDERING_PREFIX     = '_wc_pes_';
	public const OFFLINE_METHODS_ORDERING_GROUP = '_wc_offline_payment_methods_group';

	public const CATEGORY_EXPRESS_CHECKOUT = 'express_checkout';
	public const CATEGORY_BNPL             = 'bnpl';
	public const CATEGORY_CRYPTO           = 'crypto';
	public const CATEGORY_PSP              = 'psp';

	/**
	 * The map of gateway IDs to their respective provider classes.
	 *
	 * @var \class-string[]
	 */
	private array $payment_gateways_providers_class_map = array(
		WC_Gateway_BACS::ID           => WCCore::class,
		WC_Gateway_Cheque::ID         => WCCore::class,
		WC_Gateway_COD::ID            => WCCore::class,
		WC_Gateway_Paypal::ID         => WCCore::class,
		'woocommerce_payments'        => WooPayments::class,
		'ppcp-gateway'                => PayPal::class,
		'stripe'                      => Stripe::class,
		'stripe_*'                    => Stripe::class,
		'mollie'                      => Mollie::class,
		'mollie_wc_gateway_*'         => Mollie::class, // Target all the Mollie gateways.
		'amazon_payments_advanced*'   => AmazonPay::class,
		'woo-mercado-pago-*'          => MercadoPago::class,
		'affirm'                      => Affirm::class,
		'klarna_payments'             => Klarna::class,
		'afterpay'                    => AfterpayClearpay::class,
		'clearpay'                    => AfterpayClearpay::class,
		'antom_*'                     => Antom::class,
		'razorpay'                    => Razorpay::class,
		'paystack'                    => Paystack::class,
		'paystack-*'                  => Paystack::class,
		'payfast'                     => Payfast::class,
		'payoneer-*'                  => Payoneer::class,
		'payubiz'                     => PayUIndia::class,
		'paymob'                      => Paymob::class,
		'paymob-*'                    => Paymob::class,
		'airwallex_*'                 => Airwallex::class,
		'vivawallet*'                 => Vivacom::class,
		'tilopay'                     => Tilopay::class,
		'helio'                       => HelioPay::class,
		'paytrail'                    => Paytrail::class,
		'monei'                       => Monei::class,
		'monei_*'                     => Monei::class,
		'gocardless'                  => GoCardless::class,
		'kco'                         => KlarnaCheckout::class,
		'visa_acceptance_solutions_*' => Visa::class,
		'eway'                        => Eway::class,
		'dibs_easy'                   => NexiCheckout::class,
	);

	/**
	 * The map of payment extension suggestion IDs to their respective provider classes.
	 *
	 * This is used to instantiate providers to provide details for the payment extension suggestions, pre-attachment.
	 *
	 * @var \class-string[]
	 */
	private array $payment_extension_suggestions_providers_class_map = array(
		ExtensionSuggestions::WOOPAYMENTS       => WooPayments::class,
		ExtensionSuggestions::PAYPAL_FULL_STACK => PayPal::class,
		ExtensionSuggestions::PAYPAL_WALLET     => PayPal::class,
		ExtensionSuggestions::STRIPE            => Stripe::class,
		ExtensionSuggestions::MOLLIE            => Mollie::class,
		ExtensionSuggestions::AMAZON_PAY        => AmazonPay::class,
		ExtensionSuggestions::MERCADO_PAGO      => MercadoPago::class,
		ExtensionSuggestions::AFFIRM            => Affirm::class,
		ExtensionSuggestions::KLARNA            => Klarna::class,
		ExtensionSuggestions::AFTERPAY          => AfterpayClearpay::class,
		ExtensionSuggestions::CLEARPAY          => AfterpayClearpay::class,
		ExtensionSuggestions::ANTOM             => Antom::class,
		ExtensionSuggestions::RAZORPAY          => Razorpay::class,
		ExtensionSuggestions::PAYSTACK          => Paystack::class,
		ExtensionSuggestions::PAYFAST           => Payfast::class,
		ExtensionSuggestions::PAYONEER          => Payoneer::class,
		ExtensionSuggestions::PAYU_INDIA        => PayUIndia::class,
		ExtensionSuggestions::PAYMOB            => Paymob::class,
		ExtensionSuggestions::AIRWALLEX         => Airwallex::class,
		ExtensionSuggestions::VIVA_WALLET       => Vivacom::class,
		ExtensionSuggestions::TILOPAY           => Tilopay::class,
		ExtensionSuggestions::HELIOPAY          => HelioPay::class,
		ExtensionSuggestions::PAYTRAIL          => Paytrail::class,
		ExtensionSuggestions::MONEI             => Monei::class,
		ExtensionSuggestions::GOCARDLESS        => GoCardless::class,
		ExtensionSuggestions::KLARNA_CHECKOUT   => KlarnaCheckout::class,
		ExtensionSuggestions::VISA              => Visa::class,
		ExtensionSuggestions::EWAY              => Eway::class,
		ExtensionSuggestions::NEXI_CHECKOUT     => NexiCheckout::class,
	);

	/**
	 * The instances of the payment providers.
	 *
	 * @var PaymentGateway[]
	 */
	private array $instances = array();

	/**
	 * The memoized payment gateways to avoid computing the list multiple times during a request.
	 *
	 * @var array
	 */
	private array $payment_gateways_memo = array();

	/**
	 * The memoized payment gateways for display to avoid computing the list multiple times during a request.
	 *
	 * This is especially important since it avoids triggering the legacy action multiple times during a request.
	 *
	 * @var array
	 */
	private array $payment_gateways_for_display_memo = array();

	/**
	 * The payment extension suggestions service.
	 *
	 * @var ExtensionSuggestions
	 */
	private ExtensionSuggestions $extension_suggestions;

	/**
	 * Initialize the class instance.
	 *
	 * @param ExtensionSuggestions $payment_extension_suggestions The payment extension suggestions service.
	 *
	 * @internal
	 */
	final public function init( ExtensionSuggestions $payment_extension_suggestions ): void {
		$this->extension_suggestions = $payment_extension_suggestions;
	}

	/**
	 * Get the payment gateways for the settings page.
	 *
	 * We apply the same actions and logic that the non-React Payments settings page uses to get the gateways.
	 * This way we maintain backwards compatibility.
	 *
	 * @param bool   $for_display  Whether the payment gateway list is intended for display purposes.
	 *                             This triggers the legacy `woocommerce_admin_field_payment_gateways` action and
	 *                             the exclusion of "shell" gateways.
	 *                             Default is true.
	 * @param string $country_code Optional. The country code for which the payment gateways are being generated.
	 *                             This should be an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The payment gateway objects list.
	 */
	public function get_payment_gateways( bool $for_display = true, string $country_code = '' ): array {
		// If we are asked for a display gateways list, we need to fire legacy actions and filter out "shells".
		if ( $for_display ) {
			if ( isset( $this->payment_gateways_for_display_memo[ $country_code ] ) ) {
				return $this->payment_gateways_for_display_memo[ $country_code ];
			}

			// We don't want to output anything from the action. So we buffer it and discard it.
			// We just want to give the payment extensions a chance to adjust the payment gateways list for the settings page.
			// This is primarily for backwards compatibility.
			ob_start();
			/**
			 * Fires before the payment gateways settings fields are rendered.
			 *
			 * @since 1.5.7
			 */
			do_action( 'woocommerce_admin_field_payment_gateways' );
			ob_end_clean();

			// Get all payment gateways, ordered by the user.
			$payment_gateways = WC()->payment_gateways()->payment_gateways;

			// Handle edge-cases for certain providers.
			$payment_gateways = $this->handle_non_standard_registration_for_payment_gateways( $payment_gateways );

			// Remove "shell" gateways from the list.
			$payment_gateways = $this->remove_shell_payment_gateways( $payment_gateways, $country_code );

			// Store the entire payment gateways list for display for later use.
			$this->payment_gateways_for_display_memo[ $country_code ] = $payment_gateways;

			return $payment_gateways;
		}

		// We were asked for the raw payment gateways list.
		if ( isset( $this->payment_gateways_memo[ $country_code ] ) ) {
			return $this->payment_gateways_memo[ $country_code ];
		}

		// Get all payment gateways, ordered by the user.
		$payment_gateways = WC()->payment_gateways()->payment_gateways;

		// Handle edge-cases for certain providers.
		$payment_gateways = $this->handle_non_standard_registration_for_payment_gateways( $payment_gateways );

		// Store the entire payment gateways list for later use.
		$this->payment_gateways_memo[ $country_code ] = $payment_gateways;

		return $payment_gateways;
	}

	/**
	 * Remove "shell" gateways from the provided payment gateways list.
	 *
	 * We consider a gateway to be a "shell" if it has no WC admin title or description.
	 * The removal is done in a way that ensures we do not remove all gateways from an extension,
	 * thus preventing user access to the settings page(s) for that extension.
	 *
	 * @param array  $payment_gateways The payment gateways list to process.
	 * @param string $country_code     Optional. The country code for which the payment gateways are being generated.
	 *                                 This should be an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The processed payment gateways list.
	 */
	public function remove_shell_payment_gateways( array $payment_gateways, string $country_code = '' ): array {
		$grouped_payment_gateways = $this->group_gateways_by_extension( $payment_gateways, $country_code );
		return array_filter(
			$payment_gateways,
			function ( $gateway ) use ( $grouped_payment_gateways, $country_code ) {
				// If the gateway is a shell, we only remove it if there are other, non-shell gateways from that extension.
				// This is to avoid removing all the gateways registered by an extension and
				// preventing user access to the settings page(s) for that extension.
				if ( $this->is_shell_payment_gateway( $gateway ) ) {
					$gateway_details = $this->get_payment_gateway_details( $gateway, 0, $country_code );
					// In case we don't have the needed extension details,
					// we allow the gateway to be displayed (aka better safe than sorry).
					if ( empty( $gateway_details ) || ! isset( $gateway_details['plugin'] ) || empty( $gateway_details['plugin']['file'] ) ) {
						return true;
					}

					if ( empty( $grouped_payment_gateways[ $gateway_details['plugin']['file'] ] ) ||
						count( $grouped_payment_gateways[ $gateway_details['plugin']['file'] ] ) <= 1 ) {
						// If there are no other gateways from the same extension, we let the shell gateway be displayed.
						return true;
					}

					// Check if there are any other gateways from the same extension that are NOT shells.
					foreach ( $grouped_payment_gateways[ $gateway_details['plugin']['file'] ] as $extension_gateway ) {
						if ( ! $this->is_shell_payment_gateway( $extension_gateway ) ) {
							// If we found a gateway from the same extension that is not a shell,
							// we hide all shells from that extension.
							return false;
						}
					}
				}

				// By this point, we know that the gateway is not a shell or that it is a shell
				// but there are no non-shell gateways from the same extension. Include it.
				return true;
			}
		);
	}

	/**
	 * Get the payment gateway provider instance.
	 *
	 * @param string $gateway_id The gateway ID.
	 *
	 * @return PaymentGateway The payment gateway provider instance.
	 *                        Will return the general provider of no specific provider is found.
	 */
	public function get_payment_gateway_provider_instance( string $gateway_id ): PaymentGateway {
		if ( isset( $this->instances[ $gateway_id ] ) ) {
			return $this->instances[ $gateway_id ];
		}

		/**
		 * The provider class for the gateway.
		 *
		 * @var PaymentGateway|null $provider_class
		 */
		$provider_class = null;
		if ( isset( $this->payment_gateways_providers_class_map[ $gateway_id ] ) ) {
			$provider_class = $this->payment_gateways_providers_class_map[ $gateway_id ];
		} else {
			// Check for wildcard mappings.
			foreach ( $this->payment_gateways_providers_class_map as $gateway_id_pattern => $mapped_class ) {
				// Try to see if we have a wildcard mapping and if the gateway ID matches it.
				// Use the first found match.
				if ( false !== strpos( $gateway_id_pattern, '*' ) ) {
					$gateway_id_pattern = str_replace( '*', '.*', $gateway_id_pattern );
					if ( preg_match( '/^' . $gateway_id_pattern . '$/', $gateway_id ) ) {
						$provider_class = $mapped_class;
						break;
					}
				}
			}
		}

		// If the gateway ID is not mapped to a provider class, return the generic provider.
		if ( is_null( $provider_class ) ) {
			if ( ! isset( $this->instances['generic'] ) ) {
				$this->instances['generic'] = new PaymentGateway();
			}

			return $this->instances['generic'];
		}

		$this->instances[ $gateway_id ] = new $provider_class();

		return $this->instances[ $gateway_id ];
	}

	/**
	 * Get the payment extension suggestion (PES) provider instance.
	 *
	 * @param string $pes_id The payment extension suggestion ID.
	 *
	 * @return PaymentGateway The payment extension suggestion provider instance.
	 *                        Will return the general provider of no specific provider is found.
	 */
	public function get_payment_extension_suggestion_provider_instance( string $pes_id ): PaymentGateway {
		if ( isset( $this->instances[ $pes_id ] ) ) {
			return $this->instances[ $pes_id ];
		}

		/**
		 * The provider class for the payment extension suggestion (PES).
		 *
		 * @var PaymentGateway|null $provider_class
		 */
		$provider_class = null;
		if ( isset( $this->payment_extension_suggestions_providers_class_map[ $pes_id ] ) ) {
			$provider_class = $this->payment_extension_suggestions_providers_class_map[ $pes_id ];
		}

		// If the gateway ID is not mapped to a provider class, return the generic provider.
		if ( is_null( $provider_class ) ) {
			if ( ! isset( $this->instances['generic'] ) ) {
				$this->instances['generic'] = new PaymentGateway();
			}

			return $this->instances['generic'];
		}

		$this->instances[ $pes_id ] = new $provider_class();

		return $this->instances[ $pes_id ];
	}

	/**
	 * Get the payment gateways details.
	 *
	 * @param WC_Payment_Gateway $payment_gateway       The payment gateway object.
	 * @param int                $payment_gateway_order The order of the payment gateway.
	 * @param string             $country_code          Optional. The country code for which the details are being gathered.
	 *                                                  This should be an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The payment gateway details.
	 */
	public function get_payment_gateway_details( WC_Payment_Gateway $payment_gateway, int $payment_gateway_order, string $country_code = '' ): array {
		return $this->enhance_payment_gateway_details(
			$this->get_payment_gateway_base_details( $payment_gateway, $payment_gateway_order, $country_code ),
			$payment_gateway,
			$country_code
		);
	}

	/**
	 * Get the payment gateways details from the object.
	 *
	 * @param WC_Payment_Gateway $payment_gateway       The payment gateway object.
	 * @param int                $payment_gateway_order The order of the payment gateway.
	 * @param string             $country_code          Optional. The country code for which the details are being gathered.
	 *                                                  This should be an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The payment gateway base details.
	 */
	public function get_payment_gateway_base_details( WC_Payment_Gateway $payment_gateway, int $payment_gateway_order, string $country_code = '' ): array {
		$provider = $this->get_payment_gateway_provider_instance( $payment_gateway->id );

		return $provider->get_details( $payment_gateway, $payment_gateway_order, $country_code );
	}

	/**
	 * Get the source plugin slug of a payment gateway instance.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The plugin slug of the payment gateway.
	 *                Empty string if a plugin slug could not be determined.
	 */
	public function get_payment_gateway_plugin_slug( WC_Payment_Gateway $payment_gateway ): string {
		$provider = $this->get_payment_gateway_provider_instance( $payment_gateway->id );

		return $provider->get_plugin_slug( $payment_gateway );
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
	public function get_payment_gateway_plugin_file( WC_Payment_Gateway $payment_gateway, string $plugin_slug = '' ): string {
		$provider = $this->get_payment_gateway_provider_instance( $payment_gateway->id );

		return $provider->get_plugin_file( $payment_gateway, $plugin_slug );
	}

	/**
	 * Get the offline payment methods gateways.
	 *
	 * @return array The registered offline payment methods gateways keyed by their global gateways list order/index.
	 */
	public function get_offline_payment_methods_gateways(): array {
		return array_filter(
			$this->get_payment_gateways( false ), // We request the raw gateways list to get the global order/index.
			function ( $gateway ) {
				return $this->is_offline_payment_method( $gateway->id );
			}
		);
	}

	/**
	 * Check if a payment gateway is an offline payment method.
	 *
	 * @param string $id The ID of the payment gateway.
	 *
	 * @return bool True if the payment gateway is an offline payment method, false otherwise.
	 */
	public function is_offline_payment_method( string $id ): bool {
		return in_array( $id, self::OFFLINE_METHODS, true );
	}

	/**
	 * Check if a payment gateway is a shell payment gateway.
	 *
	 * A shell payment gateway is generally one that has no method title or description.
	 * This is used to identify gateways that are not intended for display in the admin UI.
	 *
	 * @param WC_Payment_Gateway $gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is a shell, false otherwise.
	 */
	public function is_shell_payment_gateway( WC_Payment_Gateway $gateway ): bool {
		return ( empty( $gateway->get_method_title() ) && empty( $gateway->get_method_description() ) ) ||
			// Special case for WooPayments gateways that are not the main one: their method title is "WooPayments",
			// but their ID is made up of the main gateway ID and a suffix for the payment method.
			( 'WooPayments' === $gateway->get_method_title() && str_starts_with( $gateway->id, WooPaymentsService::GATEWAY_ID . '_' ) );
	}

	/**
	 * Get the payment extension suggestions for the given location.
	 *
	 * @param string $location The location for which the suggestions are being fetched.
	 * @param string $context  Optional. The context ID of where these extensions are being used.
	 *
	 * @return array[] The payment extension suggestions for the given location, split into preferred and other.
	 * @throws Exception If there are malformed or invalid suggestions.
	 */
	public function get_extension_suggestions( string $location, string $context = '' ): array {
		$preferred_psp         = null;
		$preferred_apm         = null;
		$preferred_offline_psp = null;
		$other                 = array();

		$extensions = $this->extension_suggestions->get_country_extensions( $location, $context );
		// Sort them by _priority.
		usort(
			$extensions,
			function ( $a, $b ) {
				return $a['_priority'] <=> $b['_priority'];
			}
		);

		$has_enabled_ecommerce_gateways = $this->has_enabled_ecommerce_gateways();

		// Keep track of the active extensions.
		$active_extensions = array();

		foreach ( $extensions as $extension ) {
			$extension = $this->enhance_extension_suggestion( $extension );

			if ( self::EXTENSION_ACTIVE === $extension['plugin']['status'] ) {
				// If the suggested extension is active, we no longer suggest it.
				// But remember it for later.
				$active_extensions[] = $extension['id'];
				continue;
			}

			// Determine if the suggestion is preferred or not by looking at its tags.
			$is_preferred = in_array( ExtensionSuggestions::TAG_PREFERRED, $extension['tags'], true );

			// Determine if the suggestion is hidden (from the preferred locations).
			$is_hidden = $this->is_payment_extension_suggestion_hidden( $extension );

			if ( ! $is_hidden && $is_preferred ) {
				// If we don't have a preferred offline payments PSP and the suggestion is an offline payments preferred PSP,
				// add it to the preferred list.
				// Check this first so we don't inadvertently "fill" the preferred PSP slot.
				if ( empty( $preferred_offline_psp ) &&
					ExtensionSuggestions::TYPE_PSP === $extension['_type'] &&
					in_array( ExtensionSuggestions::TAG_PREFERRED_OFFLINE, $extension['tags'], true ) ) {

					$preferred_offline_psp = $extension;
					continue;
				}

				// If we don't have a preferred PSP and the suggestion is a preferred PSP, add it to the preferred list.
				if ( empty( $preferred_psp ) && ExtensionSuggestions::TYPE_PSP === $extension['_type'] ) {
					$preferred_psp = $extension;
					continue;
				}

				// If we don't have a preferred APM and the suggestion is a preferred APM, add it to the preferred list.
				// In the preferred APM slot we might surface APMs but also Express Checkouts (PayPal Wallet).
				if ( empty( $preferred_apm ) &&
					in_array( $extension['_type'], array( ExtensionSuggestions::TYPE_APM, ExtensionSuggestions::TYPE_EXPRESS_CHECKOUT ), true ) ) {

					$preferred_apm = $extension;
					continue;
				}
			}

			if ( $is_hidden &&
				ExtensionSuggestions::TYPE_APM === $extension['_type'] &&
				ExtensionSuggestions::PAYPAL_FULL_STACK === $extension['id'] ) {
				// If the PayPal Full Stack suggestion is hidden, we no longer suggest it,
				// because we have the PayPal Express Checkout (Wallet) suggestion.
				continue;
			}

			// If there are no enabled ecommerce gateways (no PSP selected),
			// we don't suggest express checkout, BNPL, or crypto extensions.
			if ( ! $has_enabled_ecommerce_gateways &&
				in_array( $extension['_type'], array( ExtensionSuggestions::TYPE_EXPRESS_CHECKOUT, ExtensionSuggestions::TYPE_BNPL, ExtensionSuggestions::TYPE_CRYPTO ), true )
			) {
				continue;
			}

			// If WooPayments or Stripe is active, we don't suggest other BNPLs.
			if ( ExtensionSuggestions::TYPE_BNPL === $extension['_type'] &&
				(
					in_array( ExtensionSuggestions::STRIPE, $active_extensions, true ) ||
					in_array( ExtensionSuggestions::WOOPAYMENTS, $active_extensions, true )
				)
			) {
				continue;
			}

			// If we made it to this point, the suggestion goes into the other list.
			// But first, make sure there isn't already an extension added to the other list with the same plugin slug.
			// This can happen if the same extension is suggested as both a PSP and an APM.
			// The first entry that we encounter is the one that we keep.
			$extension_slug   = $extension['plugin']['slug'];
			$extension_exists = array_filter(
				$other,
				function ( $suggestion ) use ( $extension_slug ) {
					return $suggestion['plugin']['slug'] === $extension_slug;
				}
			);
			if ( ! empty( $extension_exists ) ) {
				continue;
			}

			$other[] = $extension;
		}

		// Make sure that the preferred suggestions are not among the other list by removing any entries with their plugin slug.
		$other = array_values(
			array_filter(
				$other,
				function ( $suggestion ) use ( $preferred_psp, $preferred_apm ) {
					return ( empty( $preferred_psp ) || $suggestion['plugin']['slug'] !== $preferred_psp['plugin']['slug'] ) &&
							( empty( $preferred_apm ) || $suggestion['plugin']['slug'] !== $preferred_apm['plugin']['slug'] );
				}
			)
		);

		// The preferred PSP gets a recommended tag that instructs the UI to highlight it further.
		if ( ! empty( $preferred_psp ) ) {
			$preferred_psp['tags'][] = ExtensionSuggestions::TAG_RECOMMENDED;
		}

		return array(
			'preferred' => array_values(
				array_filter(
					array(
						// The PSP should naturally have a higher priority than the APM, with the preferred offline PSP last.
						// No need to impose a specific order here.
						$preferred_psp,
						$preferred_apm,
						$preferred_offline_psp,
					)
				)
			),
			'other'     => $other,
		);
	}

	/**
	 * Get a payment extension suggestion by ID.
	 *
	 * @param string $id The ID of the payment extension suggestion.
	 *
	 * @return ?array The payment extension suggestion details, or null if not found.
	 */
	public function get_extension_suggestion_by_id( string $id ): ?array {
		$suggestion = $this->extension_suggestions->get_by_id( $id );
		if ( ! is_null( $suggestion ) ) {
			// Enhance the suggestion details.
			$suggestion = $this->enhance_extension_suggestion( $suggestion );
		}

		return $suggestion;
	}

	/**
	 * Get a payment extension suggestion by plugin slug.
	 *
	 * @param string $slug         The plugin slug of the payment extension suggestion.
	 * @param string $country_code Optional. The business location country code to get the suggestions for.
	 *
	 * @return ?array The payment extension suggestion details, or null if not found.
	 */
	public function get_extension_suggestion_by_plugin_slug( string $slug, string $country_code = '' ): ?array {
		$suggestion = $this->extension_suggestions->get_by_plugin_slug( $slug, $country_code, Payments::SUGGESTIONS_CONTEXT );
		if ( ! is_null( $suggestion ) ) {
			// Enhance the suggestion details.
			$suggestion = $this->enhance_extension_suggestion( $suggestion );
		}

		return $suggestion;
	}

	/**
	 * Attach a payment extension suggestion.
	 *
	 * Attachment is a broad concept that can mean different things depending on the suggestion.
	 * Currently, we use it to record the extension installation. This is why we expect to receive
	 * instructions to record attachment when the extension is installed.
	 *
	 * @param string $id The ID of the payment extension suggestion to attach.
	 *
	 * @return bool True if the suggestion was successfully marked as attached, false otherwise.
	 * @throws Exception If the suggestion ID is invalid.
	 */
	public function attach_extension_suggestion( string $id ): bool {
		// We may receive a suggestion ID that is actually an order map ID used in the settings page providers list.
		// Extract the suggestion ID from the order map ID.
		if ( $this->is_suggestion_order_map_id( $id ) ) {
			$id = $this->get_suggestion_id_from_order_map_id( $id );
		}

		$suggestion = $this->get_extension_suggestion_by_id( $id );
		if ( is_null( $suggestion ) ) {
			throw new Exception( esc_html__( 'Invalid suggestion ID.', 'woocommerce' ) );
		}

		$payments_nox_profile = get_option( Payments::PAYMENTS_NOX_PROFILE_KEY, array() );
		if ( empty( $payments_nox_profile ) ) {
			$payments_nox_profile = array();
		} else {
			$payments_nox_profile = maybe_unserialize( $payments_nox_profile );
		}

		// Check if it is already marked as attached.
		if ( ! empty( $payments_nox_profile['suggestions'][ $id ]['attached']['timestamp'] ) ) {
			return true;
		}

		// Mark the suggestion as attached.
		if ( empty( $payments_nox_profile['suggestions'] ) ) {
			$payments_nox_profile['suggestions'] = array();
		}
		if ( empty( $payments_nox_profile['suggestions'][ $id ] ) ) {
			$payments_nox_profile['suggestions'][ $id ] = array();
		}
		if ( empty( $payments_nox_profile['suggestions'][ $id ]['attached'] ) ) {
			$payments_nox_profile['suggestions'][ $id ]['attached'] = array();
		}
		$payments_nox_profile['suggestions'][ $id ]['attached']['timestamp'] = time();

		// Store the modified profile data.
		$result = update_option( Payments::PAYMENTS_NOX_PROFILE_KEY, $payments_nox_profile, false );
		// Since we already check if the suggestion is already attached, we should not get a false result
		// for trying to update with the same value.
		// False means the update failed and the suggestion is not marked as attached.
		if ( false === $result ) {
			return false;
		}

		// Handle custom attachment logic per-provider.
		switch ( $id ) {
			case ExtensionSuggestions::PAYPAL_FULL_STACK:
			case ExtensionSuggestions::PAYPAL_WALLET:
				// Set an option to inform the extension.
				update_option( 'woocommerce_paypal_branded', 'payments_settings', false );
				break;
			default:
				break;
		}

		return true;
	}

	/**
	 * Hide a payment extension suggestion.
	 *
	 * @param string $id The ID of the payment extension suggestion to hide.
	 *
	 * @return bool True if the suggestion was successfully hidden, false otherwise.
	 * @throws Exception If the suggestion ID is invalid.
	 */
	public function hide_extension_suggestion( string $id ): bool {
		// We may receive a suggestion ID that is actually an order map ID used in the settings page providers list.
		// Extract the suggestion ID from the order map ID.
		if ( $this->is_suggestion_order_map_id( $id ) ) {
			$id = $this->get_suggestion_id_from_order_map_id( $id );
		}

		$suggestion = $this->get_extension_suggestion_by_id( $id );
		if ( is_null( $suggestion ) ) {
			throw new Exception( esc_html__( 'Invalid suggestion ID.', 'woocommerce' ) );
		}

		$user_payments_nox_profile = get_user_meta( get_current_user_id(), Payments::PAYMENTS_NOX_PROFILE_KEY, true );
		if ( empty( $user_payments_nox_profile ) ) {
			$user_payments_nox_profile = array();
		} else {
			$user_payments_nox_profile = maybe_unserialize( $user_payments_nox_profile );
		}

		// Mark the suggestion as hidden.
		if ( empty( $user_payments_nox_profile['hidden_suggestions'] ) ) {
			$user_payments_nox_profile['hidden_suggestions'] = array();
		}
		// Check if it is already hidden.
		if ( in_array( $id, array_column( $user_payments_nox_profile['hidden_suggestions'], 'id' ), true ) ) {
			return true;
		}
		$user_payments_nox_profile['hidden_suggestions'][] = array(
			'id'        => $id,
			'timestamp' => time(),
		);

		$result = update_user_meta( get_current_user_id(), Payments::PAYMENTS_NOX_PROFILE_KEY, $user_payments_nox_profile );
		// Since we already check if the suggestion is already hidden, we should not get a false result
		// for trying to update with the same value. False means the update failed and the suggestion is not hidden.
		if ( false === $result ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the payment extension suggestions categories details.
	 *
	 * @return array The payment extension suggestions categories.
	 */
	public function get_extension_suggestion_categories(): array {
		$categories   = array();
		$categories[] = array(
			'id'          => self::CATEGORY_EXPRESS_CHECKOUT,
			'_priority'   => 10,
			'title'       => esc_html__( 'Wallets & Express checkouts', 'woocommerce' ),
			'description' => esc_html__( 'Allow shoppers to fast-track the checkout process with express options like Apple Pay and Google Pay.', 'woocommerce' ),
		);
		$categories[] = array(
			'id'          => self::CATEGORY_BNPL,
			'_priority'   => 20,
			'title'       => esc_html__( 'Buy Now, Pay Later', 'woocommerce' ),
			'description' => esc_html__( 'Offer flexible payment options to your shoppers.', 'woocommerce' ),
		);
		$categories[] = array(
			'id'          => self::CATEGORY_CRYPTO,
			'_priority'   => 30,
			'title'       => esc_html__( 'Crypto Payments', 'woocommerce' ),
			'description' => esc_html__( 'Offer cryptocurrency payment options to your shoppers.', 'woocommerce' ),
		);
		$categories[] = array(
			'id'          => self::CATEGORY_PSP,
			'_priority'   => 40,
			'title'       => esc_html__( 'Payment Providers', 'woocommerce' ),
			'description' => esc_html__( 'Give your shoppers additional ways to pay.', 'woocommerce' ),
		);

		return $categories;
	}

	/**
	 * Get the payment providers order map.
	 *
	 * @return array The payment providers order map.
	 */
	public function get_order_map(): array {
		// This will also handle backwards compatibility.
		return $this->enhance_order_map( get_option( self::PROVIDERS_ORDER_OPTION, array() ) );
	}

	/**
	 * Save the payment providers order map.
	 *
	 * @param array $order_map The order map to save.
	 *
	 * @return bool True if the payment providers order map was successfully saved, false otherwise.
	 */
	public function save_order_map( array $order_map ): bool {
		return update_option( self::PROVIDERS_ORDER_OPTION, $order_map );
	}

	/**
	 * Update the payment providers order map.
	 *
	 * This has effects both on the Payments settings page and the checkout page
	 * since registered payment gateways (enabled or not) are among the providers.
	 *
	 * @param array $order_map The new order for payment providers.
	 *                         The order map should be an associative array where the keys are the payment provider IDs
	 *                         and the values are the new integer order for the payment provider.
	 *                         This can be a partial list of payment providers and their orders.
	 *                         It can also contain new IDs and their orders.
	 *
	 * @return bool True if the payment providers ordering was successfully updated, false otherwise.
	 */
	public function update_payment_providers_order_map( array $order_map ): bool {
		$existing_order_map = get_option( self::PROVIDERS_ORDER_OPTION, array() );

		$new_order_map = $this->payment_providers_order_map_apply_mappings( $existing_order_map, $order_map );

		// This will also handle backwards compatibility.
		$new_order_map = $this->enhance_order_map( $new_order_map );

		// Save the new order map to the DB.
		return $this->save_order_map( $new_order_map );
	}

	/**
	 * Enhance a payment providers order map.
	 *
	 * If the payments providers order map is empty, it will be initialized with the current WC payment gateway ordering.
	 * If there are missing entries (registered payment gateways, suggestions, offline PMs, etc.), they will be added.
	 * Various rules will be enforced (e.g., offline PMs and their relation with the offline PMs group).
	 *
	 * @param array $order_map The payment providers order map.
	 *
	 * @return array The updated payment providers order map.
	 */
	public function enhance_order_map( array $order_map ): array {
		// We don't request the display gateways list because we need to get the order of all the registered payment gateways.
		$payment_gateways = $this->get_payment_gateways( false );
		// Make it a list keyed by the payment gateway ID.
		$payment_gateways = array_combine(
			array_map(
				fn( $gateway ) => $gateway->id,
				$payment_gateways
			),
			$payment_gateways
		);
		// Get the payment gateways order map.
		$payment_gateways_order_map = array_flip( array_keys( $payment_gateways ) );
		// Get the payment gateways to suggestions map.
		// There will be null entries for payment gateways where we couldn't find a suggestion.
		$payment_gateways_to_suggestions_map = array_map(
			fn( $gateway ) => $this->extension_suggestions->get_by_plugin_slug( Utils::normalize_plugin_slug( $this->get_payment_gateway_plugin_slug( $gateway ) ) ),
			$payment_gateways
		);

		/*
		 * Initialize the order map with the current ordering.
		 */
		if ( empty( $order_map ) ) {
			$order_map = $payment_gateways_order_map;
		}

		$order_map = Utils::order_map_normalize( $order_map );

		$handled_suggestion_ids = array();

		/*
		 * Go through the registered gateways and add any missing ones.
		 */
		// Use a map to keep track of the insertion offset for each suggestion ID.
		// We need this so we can place multiple PGs matching a suggestion right after it but maintain their relative order.
		$suggestion_order_map_id_to_offset_map = array();
		foreach ( $payment_gateways_order_map as $id => $order ) {
			if ( isset( $order_map[ $id ] ) ) {
				continue;
			}

			// If there is a suggestion entry matching this payment gateway,
			// we will add the payment gateway right after it so gateways pop-up in place of matching suggestions.
			// We rely on suggestions and matching registered PGs being mutually exclusive in the UI.
			if ( ! empty( $payment_gateways_to_suggestions_map[ $id ] ) ) {
				$suggestion_id           = $payment_gateways_to_suggestions_map[ $id ]['id'];
				$suggestion_order_map_id = $this->get_suggestion_order_map_id( $suggestion_id );

				if ( isset( $order_map[ $suggestion_order_map_id ] ) ) {
					// Determine the offset for placing missing PGs after this suggestion.
					if ( ! isset( $suggestion_order_map_id_to_offset_map[ $suggestion_order_map_id ] ) ) {
						$suggestion_order_map_id_to_offset_map[ $suggestion_order_map_id ] = 0;
					}
					$suggestion_order_map_id_to_offset_map[ $suggestion_order_map_id ] += 1;

					// Place the missing payment gateway right after the suggestion,
					// with an offset to maintain relative order between multiple PGs matching the same suggestion.
					$order_map = Utils::order_map_place_at_order(
						$order_map,
						$id,
						$order_map[ $suggestion_order_map_id ] + $suggestion_order_map_id_to_offset_map[ $suggestion_order_map_id ]
					);

					// Remember that we handled this suggestion - don't worry about remembering it multiple times.
					$handled_suggestion_ids[] = $suggestion_id;
					continue;
				}
			}

			// Add the missing payment gateway at the end.
			$order_map[ $id ] = empty( $order_map ) ? 0 : max( $order_map ) + 1;
		}

		$handled_suggestion_ids = array_unique( $handled_suggestion_ids );

		/*
		 * Place not yet handled suggestion entries right before their matching registered payment gateway IDs.
		 * This means that registered PGs already in the order map force the suggestions
		 * to be placed/moved right before them. We rely on suggestions and registered PGs being mutually exclusive.
		 */
		foreach ( array_keys( $order_map ) as $id ) {
			// If the id is not of a payment gateway or there is no suggestion for this payment gateway, ignore it.
			if ( ! array_key_exists( $id, $payment_gateways_to_suggestions_map ) ||
				empty( $payment_gateways_to_suggestions_map[ $id ] )
			) {
				continue;
			}

			$suggestion = $payment_gateways_to_suggestions_map[ $id ];
			// If the suggestion was already handled, skip it.
			if ( in_array( $suggestion['id'], $handled_suggestion_ids, true ) ) {
				continue;
			}

			// Place the suggestion at the same order as the payment gateway
			// thus ensuring that the suggestion is placed right before the payment gateway.
			$order_map = Utils::order_map_place_at_order(
				$order_map,
				$this->get_suggestion_order_map_id( $suggestion['id'] ),
				$order_map[ $id ]
			);

			// Remember that we've handled this suggestion to avoid adding it multiple times.
			// We only want to attach the suggestion to the first payment gateway that matches the plugin slug.
			$handled_suggestion_ids[] = $suggestion['id'];
		}

		// Extract all the registered offline PMs and keep their order values.
		$offline_methods = array_filter(
			$order_map,
			array( $this, 'is_offline_payment_method' ),
			ARRAY_FILTER_USE_KEY
		);
		if ( ! empty( $offline_methods ) ) {
			/*
			 * If the offline PMs group is missing, add it before the last offline PM.
			 */
			if ( ! array_key_exists( self::OFFLINE_METHODS_ORDERING_GROUP, $order_map ) ) {
				$last_offline_method_order = max( $offline_methods );

				$order_map = Utils::order_map_place_at_order( $order_map, self::OFFLINE_METHODS_ORDERING_GROUP, $last_offline_method_order );
			}

			/*
			 * Place all the offline PMs right after the offline PMs group entry.
			 */
			$target_order = $order_map[ self::OFFLINE_METHODS_ORDERING_GROUP ] + 1;
			// Sort the offline PMs by their order.
			asort( $offline_methods );
			foreach ( $offline_methods as $offline_method => $order ) {
				$order_map = Utils::order_map_place_at_order( $order_map, $offline_method, $target_order );
				++$target_order;
			}
		}

		return Utils::order_map_normalize( $order_map );
	}

	/**
	 * Get the ID of the suggestion order map entry.
	 *
	 * @param string $suggestion_id The ID of the suggestion.
	 *
	 * @return string The ID of the suggestion order map entry.
	 */
	public function get_suggestion_order_map_id( string $suggestion_id ): string {
		return self::SUGGESTION_ORDERING_PREFIX . $suggestion_id;
	}

	/**
	 * Check if the ID is a suggestion order map entry ID.
	 *
	 * @param string $id The ID to check.
	 *
	 * @return bool True if the ID is a suggestion order map entry ID, false otherwise.
	 */
	public function is_suggestion_order_map_id( string $id ): bool {
		return 0 === strpos( $id, self::SUGGESTION_ORDERING_PREFIX );
	}

	/**
	 * Get the ID of the suggestion from the suggestion order map entry ID.
	 *
	 * @param string $order_map_id The ID of the suggestion order map entry.
	 *
	 * @return string The ID of the suggestion.
	 */
	public function get_suggestion_id_from_order_map_id( string $order_map_id ): string {
		return str_replace( self::SUGGESTION_ORDERING_PREFIX, '', $order_map_id );
	}

	/**
	 * Reset the memoized data. Useful for testing purposes.
	 *
	 * @internal
	 * @return void
	 */
	public function reset_memo(): void {
		$this->payment_gateways_memo             = array();
		$this->payment_gateways_for_display_memo = array();
	}

	/**
	 * Handle payment gateways with non-standard registration behavior.
	 *
	 * @param array $payment_gateways The payment gateways list.
	 *
	 * @return array The payment gateways list with the necessary adjustments.
	 */
	private function handle_non_standard_registration_for_payment_gateways( array $payment_gateways ): array {
		/*
		 * Handle the Mollie gateway's particular behavior: if there are no API keys or no PMs enabled,
		 * the extension doesn't register a gateway instance.
		 * We will need to register a mock gateway to represent Mollie in the settings page.
		 */
		$payment_gateways = $this->maybe_add_pseudo_mollie_gateway( $payment_gateways );

		return $payment_gateways;
	}

	/**
	 * Add the pseudo Mollie gateway to the payment gateways list if necessary.
	 *
	 * @param array $payment_gateways The payment gateways list.
	 *
	 * @return array The payment gateways list with the pseudo Mollie gateway added if necessary.
	 */
	private function maybe_add_pseudo_mollie_gateway( array $payment_gateways ): array {
		$mollie_provider = $this->get_payment_gateway_provider_instance( 'mollie' );

		// Do nothing if there is a Mollie gateway registered.
		if ( $mollie_provider->is_gateway_registered( $payment_gateways ) ) {
			return $payment_gateways;
		}

		// Get the Mollie suggestion and determine if the plugin is active.
		$mollie_suggestion = $this->get_extension_suggestion_by_id( ExtensionSuggestions::MOLLIE );
		if ( empty( $mollie_suggestion ) ) {
			return $payment_gateways;
		}
		// Do nothing if the plugin is not active.
		if ( self::EXTENSION_ACTIVE !== $mollie_suggestion['plugin']['status'] ) {
			return $payment_gateways;
		}

		// Add the pseudo Mollie gateway to the list since the plugin is active but there is no Mollie gateway registered.
		$payment_gateways[] = $mollie_provider->get_pseudo_gateway( $mollie_suggestion );

		return $payment_gateways;
	}

	/**
	 * Enhance the payment gateway details with additional information from other sources.
	 *
	 * @param array              $gateway_details The gateway details to enhance.
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 * @param string             $country_code    The country code for which the details are being enhanced.
	 *                                            This should be an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The enhanced gateway details.
	 */
	private function enhance_payment_gateway_details( array $gateway_details, WC_Payment_Gateway $payment_gateway, string $country_code ): array {
		// We discriminate between offline payment methods and gateways.
		$gateway_details['_type'] = $this->is_offline_payment_method( $payment_gateway->id ) ? self::TYPE_OFFLINE_PM : self::TYPE_GATEWAY;

		$plugin_slug = $gateway_details['plugin']['slug'];
		// The payment gateway plugin might use a non-standard directory name.
		// Try to normalize it to the common slug to avoid false negatives when matching.
		$normalized_plugin_slug = Utils::normalize_plugin_slug( $plugin_slug );

		// If we have a matching suggestion, hoist details from there.
		// The suggestions only know about the normalized (aka official) plugin slug.
		$suggestion = $this->get_extension_suggestion_by_plugin_slug( $normalized_plugin_slug, $country_code );
		if ( ! is_null( $suggestion ) ) {
			// The title, description, icon, and image from the suggestion take precedence over the ones from the gateway.
			// This is temporary until we update the partner extensions.
			// Do not override the title and description for certain suggestions because theirs are more descriptive
			// (like including the payment method when registering multiple gateways for the same provider).
			if ( ! in_array(
				$suggestion['id'],
				array(
					ExtensionSuggestions::PAYPAL_FULL_STACK,
					ExtensionSuggestions::PAYPAL_WALLET,
					ExtensionSuggestions::MOLLIE,
					ExtensionSuggestions::MONEI,
					ExtensionSuggestions::ANTOM,
					ExtensionSuggestions::MERCADO_PAGO,
					ExtensionSuggestions::AMAZON_PAY,
					ExtensionSuggestions::SQUARE,
					ExtensionSuggestions::PAYONEER,
					ExtensionSuggestions::AIRWALLEX,
					ExtensionSuggestions::COINBASE,         // We don't have suggestion details yet.
					ExtensionSuggestions::AUTHORIZE_NET,    // We don't have suggestion details yet.
					ExtensionSuggestions::BOLT,             // We don't have suggestion details yet.
					ExtensionSuggestions::DEPAY,            // We don't have suggestion details yet.
					ExtensionSuggestions::ELAVON,           // We don't have suggestion details yet.
					ExtensionSuggestions::FORTISPAY,        // We don't have suggestion details yet.
					ExtensionSuggestions::PAYPAL_ZETTLE,    // We don't have suggestion details yet.
					ExtensionSuggestions::RAPYD,            // We don't have suggestion details yet.
					ExtensionSuggestions::PAYPAL_BRAINTREE, // We don't have suggestion details yet.
				),
				true
			) ) {
				if ( ! empty( $suggestion['title'] ) ) {
					$gateway_details['title'] = $suggestion['title'];
				}

				if ( ! empty( $suggestion['description'] ) ) {
					$gateway_details['description'] = $suggestion['description'];
				}
			}

			if ( ! empty( $suggestion['icon'] ) ) {
				$gateway_details['icon'] = $suggestion['icon'];
			}

			if ( ! empty( $suggestion['image'] ) ) {
				$gateway_details['image'] = $suggestion['image'];
			}

			if ( empty( $gateway_details['links'] ) ) {
				$gateway_details['links'] = $suggestion['links'];
			}
			if ( empty( $gateway_details['tags'] ) ) {
				$gateway_details['tags'] = $suggestion['tags'];
			}
			if ( empty( $gateway_details['plugin'] ) ) {
				$gateway_details['plugin'] = $suggestion['plugin'];
			}
			if ( empty( $gateway_details['_incentive'] ) && ! empty( $suggestion['_incentive'] ) ) {
				$gateway_details['_incentive'] = $suggestion['_incentive'];
			}

			// Attach the suggestion ID to the gateway details so we can reference it with precision.
			$gateway_details['_suggestion_id'] = $suggestion['id'];
		}

		// Get the gateway's corresponding plugin details.
		$plugin_data = PluginsHelper::get_plugin_data( $plugin_slug );
		if ( ! empty( $plugin_data ) ) {
			// If there are no links, try to get them from the plugin data.
			if ( empty( $gateway_details['links'] ) ) {
				if ( is_array( $plugin_data ) && ! empty( $plugin_data['PluginURI'] ) ) {
					$gateway_details['links'] = array(
						array(
							'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
							'url'   => esc_url( $plugin_data['PluginURI'] ),
						),
					);
				} elseif ( ! empty( $gateway_details['plugin']['_type'] ) &&
							ExtensionSuggestions::PLUGIN_TYPE_WPORG === $gateway_details['plugin']['_type'] ) {

					// Fallback to constructing the WPORG plugin URI from the normalized plugin slug.
					$gateway_details['links'] = array(
						array(
							'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
							'url'   => 'https://wordpress.org/plugins/' . $normalized_plugin_slug,
						),
					);
				}
			}
		}

		return $gateway_details;
	}

	/**
	 * Check if the store has any enabled ecommerce gateways.
	 *
	 * We exclude offline payment methods from this check.
	 *
	 * @return bool True if the store has any enabled ecommerce gateways, false otherwise.
	 */
	private function has_enabled_ecommerce_gateways(): bool {
		$gateways         = $this->get_payment_gateways( false ); // We want the raw gateways list.
		$enabled_gateways = array_filter(
			$gateways,
			function ( $gateway ) {
				// Filter out offline gateways.
				return 'yes' === $gateway->enabled && ! $this->is_offline_payment_method( $gateway->id );
			}
		);

		return ! empty( $enabled_gateways );
	}

	/**
	 * Enhance a payment extension suggestion with additional information.
	 *
	 * @param array $extension_suggestion The extension suggestion.
	 *
	 * @return array The enhanced payment extension suggestion.
	 */
	private function enhance_extension_suggestion( array $extension_suggestion ): array {
		// Determine the category of the extension.
		switch ( $extension_suggestion['_type'] ) {
			case ExtensionSuggestions::TYPE_PSP:
				$extension_suggestion['category'] = self::CATEGORY_PSP;
				break;
			case ExtensionSuggestions::TYPE_EXPRESS_CHECKOUT:
				$extension_suggestion['category'] = self::CATEGORY_EXPRESS_CHECKOUT;
				break;
			case ExtensionSuggestions::TYPE_BNPL:
				$extension_suggestion['category'] = self::CATEGORY_BNPL;
				break;
			case ExtensionSuggestions::TYPE_CRYPTO:
				$extension_suggestion['category'] = self::CATEGORY_CRYPTO;
				break;
			default:
				$extension_suggestion['category'] = '';
				break;
		}

		// Determine the PES's plugin status.
		// Default to not installed.
		$extension_suggestion['plugin']['status'] = self::EXTENSION_NOT_INSTALLED;
		// Put in the default plugin file.
		$extension_suggestion['plugin']['file'] = '';
		if ( ! empty( $extension_suggestion['plugin']['slug'] ) ) {
			// This is a best-effort approach, as the plugin might be sitting under a directory (slug) that we can't handle.
			// Always try the official plugin slug first, then the testing variations.
			$plugin_slug_variations = Utils::generate_testing_plugin_slugs( $extension_suggestion['plugin']['slug'], true );
			// Favor active plugins by checking the entire variations list for active plugins first.
			// This way we handle cases where there are multiple variations installed and one is active.
			$found = false;
			foreach ( $plugin_slug_variations as $plugin_slug ) {
				if ( PluginsHelper::is_plugin_active( $plugin_slug ) ) {
					$found                                    = true;
					$extension_suggestion['plugin']['status'] = self::EXTENSION_ACTIVE;
					// Make sure we put in the actual slug and file path that we found.
					$extension_suggestion['plugin']['slug'] = $plugin_slug;
					$extension_suggestion['plugin']['file'] = PluginsHelper::get_plugin_path_from_slug( $plugin_slug );
					// Sanity check.
					if ( ! is_string( $extension_suggestion['plugin']['file'] ) ) {
						$extension_suggestion['plugin']['file'] = '';
						break;
					}
					// Remove the .php extension from the file path. The WP API expects it without it.
					$extension_suggestion['plugin']['file'] = Utils::trim_php_file_extension( $extension_suggestion['plugin']['file'] );
					break;
				}
			}
			if ( ! $found ) {
				foreach ( $plugin_slug_variations as $plugin_slug ) {
					if ( PluginsHelper::is_plugin_installed( $plugin_slug ) ) {
						$extension_suggestion['plugin']['status'] = self::EXTENSION_INSTALLED;
						// Make sure we put in the actual slug and file path that we found.
						$extension_suggestion['plugin']['slug'] = $plugin_slug;
						$extension_suggestion['plugin']['file'] = PluginsHelper::get_plugin_path_from_slug( $plugin_slug );
						// Sanity check.
						if ( ! is_string( $extension_suggestion['plugin']['file'] ) ) {
							$extension_suggestion['plugin']['file'] = '';
							break;
						}
						// Remove the .php extension from the file path. The WP API expects it without it.
						$extension_suggestion['plugin']['file'] = Utils::trim_php_file_extension( $extension_suggestion['plugin']['file'] );
						break;
					}
				}
			}
		}

		// Finally, allow the extension suggestion's matching provider to add further details.
		$gateway_provider     = $this->get_payment_extension_suggestion_provider_instance( $extension_suggestion['id'] );
		$extension_suggestion = $gateway_provider->enhance_extension_suggestion( $extension_suggestion );

		return $extension_suggestion;
	}

	/**
	 * Check if a payment extension suggestion has been hidden by the user.
	 *
	 * @param array $extension The extension suggestion.
	 *
	 * @return bool True if the extension suggestion is hidden, false otherwise.
	 */
	private function is_payment_extension_suggestion_hidden( array $extension ): bool {
		$user_payments_nox_profile = get_user_meta( get_current_user_id(), Payments::PAYMENTS_NOX_PROFILE_KEY, true );
		if ( empty( $user_payments_nox_profile ) ) {
			return false;
		}
		$user_payments_nox_profile = maybe_unserialize( $user_payments_nox_profile );

		if ( empty( $user_payments_nox_profile['hidden_suggestions'] ) ) {
			return false;
		}

		return in_array( $extension['id'], array_column( $user_payments_nox_profile['hidden_suggestions'], 'id' ), true );
	}

	/**
	 * Apply order mappings to a base payment providers order map.
	 *
	 * @param array $base_map     The base order map.
	 * @param array $new_mappings The order mappings to apply.
	 *                            This can be a full or partial list of the base one,
	 *                            but it can also contain (only) new provider IDs and their orders.
	 *
	 * @return array The updated base order map, normalized.
	 */
	private function payment_providers_order_map_apply_mappings( array $base_map, array $new_mappings ): array {
		// Sanity checks.
		// Remove any null or non-integer values.
		$new_mappings = array_filter( $new_mappings, 'is_int' );
		if ( empty( $new_mappings ) ) {
			$new_mappings = array();
		}

		// If we have no existing order map or
		// both the base and the new map have the same length and keys, we can simply use the new map.
		if ( empty( $base_map ) ||
			( count( $base_map ) === count( $new_mappings ) &&
				empty( array_diff( array_keys( $base_map ), array_keys( $new_mappings ) ) ) )
		) {
			$new_order_map = $new_mappings;
		} else {
			// If we are dealing with ONLY offline PMs updates (for all that are registered) and their group is present,
			// normalize the new order map to keep behavior as intended (i.e., reorder only inside the offline PMs list).
			$offline_pms = $this->get_offline_payment_methods_gateways();
			// Make it a list keyed by the payment gateway ID.
			$offline_pms = array_combine(
				array_map(
					fn( $gateway ) => $gateway->id,
					$offline_pms
				),
				$offline_pms
			);
			if (
				isset( $base_map[ self::OFFLINE_METHODS_ORDERING_GROUP ] ) &&
				count( $new_mappings ) === count( $offline_pms ) &&
				empty( array_diff( array_keys( $new_mappings ), array_keys( $offline_pms ) ) )
			) {

				$new_mappings = Utils::order_map_change_min_order( $new_mappings, $base_map[ self::OFFLINE_METHODS_ORDERING_GROUP ] + 1 );
			}

			$new_order_map = Utils::order_map_apply_mappings( $base_map, $new_mappings );
		}

		return Utils::order_map_normalize( $new_order_map );
	}

	/**
	 * Group payment gateways by their plugin extension filename.
	 *
	 * @param WC_Payment_Gateway[] $gateways     The list of payment gateway instances to group.
	 * @param string               $country_code Optional. The country code for which the gateways are being generated.
	 *                                           This should be an ISO 3166-1 alpha-2 country code.
	 *
	 * @return array The grouped payment gateway instances, keyed by the plugin file.
	 *               Each group contains an array of payment gateway instances that belong to the same plugin.
	 *               If a payment gateway does not have a corresponding plugin file,
	 *               it will be grouped under the 'unknown_extension' key.
	 */
	private function group_gateways_by_extension( array $gateways, string $country_code = '' ): array {
		$grouped = array(
			// This is the group for gateways that we don't know how to group by extension.
			// It can be used for gateways that are not registered by a WP plugin.
			'unknown_extension' => array(),
		);

		foreach ( $gateways as $gateway ) {
			// Get the payment gateway details, but use a dummy gateway order since it is inconsequential here.
			$gateway_details = $this->get_payment_gateway_details( $gateway, 0, $country_code );
			// If we don't have the necessary plugin details, put it in the unknown group.
			if ( empty( $gateway_details ) || ! isset( $gateway_details['plugin'] ) || empty( $gateway_details['plugin']['file'] ) ) {
				$grouped['unknown_extension'][] = $gateway;
				continue;
			}

			if ( empty( $grouped[ $gateway_details['plugin']['file'] ] ) ) {
				$grouped[ $gateway_details['plugin']['file'] ] = array();
			}

			$grouped[ $gateway_details['plugin']['file'] ][] = $gateway;
		}

		return $grouped;
	}
}
