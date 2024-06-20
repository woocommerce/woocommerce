/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type {
	CartResponseShippingRate,
	CartResponseShippingAddress,
} from '@woocommerce/types';
import NoticeBanner from '@woocommerce/base-components/notice-banner';
import { formatShippingAddress } from '@woocommerce/base-utils';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ShippingRatesControl from '../../shipping-rates-control';

export interface ShippingRateSelectorProps {
	hasRates: boolean;
	shippingRates: CartResponseShippingRate[];
	isLoadingRates: boolean;
	isAddressComplete: boolean;
	shippingAddress: CartResponseShippingAddress;
	showCalculator?: boolean;
}

export const ShippingRateSelector = ( {
	hasRates,
	shippingRates,
	isLoadingRates,
	isAddressComplete,
	shippingAddress,
	showCalculator = true,
}: ShippingRateSelectorProps ): JSX.Element => {
	const legend = hasRates
		? __( 'Shipping options', 'woocommerce' )
		: __( 'Choose a shipping option', 'woocommerce' );

	const formattedLocation = formatShippingAddress( shippingAddress );
	const noResultsMessage = createInterpolateElement(
		__(
			'No delivery options available for <FormattedLocation />. Please verify the address is correct or try a different address.',
			'woocommerce'
		),
		{
			FormattedLocation: <strong>{ formattedLocation }</strong>,
		}
	);

	return (
		<fieldset className="wc-block-components-totals-shipping__fieldset">
			<legend className="screen-reader-text">{ legend }</legend>
			<ShippingRatesControl
				className="wc-block-components-totals-shipping__options"
				noResultsMessage={
					<>
						{ isAddressComplete && ! showCalculator && (
							<NoticeBanner
								isDismissible={ false }
								className="wc-block-components-shipping-rates-control__no-results-notice"
								status="warning"
							>
								{ noResultsMessage }
							</NoticeBanner>
						) }
					</>
				}
				shippingRates={ shippingRates }
				isLoadingRates={ isLoadingRates }
				context="woocommerce/cart"
			/>
		</fieldset>
	);
};

export default ShippingRateSelector;
