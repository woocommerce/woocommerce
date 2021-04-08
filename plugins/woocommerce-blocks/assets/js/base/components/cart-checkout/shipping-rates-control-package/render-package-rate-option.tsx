/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { DISPLAY_CART_PRICES_INCLUDING_TAX } from '@woocommerce/block-settings';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import type { Rate, PackageRateOption } from '@woocommerce/type-defs/shipping';

/**
 * Default render function for package rate options.
 *
 * @param {Object} rate Rate data.
 */
export const renderPackageRateOption = ( rate: Rate ): PackageRateOption => {
	const priceWithTaxes: number = DISPLAY_CART_PRICES_INCLUDING_TAX
		? parseInt( rate.price, 10 ) + parseInt( rate.taxes, 10 )
		: parseInt( rate.price, 10 );

	return {
		label: decodeEntities( rate.name ),
		value: rate.rate_id,
		description: (
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
		),
	};
};

export default renderPackageRateOption;
