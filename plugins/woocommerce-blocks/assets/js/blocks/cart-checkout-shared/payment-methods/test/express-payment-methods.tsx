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
import mockEditorContext from './__mocks__/editor-context';
import { getExpectedExpressPaymentProps } from './__mocks__/express-payment-props';
import ExpressPaymentMethods from '../express-payment-methods';
jest.mock( '@woocommerce/base-context', () => ( {
	useEditorContext: mockEditorContext,
} ) );

// Button styles are disabled by default. We need to mock the express payment context
// to enable them.
jest.mock( '../express-payment/express-payment-context', () => {
	return {
		useExpressPaymentContext: jest.fn().mockReturnValue( {
			showButtonStyles: true,
			buttonHeight: '48',
			buttonBorderRadius: '4',
		} ),
	};
} );

const mockExpressPaymentMethodNames = [ 'paypal', 'google pay', 'apple pay' ];

const MockExpressButton = jest.fn( ( { name } ) => (
	<div className="boo">{ `${ name } button` }</div>
) );

const MockEditorExpressButton = jest.fn( ( { name } ) => (
	<div>{ `${ name } preview` }</div>
) );

const registerMockExpressPaymentMethods = () => {
	mockExpressPaymentMethodNames.forEach( ( name ) => {
		registerExpressPaymentMethod( {
			name,
			title: `${ name } payment method`,
			description: `A test ${ name } payment method`,
			gatewayId: 'test-express-payment-method',
			paymentMethodId: name,
			content: <MockExpressButton name={ name } />,
			edit: <MockEditorExpressButton name={ name } />,
			canMakePayment: () => true,
			supports: {
				features: [ 'products' ],
			},
		} );
	} );
	dispatch( PAYMENT_STORE_KEY ).__internalUpdateAvailablePaymentMethods();
};

const deregisterMockExpressPaymentMethods = () => {
	mockExpressPaymentMethodNames.forEach( ( name ) => {
		__experimentalDeRegisterExpressPaymentMethod( name );
	} );
};

describe( 'Express payment methods', () => {
	afterAll( () => {
		jest.restoreAllMocks();
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

	describe( 'Payment methods available', () => {
		beforeAll( () => {
			registerMockExpressPaymentMethods();
		} );
		afterAll( () => {
			deregisterMockExpressPaymentMethods();
		} );
		describe( 'In a frontend context', () => {
			it( 'should display the element provided by paymentMethods.content', () => {
				render( <ExpressPaymentMethods /> );
				mockExpressPaymentMethodNames.forEach( ( name ) => {
					const btn = screen.getByText( `${ name } button` );
					expect( btn ).toBeVisible();
				} );
			} );
			it( 'should pass the correct properties to the rendered element', () => {
				render( <ExpressPaymentMethods /> );
				mockExpressPaymentMethodNames.forEach( ( name ) => {
					expect( MockExpressButton ).toHaveBeenCalledWith(
						getExpectedExpressPaymentProps( name ),
						{}
					);
				} );
				// This is a bit out of place, but the console warning is triggered when the
				// usePaymentMethodInterface hook is called so we need to expect it here otherwise
				// the test fails on unexpected console warnings.
				expect( console ).toHaveWarnedWith(
					'isPristine is deprecated since version 9.6.0. Please use isIdle instead. See: https://github.com/woocommerce/woocommerce-blocks/pull/8110'
				);
			} );
		} );
		describe( 'In an editor context', () => {
			beforeEach( () => {
				mockEditorContext.mockImplementation( () => ( {
					isEditor: true,
				} ) );
			} );
			it( 'should display the element provided by paymentMethods.edit', () => {
				render( <ExpressPaymentMethods /> );
				mockExpressPaymentMethodNames.forEach( ( name ) => {
					const btn = screen.getByText( `${ name } preview` );
					expect( btn ).toBeVisible();
				} );
			} );
			it( 'should pass the correct properties to the rendered element', () => {
				render( <ExpressPaymentMethods /> );
				mockExpressPaymentMethodNames.forEach( ( name ) => {
					expect( MockEditorExpressButton ).toHaveBeenCalledWith(
						getExpectedExpressPaymentProps( name ),
						{}
					);
				} );
			} );
		} );
	} );
} );
