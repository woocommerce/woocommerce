<?php
/**
 * WooCommerce Payment Gateways
 *
 * Manages payment gateways through a registry pattern.
 *
 * @package WooCommerce\Classes\Payment
 */

use Automattic\WooCommerce\Internal\Admin\Settings\Payments as SettingsPaymentsService;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\ArrayUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Payment gateways registry class.
 */
class WC_Payment_Gateways {
	/**
	 * Registry object for gateways.
	 *
	 * @var WC_Payment_Gateway_Registry
	 */
	private WC_Payment_Gateway_Registry $registry;

	/**
	 * Memoization cache for gateway verification results.
	 *
	 * @var array
	 */
	private array $verification_cache = [];

	/**
	 * Memoization cache for file signatures.
	 *
	 * @var array
	 */
	private array $signature_cache = [];

	/**
	 * Public key resource for signature verification.
	 *
	 * @var resource|null
	 */
	private $public_key_resource = null;

	/**
	 * Registry of gateways that were rejected due to security issues.
	 *
	 * @var array
	 */
	private array $rejected_gateways = [];

	/**
	 * Flag indicating if the system is currently in a gateway registration process.
	 *
	 * @var bool
	 */
	private bool $is_registering = false;

	/**
	 * The single instance of the class.
	 *
	 * @var WC_Payment_Gateways
	 */
	protected static $_instance = null;

	/**
	 * Main WC_Payment_Gateways Instance.
	 *
	 * Ensures only one instance of WC_Payment_Gateways is loaded or can be loaded.
	 *
	 * @return WC_Payment_Gateways Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Initialize payment gateways.
	 */
	public function __construct() {
		$this->registry = new WC_Payment_Gateway_Registry();
		$this->init();
	}

