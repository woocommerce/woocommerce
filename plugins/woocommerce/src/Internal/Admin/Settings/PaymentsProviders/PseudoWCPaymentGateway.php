<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

/**
 * Pseudo payment gateway for registering pseudo payment gateways for the settings page.
 *
 * It is similar to the FakePaymentGateway class used for testing purposes.
 *
 * Use it when a certain payment gateway doesn't register properly in the context of the settings page and
 * you need an in-between gateway to handle the settings page logic.
 *
 * @internal
 */
class PseudoWCPaymentGateway extends \WC_Payment_Gateway {
	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Gateway title.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Gateway description.
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * Gateway method title.
	 *
	 * @var string
	 */
	public $method_title = '';

	/**
	 * Gateway method description.
	 *
	 * @var string
	 */
	public $method_description = '';

	/**
	 * Corresponding gateway plugin slug.
	 *
	 * @var string
	 */
	public string $plugin_slug = 'generic-plugin-slug';

	/**
	 * Corresponding gateway plugin file.
	 *
	 * Skip the .php extension to match the format used by the WP API.
	 *
	 * @var string
	 */
	public string $plugin_file = 'generic-plugin-slug/generic-plugin-file';

	/**
	 * The recommended payment methods list.
	 *
	 * @var array
	 */
	public array $recommended_payment_methods = array();

	/**
	 * Whether or not this gateway still requires setup to function.
	 *
	 * @var bool
	 */
	public bool $needs_setup = false;

	/**
	 * The test mode.
	 *
	 * @var bool
	 */
	public bool $test_mode = false;

	/**
	 * The dev mode.
	 *
	 * @var bool
	 */
	public bool $dev_mode = false;

	/**
	 * The account connected flag.
	 *
	 * @var bool
	 */
	public bool $account_connected = false;

	/**
	 * The onboarding started flag.
	 *
	 * @var bool
	 */
	public bool $onboarding_started = false;

	/**
	 * The onboarding completed flag.
	 *
	 * @var bool
	 */
	public bool $onboarding_completed = false;

	/**
	 * The test mode onboarding flag.
	 *
	 * @var bool
	 */
	public bool $test_mode_onboarding = false;

	/**
	 * Constructor.
	 *
	 * @param string $id    The gateway ID.
	 * @param array  $props Optional. The gateway properties to apply.
	 */
	public function __construct( string $id, array $props = array() ) {
		$this->id = $id;

		// Go through the props and set them on the object.
		foreach ( $props as $prop => $value ) {
			$this->$prop = $value;
		}
	}

	/**
	 * Return whether or not this gateway still requires setup to function.
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
	public function get_settings_url(): string {
		if ( isset( $this->settings_url ) ) {
			return $this->settings_url;
		}

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . strtolower( $this->id ) );
	}

	/**
	 * Get the gateway onboarding start/continue URL.
	 *
	 * @return string The gateway onboarding start/continue URL.
	 */
	public function get_connection_url(): string {
		if ( isset( $this->connection_url ) ) {
			return $this->connection_url;
		}

		return $this->get_settings_url();
	}

	/**
	 * Get the recommended payment methods list.
	 *
	 * @param string $country_code Optional. The business location country code.
	 *
	 * @return array List of recommended payment methods for the given country.
	 */
	public function get_recommended_payment_methods( string $country_code = '' ): array {
		return $this->recommended_payment_methods;
	}

	/**
	 * Check if the gateway is in test mode.
	 *
	 * @return bool True if the gateway is in test mode, false otherwise.
	 */
	public function is_test_mode(): bool {
		return $this->test_mode;
	}

	/**
	 * Check if the gateway is in dev mode.
	 *
	 * @return bool True if the gateway is in dev mode, false otherwise.
	 */
	public function is_dev_mode(): bool {
		return $this->dev_mode;
	}

	/**
	 * Check if the gateway has an account connected.
	 *
	 * @return bool True if the gateway has an account connected, false otherwise.
	 */
	public function is_account_connected(): bool {
		return $this->account_connected;
	}

	/**
	 * Check if the gateway has started onboarding.
	 *
	 * @return bool True if the gateway has started onboarding, false otherwise.
	 */
	public function is_onboarding_started(): bool {
		return $this->onboarding_started;
	}

	/**
	 * Check if the gateway has completed onboarding.
	 *
	 * @return bool True if the gateway has completed onboarding, false otherwise.
	 */
	public function is_onboarding_completed(): bool {
		return $this->onboarding_completed;
	}

	/**
	 * Check if the gateway is in test mode onboarding.
	 *
	 * @return bool True if the gateway is in test mode onboarding, false otherwise.
	 */
	public function is_test_mode_onboarding(): bool {
		return $this->test_mode_onboarding;
	}
}
