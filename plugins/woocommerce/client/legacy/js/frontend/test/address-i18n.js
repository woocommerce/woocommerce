/**
 * @jest-environment jest-fixed-jsdom
 */

const LOCALE_FIELDS = {
	state: '#billing_state_field, #shipping_state_field, #calc_shipping_state_field',
	postcode:
		'#billing_postcode_field, #shipping_postcode_field, #calc_shipping_postcode_field',
	city: '#billing_city_field, #shipping_city_field, #calc_shipping_city_field',
};

const DEFAULT_LOCALE = {
	state: { required: true, priority: 80 },
	postcode: { required: true, priority: 90 },
	city: { required: true, priority: 70 },
};

function row( id, key, control, priority ) {
	return `
		<p class="form-row address-field validate-required" id="${ id }_field" data-priority="${ priority }">
			<label for="${ id }">${ key } <span class="required" aria-hidden="true">*</span></label>
			<span class="woocommerce-input-wrapper">${ control }</span>
		</p>`;
}

function renderCheckoutForm() {
	document.body.innerHTML = `
		<form class="checkout woocommerce-checkout">
			<div class="woocommerce-billing-fields">
				<div class="woocommerce-billing-fields__field-wrapper">
					${ row(
						'billing_city',
						'Town / City',
						'<input type="text" class="input-text" name="billing_city" id="billing_city" value="New York">',
						70
					) }
					${ row(
						'billing_state',
						'State',
						'<select name="billing_state" id="billing_state">' +
							'<option value="CA" selected>California</option>' +
							'<option value="NY">New York</option>' +
							'</select>',
						80
					) }
					${ row(
						'billing_postcode',
						'ZIP Code',
						'<input type="text" class="input-text" name="billing_postcode" id="billing_postcode" value="">',
						90
					) }
				</div>
			</div>
		</form>
		<div id="order_review"></div>`;
}

/**
 * Loads the scripts the way the classic checkout page does, with US locale
 * overrides for the postcode, then applies the US locale to the form.
 */
async function loadCheckout( usPostcodeLocale, scripts ) {
	jest.resetModules();
	renderCheckoutForm();

	const $ = require( 'jquery' );
	global.jQuery = $;
	window.jQuery = $;
	$.blockUI = { defaults: { overlayCSS: {} } };
	$.fn.block = function () {
		return this;
	};
	$.fn.unblock = function () {
		return this;
	};
	$.ajax = jest.fn( () => ( { abort: jest.fn() } ) );

	window.wc_address_i18n_params = {
		locale: JSON.stringify( {
			default: DEFAULT_LOCALE,
			US: { postcode: usPostcodeLocale },
		} ),
		locale_fields: JSON.stringify( LOCALE_FIELDS ),
		i18n_required_text: 'required',
		i18n_optional_text: 'optional',
	};
	window.wc_checkout_params = {
		is_checkout: '0',
		option_guest_checkout: 'no',
		wc_ajax_url: '/?wc-ajax=%%endpoint%%',
		update_order_review_nonce: 'nonce',
	};
	window.wc = { customPlaceOrderButton: { __cleanup: jest.fn() } };

	scripts.forEach( ( script ) => require( script ) );

	// jQuery runs ready callbacks asynchronously; wait for the scripts' own.
	await new Promise( ( resolve ) => $( resolve ) );

	$( document.body ).trigger( 'country_to_state_changing', [
		'US',
		$( 'form.checkout' ),
	] );

	return $;
}

describe( 'address-i18n', () => {
	test( 'a field the locale hides with true is not marked required', async () => {
		const $ = await loadCheckout( { hidden: true }, [ '../address-i18n' ] );

		const $postcodeRow = $( '#billing_postcode_field' );
		expect( $postcodeRow.css( 'display' ) ).toBe( 'none' );
		expect( $postcodeRow.hasClass( 'validate-required' ) ).toBe( false );
		expect( $( '#billing_city_field' ).hasClass( 'validate-required' ) ).toBe(
			true
		);
	} );

	test.each( [ 'yes', 1 ] )(
		'a field whose hidden flag is %p stays required, like the server treats it',
		async ( hidden ) => {
			const $ = await loadCheckout( { hidden }, [ '../address-i18n' ] );

			expect(
				$( '#billing_postcode_field' ).hasClass( 'validate-required' )
			).toBe( true );
		}
	);
} );

describe( 'classic checkout with a locale-hidden required field', () => {
	test( 'changing the state refreshes the order review', async () => {
		const $ = await loadCheckout( { hidden: true }, [
			'../address-i18n',
			'../checkout',
		] );
		const updateCheckout = jest.fn();
		$( document.body ).on( 'update_checkout', updateCheckout );

		$( '#billing_state' ).val( 'NY' ).trigger( 'change' );

		expect( updateCheckout ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'an empty visible required field still holds the refresh', async () => {
		const $ = await loadCheckout( {}, [ '../address-i18n', '../checkout' ] );
		const updateCheckout = jest.fn();
		$( document.body ).on( 'update_checkout', updateCheckout );

		$( '#billing_state' ).val( 'NY' ).trigger( 'change' );

		expect( updateCheckout ).not.toHaveBeenCalled();
	} );
} );
