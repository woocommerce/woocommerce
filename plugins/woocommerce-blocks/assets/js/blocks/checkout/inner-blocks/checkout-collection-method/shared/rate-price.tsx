/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { createInterpolateElement } from '@wordpress/element';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import type { CartShippingPackageShippingRate } from '@woocommerce/type-defs/cart';

export const RatePrice = ( {
	rate,
}: {
	rate: CartShippingPackageShippingRate;
} ) => {
	const ratePrice = getSetting( 'displayCartPricesIncludingTax', false )
		? parseInt( rate.price, 10 ) + parseInt( rate.taxes, 10 )
		: parseInt( rate.price, 10 );
	return (
		<span className="wc-block-checkout__collection-item-price">
			{ ratePrice === 0
				? __( 'free', 'woo-gutenberg-products-block' )
				: createInterpolateElement(
						__( 'from <price />', 'woo-gutenberg-products-block' ),
						{
							price: (
								<FormattedMonetaryAmount
									currency={ getCurrencyFromPriceResponse(
										rate
									) }
									value={ ratePrice }
								/>
							),
						}
				  ) }
		</span>
	);
};
