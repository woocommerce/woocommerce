/**
 * External dependencies
 */
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useSelect, useDispatch } from '@wordpress/data';
import { isObject } from '@woocommerce/types';
import { useEffect, useRef, useCallback } from '@wordpress/element';
import { deriveSelectedShippingRates } from '@woocommerce/base-utils';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { previewCart } from '@woocommerce/resource-previews';
import { useThrowError } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import { useStoreEvents } from '../use-store-events';
import type { ShippingData } from './types';

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

	// See if rates are being selected.
	const isSelectingRate = useSelect< boolean >( ( select ) => {
		return select( storeKey ).isShippingRateBeingSelected();
	}, [] );

	// set selected rates on ref so it's always current.
	const selectedRates = useRef< Record< string, string > >( {} );
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

	const { selectShippingRate: dispatchSelectShippingRate } = useDispatch(
		storeKey
	) as {
		selectShippingRate: unknown;
	} as {
		selectShippingRate: (
			newShippingRateId: string,
			packageId?: string | number
		) => Promise< unknown >;
	};

	// Selects a shipping rate, fires an event, and catch any errors.
	const throwError = useThrowError();
	const { dispatchCheckoutEvent } = useStoreEvents();
	const selectShippingRate = useCallback(
		(
			newShippingRateId: string,
			packageId?: string | number | undefined
		): void => {
			let selectPromise;

			/**
			 * Picking location handling
			 *
			 * Forces pickup location to be selected for all packages since we don't allow a mix of shipping and pickup.
			 */
			const hasSelectedLocalPickup = !! Object.values(
				selectedRates.current
			).find( ( rate ) => rate.includes( 'pickup_location:' ) );

			if (
				newShippingRateId.includes( 'pickup_location:' ) ||
				hasSelectedLocalPickup
			) {
				selectPromise = dispatchSelectShippingRate( newShippingRateId );
			} else {
				selectPromise = dispatchSelectShippingRate(
					newShippingRateId,
					packageId
				);
			}
			selectPromise
				.then( () => {
					dispatchCheckoutEvent( 'set-selected-shipping-rate', {
						shippingRateId: newShippingRateId,
					} );
				} )
				.catch( ( error ) => {
					// Throw an error because an error when selecting a rate is problematic.
					throwError( error );
				} );
		},
		[
			dispatchSelectShippingRate,
			dispatchCheckoutEvent,
			throwError,
			selectedRates,
		]
	);

	return {
		isSelectingRate,
		selectedRates: selectedRates.current,
		selectShippingRate,
		shippingRates,
		needsShipping,
		hasCalculatedShipping,
		isLoadingRates,
		isCollectable,
		hasSelectedLocalPickup: !! Object.values( selectedRates.current ).find(
			( rate ) => rate.includes( 'pickup_location:' )
		),
	};
};
