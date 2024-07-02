/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { FormattedMonetaryAmount } from '@woocommerce/blocks-components';
import type { PackageRateOption } from '@woocommerce/types';
import { getSetting } from '@woocommerce/settings';
import { CartShippingPackageShippingRate } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';

/**
 * Default render function for package rate options.
 *
 * @param {Object} rate Rate data.
 */
export const renderPackageRateOption = (
	rate: CartShippingPackageShippingRate
): PackageRateOption => {
	const priceWithTaxes: number = getSetting(
		'displayCartPricesIncludingTax',
		false
	)
		? parseInt( rate.price, 10 ) + parseInt( rate.taxes, 10 )
		: parseInt( rate.price, 10 );

	let description = (
		<>
			{ Number.isFinite( priceWithTaxes ) && (
				<FormattedMonetaryAmount
					currency={ getCurrencyFromPriceResponse( rate ) }
					value={ priceWithTaxes }
				/>
			) }
			{ Number.isFinite( priceWithTaxes ) && rate.delivery_time
				? ' — '
				: null }
			{ decodeEntities( rate.delivery_time ) }
		</>
	);

	if ( priceWithTaxes === 0 ) {
		description = (
			<span className="wc-block-components-shipping-rates-control__package__description--free">
				{ __( 'Free', 'woocommerce' ) }
			</span>
		);
	}

	return {
		label: decodeEntities( rate.name ),
		value: rate.rate_id,
		description,
	};
};

export default renderPackageRateOption;
