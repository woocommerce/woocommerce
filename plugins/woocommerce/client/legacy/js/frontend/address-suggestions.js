( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const featureFlags = window.STORE_SETTINGS || {};
		const enableAddressSuggestions =
			featureFlags.ENABLE_ADDRESS_SUGGESTIONS === true;

		// if ( ! enableAddressSuggestions ) {
		// 	return;
		// }

		const addressTypes = [ 'billing', 'shipping' ];
		const addressInputs = {};
		const suggestionsContainers = {};
		const suggestionsLists = {};
		let activeSuggestionIndices = {};

		// Initialize for both billing and shipping
		addressTypes.forEach( ( type ) => {
			const addressInput = document.getElementById(
				`${ type }_address_1`
			);
			if ( addressInput ) {
				// Create suggestions container if it doesn't exist
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

					// Add search icon
					const searchIcon = document.createElement( 'div' );
					searchIcon.className = 'address-search-icon';
					searchIcon.innerHTML =
						'<svg xmlns="http://www.w3.org/2000/svg" ' +
						'viewBox="0 0 14 14" ' +
						'focusable="false" ' +
						'aria-hidden="true">' +
						'<circle cx="6" cy="6" r="4"></circle>' +
						'<path stroke-linecap="round" ' +
						'stroke-linejoin="round" ' +
						'd="m9.25 9.25 2.5 2.5"></path>' +
						'</svg>';
					addressInput.parentNode.appendChild( searchIcon );
				}

				addressInputs[ type ] = addressInput;
				suggestionsContainers[ type ] = document.getElementById(
					`address_suggestions_${ type }`
				);
				suggestionsLists[ type ] =
					suggestionsContainers[ type ].querySelector(
						'.suggestions-list'
					);
				activeSuggestionIndices[ type ] = -1;
			}
		} );

		// Mock data for suggestions
		const mockSuggestions = [
			{
				id: '01971ca5-35d2-7514-adaf-d4ab97c02c19',
				label: '10 Downing Street, London, SW1A 2AA, GB',
				matchedSubstrings: [
					{
						length: 19,
						offset: 0,
					},
					{
						length: 1,
						offset: 21,
					},
				],
			},
			{
				id: '01971ca5-35d2-7514-adaf-da427f3b640f',
				label: '1600 Amphitheatre Parkway, Mountain View, CA, US',
				matchedSubstrings: [
					{
						length: 19,
						offset: 5,
					},
					{
						length: 1,
						offset: 26,
					},
				],
			},
			{
				id: '01971ca5-35d2-7514-adaf-dfa0a03f1e49',
				label: 'Eiffel Tower, Paris, 75007, FR',
				matchedSubstrings: [
					{
						length: 19,
						offset: 5,
					},
					{
						length: 1,
						offset: 26,
					},
				],
			},
			{
				id: '01971ca5-35d2-7514-adaf-e3bd0086bea9',
				label: '1 Hacker Way, Menlo Park, CA, US',
				matchedSubstrings: [
					{
						length: 19,
						offset: 5,
					},
					{
						length: 1,
						offset: 26,
					},
				],
			},
			{
				id: '01971ca5-35d2-7514-adaf-e3bd0086bea6',
				label: 'Very long address in the middle of the screen, 98000, Menlo Park, CA, US',
				matchedSubstrings: [
					{
						length: 19,
						offset: 5,
					},
					{
						length: 1,
						offset: 26,
					},
				],
			},
		];

		const mockAddressData = {
			'01971ca5-35d2-7514-adaf-d4ab97c02c19': {
				address1: '10 Downing Street',
				city: 'London',
				postcode: 'SW1A 2AA',
				country: 'GB',
			},
			'01971ca5-35d2-7514-adaf-da427f3b640f': {
				address1: '1600 Amphitheatre Parkway',
				city: 'Mountain View',
				postcode: '94043',
				country: 'US',
			},
			'01971ca5-35d2-7514-adaf-dfa0a03f1e49': {
				address1: 'Eiffel Tower',
				city: 'Paris',
				postcode: '75007',
				country: 'FR',
			},
			'01971ca5-35d2-7514-adaf-e3bd0086bea9': {
				address1: '1 Hacker Way',
				city: 'Menlo Park',
				postcode: '94025',
				country: 'US',
			},
			'01971ca5-35d2-7514-adaf-e3bd0086bea6': {
				address1: 'Very long address in the middle of the screen',
				city: 'Menlo Park',
				postcode: '98000',
				country: 'US',
			},
		};

		function disableBrowserAutofill( input ) {
			if ( input.getAttribute( 'autocomplete' ) === 'off' ) {
				return;
			}

			input.setAttribute( 'autocomplete', 'off' );
			input.setAttribute( 'data-1p-ignore', '' );
			input.setAttribute( 'data-lpignore', '' );

			// We need to refocus the element so that the browser can reset the autocomplete attribute.
			const parentElement = input.parentElement;
			if ( parentElement ) {
				parentElement.insertBefore( input, parentElement.firstChild );
				input.focus();
			}
		}

		function enableBrowserAutofill( input ) {
			if ( input.getAttribute( 'autocomplete' ) !== 'off' ) {
				return;
			}

			input.setAttribute( 'autocomplete', 'address-line1' );
			input.removeAttribute( 'data-1p-ignore' );
			input.removeAttribute( 'data-lpignore' );
		}

		function getHighlightedLabel( label, matches ) {
			const parts = [];
			let lastIndex = 0;

			matches.forEach( ( match ) => {
				// Add text before match
				if ( match.offset > lastIndex ) {
					parts.push(
						document.createTextNode(
							label.slice( lastIndex, match.offset )
						)
					);
				}

				// Add bold matched text
				const bold = document.createElement( 'strong' );
				bold.textContent = label.slice(
					match.offset,
					match.offset + match.length
				);
				parts.push( bold );

				lastIndex = match.offset + match.length;
			} );

			// Add remaining text
			if ( lastIndex < label.length ) {
				parts.push(
					document.createTextNode( label.slice( lastIndex ) )
				);
			}

			return parts;
		}

		function displaySuggestions( type, inputValue ) {
			const addressInput = addressInputs[ type ];
			const suggestionsList = suggestionsLists[ type ];
			const suggestionsContainer = suggestionsContainers[ type ];

			if ( inputValue.length < 3 ) {
				hideSuggestions( type );
				enableBrowserAutofill( addressInput );
				return;
			}

			suggestionsList.innerHTML = '';
			const filteredSuggestions = mockSuggestions;

			if ( filteredSuggestions.length === 0 ) {
				hideSuggestions( type );
				return;
			}

			filteredSuggestions.forEach( ( suggestion, index ) => {
				const li = document.createElement( 'li' );
				li.setAttribute( 'role', 'option' );
				li.id = `suggestion-item-${ type }-${ index }`;
				li.dataset.id = suggestion.id;
				li.setAttribute( 'tabindex', '-1' );

				li.textContent = ''; // Clear existing content
				const labelParts = getHighlightedLabel(
					suggestion.label,
					suggestion.matchedSubstrings || []
				);
				labelParts.forEach( ( part ) => li.appendChild( part ) );

				li.addEventListener( 'click', function () {
					selectAddress( type, this.dataset.id );
					hideSuggestions( type );
					addressInput.focus();
				} );

				li.addEventListener( 'mouseenter', function () {
					setActiveSuggestion( type, index );
				} );

				suggestionsList.appendChild( li );
			} );

			disableBrowserAutofill( addressInput );
			suggestionsContainer.style.display = 'block';
			addressInput.setAttribute( 'aria-expanded', 'true' );
			addressInput.setAttribute(
				'aria-owns',
				`address_suggestions_${ type }_list`
			);
			suggestionsList.id = `address_suggestions_${ type }_list`;
			setActiveSuggestion( type, 0 );
		}

		function hideSuggestions( type ) {
			const suggestionsList = suggestionsLists[ type ];
			const suggestionsContainer = suggestionsContainers[ type ];
			const addressInput = addressInputs[ type ];

			suggestionsList.innerHTML = '';
			suggestionsContainer.style.display = 'none';
			addressInput.setAttribute( 'aria-expanded', 'false' );
			addressInput.removeAttribute( 'aria-activedescendant' );
			addressInput.removeAttribute( 'aria-owns' );
			activeSuggestionIndices[ type ] = -1;
		}

		function selectAddress( type, addressId ) {
			const addressInput = addressInputs[ type ];
			const addressData = mockAddressData[ addressId ];
			addressInput.value = addressData.address1;
			addressInput.dispatchEvent( new Event( 'change' ) );
			hideSuggestions( type );
		}

		function setActiveSuggestion( type, index ) {
			const suggestionsList = suggestionsLists[ type ];
			const addressInput = addressInputs[ type ];

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

		// Initialize event handlers for each address type
		addressTypes.forEach( ( type ) => {
			const addressInput = addressInputs[ type ];
			if ( addressInput ) {
				let inputTimeout;

				addressInput.addEventListener( 'input', function () {
					clearTimeout( inputTimeout );
					inputTimeout = window.setTimeout( () => {
						if ( document.activeElement === this ) {
							displaySuggestions( type, this.value );
						}
					}, 100 );
				} );

				addressInput.addEventListener( 'keydown', function ( e ) {
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
							selectAddress( type, selectedItem.dataset.id );
						}
					} else if ( e.key === 'Escape' ) {
						hideSuggestions( type );
					}
				} );
			}
		} );

		// Hide suggestions when clicking outside
		document.addEventListener( 'click', function ( event ) {
			addressTypes.forEach( ( type ) => {
				const target = event.target;
				if (
					target !== suggestionsContainers[ type ] &&
					! suggestionsContainers[ type ].contains( target ) &&
					target !== addressInputs[ type ]
				) {
					hideSuggestions( type );
				}
			} );
		} );
	} );
} )();
