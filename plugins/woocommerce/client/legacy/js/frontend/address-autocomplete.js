/**
 * Simple address provider registration for WooCommerce checkout
 */
var wooAddressProviders = {};

/**
 * Register an address autocomplete provider
 *
 * @param {Object} provider The provider object
 * @return {boolean} Whether the registration was successful
 */
function registerAddressAutocompleteProvider( provider ) {
	try {
		// Check required properties
		if ( ! provider || typeof provider !== 'object' ) {
			throw new Error( 'Address provider must be a valid object' );
		}

		if ( ! provider.id || typeof provider.id !== 'string' ) {
			throw new Error( 'Address provider must have a valid ID' );
		}

		if ( typeof provider.canSearch !== 'function' ) {
			throw new Error(
				'Address provider must have a canSearch function'
			);
		}

		if ( typeof provider.search !== 'function' ) {
			throw new Error( 'Address provider must have a search function' );
		}

		if ( typeof provider.select !== 'function' ) {
			throw new Error( 'Address provider must have a select function' );
		}

		// Check if provider is registered on server
		var serverProviders = [];
		if (
			window &&
			window.wc_checkout_params &&
			window.wc_checkout_params.address_providers
		) {
			serverProviders = window.wc_checkout_params.address_providers;
		}

		if ( ! Array.isArray( serverProviders ) ) {
			throw new Error( 'Server providers configuration is invalid' );
		}

		var isRegistered = serverProviders.some( function ( serverProvider ) {
			return (
				serverProvider &&
				typeof serverProvider === 'object' &&
				typeof serverProvider.id === 'string' &&
				serverProvider.id === provider.id
			);
		} );
		if ( ! isRegistered ) {
			throw new Error(
				'Provider ' + provider.id + ' not registered on server'
			);
		}

		// Freeze and add provider to registry.
		Object.freeze( provider );
		wooAddressProviders[ provider.id ] = provider;
		return true;
	} catch ( error ) {
		console.error( 'Error registering address provider:', error.message );
		return false;
	}
}

// Make functions available globally
window.wc = window.wc || {};
window.wc.addressAutocomplete = {
	registerAddressAutocompleteProvider: registerAddressAutocompleteProvider,
};
