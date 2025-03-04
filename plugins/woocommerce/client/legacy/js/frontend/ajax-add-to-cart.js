/**
 * This is an interim solution for handling AJAX add-to-cart functionality,
 * allowing features like the mini-cart drawer to open when products are added.
 * This will eventually be replaced by the blockified Add to Cart implementation
 * tracked in https://github.com/woocommerce/woocommerce/issues/53014
 */
( function () {
	function getFormData( form ) {
		const formData = [];
		const elements = form.querySelectorAll(
			'input:not([name="product_id"]), select, button, textarea'
		);

		elements.forEach( ( element ) => {
			const name = element.getAttribute( 'name' );
			let value = element.value;

			if ( ! name ) {
				return;
			}

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

	document.addEventListener( 'click', function ( e ) {
		const button = e.target.closest(
			'.single_add_to_cart_button:not(.disabled)'
		);
		if ( ! button ) return;

		e.preventDefault();

		const form = button.closest( 'form.cart' );
		const formData = getFormData( form );

		formData.forEach( ( item ) => {
			if ( item.name === 'add-to-cart' ) {
				item.name = 'product_id';
				item.value =
					form.querySelector( 'input[name=variation_id]' )?.value ||
					button.value;
			}
		} );

		triggerEvent( document.body, 'adding_to_cart', [ button, formData ] );

		const formDataObject = new URLSearchParams();
		formData.forEach( ( item ) => {
			formDataObject.append( item.name, item.value );
		} );

		fetch(
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
		)
			.then( ( response ) => response.json() )
			.then( ( response ) => {
				button.classList.remove( 'loading' );
				button.classList.add( 'added' );

				if ( response.error && response.product_url ) {
					window.location = response.product_url;
					return;
				}

				triggerEvent( document.body, 'added_to_cart', {
					detail: [ response.fragments, response.cart_hash, button ],
				} );
			} )
			.catch( ( error ) => {
				console.error( 'Error:', error );
				button.classList.remove( 'loading' );
			} );

		button.classList.add( 'loading' );
		button.classList.remove( 'added' );

		return false;
	} );
} )();
