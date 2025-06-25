<?php
/**
 * Plugin Name: WooCommerce Gateway Signing
 * Description: A plugin to help sign gateway classes with a private key.
 * Version: 1.0
 * Author: Vlad Olaru
 * Author URI: https://woocommerce.com
 */

/**
 * WP-CLI command to sign payment gateway classes with a private key.
 */

if (!defined('WP_CLI') || !WP_CLI) {
	return;
}

/**
 * Signs WooCommerce payment gateway class files.
 */
class WC_Gateway_Signer_Command {

	/**
	 * Default location for the private key.
	 *
	 * @var string
	 */
	private string $default_private_key_path = '';

	/**
	 * Constructor that sets up default paths.
	 */
	public function __construct() {
		// Set default private key path to the plugin directory
		$this->default_private_key_path = WP_CONTENT_DIR . '/uploads/wc-gateway-keys/private-key.pem';
	}

	/**
	 * Signs a WooCommerce payment gateway class file and all its parent classes.
	 *
	 * ## OPTIONS
	 *
	 * [<gateway_class>]
	 * : The gateway class name to sign (e.g., WC_Gateway_BACS).
	 * If omitted, all registered gateways will be signed.
	 *
	 * [--private-key=<path>]
	 * : Path to the private key file. If not provided, will look in default location.
	 *
	 * [--generate-keys]
	 * : Generate a new key pair if none exists.
	 *
	 * [--key-output=<path>]
	 * : Directory to store generated keys. Defaults to current directory.
	 *
	 * [--verify]
	 * : Verify signatures instead of creating them.
	 *
	 * [--recursive]
	 * : Sign parent classes in the inheritance chain (enabled by default).
	 *
	 * [--no-recursive]
	 * : Only sign the specified gateway class without parent classes.
	 *
	 * ## EXAMPLES
	 *
	 *     # Sign all gateways and their parent classes using default key
	 *     wp wc gateway sign
	 *
	 *     # Sign all gateways with a specific private key
	 *     wp wc gateway sign --private-key=/path/to/private-key.pem
	 *
	 *     # Sign a specific gateway and its parent classes
	 *     wp wc gateway sign WC_Gateway_BACS
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function sign($args, $assoc_args) {
		// Check if WooCommerce is active
		if (!class_exists('WooCommerce')) {
			WP_CLI::error('WooCommerce is not active.');
			return;
		}

		$verify_only = isset($assoc_args['verify']);
		$recursive = !isset($assoc_args['no-recursive']);

		// Handle key generation if requested
		if (isset($assoc_args['generate-keys'])) {
			$output_dir = isset($assoc_args['key-output']) ? $assoc_args['key-output'] : dirname($this->default_private_key_path);

			// Create directory if it doesn't exist
			if (!is_dir($output_dir)) {
				if (!wp_mkdir_p($output_dir)) {
					WP_CLI::error("Failed to create directory for keys: $output_dir");
					return;
				}
			}

			if (!$this->generate_key_pair($output_dir)) {
				WP_CLI::error('Failed to generate key pair.');
				return;
			}

			if (!isset($assoc_args['private-key'])) {
				$assoc_args['private-key'] = $output_dir . '/private-key.pem';
			}
		}

		// If we're not just verifying, ensure we have a private key
		$private_key = null;
		if (!$verify_only) {
			// Check if private key is specified or use default
			$private_key_path = isset($assoc_args['private-key']) ? $assoc_args['private-key'] : $this->default_private_key_path;

			if (!file_exists($private_key_path)) {
				WP_CLI::error("Private key file not found at: $private_key_path. Use --private-key to specify a key or --generate-keys to create one.");
				return;
			}

			$private_key = file_get_contents($private_key_path);
			if ($private_key === false) {
				WP_CLI::error("Failed to read private key from: $private_key_path");
				return;
			}

			WP_CLI::log("Using private key: $private_key_path");
		}

		// Get public key path for verification
		$public_key_path = WP_PLUGIN_DIR . '/woocommerce/includes/gateways/integrity-checks-public-key.pem';

		// Get gateway class(es) to sign
		$gateways = [];
		$processed_classes = [];

		if (!empty($args[0])) {
			// Specific gateway class provided
			$gateway_class = $args[0];
			if (!class_exists($gateway_class)) {
				WP_CLI::error("Gateway class not found: $gateway_class");
				return;
			}

			$gateways[] = $gateway_class;
		} else {
			// No specific class provided, get all registered gateways
			WC()->payment_gateways(); // Initialize payment gateways
			$available_gateways = WC()->payment_gateways()->payment_gateways();

			if (empty($available_gateways)) {
				WP_CLI::error('No payment gateways found.');
				return;
			}

			foreach ($available_gateways as $gateway) {
				$gateways[] = get_class($gateway);
			}
		}

		// Process each gateway and its parent classes
		$success_count = 0;
		$fail_count = 0;

		foreach ($gateways as $gateway_class) {
			if ($recursive) {
				// Get inheritance chain for the gateway
				$inheritance_chain = $this->get_inheritance_chain($gateway_class);
				WP_CLI::log("Processing inheritance chain for $gateway_class:");

				foreach ($inheritance_chain as $class_name) {
					// Skip if we've already processed this class
					if (isset($processed_classes[$class_name])) {
						WP_CLI::log("  - $class_name (already processed)");
						continue;
					}

					$processed_classes[$class_name] = true;
					WP_CLI::log("  - Processing $class_name");

					$result = $this->process_class($class_name, $private_key, $public_key_path, $verify_only);
					if ($result) {
						$success_count++;
					} else {
						$fail_count++;
					}
				}
			} else {
				// Just process the specific gateway class
				if (!isset($processed_classes[$gateway_class])) {
					$processed_classes[$gateway_class] = true;
					$result = $this->process_class($gateway_class, $private_key, $public_key_path, $verify_only);
					if ($result) {
						$success_count++;
					} else {
						$fail_count++;
					}
				}
			}
		}

		// Report results
		$action = $verify_only ? 'Verified' : 'Signed';
		WP_CLI::success("$action $success_count class(es), failed on $fail_count class(es).");
	}

	/**
	 * Process a single class - sign or verify it.
	 *
	 * @param string $class_name The class to process.
	 * @param string|null $private_key Private key for signing (null for verify only).
	 * @param string $public_key_path Path to public key for verification.
	 * @param bool $verify_only Whether to only verify signatures.
	 * @return bool True if successful, false otherwise.
	 */
	private function process_class($class_name, $private_key, $public_key_path, $verify_only) {
		try {
			$reflection = new ReflectionClass($class_name);
			$file_path = $reflection->getFileName();

			if (!$file_path || !file_exists($file_path)) {
				WP_CLI::warning("  Cannot find file for class: $class_name");
				return false;
			}

			$signature_path = $file_path . '.sig';

			if ($verify_only) {
				$result = $this->verify_signature($file_path, $signature_path, $public_key_path);
				if ($result) {
					WP_CLI::log("  ✅ Signature verified for $class_name");
				} else {
					WP_CLI::log("  ❌ Signature verification failed for $class_name");
				}
				return $result;
			} else {
				$result = $this->sign_file($file_path, $signature_path, $private_key);
				if ($result) {
					WP_CLI::log("  ✅ Signed $class_name successfully");
				} else {
					WP_CLI::log("  ❌ Failed to sign $class_name");
				}
				return $result;
			}
		} catch (Exception $e) {
			WP_CLI::warning("  Error processing $class_name: " . $e->getMessage());
			return false;
		}
	}

