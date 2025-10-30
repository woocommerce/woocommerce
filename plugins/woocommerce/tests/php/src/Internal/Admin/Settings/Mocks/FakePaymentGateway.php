<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks;

/**
 * Fake payment gateway for testing.
 */
class FakePaymentGateway extends \WC_Payment_Gateway {
	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'fake-gateway-id';

	/**
	 * Gateway title.
	 *
	 * @var string
	 */
	public $title = 'Fake Gateway Title';

	/**
	 * Gateway description.
	 *
	 * @var string
	 */
	public $description = 'Fake Gateway Description';

	/**
	 * Gateway method title.
	 *
	 * @var string
	 */
	public $method_title = 'Fake Gateway Method Title';

	/**
	 * Gateway method description.
	 *
	 * @var string
	 */
	public $method_description = 'Fake Gateway Method Description';

	/**
	 * Corresponding gateway plugin slug.
	 *
	 * @var string
	 */
	public $plugin_slug = 'fake-plugin-slug';

	/**
	 * Corresponding gateway plugin file.
	 *
	 * Skip the .php extension to match the format used by the WP API.
	 *
	 * @var string
	 */
	public $plugin_file = 'fake-plugin-slug/fake-plugin-file';

	/**
	 * The recommended payment methods list.
	 *
	 * @var array
	 */
	public $recommended_payment_methods = array();

	/**
	 * Whether this gateway still requires setup to function.
	 *
	 * @var bool
	 */
	public $needs_setup = false;

	/**
	 * The test mode.
	 *
	 * @var bool
	 */
	public $test_mode = false;

	/**
	 * The dev mode.
	 *
	 * @var bool
	 */
	public $dev_mode = false;

	/**
	 * The account connected flag.
	 *
	 * Default to true the same way the PaymentGateway::is_account_connected method does.
	 *
	 * @var bool
	 */
	public $account_connected = true;

	/**
	 * The onboarding supported flag.
	 *
	 * @var bool
	 */
	public $onboarding_supported = true;

	/**
	 * The onboarding not supported message.
	 *
	 * @var string|null
	 */
	public $onboarding_not_supported_message = null;

	/**
	 * The onboarding started flag.
	 *
	 * @var bool
	 */
	public $onboarding_started;

	/**
	 * The onboarding completed flag.
	 *
	 * @var bool
	 */
	public $onboarding_completed;

	/**
	 * The test mode onboarding flag.
	 *
	 * Default to false the same way the PaymentGateway::is_in_test_mode_onboarding method does.
	 *
	 * @var bool
	 */
	public $test_mode_onboarding = false;

	/**
	 * Constructor.
	 *
	 * @param string $id    Optional. The gateway ID.
	 * @param array  $props Optional. The gateway properties to apply.
	 */
	public function __construct( string $id = '', array $props = array() ) {
		if ( ! empty( $id ) ) {
			$this->id = $id;
		}

		// Go through the props and set them on the object.
		foreach ( $props as $prop => $value ) {
			$this->$prop = $value;
		}

		// Put in the default values for properties that are not set.
		// Onboarding properties are set to the same value as account_connected by default.
		if ( ! isset( $this->onboarding_started ) ) {
			$this->onboarding_started = $this->account_connected;
		}
		if ( ! isset( $this->onboarding_completed ) ) {
			$this->onboarding_completed = $this->account_connected;
		}
	}

	/**
	 * Return whether this gateway still requires setup to function.
	 *
	 * @return bool
	 */
	public function needs_setup() {
		return $this->needs_setup;
	}

	/**
	 * Get the gateway settings page URL.
	 *
	 * @return string The gateway settings page URL.
	 */
	public function get_settings_url() {
		if ( isset( $this->settings_url ) ) {
			return $this->settings_url;
		}

		return 'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bogus_settings';
	}

	/**
	 * Get the gateway onboarding start/continue URL.
	 *
	 * @return string The gateway onboarding start/continue URL.
	 */
	public function get_connection_url() {
		if ( isset( $this->connection_url ) ) {
			return $this->connection_url;
		}

		return 'https://example.com/connection-url';
	}

	/**
	 * Get the recommended payment methods list.
	 *
	 * @param string $country_code Optional. The business location country code.
	 *
	 * @return array List of recommended payment methods for the given country.
	 */
	public function get_recommended_payment_methods( string $country_code = '' ) {
		return $this->recommended_payment_methods;
	}

	/**
	 * Check if the gateway is in test mode.
	 *
	 * @return bool True if the gateway is in test mode, false otherwise.
	 */
	public function is_test_mode() {
		return $this->test_mode;
	}

	/**
	 * Check if the gateway is in dev mode.
	 *
	 * @return bool True if the gateway is in dev mode, false otherwise.
	 */
	public function is_dev_mode() {
		return $this->dev_mode;
	}

	/**
	 * Check if the gateway has an account connected.
	 *
	 * @return bool True if the gateway has an account connected, false otherwise.
	 */
	public function is_account_connected() {
		return $this->account_connected;
	}

	/**
	 * Check if the gateway supports onboarding.
	 *
	 * @param string $country_code Optional. The country code for which to check.
	 * @return bool True if the gateway supports onboarding, false otherwise.
	 */
	public function is_onboarding_supported( $country_code = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return $this->onboarding_supported;
	}

	/**
	 * Get the onboarding not supported message.
	 *
	 * @param string $country_code Optional. The country code for which to get the message.
	 * @return string The onboarding not supported message.
	 */
	public function get_onboarding_not_supported_message( $country_code = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return $this->onboarding_not_supported_message;
	}

	/**
	 * Check if the gateway has started onboarding.
	 *
	 * @return bool True if the gateway has started onboarding, false otherwise.
	 */
	public function is_onboarding_started() {
		return $this->onboarding_started;
	}

	/**
	 * Check if the gateway has completed onboarding.
	 *
	 * @return bool True if the gateway has completed onboarding, false otherwise.
	 */
	public function is_onboarding_completed() {
		return $this->onboarding_completed;
	}

	/**
	 * Check if the gateway is in test mode onboarding.
	 *
	 * @return bool True if the gateway is in test mode onboarding, false otherwise.
	 */
	public function is_in_test_mode_onboarding() {
		return $this->test_mode_onboarding;
	}
}
