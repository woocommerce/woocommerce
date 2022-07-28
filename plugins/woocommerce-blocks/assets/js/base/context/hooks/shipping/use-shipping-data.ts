/**
 * External dependencies
 */
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { Cart } from '@woocommerce/type-defs/cart';
import { SelectShippingRateType } from '@woocommerce/type-defs/shipping';
import { useEffect, useRef } from '@wordpress/element';
import { deriveSelectedShippingRates } from '@woocommerce/base-utils';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { isObject } from '@woocommerce/types';
import { previewCart } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import { useSelectShippingRate } from './use-select-shipping-rate';

interface ShippingData extends SelectShippingRateType {
	needsShipping: Cart[ 'needsShipping' ];
	hasCalculatedShipping: Cart[ 'hasCalculatedShipping' ];
	shippingRates: Cart[ 'shippingRates' ];
	isLoadingRates: boolean;
	selectedRates: Record< string, string | unknown >;
}

export const useShippingData = (): ShippingData => {
	const {
		shippingRates,
		needsShipping,
		hasCalculatedShipping,
		isLoadingRates,
	} = useSelect( ( select ) => {
		const isEditor = !! select( 'core/editor' );
		const store = select( storeKey );
		return {
			shippingRates: isEditor
				? previewCart.shipping_rates
				: store.getShippingRates(),
			needsShipping: isEditor
				? previewCart.needs_shipping
				: store.getNeedsShipping(),
			hasCalculatedShipping: isEditor
				? previewCart.has_calculated_shipping
				: store.getHasCalculatedShipping(),
			isLoadingRates: isEditor ? false : store.isCustomerDataUpdating(),
		};
	} );
	const { isSelectingRate, selectShippingRate } = useSelectShippingRate();

	// set selected rates on ref so it's always current.
	const selectedRates = useRef< Record< string, unknown > >( {} );
	useEffect( () => {
		const derivedSelectedRates =
			deriveSelectedShippingRates( shippingRates );
		if (
			isObject( derivedSelectedRates ) &&
			! isShallowEqual( selectedRates.current, derivedSelectedRates )
		) {
			selectedRates.current = derivedSelectedRates;
		}
	}, [ shippingRates ] );

	return {
		isSelectingRate,
		selectedRates: selectedRates.current,
		selectShippingRate,
		shippingRates,
		needsShipping,
		hasCalculatedShipping,
		isLoadingRates,
	};
};
