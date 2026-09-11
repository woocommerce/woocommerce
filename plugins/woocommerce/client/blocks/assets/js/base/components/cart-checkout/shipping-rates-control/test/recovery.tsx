/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { cartStore } from '@woocommerce/block-data';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';
import type { CartShippingPackageShippingRate } from '@woocommerce/types';
import { useShippingData } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import ShippingRatesControl from '..';
import {
	generateShippingPackage,
	generateShippingRate,
} from '../../../../../mocks/shipping-package';

jest.mock( '@wordpress/api-fetch', () => ( {
	__esModule: true,
	default: Object.assign( jest.fn(), {
		use: jest.fn(),
		setNonce: jest.fn(),
		setCartHash: jest.fn(),
	} ),
} ) );

const ShippingOptions = () => {
	const { shippingRates } = useShippingData();
	return (
		<ShippingRatesControl
			shippingRates={ shippingRates.map( ( shippingPackage ) => ( {
				...shippingPackage,
				shipping_rates: shippingPackage.shipping_rates.filter(
					( rate ) => rate.method_id !== 'pickup_location'
				),
			} ) ) }
			isLoadingRates={ false }
			context="woocommerce/checkout"
		/>
	);
};

const createPackage = ( selectedRate: string ) =>
	generateShippingPackage( {
		packageId: 0,
		shippingRates: [
			generateShippingRate( {
				rateId: 'flat_rate:1',
				name: 'Flat rate',
				price: '1250',
				instanceID: 1,
				selected: selectedRate === 'flat_rate:1',
			} ),
			generateShippingRate( {
				rateId: 'pickup_location:0',
				name: 'Pickup location',
				price: '0',
				instanceID: 0,
				selected: selectedRate === 'pickup_location:0',
			} ),
		],
	} );

test( 'failed initialization stays retryable without automatically repeating rejected requests', async () => {
	jest.useFakeTimers();
	const user = userEvent.setup( { advanceTimers: jest.advanceTimersByTime } );
	const registry = createRegistry();
	registry.register( cartStore );
	const dispatch = registry.dispatch( cartStore );
	const select = registry.select( cartStore );
	dispatch.finishResolution( 'getCartData' );
	dispatch.setCartData( {
		shippingRates: [ createPackage( 'pickup_location:0' ) ],
	} );
	jest.mocked( apiFetch ).mockResolvedValue( {
		responses: [
			{
				status: 400,
				headers: {},
				body: {
					code: 'woocommerce_rest_cart_shipping_rate_invalid',
					message: 'Shipping rate rejected.',
					data: { status: 400 },
				},
			},
		],
	} );

	const { unmount } = render(
		<RegistryProvider value={ registry }>
			<SlotFillProvider>
				<ShippingOptions />
			</SlotFillProvider>
		</RegistryProvider>
	);
	try {
		await act( async () => {
			await jest.runOnlyPendingTimersAsync();
		} );
		const flatRate = screen.getByRole( 'radio', {
			name: 'Flat rate $12.50',
		} );
		expect( flatRate ).not.toBeChecked();
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );

		// Give a render-driven retry loop enough time to reveal itself.
		await act( async () => {
			await jest.advanceTimersByTimeAsync( 3000 );
		} );
		expect( apiFetch ).toHaveBeenCalledTimes( 1 );

		// The click starts a store update that only settles once the fake timers
		// run, so both have to happen inside the same act boundary.
		// eslint-disable-next-line testing-library/no-unnecessary-act
		await act( async () => {
			await user.click( flatRate );
			await jest.runOnlyPendingTimersAsync();
		} );
		expect( flatRate ).not.toBeChecked();
		expect( apiFetch ).toHaveBeenCalledTimes( 2 );

		jest.mocked( apiFetch ).mockResolvedValueOnce( {
			responses: [
				{
					status: 200,
					headers: {},
					body: {
						shipping_rates: [ createPackage( 'flat_rate:1' ) ],
					},
				},
			],
		} );
		// The click starts a store update that only settles once the fake timers
		// run, so both have to happen inside the same act boundary.
		// eslint-disable-next-line testing-library/no-unnecessary-act
		await act( async () => {
			await user.click( flatRate );
			await jest.runOnlyPendingTimersAsync();
		} );
		expect( flatRate ).toBeChecked();
		expect(
			select
				.getShippingRates()[ 0 ]
				.shipping_rates.find(
					( rate: CartShippingPackageShippingRate ) => rate.selected
				)?.rate_id
		).toBe( 'flat_rate:1' );
		expect( apiFetch ).toHaveBeenCalledTimes( 3 );
	} finally {
		unmount();
		jest.useRealTimers();
	}
} );
