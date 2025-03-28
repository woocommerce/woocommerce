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

			if ( data.error && data.product_url ) {
				window.location = data.product_url;
				return;
			}

			triggerEvent( document.body, 'added_to_cart', {
				detail: [ data.fragments, data.cart_hash, button ],
			} );

			return data;
		} catch ( error ) {
			console.error( error );
		}
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

		triggerEvent( document.body, 'adding_to_cart', [ button, formData ] );

		return addToCart( formData, button );
	}

	document.addEventListener( 'click', function ( e ) {
		const button = e.target.closest(
			'.single_add_to_cart_button:not(.disabled)'
		);
		if ( ! button ) return;

		e.preventDefault();

		const form = button.closest( 'form.cart' );
		if ( ! form ) return;

		handleRegularProduct( form, button );

		return false;
	} );
} )();
