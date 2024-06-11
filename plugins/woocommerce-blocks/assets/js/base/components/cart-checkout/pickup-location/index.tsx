/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { isObject, objectHasProp } from '@woocommerce/types';
import { isPackageRateCollectable } from '@woocommerce/base-utils';

export interface PickupLocationProps {
	setShippingCalculatorLabel: React.Dispatch<
		React.SetStateAction< string >
	>;
	setShippingCalculatorAddress: React.Dispatch<
		React.SetStateAction< string >
	>;
}
/**
 * Shows a formatted pickup location.
 */
const PickupLocation = ( {
	setShippingCalculatorLabel,
	setShippingCalculatorAddress,
}: PickupLocationProps ): JSX.Element | null => {
	const { pickupAddress } = useSelect( ( select ) => {
		const cartShippingRates = select( 'wc/store/cart' ).getShippingRates();

		const flattenedRates = cartShippingRates.flatMap(
			( cartShippingRate ) => cartShippingRate.shipping_rates
		);
		const selectedCollectableRate = flattenedRates.find(
			( rate ) => rate.selected && isPackageRateCollectable( rate )
		);

		// If the rate has an address specified in its metadata.
		if (
			isObject( selectedCollectableRate ) &&
			objectHasProp( selectedCollectableRate, 'meta_data' )
		) {
			const selectedRateMetaData = selectedCollectableRate.meta_data.find(
				( meta ) => meta.key === 'pickup_address'
			);
			if (
				isObject( selectedRateMetaData ) &&
				objectHasProp( selectedRateMetaData, 'value' ) &&
				selectedRateMetaData.value
			) {
				const selectedRatePickupAddress = selectedRateMetaData.value;
				return {
					pickupAddress: selectedRatePickupAddress,
				};
			}
		}

		if ( isObject( selectedCollectableRate ) ) {
			return {
				pickupAddress: undefined,
			};
		}
		return {
			pickupAddress: undefined,
		};
	} );

	// If the method does not contain an address, or the method supporting collection was not found, return early.
	if ( typeof pickupAddress === 'undefined' ) {
		return null;
	}

	const pickupLabel = __( 'Collection from', 'woocommerce' );

	setShippingCalculatorLabel( pickupLabel );
	setShippingCalculatorAddress( pickupAddress );
	return null;
};

export default PickupLocation;
