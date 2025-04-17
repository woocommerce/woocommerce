<?php
declare( strict_types=1 );
namespace Automattic\WooCommerce\Internal\AddressProvider;

use WC_Address_Provider;

/**
 * Service class for managing address providers.
 */
class AddressProviderController {

	/**
	 * Cached provider class names from the last filter call.
	 *
	 * @var string[]
	 */
	private $cached_provider_class_names = array();

	/**
	 * Cached provider instances.
	 *
	 * @var WC_Address_Provider[]
	 */
	private $cached_providers = array();

	/**
	 * Get all registered providers.
	 *
	 * @return WC_Address_Provider[] array of WC_Address_Providers.
	 */
	public function get_registered_providers(): array {
		/**
		 * Filter the registered address providers.
		 *
		 * @since 9.9.0
		 * @param WC_Address_Provider[] $providers Array of fully qualified class names that extend WC_Address_Provider.
		 */
		$provider_class_names = apply_filters( 'woocommerce_address_providers', array() );

		$logger = wc_get_logger();

		if ( ! is_array( $provider_class_names ) ) {
			$logger->error(
				'Invalid return value for woocommerce_address_providers, expected an array of class names.',
				array(
					'context' => 'address_provider_service',
				)
			);
			return array();
		}

		// If the class names haven't changed, return the cached instances.
		if ( $this->cached_provider_class_names === $provider_class_names && ! empty( $this->cached_providers ) ) {
			return $this->cached_providers;
		}

		$providers = array();
		$seen_ids  = array();

		foreach ( $provider_class_names as $provider_class_name ) {

			// Validate the class name is a string.
			if ( ! is_string( $provider_class_name ) ) {
				$logger->error(
					'Invalid class name for address provider, expected a string.',
					array(
						'context' => 'address_provider_service',
					)
				);
				continue;
			}

			// Ensure the class exists and is a valid WC_Address_Provider subclass.
			if ( ! class_exists( $provider_class_name ) || ! is_subclass_of( $provider_class_name, WC_Address_Provider::class ) ) {
				$logger->error(
					'Invalid address provider class, class does not exist or is not a subclass of WC_Address_Provider: ' . $provider_class_name,
					array(
						'context' => 'address_provider_service',
					)
				);
				continue;
			}

			$provider_instance = new $provider_class_name();

			// Validate the instance has the necessary properties.
			if ( empty( $provider_instance->id ) || empty( $provider_instance->name ) ) {
				$logger->error(
					'Invalid address provider instance, id or name property is missing or empty: ' . $provider_class_name,
					array(
						'context' => 'address_provider_service',
					)
				);
				continue;
			}

			// Check for duplicate IDs.
			if ( isset( $seen_ids[ $provider_instance->id ] ) ) {
				$logger->error(
					sprintf(
						'Duplicate provider ID found. ID "%s" is used by both %s and %s.',
						$provider_instance->id,
						$seen_ids[ $provider_instance->id ],
						$provider_class_name
					),
					array(
						'context' => 'address_provider_service',
					)
				);
				continue;
			}

			// Track the ID and its provider class for error reporting.
			$seen_ids[ $provider_instance->id ] = $provider_class_name;

			// Add the provider instance to the array after all checks are completed.
			$providers[] = $provider_instance;
		}

		// Update the cache.
		$this->cached_provider_class_names = $provider_class_names;
		$this->cached_providers            = $providers;

		return $providers;
	}

	/**
	 * Check if a specific provider is registered and available.
	 *
	 * @param string $provider_id The provider ID to check.
	 * @return bool
	 */
	public function is_provider_available( string $provider_id ): bool {
		$providers = $this->get_registered_providers();

		foreach ( $providers as $provider ) {
			if ( $provider->id === $provider_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the preferred provider; this is what was selected in the WooCommerce "preferred provider" setting *or* the
	 * first registered provider if no preference was set. If the provider selected in WC Settings is not registered
	 * anymore, it will fall back to the first registered provider. Any other case will return an empty string.
	 *
	 * @return string
	 */
	public function get_preferred_provider(): string {
		$providers = $this->get_registered_providers();

		if ( empty( $providers ) ) {
			return '';
		}

		$preferred_provider = get_option( 'woocommerce_address_autocomplete_provider', '' );

		if ( $this->is_provider_available( $preferred_provider ) ) {
			return $preferred_provider;
		}

		// Get the first provider's ID by instantiating it.
		return $providers[0]->id ?? '';
	}
}