	/**
	 * Get the inheritance chain of a class up to but not including WC_Payment_Gateway.
	 *
	 * @param string $class_name The class name to get the inheritance chain for.
	 * @return array An array of class names in the inheritance chain.
	 */
	private function get_inheritance_chain(string $class_name): array {
		$inheritance_chain = [];
		$current_class = $class_name;

		while ($current_class !== 'WC_Payment_Gateway' && $current_class) {
			$inheritance_chain[] = $current_class;
			$current_class = get_parent_class($current_class);
		}

		return $inheritance_chain;
	}

	/**
	 * Sign a file with the given private key.
	 *
	 * @param string $file_path The file to sign.
	 * @param string $signature_path Where to save the signature.
	 * @param string $private_key The private key.
	 * @return bool True if successful, false otherwise.
	 */
	private function sign_file($file_path, $signature_path, $private_key) {
		$file_contents = file_get_contents($file_path);
		if ($file_contents === false) {
			return false;
		}

		$private_key_resource = openssl_pkey_get_private($private_key);
		if ($private_key_resource === false) {
			return false;
		}

		$signature = '';
		$result = openssl_sign($file_contents, $signature, $private_key_resource, OPENSSL_ALGO_SHA256);

		if ($private_key_resource) {
			openssl_free_key($private_key_resource);
		}

		if (!$result) {
			return false;
		}

		// Base64 encode the signature for storage
		$encoded_signature = base64_encode($signature);

		// Ensure the directory exists
		$signature_dir = dirname($signature_path);
		if (!is_dir($signature_dir)) {
			wp_mkdir_p($signature_dir);
		}

		return file_put_contents($signature_path, $encoded_signature) !== false;
	}

