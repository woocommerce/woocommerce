/**
 * External dependencies
 */
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { Cart, SelectShippingRateType, isObject } from '@woocommerce/types';
import { useEffect, useRef } from '@wordpress/element';
import { deriveSelectedShippingRates } from '@woocommerce/base-utils';
import isShallowEqual from '@wordpress/is-shallow-equal';
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
	/**
	 * The following values are used to determine if pickup methods are shown separately from shipping methods, or if
	 * those options should be hidden.
	 */
	isCollectable: boolean; // Only true when ALL packages support local pickup. If true, we can show the collection/delivery toggle
}

export const useShippingData = (): ShippingData => {
	const {
		shippingRates,
		needsShipping,
		hasCalculatedShipping,
		isLoadingRates,
		isCollectable,
	} = useSelect( ( select ) => {
		const isEditor = !! select( 'core/editor' );
		const store = select( storeKey );
		const rates = isEditor
			? previewCart.shipping_rates
			: store.getShippingRates();
		return {
			shippingRates: rates,
			needsShipping: isEditor
				? previewCart.needs_shipping
				: store.getNeedsShipping(),
			hasCalculatedShipping: isEditor
				? previewCart.has_calculated_shipping
				: store.getHasCalculatedShipping(),
			isLoadingRates: isEditor ? false : store.isCustomerDataUpdating(),
			isCollectable: rates.every(
				( { shipping_rates: packageShippingRates } ) =>
					packageShippingRates.find(
						( { method_id: methodId } ) =>
							methodId === 'pickup_location'
					)
			),
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
		isCollectable,
	};
};
