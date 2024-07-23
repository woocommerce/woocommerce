/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';
import {
	registerExpressPaymentMethod,
	__experimentalDeRegisterExpressPaymentMethod,
} from '@woocommerce/blocks-registry';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import ExpressPaymentMethods from '../express-payment-methods';

jest.mock( '@woocommerce/base-context', () => ( {
	useEditorContext: jest.fn( () => ( {
		isEditor: false,
	} ) ),
} ) );

const expressPaymentMethodNames = [ 'paypal', 'google pay', 'apple pay' ];

const registerMockExpressPaymentMethods = () => {
	expressPaymentMethodNames.forEach( ( name ) => {
		registerExpressPaymentMethod( {
			name,
			paymentMethodId: name,
			content: <div className="boo">{ `${ name } button` }</div>,
			edit: <div>{ `${ name } preview` }</div>,
			canMakePayment: () => true,
			supports: {
				features: [ 'products' ],
			},
		} );
	} );
	dispatch( PAYMENT_STORE_KEY ).__internalUpdateAvailablePaymentMethods();
};

const deregisterMockExpressPaymentMethods = () => {
	expressPaymentMethodNames.forEach( ( name ) => {
		__experimentalDeRegisterExpressPaymentMethod( name );
	} );
};

describe( 'Express payment methods', () => {
	afterAll( () => {
		// editorContextMock.resetAllMocks();
	} );
	describe( 'No payment methods available', () => {
		it( 'should display no registered payment methods', () => {
			render( <ExpressPaymentMethods /> );

			const noPaymentMethods = screen.queryAllByText(
				/No registered Payment Methods/
			);
			expect( noPaymentMethods.length ).toEqual( 1 );
		} );
	} );

	// At the moment using `cloneElement` with non-native props throws a warning. There are
	// a lot of these and would be a lot of bloat to expect every console warning. Need find
	// a way around this before enabling this test.
	describe.skip( 'Payment methods available', () => {
		beforeAll( () => {
			registerMockExpressPaymentMethods();
		} );
		afterAll( () => {
			deregisterMockExpressPaymentMethods();
		} );

		it( 'should pass the correct properties to the rendered element', () => {} );
		describe( 'In an editor context', () => {
			it( 'should display the element provided by paymentMethods.edit', () => {} );
		} );
		describe( 'In a frontend context', () => {
			it( 'should display the element provided by paymentMethods.content', () => {} );
		} );
	} );
} );
