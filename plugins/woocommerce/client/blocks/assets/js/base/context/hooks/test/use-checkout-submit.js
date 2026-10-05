import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { createRegistry, RegistryProvider } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	CHECKOUT_STORE_KEY,
	config as checkoutStoreConfig,
} from '@woocommerce/block-data/checkout';
import {
	PAYMENT_STORE_KEY,
	config as paymentDataStoreConfig,
} from '@woocommerce/block-data/payment';
import { useCheckoutSubmit } from '../use-checkout-submit';

vi.mock( '../../providers/cart-checkout/checkout-events', async () => {
	const original = await vi.importActual(
		'../../providers/cart-checkout/checkout-events'
	);
	return ( ( mock ) => ( { default: mock, ...mock } ) )( {
		...original,
		useCheckoutEventsContext: () => {
			return { onSubmit: vi.fn() };
		},
	} );
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
