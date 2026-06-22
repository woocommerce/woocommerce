/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import {
	getTotalShippingValue,
	isPackageRateCollectable,
} from '@woocommerce/base-utils';
import { FormattedMonetaryAmount } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	isObject,
	objectHasProp,
	CartShippingRate,
	CartResponseTotals,
} from '@woocommerce/types';

export const renderShippingTotalValue = (
	values: CartResponseTotals,
	shippingRates: CartShippingRate[] = []
) => {
	const totalShippingValue = getTotalShippingValue( values );
	const selectedDiscountedRate = shippingRates
		.flatMap( ( shippingPackage ) => shippingPackage.shipping_rates )
		.find( ( rate ) => rate.selected && rate.price_before_discount );
	const priceBeforeDiscount = parseInt(
		selectedDiscountedRate?.price_before_discount || '',
		10
	);
	const taxesBeforeDiscount = parseInt(
		selectedDiscountedRate?.taxes_before_discount || '',
		10
	);
	const priceBeforeDiscountWithTaxes = getSetting(
		'displayCartPricesIncludingTax',
		false
	)
		? priceBeforeDiscount + taxesBeforeDiscount
		: priceBeforeDiscount;

	if ( totalShippingValue === 0 ) {
		return (
			<span className="wc-block-checkout__shipping-option-price wc-block-components-totals-shipping__discounted-value">
				{ Number.isFinite( priceBeforeDiscountWithTaxes ) &&
					priceBeforeDiscountWithTaxes > 0 && (
						<del className="wc-block-checkout__shipping-option-price--original">
							<FormattedMonetaryAmount
								currency={ getCurrencyFromPriceResponse( values ) }
								value={ priceBeforeDiscountWithTaxes }
							/>
						</del>
					) }
				<span className="wc-block-checkout__shipping-option--free">
					{ __( 'Free', 'woocommerce' ) }
				</span>
			</span>
		);
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
