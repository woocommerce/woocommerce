/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import type {
	CartResponseShippingAddress,
	CartResponseShippingRate,
} from '@woocommerce/types';
import NoticeBanner from '@woocommerce/base-components/notice-banner';
import { createInterpolateElement } from '@wordpress/element';
/**
 * Internal dependencies
 */
import ShippingRatesControl from '../../shipping-rates-control';
import { formatShippingAddress } from '../../../../utils';

export interface ShippingRateSelectorProps {
	hasRates: boolean;
	shippingRates: CartResponseShippingRate[];
	shippingAddress: CartResponseShippingAddress;
	isLoadingRates: boolean;
	isAddressComplete: boolean;
}

export const ShippingRateSelector = ( {
	hasRates,
	shippingRates,
	shippingAddress,
	isLoadingRates,
	isAddressComplete,
}: ShippingRateSelectorProps ): JSX.Element => {
	const legend = hasRates
		? __( 'Shipping options', 'woocommerce' )
		: __( 'Choose a shipping option', 'woocommerce' );

	return (
		<fieldset className="wc-block-components-totals-shipping__fieldset">
			<legend className="screen-reader-text">{ legend }</legend>
			<ShippingRatesControl
				className="wc-block-components-totals-shipping__options"
				noResultsMessage={
					<>
						{ isAddressComplete && (
							<NoticeBanner
								isDismissible={ false }
								className="wc-block-components-shipping-rates-control__no-results-notice"
								status="warning"
							>
								{ createInterpolateElement(
									sprintf(
										// translators: %s is the address that was used to calculate shipping.
										__(
											'No delivery options available for <strong>%s</strong>. Please verify the address is correct or try a different address.',
											'woocommerce'
										),
										formatShippingAddress( shippingAddress )
									),
									{
										strong: <strong />,
									}
								) }
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
