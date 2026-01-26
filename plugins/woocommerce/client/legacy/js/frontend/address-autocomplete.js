/**
 * Address provider implementation for WooCommerce shortcode checkout
 *
 * Note: The core registration logic and provider management is handled
 * by the common module (address-autocomplete-common.js). This file focuses
 * on the shortcode-specific implementation.
 */

// The common module will have already initialized window.wc.addressAutocomplete
// with providers, activeProvider, serverProviders, and the registration function.
// We just need to use them here.

if (
	! window.wc ||
	! window.wc.wcSettings ||
	! window.wc.wcSettings.allSettings ||
	! window.wc.wcSettings.allSettings.isCheckoutBlock
) {
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
				window.wc.addressAutocomplete.serverProviders;

			// Check providers in preference order (server handles preferred provider ordering).
			for ( const serverProvider of serverProviders ) {
				const provider =
					window.wc.addressAutocomplete.providers[
						serverProvider.id
					];

				if ( provider && provider.canSearch( country ) ) {
					window.wc.addressAutocomplete.activeProvider[ type ] =
						provider;
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
						// Add combobox role and ARIA attributes for accessibility
						addressInput.setAttribute( 'role', 'combobox' );
						addressInput.setAttribute(
							'aria-autocomplete',
							'list'
						);
						addressInput.setAttribute( 'aria-expanded', 'false' );
						addressInput.setAttribute( 'aria-haspopup', 'listbox' );
					}
					return;
				}
			}

			// No provider can search for this country.
			window.wc.addressAutocomplete.activeProvider[ type ] = null;
			// Remove autocomplete-available class from parent .woocommerce-input-wrapper
			const addressInput = document.getElementById(
				`${ type }_address_1`
			);
			if ( addressInput ) {
				const wrapper = addressInput.closest(
					'.woocommerce-input-wrapper'
				);
				if ( wrapper ) {
					wrapper.classList.remove( 'autocomplete-available' );
				}
				// Remove all ARIA attributes when no provider is available
				addressInput.removeAttribute( 'role' );
				addressInput.removeAttribute( 'aria-autocomplete' );
				addressInput.removeAttribute( 'aria-expanded' );
				addressInput.removeAttribute( 'aria-haspopup' );
				addressInput.removeAttribute( 'aria-activedescendant' );
				addressInput.removeAttribute( 'aria-owns' );
				addressInput.removeAttribute( 'aria-controls' );
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
				addressInputs[ type ][ 'address_2' ] = document.getElementById(
					`${ type }_address_2`
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
				const countryInput = addressInputs[ type ][ 'country' ];

				if ( addressInput ) {
					// Create suggestions container if it doesn't exist.
					if (
						! document.getElementById(
							`address_suggestions_${ type }`
						)
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
						list.setAttribute(
							'aria-label',
							'Address suggestions'
						);

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
							// Remove branding element when country changes
							if ( suggestionsContainers[ type ] ) {
								const brandingElement = suggestionsContainers[
									type
								].querySelector(
									'.woocommerce-address-autocomplete-branding'
								);
								if ( brandingElement ) {
									brandingElement.remove();
								}
							}
						}
					};

					countryInput.addEventListener(
						'change',
						handleCountryChange
					);

					// Also listen for Select2 change event if jQuery and Select2 are available.
					if (
						window.jQuery &&
						window.jQuery( countryInput ).select2
					) {
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
				if ( input.getAttribute( 'autocomplete' ) === 'none' ) {
					return;
				}

				// Store the original autocomplete value before disabling
				const originalAutocomplete =
					input.getAttribute( 'autocomplete' ) || '';
				input.setAttribute(
					'data-original-autocomplete',
					originalAutocomplete
				);

				input.setAttribute( 'autocomplete', 'none' );
				input.setAttribute( 'data-lpignore', 'true' );
				input.setAttribute( 'data-op-ignore', 'true' );
				input.setAttribute( 'data-1p-ignore', 'true' );

				// To prevent 1Password/LastPass and autocomplete clashes, we need to refocus the element.
				// This is achieved by removing and re-adding the element to trigger browser updates.
				const parentElement = input.parentElement;
				if ( parentElement ) {
					// Store the current value to preserve it
					const currentValue = input.value;

					// Mark that we're manipulating the DOM to prevent checkout updates
					input.setAttribute(
						'data-autocomplete-manipulating',
						'true'
					);

					parentElement.appendChild(
						parentElement.removeChild( input )
					);

					// Restore the value if it was lost
					if ( input.value !== currentValue ) {
						input.value = currentValue;
					}

					// Remove the manipulation flag after a brief delay
					setTimeout( function () {
						input.removeAttribute(
							'data-autocomplete-manipulating'
						);
					}, 10 );

					input.focus();
				}
			}

			/**
			 * Enable browser autofill for address input.
			 * @param input {HTMLInputElement} The input element to enable autofill for.
			 * @param shouldFocus {boolean} Whether to focus the input after enabling autofill.
			 */
			function enableBrowserAutofill( input, shouldFocus = true ) {
				if ( input.getAttribute( 'autocomplete' ) !== 'none' ) {
					return;
				}

				// Restore the original autocomplete value
				const originalAutocomplete =
					input.getAttribute( 'data-original-autocomplete' ) ||
					'address-line1';
				input.setAttribute( 'autocomplete', originalAutocomplete );
				input.setAttribute( 'data-lpignore', 'false' );
				input.setAttribute( 'data-op-ignore', 'false' );
				input.setAttribute( 'data-1p-ignore', 'false' );

				// To ensure browser updates and re-enables autofill, we need to refocus the element.
				// This is achieved by removing and re-adding the element to trigger browser updates.
				const parentElement = input.parentElement;
				if ( parentElement ) {
					// Store the current value to preserve it
					const currentValue = input.value;

					// Mark that we're manipulating the DOM to prevent checkout updates
					input.setAttribute(
						'data-autocomplete-manipulating',
						'true'
					);

					parentElement.appendChild(
						parentElement.removeChild( input )
					);

					// Restore the value if it was lost
					if ( input.value !== currentValue ) {
						input.value = currentValue;
					}

					// Remove the manipulation flag after a brief delay. Use two rAFs to ensure layout/assistive tech settle.
					requestAnimationFrame( function () {
						requestAnimationFrame( function () {
							input.removeAttribute(
								'data-autocomplete-manipulating'
							);
						} );
					} );

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
						document.createTextNode(
							sanitizedLabel.slice( lastIndex )
						)
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
						li.setAttribute( 'aria-label', suggestion.label );
						li.id = `suggestion-item-${ type }-${ index }`;
						li.dataset.id = suggestion.id;

						li.textContent = ''; // Clear existing content.
						const labelParts = getHighlightedLabel(
							suggestion.label,
							suggestion.matchedSubstrings || []
						);
						labelParts.forEach( ( part ) =>
							li.appendChild( part )
						);

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

					// Update branding HTML content and make sure it's visible.
					// Sanitize the HTML using DOMPurify if available
					if (
						typeof DOMPurify !== 'undefined' &&
						typeof DOMPurify.sanitize === 'function'
					) {
						// Add branding HTML if available from the active provider.
						const activeProvider =
							window.wc.addressAutocomplete.activeProvider[
								type
							];
						if ( activeProvider && activeProvider.id ) {
							const serverProvider =
								window.wc.addressAutocomplete.getServerProvider(
									activeProvider.id
								);
							const brandingHtml =
								serverProvider &&
								typeof serverProvider.branding_html === 'string'
									? serverProvider.branding_html.trim()
									: '';
							if ( brandingHtml ) {
								// Check if branding element already exists.
								let brandingElement =
									suggestionsContainer.querySelector(
										'.woocommerce-address-autocomplete-branding'
									);
								if ( ! brandingElement ) {
									brandingElement =
										document.createElement( 'div' );
									brandingElement.className =
										'woocommerce-address-autocomplete-branding';
									suggestionsContainer.appendChild(
										brandingElement
									);
								}
								// Allow common HTML tags and attributes for branding
								const sanitizedHtml = DOMPurify.sanitize(
									serverProvider.branding_html,
									{
										ALLOWED_TAGS: [
											'img',
											'span',
											'div',
											'a',
											'b',
											'i',
											'em',
											'strong',
											'br',
										],
										ALLOWED_ATTR: [
											'href',
											'target',
											'rel',
											'src',
											'alt',
											'style',
											'class',
											'id',
											'width',
											'height',
										],
										ALLOW_DATA_ATTR: false,
									}
								);
								brandingElement.innerHTML = sanitizedHtml;
								brandingElement.style.display = 'flex';
								brandingElement.removeAttribute(
									'aria-hidden'
								);
							}
						}
					}

					disableBrowserAutofill( addressInput );
					suggestionsContainer.style.display = 'block';
					suggestionsContainer.style.marginTop =
						addressInputs[ type ][ 'address_1' ].offsetHeight +
						'px';
					addressInput.setAttribute( 'aria-expanded', 'true' );
					suggestionsList.id = `address_suggestions_${ type }_list`;
					addressInput.setAttribute(
						'aria-controls',
						`address_suggestions_${ type }_list`
					);
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

				// Hide branding element but keep it in DOM (will be removed on country change).
				const brandingElement = suggestionsContainer.querySelector(
					'.woocommerce-address-autocomplete-branding'
				);
				if ( brandingElement ) {
					brandingElement.style.display = 'none';
					brandingElement.setAttribute( 'aria-hidden', 'true' );
				}

				suggestionsContainer.style.display = 'none';
				addressInput.setAttribute( 'aria-expanded', 'false' );
				addressInput.removeAttribute( 'aria-activedescendant' );
				addressInput.removeAttribute( 'aria-controls' );
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

				// Check if addressInputs exists for this type
				if ( ! addressInputs[ type ] ) {
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

					// Check if addressInputs exists for this type after re-caching
					if ( ! addressInputs[ type ] ) {
						return;
					}

					// Set all available fields.
					// Only set fields if the address data property exists and has a value.
					if ( addressData.address_2 ) {
						setFieldValue(
							addressInputs[ type ][ 'address_2' ],
							addressData.address_2
						);
					} else {
						// Clear address_2 if not provided in address data.
						const addr2El = addressInputs[ type ][ 'address_2' ];
						if ( addr2El && addr2El.value ) {
							setFieldValue( addr2El, '' );
						}
					}
					if ( addressData.city ) {
						setFieldValue(
							addressInputs[ type ][ 'city' ],
							addressData.city
						);
					} else {
						// Clear city if not provided in address data.
						const cityEl = addressInputs[ type ][ 'city' ];
						if ( cityEl && cityEl.value ) {
							setFieldValue( cityEl, '' );
						}
					}
					if ( addressData.postcode ) {
						setFieldValue(
							addressInputs[ type ][ 'postcode' ],
							addressData.postcode
						);
					} else {
						// Clear postcode if not provided in address data.
						const postcodeEl = addressInputs[ type ][ 'postcode' ];
						if ( postcodeEl && postcodeEl.value ) {
							setFieldValue( postcodeEl, '' );
						}
					}
					if ( addressData.state ) {
						setFieldValue(
							addressInputs[ type ][ 'state' ],
							addressData.state
						);
					} else {
						// Clear state if not provided in address data.
						const stateEl = addressInputs[ type ][ 'state' ];
						if ( stateEl && stateEl.value ) {
							setFieldValue( stateEl, '' );
						}
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
				// Check if addressInputs exists for this type
				if ( ! addressInputs[ type ] ) {
					return;
				}
				const addressInput = addressInputs[ type ][ 'address_1' ];
				const countryInput = addressInputs[ type ][ 'country' ];
				if ( addressInput && countryInput ) {
					addressInput.addEventListener( 'input', function () {
						// Unset any active suggestion when user types
						if ( suggestionsLists[ type ] ) {
							const activeLi =
								suggestionsLists[ type ].querySelector(
									'li.active'
								);
							if ( activeLi ) {
								activeLi.classList.remove( 'active' );
								activeLi.setAttribute(
									'aria-selected',
									'false'
								);
							}
							addressInput.removeAttribute(
								'aria-activedescendant'
							);
							activeSuggestionIndices[ type ] = -1;
						}
						displaySuggestions(
							this.value,
							countryInput.value,
							type
						);
					} );

					addressInput.addEventListener(
						'keydown',
						async function ( e ) {
							// Check if suggestions exist before accessing them
							if (
								! suggestionsLists[ type ] ||
								! suggestionsContainers[ type ]
							) {
								return;
							}

							const items =
								suggestionsLists[ type ].querySelectorAll(
									'li'
								);
							if (
								items.length === 0 ||
								suggestionsContainers[ type ].style.display ===
									'none'
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
									if (
										! selectedItem ||
										! selectedItem.dataset ||
										! selectedItem.dataset.id
									) {
										// The selected item was invalid, hide suggestions and re-enable autofill.
										hideSuggestions( type );
										enableBrowserAutofill( addressInput );
										return;
									}
									// Hide suggestions immediately for better UX.
									hideSuggestions( type );
									enableBrowserAutofill( addressInput );
									await selectAddress(
										type,
										selectedItem.dataset.id
									);
									// Return focus to the address input after selection
									addressInput.focus();
								}
							} else if ( e.key === 'Escape' ) {
								hideSuggestions( type );
								enableBrowserAutofill( addressInput );
							}
						}
					);
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
						// Restore native autofill after manual dismissal.
						if (
							addressInputs[ type ] &&
							addressInputs[ type ][ 'address_1' ]
						) {
							enableBrowserAutofill(
								addressInputs[ type ][ 'address_1' ],
								false
							);
						}
					}
				} );
			} );
		} );
	} )();
}
