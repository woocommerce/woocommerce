/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	getTotalShippingValue,
	isPackageRateCollectable,
} from '@woocommerce/base-utils';
import {
	isObject,
	objectHasProp,
	CartShippingRate,
	CartResponseTotals,
} from '@woocommerce/types';

export const renderShippingTotalValue = ( values: CartResponseTotals ) => {
	const totalShippingValue = getTotalShippingValue( values );
	if ( totalShippingValue === 0 ) {
		return <strong>{ __( 'Free', 'woocommerce' ) }</strong>;
	}
	return totalShippingValue;
};

export const getPickupLocation = (
	shippingRates: CartShippingRate[]
): string => {
	const flattenedRates = ( shippingRates || [] ).flatMap(
		( shippingRate ) => shippingRate.shipping_rates
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
			return selectedRateMetaData.value;
		}
	}

	return '';
};
