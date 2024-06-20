/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { CART_STORE_KEY, CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { dispatch } from '@wordpress/data';
import { previewCart } from '@woocommerce/resource-previews';
import PickupLocation from '@woocommerce/base-components/cart-checkout/pickup-location';

jest.mock( '@woocommerce/settings', () => {
	const originalModule = jest.requireActual( '@woocommerce/settings' );

	return {
		...originalModule,
		getSetting: ( setting: string, ...rest: unknown[] ) => {
			if ( setting === 'localPickupEnabled' ) {
				return true;
			}
			if ( setting === 'collectableMethodIds' ) {
				return [ 'pickup_location' ];
			}
			return originalModule.getSetting( setting, ...rest );
		},
	};
} );

describe( 'PickupLocation', () => {
	const setShippingCalculatorLabel = jest.fn();
	const setShippingCalculatorAddress = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		dispatch( CHECKOUT_STORE_KEY ).setPrefersCollection( true );

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

		dispatch( CART_STORE_KEY ).receiveCart( previewCart );
	} );

	it( 'sets the pickup address when one is set in the methods metadata', () => {
		render(
			<PickupLocation
				setShippingCalculatorLabel={ setShippingCalculatorLabel }
				setShippingCalculatorAddress={ setShippingCalculatorAddress }
			/>
		);

		expect( setShippingCalculatorLabel ).toHaveBeenCalledWith(
			'Collection from'
		);
		expect( setShippingCalculatorAddress ).toHaveBeenCalledWith(
			'123 Easy Street, New York, 12345'
		);
	} );

	it( 'does not set the pickup address when one is not set in the methods metadata', () => {
		const pickupRateIndex =
			previewCart.shipping_rates[ 0 ].shipping_rates.findIndex(
				( rate ) => rate.method_id === 'pickup_location'
			);
		const addressKeyIndex = previewCart.shipping_rates[ 0 ].shipping_rates[
			pickupRateIndex
		].meta_data.findIndex(
			( metaData ) => metaData.key === 'pickup_address'
		);
		previewCart.shipping_rates[ 0 ].shipping_rates[
			pickupRateIndex
		].meta_data[ addressKeyIndex ].value = '';

		dispatch( CART_STORE_KEY ).receiveCart( previewCart );

		render(
			<PickupLocation
				setShippingCalculatorLabel={ setShippingCalculatorLabel }
				setShippingCalculatorAddress={ setShippingCalculatorAddress }
			/>
		);

		expect( setShippingCalculatorLabel ).not.toHaveBeenCalled();
		expect( setShippingCalculatorAddress ).not.toHaveBeenCalled();
	} );
} );
