<?php
declare( strict_types=1 );
namespace Automattic\WooCommerce\Internal\AddressProvider;

use WC_Address_Provider;

/**
 * Service class for managing address providers.
 */
class AddressProviderController {
	/**
	 * Registered provider instances.
	 *
	 * @var WC_Address_Provider[]
	 */
	private $providers = array();

	/**
	 * Preferred provider from options.
	 *
	 * @var string ID of preferred address provider.
	 */
	private $preferred_provider_option;

	/**
	 * Constructor.
	 *
	 * @internal
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Init function runs after this provider was added to DI container.
	 *
	 * @internal
	 */
	final public function init() {
		$this->preferred_provider_option = get_option( 'woocommerce_address_autocomplete_provider', '' );
		$this->providers                 = $this->get_registered_providers();
	}

	/**
	 * Get the registered providers.
	 *
	 * @return WC_Address_Provider[] array of WC_Address_Providers.
	 */
	public function get_providers(): array {
		return $this->providers;
	}

	/**
	 * Get all registered providers.
	 *
	 * @return WC_Address_Provider[] array of WC_Address_Providers.
	 */
	private function get_registered_providers(): array {
		/**
		 * Filter the registered address providers.
		 *
		 * @since 9.9.0
		 * @param array $providers Array of fully qualified class names (strings) or WC_Address_Provider instances.
		 *                         Class names will be instantiated automatically.
		 *                         Example: array( 'My_Provider_Class', new My_Other_Provider() )
		 */
		$provider_items = apply_filters( 'woocommerce_address_providers', array() );

		// The filter returned nothing but an empty array, so we can skip the rest of the function.
		if ( empty( $provider_items ) && is_array( $provider_items ) ) {
			return array();
		}

		$logger = wc_get_logger();

		if ( ! is_array( $provider_items ) ) {
			$logger->error(
				'Invalid return value for woocommerce_address_providers, expected an array of class names or instances.',
				array(
					'context' => 'address_provider_service',
				)
			);
			return array();
		}

		$providers = array();
		$seen_ids  = array();

		foreach ( $provider_items as $provider_item ) {
			if ( is_string( $provider_item ) && class_exists( $provider_item ) ) {
				$provider_item = new $provider_item();
			}

			// Providers need to be valid and extend WC_Address_Provider.
			if ( ! is_a( $provider_item, WC_Address_Provider::class ) ) {
				$logger->error(
					sprintf(
						'Invalid address provider item "%s", expected a string class name or WC_Address_Provider instance.',
						is_object( $provider_item ) ? get_class( $provider_item ) : gettype( $provider_item )
					),
					array(
						'context' => 'address_provider_service',
					)
				);
				continue;
			}

			// Validate the instance has the necessary properties.
			if ( empty( $provider_item->id ) || empty( $provider_item->name ) ) {
				$logger->error(
					'Invalid address provider instance, id or name property is missing or empty: ' . get_class( $provider_item ),
					array(
						'context' => 'address_provider_service',
					)
				);
				continue;
			}

			// Check for duplicate IDs.
			if ( isset( $seen_ids[ $provider_item->id ] ) ) {
				$logger->error(
					sprintf(
						'Duplicate provider ID found. ID "%s" is used by both %s and %s.',
						$provider_item->id,
						$seen_ids[ $provider_item->id ],
						get_class( $provider_item )
					),
					array(
						'context' => 'address_provider_service',
					)
				);
				continue;
			}

			// Track the ID and its provider class for error reporting.
			$seen_ids[ $provider_item->id ] = get_class( $provider_item );

			// Add the provider instance to the array after all checks are completed.
			$providers[] = $provider_item;
		}

		if ( ! empty( $this->preferred_provider_option ) && ! empty( $providers ) ) {
			// Look for the preferred provider in the array.
			foreach ( $providers as $key => $provider ) {
				if ( $provider->id === $this->preferred_provider_option ) {
					// Found the preferred provider, move it to the beginning of the array.
					$preferred_provider = $provider;
					unset( $providers[ $key ] );
					array_unshift( $providers, $preferred_provider );
					break;
				}
			}
		}

		return $providers;
	}

	/**
	 * Check if a specific provider is registered and available.
	 *
	 * @param string $provider_id The provider ID to check.
	 * @return bool
	 */
	public function is_provider_available( string $provider_id ): bool {

		foreach ( $this->providers as $provider ) {
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

		if ( $this->is_provider_available( $this->preferred_provider_option ) ) {
			return $this->preferred_provider_option;
		}

		// Get the first provider's ID.
		return $this->providers[0]->id ?? '';
	}
}