	/**
	 * Initialize the gateway registry and register gateways.
	 */
	public function init() {
		try {
			// Register core gateways
			$this->register_core_gateways();

			// Use a two-phase registration approach with a temporary registry
			$temp_registry = $this->collect_extension_gateways();

			// Process extension gateways with verification
			$this->process_extension_gateways( $temp_registry );

			// Handle legacy gateway registration
			$this->handle_legacy_gateway_registration();

			// Sort gateways by order
			$this->sort_gateways();

			// Set up notification hooks for gateway settings changes
			$this->setup_gateway_notifications();
		} catch ( Exception $e ) {
			// Log any unexpected exceptions during initialization
			$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );
			$logger->error(
				'Payment gateway initialization error: ' . $e->getMessage(),
				[ 'source' => 'payment-gateway-initialization' ]
			);
		} finally {
			// Always fire the initialized action, even if there were errors
			do_action( 'wc_payment_gateways_initialized', $this );
		}
	}

	/**
	 * Collect extension gateways using action hook.
	 *
	 * @return WC_Payment_Gateway_Registry
	 */
	private function collect_extension_gateways(): WC_Payment_Gateway_Registry {
		// Create a temporary registry for extensions
		$extension_registry = new WC_Payment_Gateway_Registry();

		// Set registering flag to detect recursive registration attempts
		$this->is_registering = true;

		try {
			// Use output buffering to prevent unwanted output during hook execution
			ob_start();

			// Execute the hook
			do_action( 'woocommerce_register_payment_gateways', $extension_registry );

			// Clear any output to prevent header manipulation
			ob_end_clean();
		} catch ( Exception $e ) {
			// Log any errors during gateway registration
			$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );
			$logger->error(
				'Error during gateway registration: ' . $e->getMessage(),
				[ 'source' => 'payment-gateway-registration' ]
			);

			// Ensure output buffering is ended
			if ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
		} finally {
			// Always reset the flag
			$this->is_registering = false;
		}

		return $extension_registry;
	}

	/**
	 * Process gateways from extensions with verification.
	 *
	 * @param WC_Payment_Gateway_Registry $extension_registry
	 */
	private function process_extension_gateways( WC_Payment_Gateway_Registry $extension_registry ): void {
		// Extract all gateways to a separate array to avoid iterator modification issues
		$gateways = array_values( $extension_registry->get_all() );

		// Process each gateway with verification
		foreach ( $gateways as $gateway ) {
			$this->register_gateway( $gateway );
		}
	}

	/**
	 * Register the core payment gateways.
	 */
	private function register_core_gateways() {
		$core_gateways = [
			'WC_Gateway_BACS',
			'WC_Gateway_Cheque',
			'WC_Gateway_COD',
		];

		if ( $this->should_load_paypal_standard() ) {
			$core_gateways[] = 'WC_Gateway_Paypal';
		}

		foreach ( $core_gateways as $gateway_class ) {
			if ( class_exists( $gateway_class ) ) {
				$gateway = new $gateway_class();
				// Core gateways are now verified like any other gateway
				$this->register_gateway( $gateway );
			}
		}
	}

	/**
	 * Handle legacy gateway registration for backward compatibility.
	 */
	private function handle_legacy_gateway_registration() {
		// Legacy filter for backward compatibility
		$custom_gateways = apply_filters( 'woocommerce_payment_gateways', [] );

		if ( ! empty( $custom_gateways ) ) {
			wc_doing_it_wrong(
				'woocommerce_payment_gateways',
				sprintf(
				/* translators: %s: woocommerce_register_payment_gateways action name */
					__( 'The "woocommerce_payment_gateways" filter is deprecated. Please use the "%s" action instead.', 'woocommerce' ),
					'woocommerce_register_payment_gateways'
				),
				'10.0.0'
			);

			foreach ( $custom_gateways as $gateway ) {
				// Skip core gateways that are already registered
				if ( is_string( $gateway ) && in_array( $gateway, [
						'WC_Gateway_BACS',
						'WC_Gateway_Cheque',
						'WC_Gateway_COD',
						'WC_Gateway_Paypal',
					], true ) ) {
					continue;
				}

				// Handle string class names by instantiating them
				if ( is_string( $gateway ) && class_exists( $gateway ) ) {
					try {
						$gateway = new $gateway();
					} catch ( Exception $e ) {
						continue;
					}
				}

				// Only register valid gateway instances
				if ( is_a( $gateway, 'WC_Payment_Gateway' ) ) {
					// Don't register if already registered
					if ( ! $this->registry->has( $gateway->id ) ) {
						$this->register_gateway( $gateway );
					}
				}
			}
		}
	}

	/**
	 * Register a payment gateway with fingerprint verification.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance to register.
	 *
	 * @return bool True if added successfully, false if already exists or fails verification.
	 */
	private function register_gateway( WC_Payment_Gateway $gateway ): bool {
		if ( ! is_a( $gateway, 'WC_Payment_Gateway' ) ) {
			return false;
		}

		// Check if we're in a recursive registration state
		if ( $this->is_registering === false && ! defined( 'WP_INSTALLING' ) ) {
			// This is a late registration attempt outside the normal flow
			$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );
			$logger->warning(
				sprintf( 'Attempted to register gateway %s outside of normal registration flow', get_class( $gateway ) ),
				[ 'source' => 'payment-gateway-security' ]
			);

			return false;
		}

		$gateway_class = get_class( $gateway );

		// Check against rejected gateways list
		if ( isset( $this->rejected_gateways[ $gateway_class ] ) ) {
			return false;
		}

		// Perform fingerprint verification for all gateways
		if ( ! isset( $this->verification_cache[ $gateway_class ] ) ) {
			$this->verification_cache[ $gateway_class ] = $this->verify_gateway_fingerprint( $gateway );
		}

		if ( ! $this->verification_cache[ $gateway_class ] ) {
			// Add to rejected gateways for future reference
			$this->rejected_gateways[ $gateway_class ] = true;
			$this->notify_admin_gateway_security_issue( $gateway );

			return false;
		}

		return $this->registry->register( $gateway );
	}

	/**
	 * Verify a gateway's fingerprint and its parent classes' fingerprints.
	 *
	 * @param WC_Payment_Gateway $gateway The gateway to verify.
	 *
	 * @return bool True if fingerprint verification passes, false otherwise.
	 */
	private function verify_gateway_fingerprint( WC_Payment_Gateway $gateway ): bool {
		try {
			// Get the gateway class and all its parent classes up to but not including WC_Payment_Gateway
			$class             = get_class( $gateway );
			$inheritance_chain = $this->get_inheritance_chain( $class );

			// Verify each class in the inheritance chain
			foreach ( $inheritance_chain as $class_name ) {
				$reflection = new ReflectionClass( $class_name );
				$class_file = $reflection->getFileName();

				if ( ! $class_file ) {
					// Unable to determine class file
					return false;
				}

				// Check if signature file exists
				$signature_file = $this->get_signature_file_path( $class_file );
				if ( ! file_exists( $signature_file ) ) {
					// Missing signature file
					return false;
				}

				// Read class file content and signature from cache or file
				$file_contents = $this->get_file_contents( $class_file );
				$signature     = $this->get_signature_contents( $signature_file );
				if ( $file_contents === false || $signature === false ) {
					return false;
				}

				// Get and initialize public key (memoized)
				if ( ! $this->initialize_public_key() ) {
					return false;
				}

				// Verify signature
				$result = openssl_verify( $file_contents, $signature, $this->public_key_resource, OPENSSL_ALGO_SHA256 );

				if ( $result !== 1 ) {
					return false;
				}
			}

			return true;
		} catch ( Exception $e ) {
			$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );
			$logger->error(
				'Gateway verification error: ' . $e->getMessage(),
				[ 'source' => 'payment-gateway-verification' ]
			);

			return false;
		}
	}

	/**
	 * Get the inheritance chain of a class up to but not including WC_Payment_Gateway.
	 *
	 * @param string $class_name The class name to get the inheritance chain for.
	 *
	 * @return array An array of class names in the inheritance chain.
	 */
	private function get_inheritance_chain( string $class_name ): array {
		$inheritance_chain = [];
		$current_class     = $class_name;

		while ( $current_class !== 'WC_Payment_Gateway' && $current_class ) {
			$inheritance_chain[] = $current_class;
			$current_class       = get_parent_class( $current_class );
		}

		return $inheritance_chain;
	}

	/**
	 * Get cached file contents or load from disk.
	 *
	 * @param string $file_path Path to the file.
	 *
	 * @return string|false File contents or false on failure.
	 */
	private function get_file_contents( string $file_path ) {
		$cache_key = md5( $file_path );

		if ( ! isset( $this->signature_cache[ $cache_key ] ) ) {
			$contents = @file_get_contents( $file_path );
			if ( $contents === false ) {
				return false;
			}
			$this->signature_cache[ $cache_key ] = $contents;
		}

		return $this->signature_cache[ $cache_key ];
	}

	/**
	 * Get cached signature contents or load from disk and decode.
	 *
	 * @param string $signature_path Path to the signature file.
	 *
	 * @return string|false Decoded signature or false on failure.
	 */
	private function get_signature_contents( string $signature_path ) {
		$cache_key = md5( $signature_path );

		if ( ! isset( $this->signature_cache[ $cache_key ] ) ) {
			$encoded_signature = @file_get_contents( $signature_path );
			if ( $encoded_signature === false ) {
				return false;
			}
			$signature = base64_decode( $encoded_signature );
			if ( $signature === false ) {
				return false;
			}
			$this->signature_cache[ $cache_key ] = $signature;
		}

		return $this->signature_cache[ $cache_key ];
	}

	/**
	 * Get the signature file path for a gateway class file.
	 *
	 * @param string $class_file_path The path to the gateway class file.
	 *
	 * @return string The path to the signature file.
	 */
	private function get_signature_file_path( string $class_file_path ): string {
		// The signature file is stored alongside the class file with .sig extension
		return $class_file_path . '.sig';
	}

	/**
	 * Get the public key path used for signature verification.
	 *
	 * @return string Path to the public key file.
	 */
	private function get_public_key_path(): string {
		// Use the key from the defined location
		return WC()->plugin_path() . '/includes/gateways/integrity-checks-public-key.pem';
	}

	/**
	 * Initialize the public key resource (memoized).
	 *
	 * @return bool True if initialized successfully, false otherwise.
	 */
	private function initialize_public_key(): bool {
		if ( $this->public_key_resource !== null ) {
			return $this->public_key_resource !== false;
		}

		$public_key_path = $this->get_public_key_path();
		if ( ! file_exists( $public_key_path ) ) {
			// Missing public key
			$this->public_key_resource = false;

			return false;
		}

		$public_key = @file_get_contents( $public_key_path );
		if ( $public_key === false ) {
			$this->public_key_resource = false;

			return false;
		}

		$this->public_key_resource = openssl_pkey_get_public( $public_key );

		return $this->public_key_resource !== false;
	}

	/**
	 * Set up notification hooks for gateway settings changes.
	 */
	private function setup_gateway_notifications() {
		add_action( 'wc_payment_gateways_initialized', [ $this, 'on_payment_gateways_initialized' ] );
	}

	/**
	 * Hook into payment gateway settings changes with proper isolation.
	 *
	 * @param WC_Payment_Gateways $wc_payment_gateways The WC_Payment_Gateways instance.
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function on_payment_gateways_initialized( WC_Payment_Gateways $wc_payment_gateways ) {
		// Take a snapshot of verified gateways at initialization time
		$verified_gateways = $this->registry->get_all();

		foreach ( $verified_gateways as $gateway ) {
			$option_key = $gateway->get_option_key();

			// Use closures that capture the specific gateway instance
			add_action(
				'add_option_' . $option_key,
				function ( $option, $value ) use ( $gateway ) {
					try {
						$this->payment_gateway_settings_option_changed( $gateway, $value, $option );
					} catch ( Exception $e ) {
						$this->log_gateway_error( $gateway, $e );
					}
				},
				10,
				2
			);

			add_action(
				'update_option_' . $option_key,
				function ( $old_value, $value, $option ) use ( $gateway ) {
					try {
						$this->payment_gateway_settings_option_changed( $gateway, $value, $option, $old_value );
					} catch ( Exception $e ) {
						$this->log_gateway_error( $gateway, $e );
					}
				},
				10,
				3
			);
		}
	}

	/**
	 * Log an error related to a payment gateway.
	 *
	 * @param WC_Payment_Gateway $gateway
	 * @param Exception          $error
	 */
	private function log_gateway_error( WC_Payment_Gateway $gateway, Exception $error ): void {
		$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );
		$logger->error(
			sprintf( 'Gateway error for "%s": %s', $gateway->id, $error->getMessage() ),
			[
				'source'     => 'payment-gateway',
				'gateway_id' => $gateway->id,
			]
		);
	}

	/**
	 * Callback for when a gateway settings option was added or updated.
	 *
	 * @param WC_Payment_Gateway $gateway   The gateway for which the option was added or updated.
	 * @param mixed              $value     New value.
	 * @param string             $option    Option name.
	 * @param mixed              $old_value Old value. `null` when called via add_option_ hook.
	 */
	private function payment_gateway_settings_option_changed( $gateway, $value, $option, $old_value = null ) {
		if ( $this->was_gateway_enabled( $value, $old_value ) ) {
			// Re-verify gateway integrity before enabling
			if ( $this->verify_gateway_fingerprint( $gateway ) ) {
				$this->record_gateway_event( 'enable', $gateway );
				$this->notify_admin_payment_gateway_enabled( $gateway );
			} else {
				// Reset the gateway to disabled for security
				$value['enabled'] = 'no';
				update_option( $option, $value );
				$this->notify_admin_gateway_security_issue( $gateway );
			}
		}

		if ( $this->was_gateway_disabled( $value, $old_value ) ) {
			$this->record_gateway_event( 'disable', $gateway );
		}
	}

	/**
	 * Clean up resources when object is destroyed.
	 */
	public function __destruct() {
		// Free the public key resource if it exists
		if ( $this->public_key_resource && is_resource( $this->public_key_resource ) ) {
			openssl_free_key( $this->public_key_resource );
		}
	}

	/**
	 * Sort gateways by the configured order.
	 */
	private function sort_gateways() {
		$ordering        = (array) get_option( 'woocommerce_gateway_order' );
		$order_end       = 999;
		$sorted_gateways = [];

		// Get all gateways from registry
		$gateways = $this->registry->get_all();

		// Sort gateways according to stored order
		foreach ( $gateways as $id => $gateway ) {
			if ( isset( $ordering[ $id ] ) && is_numeric( $ordering[ $id ] ) ) {
				$sorted_gateways[ $ordering[ $id ] ] = $gateway;
			} else {
				$sorted_gateways[ $order_end ] = $gateway;
				$order_end ++;
			}
		}

		ksort( $sorted_gateways );

		// Re-register gateways in the sorted order
		$new_registry = new WC_Payment_Gateway_Registry();
		foreach ( $sorted_gateways as $gateway ) {
			$new_registry->register( $gateway );
		}

		// Replace the current registry
		$this->registry = $new_registry;
	}

	/**
	 * Notify admin about a security issue with a gateway.
	 *
	 * @param WC_Payment_Gateway $gateway The gateway with the security issue.
	 */
	private function notify_admin_gateway_security_issue( $gateway ) {
		$admin_email   = get_option( 'admin_email' );
		$site_title    = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$gateway_title = $gateway->get_method_title();
		$gateway_class = get_class( $gateway );
		$site_url      = home_url();

		$subject = sprintf(
		/* translators: %1$s: Site title, %2$s: Gateway title */
			__( '[%1$s] Payment gateway "%2$s" security alert', 'woocommerce' ),
			$site_title,
			$gateway_title
		);

		$message = sprintf(
		/* translators: %1$s: Gateway title, %2$s: Gateway class, %3$s: Site URL */
			__(
				'SECURITY ALERT: The payment gateway "%1$s" (%2$s) has failed the signature verification.

As a security precaution, this gateway has been automatically disabled to protect your store.

This could indicate that:
- The gateway code has been modified without updating the signature
- Someone has tampered with the gateway code
- Malware has modified the gateway files

Please investigate this issue immediately on your site:
%3$s

This is an automated security notification from your WooCommerce store.',
				'woocommerce'
			),
			$gateway_title,
			$gateway_class,
			$site_url
		);

		wp_mail( $admin_email, $subject, $message );

		// Log the security alert
		$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );
		$logger->alert(
			sprintf( 'Security alert - payment gateway %s (%s) has been disabled due to failed signature verification',
				$gateway->id,
				$gateway_class
			),
			[ 'source' => 'payment-gateways-security' ]
		);
	}

	/**
	 * Get all registered gateways.
	 *
	 * @return array
	 */
	public function payment_gateways() {
		return $this->registry->get_all();
	}

	/**
	 * Get gateway by ID.
	 *
	 * @param string $gateway_id Gateway ID to retrieve.
	 *
	 * @return WC_Payment_Gateway|null The gateway if found, null otherwise.
	 */
	public function get_gateway( $gateway_id ) {
		return $this->registry->get( $gateway_id );
	}

	/**
	 * Get array of registered gateway ids
	 *
	 * @return array of strings
	 */
	public function get_payment_gateway_ids() {
		return $this->registry->get_ids();
	}

	/**
	 * Get available gateways for checkout.
	 *
	 * @return array The available payment gateways.
	 */
	public function get_available_payment_gateways() {
		$available_gateways = [];

		foreach ( $this->registry->get_all() as $gateway ) {
			if ( $gateway->is_available() ) {
				if ( ! is_add_payment_method_page() ) {
					$available_gateways[ $gateway->id ] = $gateway;
				} elseif ( $gateway->supports( 'add_payment_method' ) || $gateway->supports( 'tokenization' ) ) {
					$available_gateways[ $gateway->id ] = $gateway;
				}
			}
		}

		return array_filter(
			(array) apply_filters( 'woocommerce_available_payment_gateways', $available_gateways ),
			[ $this, 'filter_valid_gateway_class' ]
		);
	}

	/**
	 * Callback for array filter. Returns true if gateway is of correct type.
	 *
	 * @param object $gateway Gateway to check.
	 *
	 * @return bool
	 */
	protected function filter_valid_gateway_class( $gateway ) {
		return $gateway && is_a( $gateway, 'WC_Payment_Gateway' );
	}

	/**
	 * Set the current, active gateway.
	 *
	 * @param array $gateways Available payment gateways.
	 */
	public function set_current_gateway( $gateways ) {
		// Be on the defensive.
		if ( ! is_array( $gateways ) || empty( $gateways ) ) {
			return;
		}

		$current_gateway = false;

		if ( WC()->session ) {
			$current = WC()->session->get( 'chosen_payment_method' );

			if ( $current && isset( $gateways[ $current ] ) ) {
				$current_gateway = $gateways[ $current ];
			}
		}

		if ( ! $current_gateway ) {
			$current_gateway = current( $gateways );
		}

		// Ensure we can make a call to set_current() without triggering an error.
		if ( $current_gateway && is_callable( [ $current_gateway, 'set_current' ] ) ) {
			$current_gateway->set_current();
		}
	}

	/**
	 * Save options in admin.
	 */
	public function process_admin_options() {
		$gateway_order = isset( $_POST['gateway_order'] ) ? wc_clean( wp_unslash( $_POST['gateway_order'] ) ) : ''; // WPCS: input var ok, CSRF ok.
		$order         = [];

		if ( is_array( $gateway_order ) && count( $gateway_order ) > 0 ) {
			$loop = 0;
			foreach ( $gateway_order as $gateway_id ) {
				$order[ esc_attr( $gateway_id ) ] = $loop;
				++ $loop;
			}
		}

		update_option( 'woocommerce_gateway_order', $order );
	}

	/**
	 * Email the site admin when a payment gateway has been enabled.
	 *
	 * @param WC_Payment_Gateway $gateway The gateway that was enabled.
	 *
	 * @return bool Whether the email was sent or not.
	 */
	private function notify_admin_payment_gateway_enabled( $gateway ) {
		$admin_email          = get_option( 'admin_email' );
		$user                 = get_user_by( 'email', $admin_email );
		$username             = $user ? $user->user_login : $admin_email;
		$gateway_title        = $gateway->get_method_title();
		$gateway_settings_url = esc_url_raw( self_admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $gateway->id ) );
		$site_name            = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$site_url             = home_url();

		$email_addresses   = apply_filters( 'wc_payment_gateway_enabled_notification_email_addresses', [], $gateway );
		$email_addresses[] = $admin_email;
		$email_addresses   = array_unique(
			array_filter(
				$email_addresses,
				function ( $email_address ) {
					return is_email( $email_address );
				}
			)
		);

		$logger = wc_get_container()->get( LegacyProxy::class )->call_function( 'wc_get_logger' );
		$logger->info( sprintf( 'Payment gateway enabled: "%s"', $gateway_title ) );

		$email_text = sprintf(
		/* translators: Payment gateway enabled notification email. */
			__(
				'Howdy %1$s,

The payment gateway "%2$s" was just enabled on this site:
%3$s

If this was intentional you can safely ignore and delete this email.

If you did not enable this payment gateway, please log in to your site and consider disabling it here:
%4$s

This email has been sent to %5$s

Regards,
All at %6$s
%7$s',
				'woocommerce'
			),
			$username,
			$gateway_title,
			$site_url,
			$gateway_settings_url,
			$admin_email,
			$site_name,
			$site_url
		);

		if ( '' !== get_option( 'blogname' ) ) {
			$site_title = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		} else {
			$site_title = wp_parse_url( home_url(), PHP_URL_HOST );
		}

		return wp_mail(
			$email_addresses,
			sprintf(
			/* translators: Payment gateway enabled notification email subject. */
				__( '[%1$s] Payment gateway "%2$s" enabled', 'woocommerce' ),
				$site_title,
				$gateway_title
			),
			$email_text
		);
	}

	/**
	 * Determines from changes in settings if a gateway was enabled.
	 *
	 * @param array $value     New value.
	 * @param array $old_value Old value.
	 *
	 * @return bool Whether the gateway was enabled or not.
	 */
	private function was_gateway_enabled( $value, $old_value = null ) {
		if ( null === $old_value ) {
			// There was no old value, so this is a new option.
			if ( ! empty( $value ) && is_array( $value ) && isset( $value['enabled'] ) && 'yes' === $value['enabled'] && isset( $value['title'] ) ) {
				return true;
			}

			return false;
		}
		// There was an old value, so this is an update.
		if (
			ArrayUtil::get_value_or_default( $value, 'enabled' ) === 'yes' &&
			ArrayUtil::get_value_or_default( $old_value, 'enabled' ) !== 'yes'
		) {
			return true;
		}

		return false;
	}

	/**
	 * Determines from changes in settings if a gateway was disabled.
	 *
	 * @param array $value     New value.
	 * @param array $old_value Old value.
	 *
	 * @return bool Whether the gateway was disabled or not.
	 */
	private function was_gateway_disabled( $value, $old_value = null ) {
		if ( null === $old_value ) {
			// There was no old value, so this is a new option.
			return false;
		}

		// There was an old value, so this is an update.
		if (
			ArrayUtil::get_value_or_default( $value, 'enabled' ) === 'no' &&
			ArrayUtil::get_value_or_default( $old_value, 'enabled' ) !== 'no'
		) {
			return true;
		}

		return false;
	}

	/**
	 * Determines if PayPal Standard should be loaded.
	 *
	 * @return bool Whether PayPal Standard should be loaded or not.
	 */
	protected function should_load_paypal_standard() {
		$paypal = new WC_Gateway_Paypal();

		return $paypal->should_load();
	}

	/**
	 * Send a Tracks event.
	 *
	 * @param string             $name    The event name.
	 * @param WC_Payment_Gateway $gateway The payment gateway object.
	 *
	 * @return void
	 */
	private function record_gateway_event( string $name, $gateway ) {
		if ( ! function_exists( 'wc_admin_record_tracks_event' ) ) {
			return;
		}

		if ( ! is_a( $gateway, 'WC_Payment_Gateway' ) ) {
			return;
		}

		if ( empty( $name ) ) {
			return;
		}

		// If the event name is not prefixed, we prefix it.
		$prefix = SettingsPaymentsService::EVENT_PREFIX . 'provider_';
		if ( ! str_starts_with( $name, $prefix ) ) {
			$name = $prefix . $name;
		}

		$properties = [
			'provider_id'      => $gateway->id,
			'business_country' => WC()->countries->get_base_country(),
		];

		try {
			/**
			 * The Payments Settings [page] service.
			 *
			 * @var SettingsPaymentsService $settings_payments_service
			 */
			$settings_payments_service      = wc_get_container()->get( SettingsPaymentsService::class );
			$properties['business_country'] = $settings_payments_service->get_country();

			/**
			 * The Payments Providers service.
			 *
			 * @var PaymentsProviders $payments_providers_service
			 */
			$payments_providers_service = wc_get_container()->get( PaymentsProviders::class );

			$gateway_details = $payments_providers_service->get_payment_gateway_details( $gateway, 0, $properties['business_country'] );
			if ( ! empty( $gateway_details['_suggestion_id'] ) ) {
				$properties['suggestion_id'] = $gateway_details['_suggestion_id'];
			}
			if ( ! empty( $gateway_details['plugin']['slug'] ) ) {
				$properties['provider_extension_slug'] = $gateway_details['plugin']['slug'];
			}
		} catch ( \Throwable $e ) {
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to gather provider-specific details for gateway: ' . $e->getMessage(),
				[
					'gateway'   => $gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				]
			);
		}

		wc_admin_record_tracks_event( $name, $properties );
	}
}
