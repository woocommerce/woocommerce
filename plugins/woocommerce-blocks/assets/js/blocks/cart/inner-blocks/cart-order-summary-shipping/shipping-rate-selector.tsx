/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ShippingRatesControl } from '@woocommerce/base-components/cart-checkout';
import type { CartResponseShippingRate } from '@woocommerce/types';
import NoticeBanner from '@woocommerce/base-components/notice-banner';

export const ShippingRateSelector = ( {
	shippingRates,
	isLoadingRates,
	hasCompleteAddress,
}: {
	shippingRates: CartResponseShippingRate[];
	isLoadingRates: boolean;
	hasCompleteAddress: boolean;
} ): JSX.Element | null => {
	return (
		<fieldset className="wc-block-components-totals-shipping__fieldset">
			<legend className="screen-reader-text">
				{ __( 'Shipping options', 'woocommerce' ) }
			</legend>
			<ShippingRatesControl
				className="wc-block-components-totals-shipping__options"
				noResultsMessage={
					<>
						{ hasCompleteAddress && (
							<NoticeBanner
								isDismissible={ false }
								className="wc-block-components-shipping-rates-control__no-results-notice"
								status="warning"
							>
								{ __(
									'There are no shipping options available. Please check your shipping address.',
									'woocommerce'
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
