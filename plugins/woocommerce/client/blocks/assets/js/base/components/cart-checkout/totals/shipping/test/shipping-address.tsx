/**
 * External dependencies
 */
import { render, screen, within } from '@testing-library/react';
import ShippingAddress from '@woocommerce/base-components/cart-checkout/totals/shipping/shipping-address';
import { cartStore, checkoutStore } from '@woocommerce/block-data';
import { ShippingCalculatorContext } from '@woocommerce/base-components/cart-checkout';
import { dispatch } from '@wordpress/data';
import { previewCart } from '@woocommerce/resource-previews';
import * as baseContextHooks from '@woocommerce/base-context/hooks';

jest.mock( '@woocommerce/settings', () => {
	const originalModule = jest.requireActual( '@woocommerce/settings' );

	return {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore We know @woocommerce/settings is an object.
		...originalModule,
		getSetting: ( setting: string, ...rest: unknown[] ) => {
			if ( setting === 'localPickupEnabled' ) {
				return true;
			}
			if ( setting === 'collectableMethodIds' ) {
				return [ 'pickup_location' ];
			}
			if ( setting === 'localPickupLocations' ) {
				return {
					'1': {
						enabled: true,
						name: 'Local pickup #1',
						formatted_address: '123 Easy Street',
						details: 'Details for Local pickup #1',
					},
					'2': {
						enabled: true,
						name: 'Local pickup #2',
						formatted_address: '456 Main St',
						details: 'Details for Local pickup #2',
					},
				};
			}
			return originalModule.getSetting( setting, ...rest );
		},
	};
} );

jest.mock( '@woocommerce/base-context/hooks', () => {
	return {
		__esModule: true,
		...jest.requireActual( '@woocommerce/base-context/hooks' ),
		useStoreCart: jest.fn(),
	};
} );

const shippingAddress = {
	first_name: 'John',
	last_name: 'Doe',
	company: 'Automattic',
	address_1: '123 Main St',
	address_2: '',
	city: 'San Francisco',
	state: 'CA',
	postcode: '94107',
	country: 'US',
	phone: '555-555-5555',
};

baseContextHooks.useStoreCart.mockReturnValue( {
	cartItems: previewCart.items,
	cartTotals: previewCart.totals,
	cartCoupons: previewCart.coupons,
	cartFees: previewCart.fees,
	cartNeedsShipping: previewCart.needs_shipping,
	shippingRates: previewCart.shipping_rates,
	shippingAddress,
	billingAddress: previewCart.billing_address,
	cartHasCalculatedShipping: previewCart.has_calculated_shipping,
	isLoadingRates: false,
} );

