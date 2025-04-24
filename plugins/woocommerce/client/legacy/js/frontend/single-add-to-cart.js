( function () {
	function getFormData( form ) {
		const formData = [];
		const elements = form.querySelectorAll(
			'input:not([name="product_id"]), select, button, textarea'
		);

		elements.forEach( ( element ) => {
			const name = element.getAttribute( 'name' );

			if ( ! name ) {
				return;
			}

			let value = element.value;

			if ( element.type === 'checkbox' ) {
				value = element.checked ? value : '';
			}

			if ( Array.isArray( value ) ) {
				value.forEach( ( val ) => {
					formData.push( {
						name: name,
						value: val.replace( /\r?\n/g, '\r\n' ),
					} );
				} );
			} else {
				formData.push( {
					name: name,
					value: value.replace( /\r?\n/g, '\r\n' ),
				} );
			}
		} );

		return formData;
	}

	function triggerEvent( element, eventName, data = null ) {
		const event = new CustomEvent( eventName, {
			bubbles: true,
			cancelable: true,
			detail: data,
		} );
		element.dispatchEvent( event );
	}

	function updateFragments( fragments ) {
		if ( ! fragments ) {
			return;
		}
		Object.entries( fragments ).forEach( ( [ key, value ] ) => {
			const element = document.querySelector( key );
			if ( ! element ) return;

			element.insertAdjacentHTML( 'afterend', value );
			element.remove();
		} );

		triggerEvent( document.body, 'wc_fragments_loaded' );
	}

	function updateGroupedProductButtonState( form ) {
		const button = form.querySelector( '.single_add_to_cart_button' );
		if ( ! button ) return;

		let hasQuantity = false;
		form.querySelectorAll( 'input[name^="quantity"]' ).forEach(
			( input ) => {
				if ( input.type === 'checkbox' && ! input.checked ) {
					return;
				}

				if ( input.value > 0 ) {
					hasQuantity = true;
				}
			}
		);

		if ( ! hasQuantity ) {
			button.classList.add( 'disabled' );
		} else {
			button.classList.remove( 'disabled' );
		}
	}

	function handleButtonState( button, state ) {
		if ( state === 'loading' ) {
			button.classList.add( 'loading' );
			button.classList.remove( 'added' );
		} else if ( state === 'added' ) {
			button.classList.remove( 'loading' );
			button.classList.add( 'added' );
		} else if ( state === 'error' ) {
			button.classList.remove( 'loading' );
			button.classList.remove( 'added' );
		}
	}

	function updateLiveRegion( message ) {
		if ( ! message ) {
			return;
		}

		let liveRegion = document.querySelector(
			'.widget_shopping_cart_live_region'
		);

		if ( ! liveRegion ) {
			liveRegion = document.createElement( 'div' );
			liveRegion.className =
				'widget_shopping_cart_live_region screen-reader-text';
			liveRegion.setAttribute( 'role', 'status' );
			document.body.appendChild( liveRegion );
		}

		// If the response after adding/removing an item to/from the cart is really fast,
		// screen readers may not have time to identify the changes in the live region element.
		// So, we add a delay to ensure an interval between messages.
		setTimeout( () => {
			liveRegion.innerHTML = message;
			liveRegion.setAttribute( 'aria-relevant', 'all' );
		}, 1000 );
	}

	async function addToCart( formData, button ) {
		const formDataObject = new URLSearchParams();
		formData.forEach( ( item ) => {
			formDataObject.append( item.name, item.value );
		} );

		try {
			const response = await fetch(
				woocommerce_params.wc_ajax_url
					.toString()
					.replace( '%%endpoint%%', 'add_to_cart' ),
				{
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: formDataObject,
				}
			);

			if ( ! response.ok ) {
				throw new Error( `HTTP error! status: ${ response.status }` );
			}

			const text = await response.text();
			let data;
			try {
				data = JSON.parse( text );
			} catch ( e ) {
				throw new Error( 'Invalid JSON response from server' );
			}

			handleButtonState( button, 'added' );

			if ( data.error && data.product_url ) {
				window.location = data.product_url;
				return;
			}

			triggerEvent( document.body, 'added_to_cart', {
				detail: [ data.fragments, data.cart_hash, button ],
			} );

			if ( ! button.dataset.originalText ) {
				button.dataset.originalText = button.textContent;
			}
			button.textContent = data.button_text;

			updateFragments( data.fragments );
			updateLiveRegion( data.success_message );

			return data;
		} catch ( error ) {
			handleButtonState( button, 'error' );
			console.error( error );
		}
	}

	function handleGroupedProduct( form, button ) {
		const quantities = {};
		let hasQuantity = false;
		form.querySelectorAll( 'input[name^="quantity"]' ).forEach(
			( input ) => {
				if ( input.type === 'checkbox' && ! input.checked ) {
					return;
				}
				quantities[ input.name ] = input.value;
				if ( input.value > 0 ) {
					hasQuantity = true;
				}
			}
		);

		if ( ! hasQuantity ) {
			return false;
		}

		handleButtonState( button, 'loading' );
		triggerEvent( document.body, 'adding_to_cart', [ button, quantities ] );

		const formData = [];
		Object.entries( quantities ).forEach( ( [ name, value ] ) => {
			if ( value > 0 ) {
				const productId = name.match( /\[(\d+)\]/ )[ 1 ];
				formData.push( { name: 'product_id[]', value: productId } );
				formData.push( { name: 'quantity[]', value: value } );
			}
		} );

		return addToCart( formData, button );
	}

	function handleRegularProduct( form, button ) {
		const formData = getFormData( form );
		formData.forEach( ( item ) => {
			if ( item.name === 'add-to-cart' ) {
				item.name = 'product_id';
				const variation = form.querySelector(
					'input[name=variation_id]'
				);
				item.value = variation ? variation.value : button.value;
			}
		} );

		handleButtonState( button, 'loading' );
		triggerEvent( document.body, 'adding_to_cart', [ button, formData ] );

		return addToCart( formData, button );
	}

	document.querySelectorAll( 'form.grouped_form' ).forEach( ( form ) => {
		updateGroupedProductButtonState( form );

		form.querySelectorAll( 'input[name^="quantity"]' ).forEach(
			( input ) => {
				input.addEventListener( 'change', () => {
					updateGroupedProductButtonState( form );
				} );
			}
		);
	} );

	document.addEventListener( 'click', function ( e ) {
		const button = e.target.closest(
			'.single_add_to_cart_button:not(.disabled)'
		);
		if ( ! button ) return;

		e.preventDefault();

		const form = button.closest( 'form.cart' );
		if ( ! form ) return;

		if ( form.classList.contains( 'grouped_form' ) ) {
			handleGroupedProduct( form, button );
		} else {
			handleRegularProduct( form, button );
		}

		return false;
	} );

	document.addEventListener(
		'woocommerce_variation_has_changed',
		function ( e ) {
			const form = e.detail.form;
			const button = form.querySelector( '.single_add_to_cart_button' );
			if ( ! button || ! button.dataset.originalText ) {
				return;
			}

			button.textContent = button.dataset.originalText;
		}
	);
} )();
