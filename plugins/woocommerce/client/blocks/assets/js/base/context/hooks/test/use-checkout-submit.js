/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { createRegistry, RegistryProvider } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useCheckoutSubmit } from '../use-checkout-submit';
import {
	CHECKOUT_STORE_KEY,
	config as checkoutStoreConfig,
} from '../../../../data/checkout';
import {
	PAYMENT_STORE_KEY,
	config as paymentDataStoreConfig,
} from '../../../../data/payment';

jest.mock( '../../providers/cart-checkout/checkout-events', () => {
	const original = jest.requireActual(
		'../../providers/cart-checkout/checkout-events'
	);
	return {
		...original,
		useCheckoutEventsContext: () => {
			return { onSubmit: jest.fn() };
		},
	};
} );

describe( 'useCheckoutSubmit', () => {
	let registry;

	const wrapper = ( { children } ) => (
		<RegistryProvider value={ registry }>{ children }</RegistryProvider>
	);

	beforeEach( () => {
		registry = createRegistry( {
			[ CHECKOUT_STORE_KEY ]: checkoutStoreConfig,
			[ PAYMENT_STORE_KEY ]: paymentDataStoreConfig,
		} );
	} );

	it( 'onSubmit calls the correct action in the checkout events context', () => {
		const { result } = renderHook( () => useCheckoutSubmit(), {
			wrapper,
		} );

		const { onSubmit } = result.current;

		onSubmit();

		expect( onSubmit ).toHaveBeenCalledTimes( 1 );
	} );
} );
