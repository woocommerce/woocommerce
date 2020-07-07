/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import { decodeEntities } from '@wordpress/html-entities';
import { getCurrencyFromPriceResponse } from '@woocommerce/base-utils';
import { ShippingRatesControl } from '@woocommerce/base-components/cart-checkout';

const renderShippingRatesControlOption = ( option ) => ( {
	label: decodeEntities( option.name ),
	value: option.rate_id,
	description: (
		<>
			{ option.price && (
				<FormattedMonetaryAmount
					currency={ getCurrencyFromPriceResponse( option ) }
					value={ option.price }
				/>
			) }
			{ option.price && option.delivery_time ? ' — ' : null }
			{ decodeEntities( option.delivery_time ) }
		</>
	),
} );

const ShippingRateSelector = ( {
	hasRates,
	shippingRates,
	shippingRatesLoading,
} ) => {
	return (
		<fieldset className="wc-block-shipping-totals__fieldset">
			<legend className="screen-reader-text">
				{ hasRates
					? __( 'Shipping options', 'woocommerce' )
					: __(
							'Choose a shipping option',
							'woocommerce'
					  ) }
			</legend>
			<ShippingRatesControl
				className="wc-block-shipping-totals__options"
				collapsibleWhenMultiple={ true }
				noResultsMessage={ __(
					'No shipping options were found.',
					'woocommerce'
				) }
				renderOption={ renderShippingRatesControlOption }
				shippingRates={ shippingRates }
				shippingRatesLoading={ shippingRatesLoading }
			/>
		</fieldset>
	);
};

export default ShippingRateSelector;