	/**
	 * Verify a file's signature.
	 *
	 * @param string $file_path The file to verify.
	 * @param string $signature_path The signature file.
	 * @param string $public_key_path Path to the public key.
	 * @return bool True if signature is valid.
	 */
	private function verify_signature($file_path, $signature_path, $public_key_path) {
		if (!file_exists($file_path) || !file_exists($signature_path) || !file_exists($public_key_path)) {
			return false;
		}

		$file_contents = file_get_contents($file_path);
		$encoded_signature = file_get_contents($signature_path);
		$public_key = file_get_contents($public_key_path);

		if ($file_contents === false || $encoded_signature === false || $public_key === false) {
			return false;
		}

		$signature = base64_decode($encoded_signature);
		if ($signature === false) {
			return false;
		}

		$public_key_resource = openssl_pkey_get_public($public_key);
		if ($public_key_resource === false) {
			return false;
		}

		$result = openssl_verify($file_contents, $signature, $public_key_resource, OPENSSL_ALGO_SHA256);

		if ($public_key_resource) {
			openssl_free_key($public_key_resource);
		}

		return $result === 1;
	}

	/**
	 * Generate a new key pair.
	 *
	 * @param string $output_dir Directory to store the keys.
	 * @return bool True if keys were generated successfully.
	 */
	private function generate_key_pair($output_dir) {
		// Create the directory if it doesn't exist
		if (!is_dir($output_dir)) {
			if (!wp_mkdir_p($output_dir)) {
				WP_CLI::error("Failed to create directory: $output_dir");
				return false;
			}
		}

		// Generate a new key pair
		$config = [
			'digest_alg' => 'sha256',
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		];

		// Create the keypair
		$res = openssl_pkey_new($config);
		if ($res === false) {
			WP_CLI::error('Failed to generate a new key pair: ' . openssl_error_string());
			return false;
		}

		// Extract private key
		$private_key = '';
		if (!openssl_pkey_export($res, $private_key)) {
			WP_CLI::error('Failed to export private key: ' . openssl_error_string());
			openssl_pkey_free($res);
			return false;
		}

		// Extract public key
		$public_key_details = openssl_pkey_get_details($res);
		if ($public_key_details === false) {
			WP_CLI::error('Failed to get public key details: ' . openssl_error_string());
			openssl_pkey_free($res);
			return false;
		}
		$public_key = $public_key_details['key'];

		// Free the key resource
		openssl_pkey_free($res);

		// Save the keys
		$private_key_path = $output_dir . '/private-key.pem';
		$public_key_path = $output_dir . '/public-key.pem';
		$wc_public_key_path = WP_PLUGIN_DIR . '/woocommerce/includes/gateways/integrity-checks-public-key.pem';

		if (file_put_contents($private_key_path, $private_key) === false) {
			WP_CLI::error("Failed to write private key to $private_key_path");
			return false;
		}

		if (file_put_contents($public_key_path, $public_key) === false) {
			WP_CLI::error("Failed to write public key to $public_key_path");
			return false;
		}

		// Set restrictive permissions on private key
		chmod($private_key_path, 0600);

		// Copy public key to WooCommerce directory if possible
		if (is_writable(dirname($wc_public_key_path))) {
			if (!copy($public_key_path, $wc_public_key_path)) {
				WP_CLI::warning("Could not copy public key to WooCommerce directory: $wc_public_key_path");
				WP_CLI::log("You will need to manually copy the public key from $public_key_path to $wc_public_key_path");
			} else {
				WP_CLI::success("Public key copied to WooCommerce directory");
			}
		} else {
			WP_CLI::warning("Cannot write to WooCommerce directory");
			WP_CLI::log("You will need to manually copy the public key from $public_key_path to $wc_public_key_path");
		}

		WP_CLI::success("Keys generated successfully");
		WP_CLI::log("Private key: $private_key_path");
		WP_CLI::log("Public key: $public_key_path");

		return true;
	}
}

// Register the command
WP_CLI::add_command('wc gateway', 'WC_Gateway_Signer_Command');
