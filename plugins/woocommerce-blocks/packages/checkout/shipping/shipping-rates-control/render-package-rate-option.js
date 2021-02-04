/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { DISPLAY_CART_PRICES_INCLUDING_TAX } from '@woocommerce/block-settings';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';

/**
 * Default render function for package rate options.
 *
 * @param {Object} option Option data.
 */
export const renderPackageRateOption = ( option ) => {
	const priceWithTaxes = DISPLAY_CART_PRICES_INCLUDING_TAX
		? parseInt( option.price, 10 ) + parseInt( option.taxes, 10 )
		: parseInt( option.price, 10 );
	return {
		label: decodeEntities( option.name ),
		value: option.rate_id,
		description: (
			<>
				{ Number.isFinite( priceWithTaxes ) && (
					<FormattedMonetaryAmount
						currency={ getCurrencyFromPriceResponse( option ) }
						value={ priceWithTaxes }
					/>
				) }
				{ Number.isFinite( priceWithTaxes ) && option.delivery_time
					? ' — '
					: null }
				{ decodeEntities( option.delivery_time ) }
			</>
		),
	};
};

export default renderPackageRateOption;