describe( 'ShippingAddress', () => {
	it( 'Renders shipping address if user does not prefer collection', () => {
		render(
			<ShippingCalculatorContext.Provider
				value={ {
					showCalculator: true,
					isShippingCalculatorOpen: false,
					setIsShippingCalculatorOpen: jest.fn(),
					shippingCalculatorID: 'shipping-calculator-form-wrapper',
				} }
			>
				<ShippingAddress />
			</ShippingCalculatorContext.Provider>
		);

		const panel = screen.getByRole( 'button' );
		const paragraph = within( panel ).getByRole( 'paragraph' );

		expect(
			within( paragraph ).getByText( ( _, element ) => {
				const text = element?.textContent || '';
				return /Delivers to 94107/.test( text );
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByText( /Collection from/ )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'Enter address to check delivery options' )
		).not.toBeInTheDocument();
	} );

	it( 'Renders pickup location if shopper prefers collection', async () => {
		dispatch( checkoutStore ).setPrefersCollection( true );

		// Deselect the default selected rate and select pickup_location:1 rate.
		const currentlySelectedIndex =
			previewCart.shipping_rates[ 0 ].shipping_rates.findIndex(
				( rate ) => rate.selected
			);
		previewCart.shipping_rates[ 0 ].shipping_rates[
			currentlySelectedIndex
		].selected = false;
		const pickupRateIndex =
			previewCart.shipping_rates[ 0 ].shipping_rates.findIndex(
				( rate ) => rate.method_id === 'pickup_location'
			);
		previewCart.shipping_rates[ 0 ].shipping_rates[
			pickupRateIndex
		].selected = true;

		dispatch( cartStore ).receiveCart( previewCart );

		render(
			<ShippingCalculatorContext.Provider
				value={ {
					showCalculator: true,
					isShippingCalculatorOpen: false,
					setIsShippingCalculatorOpen: jest.fn(),
					shippingCalculatorID: 'shipping-calculator-form-wrapper',
				} }
			>
				<ShippingAddress />
			</ShippingCalculatorContext.Provider>
		);

		const panel = screen.getByRole( 'button' );
		const paragraph = within( panel ).getByRole( 'paragraph' );

		expect(
			within( paragraph ).getByText( ( _, element ) => {
				const text = element?.textContent || '';
				return /Collection from 123 Easy Street/.test( text );
			} )
		).toBeInTheDocument();
	} );

	it( `renders an address if one is set in the methods metadata`, async () => {
		dispatch( checkoutStore ).setPrefersCollection( true );

		// Deselect the default selected rate and select pickup_location:1 rate.
		const currentlySelectedIndex =
			previewCart.shipping_rates[ 0 ].shipping_rates.findIndex(
				( rate ) => rate.selected
			);
		previewCart.shipping_rates[ 0 ].shipping_rates[
			currentlySelectedIndex
		].selected = false;
		const pickupRateIndex =
			previewCart.shipping_rates[ 0 ].shipping_rates.findIndex(
				( rate ) => rate.method_id === 'pickup_location'
			);
		previewCart.shipping_rates[ 0 ].shipping_rates[
			pickupRateIndex
		].selected = true;

		dispatch( cartStore ).receiveCart( previewCart );

		render(
			<ShippingCalculatorContext.Provider
				value={ {
					showCalculator: true,
					isShippingCalculatorOpen: false,
					setIsShippingCalculatorOpen: jest.fn(),
					shippingCalculatorID: 'shipping-calculator-form-wrapper',
				} }
			>
				<ShippingAddress />
			</ShippingCalculatorContext.Provider>
		);

		const panel = screen.getByRole( 'button' );
		const paragraph = within( panel ).getByRole( 'paragraph' );

		expect(
			within( paragraph ).getByText( ( _, element ) => {
				const text = element?.textContent || '';
				return /Collection from 123 Easy Street/.test( text );
			} )
		).toBeInTheDocument();
	} );
	it( 'renders no address if one is not set in the methods metadata', async () => {
		dispatch( checkoutStore ).setPrefersCollection( true );

		// Deselect the default selected rate and select pickup_location:1 rate.
		const currentlySelectedIndex =
			previewCart.shipping_rates[ 0 ].shipping_rates.findIndex(
				( rate ) => rate.selected
			);
		previewCart.shipping_rates[ 0 ].shipping_rates[
			currentlySelectedIndex
		].selected = false;
		const pickupRateIndex =
			previewCart.shipping_rates[ 0 ].shipping_rates.findIndex(
				( rate ) => rate.rate_id === 'pickup_location:2'
			);
		previewCart.shipping_rates[ 0 ].shipping_rates[
			pickupRateIndex
		].selected = true;

		// Set the pickup_location metadata value to an empty string in the selected pickup rate.
		const addressKeyIndex = previewCart.shipping_rates[ 0 ].shipping_rates[
			pickupRateIndex
		].meta_data.findIndex(
			( metaData ) => metaData.key === 'pickup_address'
		);
		previewCart.shipping_rates[ 0 ].shipping_rates[
			pickupRateIndex
		].meta_data[ addressKeyIndex ].value = '';

		dispatch( cartStore ).receiveCart( previewCart );

		render( <ShippingAddress /> );
		expect(
			screen.queryByText( /Collection from / )
		).not.toBeInTheDocument();
	} );
} );
