/**
 * Internal dependencies
 */
import { canMakePaymentForShippingMethods } from '../shipping-method-restrictions';

describe( 'canMakePaymentForShippingMethods', () => {
	const cartWithMethod = ( ...methods: string[] ) => ( {
		cartNeedsShipping: true,
		selectedShippingMethods: Object.fromEntries(
			methods.map( ( method, index ) => [ String( index ), method ] )
		),
	} );

	it( 'is available for any shipping method when no restriction is set', () => {
		const canMakePayment = canMakePaymentForShippingMethods( {
			enableForVirtual: false,
			enableForShippingMethods: [],
		} );

		expect( canMakePayment( cartWithMethod( 'flat_rate:1' ) ) ).toBe(
			true
		);
	} );

	it( 'treats missing settings as no restriction', () => {
		const canMakePayment = canMakePaymentForShippingMethods( {} );

		expect( canMakePayment( cartWithMethod( 'flat_rate:1' ) ) ).toBe(
			true
		);
	} );

	it( 'is only available when a selected shipping method matches', () => {
		const canMakePayment = canMakePaymentForShippingMethods( {
			enableForVirtual: true,
			enableForShippingMethods: [ 'flat_rate:1' ],
		} );

		expect( canMakePayment( cartWithMethod( 'flat_rate:1' ) ) ).toBe(
			true
		);
		expect( canMakePayment( cartWithMethod( 'flat_rate:2' ) ) ).toBe(
			false
		);
		expect( canMakePayment( cartWithMethod( 'free_shipping:1' ) ) ).toBe(
			false
		);
	} );

	it( 'matches any instance of a shipping method when restricted by method id', () => {
		const canMakePayment = canMakePaymentForShippingMethods( {
			enableForVirtual: true,
			enableForShippingMethods: [ 'flat_rate' ],
		} );

		expect( canMakePayment( cartWithMethod( 'flat_rate:7' ) ) ).toBe(
			true
		);
		expect( canMakePayment( cartWithMethod( 'local_pickup:1' ) ) ).toBe(
			false
		);
	} );

	it( 'stays available until a shipping method has been selected', () => {
		const canMakePayment = canMakePaymentForShippingMethods( {
			enableForVirtual: false,
			enableForShippingMethods: [ 'flat_rate:1' ],
		} );

		expect( canMakePayment( cartWithMethod() ) ).toBe( true );
	} );

	it( 'honors the virtual orders setting when the cart needs no shipping', () => {
		const virtualCart = {
			cartNeedsShipping: false,
			selectedShippingMethods: {},
		};

		expect(
			canMakePaymentForShippingMethods( {
				enableForVirtual: true,
				enableForShippingMethods: [ 'flat_rate:1' ],
			} )( virtualCart )
		).toBe( true );
		// Without shipping there is nothing to match against, so the
		// server-side check decides; the client stays permissive.
		expect(
			canMakePaymentForShippingMethods( {
				enableForVirtual: false,
				enableForShippingMethods: [ 'flat_rate:1' ],
			} )( virtualCart )
		).toBe( true );
	} );
} );
