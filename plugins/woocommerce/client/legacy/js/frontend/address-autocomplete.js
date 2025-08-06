/**
 * Address provider registration for WooCommerce shortcode checkout
 */

// Make functions and state available globally under window.wc.addressAutocomplete
window.wc = window.wc || {};
window.wc.addressAutocomplete = window.wc.addressAutocomplete || {
	providers: {},
	activeProvider: { billing: null, shipping: null },
};

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

		// Check if provider is registered on server.
		var serverProviders = [];
		if (
			window &&
			window.wc_checkout_params &&
			Array.isArray( window.wc_checkout_params.address_providers ) &&
			window.wc_checkout_params.address_providers.length > 0
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
		return true;
	} catch ( error ) {
		console.error( 'Error registering address provider:', error.message );
		return false;
	}
}

// Export the registration function
window.wc.addressAutocomplete.registerAddressAutocompleteProvider =
	registerAddressAutocompleteProvider;

( function () {
	/**
	 * Set the active address provider based on which providers' (queried in order) canSearch returns true.
	 * Triggers when country changes.
	 * @param country {string} country code.
	 * @param type {string} type 'billing' or 'shipping'
	 */
	function setActiveProvider( country, type ) {
		// Get server providers list (already ordered by preference).
		const serverProviders =
			( window &&
				window.wc_checkout_params &&
				window.wc_checkout_params.address_providers ) ||
			[];

		// Check providers in preference order (server handles preferred provider ordering).
		for ( const serverProvider of serverProviders ) {
			const provider =
				window.wc.addressAutocomplete.providers[ serverProvider.id ];

			if ( provider && provider.canSearch( country ) ) {
				window.wc.addressAutocomplete.activeProvider[ type ] = provider;
				// Add autocomplete-available class to parent .woocommerce-input-wrapper
				const addressInput = document.getElementById(
					`${ type }_address_1`
				);
				if ( addressInput ) {
					const wrapper = addressInput.closest(
						'.woocommerce-input-wrapper'
					);
					if ( wrapper ) {
						wrapper.classList.add( 'autocomplete-available' );
					}
				}
				return;
			}
		}

		// No provider can search for this country.
		window.wc.addressAutocomplete.activeProvider[ type ] = null;
		// Remove autocomplete-available class from parent .woocommerce-input-wrapper
		const addressInput = document.getElementById( `${ type }_address_1` );
		if ( addressInput ) {
			const wrapper = addressInput.closest(
				'.woocommerce-input-wrapper'
			);
			if ( wrapper ) {
				wrapper.classList.remove( 'autocomplete-available' );
			}
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		// This script would not be enqueued if the feature was not enabled.
		const addressTypes = [ 'billing', 'shipping' ];
		const addressInputs = {};
		const suggestionsContainers = {};
		const suggestionsLists = {};
		let activeSuggestionIndices = {};
		let addressSelectionTimeout;
		const blurHandlers = {};

		/**
		 * Cache address fields for a given type, will re-run when country changes.
		 * @param type
		 * @return {{address_2: HTMLElement, city: HTMLElement, country: HTMLElement, postcode: HTMLElement}}
		 */
		function cacheAddressFields( type ) {
			addressInputs[ type ] = {};
			addressInputs[ type ][ 'address_1' ] = document.getElementById(
				`${ type }_address_1`
			);
			addressInputs[ type ][ 'city' ] = document.getElementById(
				`${ type }_city`
			);
			addressInputs[ type ][ 'country' ] = document.getElementById(
				`${ type }_country`
			);
			addressInputs[ type ][ 'postcode' ] = document.getElementById(
				`${ type }_postcode`
			);
			addressInputs[ type ][ 'state' ] = document.getElementById(
				`${ type }_state`
			);
		}

		// Initialize for both billing and shipping.
		addressTypes.forEach( ( type ) => {
			cacheAddressFields( type );
			const addressInput = addressInputs[ type ][ 'address_1' ];
			const cityInput = addressInputs[ type ][ 'city' ];
			const countryInput = addressInputs[ type ][ 'country' ];
			const postcodeInput = addressInputs[ type ][ 'postcode' ];

			if ( addressInput ) {
				// Create suggestions container if it doesn't exist.
				if (
					! document.getElementById( `address_suggestions_${ type }` )
				) {
					const container = document.createElement( 'div' );
					container.id = `address_suggestions_${ type }`;
					container.className = 'woocommerce-address-suggestions';
					container.style.display = 'none';
					container.setAttribute( 'role', 'region' );
					container.setAttribute( 'aria-live', 'polite' );

					const list = document.createElement( 'ul' );
					list.className = 'suggestions-list';
					list.setAttribute( 'role', 'listbox' );
					list.setAttribute( 'aria-label', 'Address suggestions' );

					container.appendChild( list );
					addressInput.parentNode.insertBefore(
						container,
						addressInput.nextSibling
					);

					// Add search icon.
					const searchIcon = document.createElement( 'div' );
					searchIcon.className = 'address-search-icon';
					addressInput.parentNode.appendChild( searchIcon );
				}

				addressInputs[ type ] = {};
				addressInputs[ type ][ 'address_1' ] = addressInput;
				addressInputs[ type ][ 'city' ] = cityInput;
				addressInputs[ type ][ 'country' ] = countryInput;
				addressInputs[ type ][ 'postcode' ] = postcodeInput;

				suggestionsContainers[ type ] = document.getElementById(
					`address_suggestions_${ type }`
				);
				suggestionsLists[ type ] =
					suggestionsContainers[ type ].querySelector(
						'.suggestions-list'
					);
				activeSuggestionIndices[ type ] = -1;
			}

			// Get country value and set active address provider based on it.
			if ( countryInput ) {
				setActiveProvider( countryInput.value, type );

				/**
				 * Listen for country changes to re-evaluate provider availability.
				 * Handle both regular change events and Select2 events.
				 */
				const handleCountryChange = function () {
					cacheAddressFields( type );
					setActiveProvider( countryInput.value, type );
					if ( addressInputs[ type ][ 'address_1' ] ) {
						hideSuggestions( type );
					}
				};

				countryInput.addEventListener( 'change', handleCountryChange );

				// Also listen for Select2 change event if jQuery and Select2 are available.
				if ( window.jQuery && window.jQuery( countryInput ).select2 ) {
					window
						.jQuery( countryInput )
						.on( 'select2:select', handleCountryChange );
				}
			}
		} );

		/**
		 * Disable browser autofill for address inputs to prevent conflicts with autocomplete.
		 * @param input {HTMLInputElement} The input element to disable autofill for.
		 */
		function disableBrowserAutofill( input ) {
			if ( input.getAttribute( 'autocomplete' ) === 'off' ) {
				return;
			}

			input.setAttribute( 'autocomplete', 'off' );
			input.setAttribute( 'data-lpignore', 'true' );
			input.setAttribute( 'data-op-ignore', 'true' );
			input.setAttribute( 'data-1p-ignore', 'true' );

			// To prevent 1Password/LastPass and autocomplete clashes, we need to refocus the element.
			// This is achieved by removing and re-adding the element to trigger browser updates.
			const parentElement = input.parentElement;
			if ( parentElement ) {
				parentElement.appendChild( parentElement.removeChild( input ) );
				input.focus();
			}
		}

		/**
		 * Enable browser autofill for address input.
		 * @param input {HTMLInputElement} The input element to enable autofill for.
		 * @param shouldFocus {boolean} Whether to focus the input after enabling autofill.
		 */
		function enableBrowserAutofill( input, shouldFocus = true ) {
			if ( input.getAttribute( 'autocomplete' ) !== 'off' ) {
				return;
			}

			input.setAttribute( 'autocomplete', 'address-line1' );
			input.setAttribute( 'data-lpignore', 'false' );
			input.setAttribute( 'data-op-ignore', 'false' );
			input.setAttribute( 'data-1p-ignore', 'false' );

			// To ensure browser updates and re-enables autofill, we need to refocus the element.
			// This is achieved by removing and re-adding the element to trigger browser updates.
			const parentElement = input.parentElement;
			if ( parentElement ) {
				parentElement.appendChild( parentElement.removeChild( input ) );
				if ( shouldFocus ) {
					input.focus();
				}
			}
		}

		/**
		 * Get highlighted label parts based on matches returned by `search` results.
		 * @param label {string} The label to highlight.
		 * @param matches {*[]} Array of match objects with `offset` and `length`.
		 * @return {*[]} Array of nodes with highlighted parts.
		 */
		function getHighlightedLabel( label, matches ) {
			// Sanitize label for display.
			const sanitizedLabel = sanitizeForDisplay( label );
			const parts = [];
			let lastIndex = 0;

			// Validate matches array.
			if ( ! Array.isArray( matches ) ) {
				// If matches is invalid, just return plain text.
				parts.push( document.createTextNode( sanitizedLabel ) );
				return parts;
			}

			// Validate matches.
			const safeMatches = matches.filter(
				( match ) =>
					match &&
					typeof match.offset === 'number' &&
					typeof match.length === 'number' &&
					match.offset >= 0 &&
					match.length > 0 &&
					match.offset + match.length <= sanitizedLabel.length
			);

			safeMatches.forEach( ( match ) => {
				// Add text before match.
				if ( match.offset > lastIndex ) {
					parts.push(
						document.createTextNode(
							sanitizedLabel.slice( lastIndex, match.offset )
						)
					);
				}

				// Add bold matched text.
				const bold = document.createElement( 'strong' );
				bold.textContent = sanitizedLabel.slice(
					match.offset,
					match.offset + match.length
				);
				parts.push( bold );

				lastIndex = match.offset + match.length;
			} );

			// Add remaining text.
			if ( lastIndex < sanitizedLabel.length ) {
				parts.push(
					document.createTextNode( sanitizedLabel.slice( lastIndex ) )
				);
			}

			return parts;
		}

		/**
		 * Sanitize HTML for display by removing any HTML tags.
		 *
		 * @param html
		 * @return {string|string}
		 */
		function sanitizeForDisplay( html ) {
			const doc = document.implementation.createHTMLDocument( '' );
			doc.body.innerHTML = html;
			return doc.body.textContent || '';
		}

		/**
		 * Handle searching and displaying autocomplete results below the address input if the value meets the criteria
		 * of 3 or more characters. No suggestion is initially highlighted.
		 * @param inputValue {string} The value entered into the address input.
		 * @param country {string} The country code to pass to the provider's search method.
		 * @param type {string} The address type ('billing' or 'shipping').
		 * @return {Promise<void>}
		 */
		async function displaySuggestions( inputValue, country, type ) {
			// Sanitize input value.
			const sanitizedInput = sanitizeForDisplay( inputValue );
			if ( sanitizedInput !== inputValue ) {
				console.warn( 'Input was sanitized for security' );
			}

			// Check if the address section exists (shipping may be disabled/hidden)
			if (
				! addressInputs[ type ] ||
				! addressInputs[ type ][ 'address_1' ]
			) {
				return;
			}

			if (
				! suggestionsLists[ type ] ||
				! suggestionsContainers[ type ]
			) {
				return;
			}

			const addressInput = addressInputs[ type ][ 'address_1' ];
			const suggestionsList = suggestionsLists[ type ];
			const suggestionsContainer = suggestionsContainers[ type ];

			// Hide suggestions if input has less than 3 characters
			if ( sanitizedInput.length < 3 ) {
				hideSuggestions( type );
				enableBrowserAutofill( addressInput );
				return;
			}

			// Check if we have an active provider for this address type.
			if ( ! window.wc.addressAutocomplete.activeProvider[ type ] ) {
				hideSuggestions( type );
				enableBrowserAutofill( addressInput );
				return;
			}

			try {
				const filteredSuggestions =
					await window.wc.addressAutocomplete.activeProvider[
						type
					].search( sanitizedInput, country, type );
				// Validate suggestions array.
				if ( ! Array.isArray( filteredSuggestions ) ) {
					console.error(
						'Invalid suggestions response - not an array'
					);
					hideSuggestions( type );
					return;
				}

				// Limit number of suggestions, API may return many results but we should only show the first 5.
				const maxSuggestions = 5;
				const safeSuggestions = filteredSuggestions.slice(
					0,
					maxSuggestions
				);

				if ( safeSuggestions.length === 0 ) {
					hideSuggestions( type );
					return;
				}

				// Clear existing suggestions only when we have new results to show.
				suggestionsList.innerHTML = '';

				safeSuggestions.forEach( ( suggestion, index ) => {
					const li = document.createElement( 'li' );
					li.setAttribute( 'role', 'option' );
					li.id = `suggestion-item-${ type }-${ index }`;
					li.dataset.id = suggestion.id;
					li.setAttribute( 'tabindex', '-1' );

					li.textContent = ''; // Clear existing content.
					const labelParts = getHighlightedLabel(
						suggestion.label,
						suggestion.matchedSubstrings || []
					);
					labelParts.forEach( ( part ) => li.appendChild( part ) );

					li.addEventListener( 'click', async function () {
						// Hide suggestions immediately for better UX.
						hideSuggestions( type );
						await selectAddress( type, this.dataset.id );
						addressInput.focus();
					} );

					li.addEventListener( 'mouseenter', function () {
						setActiveSuggestion( type, index );
					} );

					suggestionsList.appendChild( li );
				} );

				disableBrowserAutofill( addressInput );
				suggestionsContainer.style.display = 'block';
				suggestionsContainer.style.marginTop =
					addressInputs[ type ][ 'address_1' ].offsetHeight + 'px';
				addressInput.setAttribute( 'aria-expanded', 'true' );
				addressInput.setAttribute(
					'aria-owns',
					`address_suggestions_${ type }_list`
				);
				suggestionsList.id = `address_suggestions_${ type }_list`;
				// Don't auto-highlight first suggestion for better screen reader accessibility
				activeSuggestionIndices[ type ] = -1;

				// Add blur event listener when suggestions are shown
				if ( ! blurHandlers[ type ] ) {
					blurHandlers[ type ] = function () {
						// Use a small delay to allow clicks on suggestions to register
						setTimeout( () => {
							hideSuggestions( type );
							enableBrowserAutofill( addressInput, false );
						}, 200 );
					};
					addressInput.addEventListener(
						'blur',
						blurHandlers[ type ]
					);
				}
			} catch ( error ) {
				console.error( 'Address search error:', error );
				hideSuggestions( type );
				enableBrowserAutofill( addressInput );
			}
		}

		/**
		 * Hide the suggestions container for a given address type.
		 * @param type {string} The address type ('billing' or 'shipping').
		 */
		function hideSuggestions( type ) {
			// Check if the address section exists (shipping may be disabled/hidden)
			if (
				! addressInputs[ type ] ||
				! addressInputs[ type ][ 'address_1' ]
			) {
				return;
			}

			if (
				! suggestionsLists[ type ] ||
				! suggestionsContainers[ type ]
			) {
				return;
			}

			const suggestionsList = suggestionsLists[ type ];
			const suggestionsContainer = suggestionsContainers[ type ];
			const addressInput = addressInputs[ type ][ 'address_1' ];

			suggestionsList.innerHTML = '';
			suggestionsContainer.style.display = 'none';
			addressInput.setAttribute( 'aria-expanded', 'false' );
			addressInput.removeAttribute( 'aria-activedescendant' );
			addressInput.removeAttribute( 'aria-owns' );
			activeSuggestionIndices[ type ] = -1;

			// Remove blur event listener when suggestions are hidden
			if ( blurHandlers[ type ] ) {
				addressInput.removeEventListener(
					'blur',
					blurHandlers[ type ]
				);
				delete blurHandlers[ type ];
			}
		}

		/**
		 * Helper function to set field value and trigger events.
		 * @param input {HTMLInputElement} The input element to set the value for.
		 * @param value {string} The value to set.
		 */
		const setFieldValue = ( input, value ) => {
			if ( input ) {
				input.value = value;
				input.dispatchEvent( new Event( 'change' ) );

				// Also trigger Select2 update if it's a Select2 field.
				if (
					window.jQuery &&
					window
						.jQuery( input )
						.hasClass( 'select2-hidden-accessible' )
				) {
					window.jQuery( input ).trigger( 'change' );
				}
			}
		};

		/**
		 * Select an address from the suggestions list and submit it to the provider's `select` method.
		 * @param type {string} The address type ('billing' or 'shipping').
		 * @param addressId {string} The ID of the address to select.
		 * @return {Promise<void>}
		 */
		async function selectAddress( type, addressId ) {
			let addressData;
			try {
				addressData =
					await window.wc.addressAutocomplete.activeProvider[
						type
					].select( addressId );
			} catch ( error ) {
				console.error(
					'Error selecting address from provider',
					window.wc.addressAutocomplete.activeProvider[ type ].id,
					error
				);
				return; // Exit early if address selection fails.
			}

			if (
				typeof addressData !== 'object' ||
				addressData === null ||
				! addressData
			) {
				// Return without setting the address since response was invalid.
				return;
			}

			if ( addressData.country ) {
				setFieldValue(
					addressInputs[ type ][ 'country' ],
					addressData.country
				);
			}
			if ( addressData.address_1 ) {
				setFieldValue(
					addressInputs[ type ][ 'address_1' ],
					addressData.address_1
				);
			}

			// Note: Passing an invalid ID to clearTimeout() silently does nothing; no exception is thrown.
			if ( addressSelectionTimeout ) {
				clearTimeout( addressSelectionTimeout );
			}

			addressSelectionTimeout = setTimeout( function () {
				// Cache address fields again as they may have updated following the country change.
				cacheAddressFields( type );

				// Set all available fields.
				// Only set fields if the address data property exists and has a value.
				if ( addressData.address_2 ) {
					setFieldValue(
						addressInputs[ type ][ 'address_2' ],
						addressData.address_2
					);
				}
				if ( addressData.city ) {
					setFieldValue(
						addressInputs[ type ][ 'city' ],
						addressData.city
					);
				}
				if ( addressData.postcode ) {
					setFieldValue(
						addressInputs[ type ][ 'postcode' ],
						addressData.postcode
					);
				}
				if ( addressData.state ) {
					setFieldValue(
						addressInputs[ type ][ 'state' ],
						addressData.state
					);
				}
			}, 50 );
		}

		/**
		 * Set the active suggestion in the suggestions list, highlights it.
		 * @param type {string} The address type ('billing' or 'shipping').
		 * @param index {number} The index of the suggestion to set as active.
		 */
		function setActiveSuggestion( type, index ) {
			// Check if the address section exists (shipping may be disabled/hidden)
			if (
				! addressInputs[ type ] ||
				! addressInputs[ type ][ 'address_1' ]
			) {
				return;
			}

			if ( ! suggestionsLists[ type ] ) {
				return;
			}

			const suggestionsList = suggestionsLists[ type ];
			const addressInput = addressInputs[ type ][ 'address_1' ];

			const activeLi = suggestionsList.querySelector( 'li.active' );
			if ( activeLi ) {
				activeLi.classList.remove( 'active' );
				activeLi.setAttribute( 'aria-selected', 'false' );
			}

			const newActiveLi = suggestionsList.querySelector(
				`li#suggestion-item-${ type }-${ index }`
			);

			if ( newActiveLi ) {
				newActiveLi.classList.add( 'active' );
				newActiveLi.setAttribute( 'aria-selected', 'true' );
				addressInput.setAttribute(
					'aria-activedescendant',
					newActiveLi.id
				);
				activeSuggestionIndices[ type ] = index;
			}
		}

		// Initialize event handlers for each address type.
		addressTypes.forEach( ( type ) => {
			const addressInput = addressInputs[ type ][ 'address_1' ];
			const countryInput = addressInputs[ type ][ 'country' ];
			if ( addressInput && countryInput ) {
				addressInput.addEventListener( 'input', function () {
					displaySuggestions( this.value, countryInput.value, type );
				} );

				addressInput.addEventListener( 'keydown', async function ( e ) {
					// Check if suggestions exist before accessing them
					if (
						! suggestionsLists[ type ] ||
						! suggestionsContainers[ type ]
					) {
						return;
					}

					const items =
						suggestionsLists[ type ].querySelectorAll( 'li' );
					if (
						items.length === 0 ||
						suggestionsContainers[ type ].style.display === 'none'
					) {
						return;
					}

					let newIndex = activeSuggestionIndices[ type ];

					if ( e.key === 'ArrowDown' ) {
						e.preventDefault();
						newIndex =
							( activeSuggestionIndices[ type ] + 1 ) %
							items.length;
						setActiveSuggestion( type, newIndex );
					} else if ( e.key === 'ArrowUp' ) {
						e.preventDefault();
						newIndex =
							( activeSuggestionIndices[ type ] -
								1 +
								items.length ) %
							items.length;
						setActiveSuggestion( type, newIndex );
					} else if ( e.key === 'Enter' ) {
						if ( activeSuggestionIndices[ type ] > -1 ) {
							e.preventDefault();
							const selectedItem = suggestionsLists[
								type
							].querySelector(
								`li#suggestion-item-${ type }-${ activeSuggestionIndices[ type ] }`
							);
							// Hide suggestions immediately for better UX.
							hideSuggestions( type );
							enableBrowserAutofill( addressInput );
							await selectAddress(
								type,
								selectedItem.dataset.id
							);
						}
					} else if ( e.key === 'Escape' ) {
						hideSuggestions( type );
						enableBrowserAutofill( addressInput );
					}
				} );
			}
		} );

		// Hide suggestions when clicking outside.
		document.addEventListener( 'click', function ( event ) {
			addressTypes.forEach( ( type ) => {
				// Check if the address section exists before accessing elements
				if (
					! addressInputs[ type ] ||
					! addressInputs[ type ][ 'address_1' ]
				) {
					return;
				}

				if ( ! suggestionsContainers[ type ] ) {
					return;
				}

				const target = event.target;
				if (
					target !== suggestionsContainers[ type ] &&
					! suggestionsContainers[ type ].contains( target ) &&
					target !== addressInputs[ type ][ 'address_1' ]
				) {
					hideSuggestions( type );
				}
			} );
		} );
	} );
} )();
