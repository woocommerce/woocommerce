( function ( wc_order_attribution ) {
	'use strict';
	// Cache params reference for shorter reusability.
	const params = wc_order_attribution.params;

	// Helper functions.
	const $ = document.querySelector.bind( document );
	const propertyAccessor = ( obj, path ) => path.split( '.' ).reduce( ( acc, part ) => acc && acc[ part ], obj );
	const returnNull = () => null;
	const stringifyFalsyInputValue = ( value ) => value === null || value === undefined ? '' : value;

	// Hardcode Checkout store key (`wc.wcBlocksData.CHECKOUT_STORE_KEY`), as we no longer have `wc-blocks-checkout` as a dependency.
	const CHECKOUT_STORE_KEY = 'wc/store/checkout';

	/**
	 * Get the order attribution data.
	 *
	 * Returns object full of `null`s if tracking is disabled.
	 *
	 * @returns {Object} Schema compatible object.
	 */
	function getData() {
		const accessor = params.allowTracking ? propertyAccessor : returnNull;
		const entries = Object.entries( wc_order_attribution.fields )
				.map( ( [ key, property ] ) => [ key, accessor( sbjs.get, property ) ] );
		return Object.fromEntries( entries );
	}

	/**
	 * Update `wc_order_attribution` input elements' values.
	 *
	 * @param {Object} values Object containing field values.
	 */
	function updateFormValues( values ) {
		// Update `<wc-order-attribution-inputs>` elements if any exist.
		for( const element of document.querySelectorAll( 'wc-order-attribution-inputs' ) ) {
			element.values = values;
		}

	};

	/**
	 * Update Checkout extension data.
	 *
	 * @param {Object} values Object containing field values.
	 */
	function updateCheckoutBlockData( values ) {
		// Update Checkout block data if available.
		if ( window.wp && window.wp.data && window.wp.data.dispatch && window.wc && window.wc.wcBlocksData ) {
			window.wp.data.dispatch( window.wc.wcBlocksData.CHECKOUT_STORE_KEY ).__internalSetExtensionData(
				'woocommerce/order-attribution',
				values,
				true
			);
		}
	}

	/**
	 * Initialize sourcebuster & set data, or clear cookies & data.
	 *
	 * @param {boolean} allow Whether to allow tracking or disable it.
	 */
	wc_order_attribution.setOrderTracking = function( allow ) {
		params.allowTracking = allow;
		if ( ! allow ) {
			// Reset cookies, and clear form data.
			removeTrackingCookies();
		} else if ( typeof sbjs === 'undefined' ) {
			return; // Do nothing, as sourcebuster.js is not loaded.
		} else {
			// If not done yet, initialize sourcebuster.js which populates `sbjs.get` object.
			sbjs.init( {
				lifetime: Number( params.lifetime ),
				session_length: Number( params.session ),
				timezone_offset: '0', // utc
			} );
		}
		const values = getData();
		updateFormValues( values );
		updateCheckoutBlockData( values );
	}

	/**
	 * Remove sourcebuster.js cookies.
	 * To be called whenever tracking is disabled or consent is revoked.
	 */
	function removeTrackingCookies() {
		const domain = window.location.hostname;
		const sbCookies = [
			'sbjs_current',
			'sbjs_current_add',
			'sbjs_first',
			'sbjs_first_add',
			'sbjs_session',
			'sbjs_udata',
			'sbjs_migrations',
			'sbjs_promo'
		];

		// Remove cookies
		sbCookies.forEach( ( name ) => {
			document.cookie = `${name}=; path=/; max-age=-999; domain=.${domain};`;
		} );
	}

	// Run init.
	wc_order_attribution.setOrderTracking( params.allowTracking );

	// Work around the lack of explicit script dependency for the checkout block.
	// Conditionally, wait for and use 'wp-data' & 'wc-blocks-checkout.

	// Wait for (async) block checkout initialization and set source values once loaded.
	function eventuallyInitializeCheckoutBlock() {
		if (
			window.wp && window.wp.data && typeof window.wp.data.subscribe === 'function'
		) {
			// Update checkout block data once more if the checkout store was loaded after this script.
			const unsubscribe = window.wp.data.subscribe( function () {
				unsubscribe();
				updateCheckoutBlockData( getData() );
			}, CHECKOUT_STORE_KEY );
		}
	};
	// Wait for DOMContentLoaded to make sure wp.data is in place, if applicable for the page.
	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", eventuallyInitializeCheckoutBlock);
	} else {
		eventuallyInitializeCheckoutBlock();
	}

	/**
	 * Define an element to contribute order attribute values to the enclosing form.
	 * To be used with the classic checkout.
	 */
	window.customElements.define( 'wc-order-attribution-inputs', class extends HTMLElement {
		// Our bundler version does not support private class members, so we use a convention of `_` prefix.
		// #values
		// #fieldNames
		constructor(){
			super();
			// Cache fieldNames available at the construction time, to avoid malformed behavior if they change in runtime.
			this._fieldNames = Object.keys( wc_order_attribution.fields );
			// Allow values to be lazily set before CE upgrade.
			if ( this.hasOwnProperty( '_values' ) ) {
			  let values = this.values;
			  // Restore the setter.
			  delete this.values;
			  this.values = values || {};
			}
		}
		/**
		 * Stamp input elements to the element's light DOM.
		 *
		 * We could use `.elementInternals.setFromValue` and avoid sprouting `<input>` elements,
		 * but it's not yet supported in Safari.
		 */
		connectedCallback() {
			this.innerHTML = '';
			const inputs = new DocumentFragment();
			for( const fieldName of this._fieldNames ) {
				const input = document.createElement( 'input' );
				input.type = 'hidden';
				input.name = `${params.prefix}${fieldName}`;
				input.value = stringifyFalsyInputValue( ( this.values && this.values[ fieldName ] ) || '' );
				inputs.appendChild( input );
			}
			this.appendChild( inputs );
		}

		/**
		 * Update form values.
		 */
		set values( values ) {
			this._values = values;
			if( this.isConnected ) {
				for( const fieldName of this._fieldNames ) {
					const input = this.querySelector( `input[name="${params.prefix}${fieldName}"]` );
					if( input ) {
						input.value = stringifyFalsyInputValue( this.values[ fieldName ] );
					} else {
						console.warn( `Field "${fieldName}" not found. Most likely, the '<wc-order-attribution-inputs>' element was manipulated.`);
					}
				}
			}
		}
		get values() {
			return this._values;
		}
	} );


}( window.wc_order_attribution ) );
