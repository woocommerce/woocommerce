/**
 * External dependencies
 */
import { render, screen, waitFor, act } from '@testing-library/react';
import { previewCart } from '@woocommerce/resource-previews';
import { dispatch } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { default as fetchMock } from 'jest-fetch-mock';

/**
 * Internal dependencies
 */
import Fields from '../inner-blocks/checkout-fields-block/frontend';
import ExpressPayment from '../inner-blocks/checkout-express-payment-block/block';
import ContactInformation from '../inner-blocks/checkout-contact-information-block/frontend';
import ShippingMethod from '../inner-blocks/checkout-shipping-method-block/frontend';
import PickupOptions from '../inner-blocks/checkout-pickup-options-block/frontend';
import ShippingAddress from '../inner-blocks/checkout-shipping-address-block/frontend';
import BillingAddress from '../inner-blocks/checkout-billing-address-block/frontend';
import ShippingMethods from '../inner-blocks/checkout-shipping-methods-block/frontend';
import Payment from '../inner-blocks/checkout-payment-block/frontend';
import AdditionalInformation from '../inner-blocks/checkout-additional-information-block/frontend';
import OrderNote from '../inner-blocks/checkout-order-note-block/block';
import Terms from '../inner-blocks/checkout-terms-block/frontend';
import { termsCheckboxDefaultText } from '../inner-blocks/checkout-terms-block/constants';
import Actions from '../inner-blocks/checkout-actions-block/frontend';
import Totals from '../inner-blocks/checkout-totals-block/frontend';
import OrderSummary from '../inner-blocks/checkout-order-summary-block/frontend';
import CartItems from '../inner-blocks/checkout-order-summary-cart-items/frontend';
import CouponForm from '../inner-blocks/checkout-order-summary-coupon-form/block';
import Subtotal from '../inner-blocks/checkout-order-summary-subtotal/frontend';
import Fee from '../inner-blocks/checkout-order-summary-fee/frontend';
import Discount from '../inner-blocks/checkout-order-summary-discount/frontend';
import Shipping from '../inner-blocks/checkout-order-summary-shipping/frontend';
import Taxes from '../inner-blocks/checkout-order-summary-taxes/frontend';

import { defaultCartState } from '../../../data/cart/default-state';

import Checkout from '../block';

jest.mock( '@wordpress/compose', () => ( {
	...jest.requireActual( '@wordpress/compose' ),
	useResizeObserver: jest.fn().mockReturnValue( [ null, { width: 0 } ] ),
} ) );

jest.mock( '@wordpress/element', () => {
	return {
		...jest.requireActual( '@wordpress/element' ),
		useId: () => {
			return 'mock-id';
		},
	};
} );

const CheckoutBlock = () => {
	return (
		<Checkout attributes={ {} }>
			<Fields>
				<ShippingAddress />
				<ExpressPayment />
				<ContactInformation />
				<BillingAddress />
				<ShippingMethod />
				<PickupOptions />
				<ShippingMethods />
				<Payment />
				<AdditionalInformation />
				<OrderNote />
				<Terms checkbox={ true } text={ termsCheckboxDefaultText } />
				<Actions />
			</Fields>
			<Totals>
				<OrderSummary>
					<CartItems />
					<CouponForm />
					<Subtotal />
					<Fee />
					<Discount />
					<Shipping />
					<Taxes />
				</OrderSummary>
			</Totals>
		</Checkout>
	);
};

describe( 'Testing cart', () => {
	beforeEach( () => {
		act( () => {
			fetchMock.mockResponse( ( req ) => {
				if ( req.url.match( /wc\/store\/v1\/cart/ ) ) {
					return Promise.resolve( JSON.stringify( previewCart ) );
				}
				return Promise.resolve( '' );
			} );
			// need to clear the store resolution state between tests.
			dispatch( storeKey ).invalidateResolutionForStore();
			dispatch( storeKey ).receiveCart( defaultCartState.cartData );
		} );
	} );

	afterEach( () => {
		fetchMock.resetMocks();
	} );

	it( 'renders checkout if there are items in the cart', async () => {
		render( <CheckoutBlock /> );
		await waitFor( () => expect( fetchMock ).toHaveBeenCalled() );

		expect( screen.getByText( /Place Order/i ) ).toBeInTheDocument();

		expect( fetchMock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'Renders the address card if the address is filled', async () => {
		act( () => {
			const cartWithAddress = {
				...previewCart,
				shipping_address: {
					first_name: 'First Name',
					last_name: 'Last Name',
					company: '',
					address_1: 'Address 1',
					address_2: '',
					city: 'Toronto',
					state: 'ON',
					postcode: 'M4W 1A6',
					country: 'CA',
					phone: '',
				},
				billing_address: {
					first_name: 'First Name',
					last_name: 'Last Name',
					company: '',
					address_1: 'Address 1',
					address_2: '',
					city: 'Toronto',
					state: 'ON',
					postcode: 'M4W 1A6',
					country: 'CA',
					phone: '',
					email: '',
				},
			};
			fetchMock.mockResponse( ( req ) => {
				if ( req.url.match( /wc\/store\/v1\/cart/ ) ) {
					return Promise.resolve( JSON.stringify( cartWithAddress ) );
				}
				return Promise.resolve( '' );
			} );
		} );
		render( <CheckoutBlock /> );
		await waitFor( () => expect( fetchMock ).toHaveBeenCalled() );

		expect(
			screen.getByRole( 'button', { name: 'Edit address' } )
		).toBeInTheDocument();

		expect(
			screen.getByText( 'Ontario', {
				selector: '.wc-block-components-address-card span',
			} )
		).toBeInTheDocument();

		expect( fetchMock ).toHaveBeenCalledTimes( 1 );
	} );
} );
