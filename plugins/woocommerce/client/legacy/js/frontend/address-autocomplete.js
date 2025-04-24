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
	// Check required properties
	if ( ! provider || typeof provider !== 'object' ) {
		console.error( 'Address provider must be a valid object' );
		return false;
	}

	if ( ! provider.id || typeof provider.id !== 'string' ) {
		console.error( 'Address provider must have a valid ID' );
		return false;
	}

	if ( typeof provider.canSearch !== 'function' ) {
		console.error( 'Address provider must have a canSearch function' );
		return false;
	}

	if ( typeof provider.search !== 'function' ) {
		console.error( 'Address provider must have a search function' );
		return false;
	}

	if ( typeof provider.select !== 'function' ) {
		console.error( 'Address provider must have a select function' );
		return false;
	}

	// Check if provider is registered on server
	var serverProviders = [];
	if ( window && window.wc_checkout_params && window.wc_checkout_params.address_providers ) {
		serverProviders = window.wc_checkout_params.address_providers;
	}

	if ( ! Array.isArray( serverProviders ) ) {
		console.error( 'Server providers configuration is invalid' );
		return false;
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
		console.error(
			'Provider ' + provider.id + ' not registered on server'
		);
		return false;
	}

	// Freeze and add provider to registry.
	Object.freeze( provider );
	wooAddressProviders[ provider.id ] = provider;
	return true;
}

// Make functions available globally
window.wc = window.wc || {};
window.wc.addressAutocomplete = {
	registerAddressAutocompleteProvider: registerAddressAutocompleteProvider,
};
