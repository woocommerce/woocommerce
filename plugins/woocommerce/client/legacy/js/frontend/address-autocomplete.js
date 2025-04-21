/**
 * Simple address provider registration for WooCommerce checkout
 */
var wooAddressProviders = {};

/**
 * Register an address autocomplete provider
 *
 * @param {Object} provider The provider object
 */
function registerAddressAutocompleteProvider( provider ) {
	// Check required properties
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
	var serverProviders = window.wc_checkout_params.address_providers || [];
	console.log( JSON.stringify( serverProviders ) );
	var isRegistered = serverProviders.some( function ( serverProvider ) {
		return serverProvider.id === provider.id;
	} );
	if ( ! isRegistered ) {
		console.error(
			'Provider ' + provider.id + ' not registered on server'
		);
		return false;
	}

	// Add provider to registry
	wooAddressProviders[ provider.id ] = provider;
	return true;
}

// Make functions available globally
window.wc = window.wc || {};
window.wc.addressAutocomplete = {
	registerAddressAutocompleteProvider: registerAddressAutocompleteProvider,
};
