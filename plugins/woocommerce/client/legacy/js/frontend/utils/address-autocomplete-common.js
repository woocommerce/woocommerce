/**
 * Common address autocomplete functionality shared between shortcode and blocks implementations.
 * This module provides the core registration and provider management logic.
 */

( function () {
	// Initialize the global wc.addressAutocomplete namespace
	window.wc = window.wc || {};
	window.wc.addressAutocomplete = window.wc.addressAutocomplete || {
		providers: {},
		activeProvider: { billing: null, shipping: null },
		serverProviders: [], // Store parsed server providers
	};

	// Parse server providers configuration
	// Try multiple possible param locations for compatibility
	let serverProviders = [];
	try {
		let params = null;

		// Check for common module params first
		if ( window && window.wc_address_autocomplete_common_params ) {
			params = window.wc_address_autocomplete_common_params;
		}
		// Fallback to regular params if common params not found
		else if ( window && window.wc_address_autocomplete_params ) {
			params = window.wc_address_autocomplete_params;
		}

		if ( params && params.address_providers ) {
			const raw = params.address_providers;
			if ( typeof raw === 'string' ) {
				const parsed = JSON.parse( raw );
				serverProviders = Array.isArray( parsed ) ? parsed : [];
			} else if ( Array.isArray( raw ) ) {
				serverProviders = raw;
			}
		}
	} catch ( e ) {
		console.error( 'Invalid address providers JSON:', e );
	}

	// Store server providers in the global namespace for external access
	window.wc.addressAutocomplete.serverProviders = serverProviders;

	/**
	 * Register an address autocomplete provider. This will be used by both shortcode and blocks implementations.
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
				throw new Error(
					'Address provider must have a search function'
				);
			}

			if ( typeof provider.select !== 'function' ) {
				throw new Error(
					'Address provider must have a select function'
				);
			}

			const serverProviders =
				window.wc.addressAutocomplete.serverProviders;
			if ( ! Array.isArray( serverProviders ) ) {
				throw new Error( 'Server providers configuration is invalid' );
			}


			var isRegistered = serverProviders.some( function (
				serverProvider
			) {
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

			// Check if a provider with the same ID already exists
			if ( window.wc.addressAutocomplete.providers[ provider.id ] ) {
				console.warn(
					'Address provider with ID "' +
						provider.id +
						'" is already registered.'
				);
				return false;
			}

			// Freeze and add provider to registry.
			Object.freeze( provider );
			window.wc.addressAutocomplete.providers[ provider.id ] = provider;

			// Check if window.wp.data and the checkout store is available, if so we're likely in a block context
			if (
				window.wp &&
				window.wp.data &&
				window.wp.data.dispatch &&
				window.wc &&
				window.wc.wcBlocksData &&
				window.wc.wcBlocksData.checkoutStore
			) {
				// Dispatch an action to notify that a new provider has been registered
				window.wp.data
					.dispatch( window.wc.wcBlocksData.checkoutStore )
					.addAddressAutocompleteProvider( provider.id );
			}
			return true;
		} catch ( error ) {
			console.error(
				'Error registering address provider:',
				error.message
			);
			return false;
		}
	}

	// Export the registration function and server providers to the global namespace
	window.wc.addressAutocomplete.registerAddressAutocompleteProvider =
		registerAddressAutocompleteProvider;

	/**
	 * Get server provider configuration by ID
	 * @param {string} providerId The provider ID
	 * @return {Object|null} The server provider configuration or null if not found
	 */
	window.wc.addressAutocomplete.getServerProvider = function ( providerId ) {
		const serverProviders = window.wc.addressAutocomplete.serverProviders;
		if ( ! Array.isArray( serverProviders ) ) {
			return null;
		}
		return (
			serverProviders.find( function ( provider ) {
				return provider && provider.id === providerId;
			} ) || null
		);
	};

	/**
	 * Get all registered providers
	 * @return {Object} All registered providers
	 */
	window.wc.addressAutocomplete.getProviders = function () {
		return window.wc.addressAutocomplete.providers;
	};

	/**
	 * Get active provider for a specific type
	 * @param {string} type The address type ('billing' or 'shipping')
	 * @return {Object|null} The active provider or null
	 */
	window.wc.addressAutocomplete.getActiveProvider = function ( type ) {
		return window.wc.addressAutocomplete.activeProvider[ type ] || null;
	};

	/**
	 * Set active provider for a specific type
	 * @param {string} type The address type ('billing' or 'shipping')
	 * @param {Object|null} provider The provider to set as active, or null to clear
	 */
	window.wc.addressAutocomplete.setActiveProvider = function (
		type,
		provider
	) {
		window.wc.addressAutocomplete.activeProvider[ type ] = provider;
	};

	/**
	 * Check if address autocomplete is available for blocks
	 * @return {boolean} Whether blocks checkout is available
	 */
	window.wc.addressAutocomplete.isBlocksContext = function () {
		return !! (
			window.wc &&
			window.wc.wcSettings &&
			window.wc.wcSettings.allSettings &&
			window.wc.wcSettings.allSettings.isCheckoutBlock
		);
	};
} )();
