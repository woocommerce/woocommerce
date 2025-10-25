/**
 * External dependencies
 */
import { _n, __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { useShippingData, useStoreCart } from '@woocommerce/base-context/hooks';
import { CartShippingPackageShippingRate } from '@woocommerce/types';
import {
	isPackageRateCollectable,
	getShippingRatesPackageCount,
} from '@woocommerce/base-utils';
import { ExperimentalOrderLocalPickupPackages } from '@woocommerce/blocks-checkout';
import { LocalPickupSelect } from '@woocommerce/base-components/cart-checkout/local-pickup-select';
import { renderPackageRateOption } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import ShippingRatesControlPackage from '../../../../base/components/cart-checkout/shipping-rates-control-package';

const Block = () => {
	const { shippingRates, selectShippingRate } = useShippingData();

	// Memoize pickup locations to prevent re-rendering when the shipping rates change.
	const pickupLocations: CartShippingPackageShippingRate[] = useMemo( () => {
		return ( shippingRates[ 0 ]?.shipping_rates || [] ).filter(
			isPackageRateCollectable
		);
	}, [ shippingRates ] );

	const [ selectedOption, setSelectedOption ] = useState<
		string | undefined
	>(
		() =>
			pickupLocations.find( ( rate ) => rate.selected )?.rate_id ??
			pickupLocations[ 0 ]?.rate_id
	);

	const handleShippingRateChange = useCallback(
		( rateId: string ) => {
			setSelectedOption( rateId );
			selectShippingRate( rateId );
		},
		[ setSelectedOption, selectShippingRate ]
	);

	// Update on mount, we do it every time to:
	// - sync the initial value with the server
	// - or reset pending request to change shipping rate that might be coming
	//   from other components (e.g. shipping options), selectShippingRate thunk
	//   in the cart store properly handles aborting the previous request if needed
	useEffect( () => {
		if ( selectedOption ) {
			selectShippingRate( selectedOption );
		}
		// We want this to run on mount only, beware of updating it as it may cause
		// shipping rate selection to end up in inifite loop
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Update the selected option if cart state changes in the data store.
	useEffect( () => {
		const selectedRate = pickupLocations.find( ( rate ) => rate.selected );
		const selectedRateId = selectedRate?.rate_id;

		if ( selectedRateId && selectedRateId !== selectedOption ) {
			setSelectedOption( selectedRateId );
		}
		// We want to explicitly react to changes in the data store only here, local state is managed
		// through different code path.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ pickupLocations ] );

	// Prepare props to pass to the ExperimentalOrderLocalPickupPackages slot fill.
	// We need to pluck out receiveCart.
	// eslint-disable-next-line no-unused-vars
	const { extensions, receiveCart, ...cart } = useStoreCart();
	const slotFillProps = {
		extensions,
		cart,
		components: {
			ShippingRatesControlPackage,
			LocalPickupSelect,
		},
		renderPickupLocation: renderPackageRateOption,
	};

	return (
		<>
			<ExperimentalOrderLocalPickupPackages.Slot { ...slotFillProps } />
			<ExperimentalOrderLocalPickupPackages>
				<LocalPickupSelect
					title={ shippingRates[ 0 ].name }
					selectedOption={ selectedOption ?? '' }
					renderPickupLocation={ renderPackageRateOption }
					pickupLocations={ pickupLocations }
					packageCount={ getShippingRatesPackageCount(
						shippingRates
					) }
					onChange={ ( value ) => handleShippingRateChange( value ) }
				/>
			</ExperimentalOrderLocalPickupPackages>
		</>
	);
};

export default Block;
