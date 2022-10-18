/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from 'wordpress-components';
import classnames from 'classnames';
import type { CartResponseShippingRate } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import ShippingRatesControl from '../../shipping-rates-control';

export interface ShippingRateSelectorProps {
	hasRates: boolean;
	shippingRates: CartResponseShippingRate[];
	isLoadingRates: boolean;
}

export const ShippingRateSelector = ( {
	hasRates,
	shippingRates,
	isLoadingRates,
}: ShippingRateSelectorProps ): JSX.Element => {
	const legend = hasRates
		? __( 'Shipping options', 'woo-gutenberg-products-block' )
		: __( 'Choose a shipping option', 'woo-gutenberg-products-block' );
	return (
		<fieldset className="wc-block-components-totals-shipping__fieldset">
			<legend className="screen-reader-text">{ legend }</legend>
			<ShippingRatesControl
				className="wc-block-components-totals-shipping__options"
				noResultsMessage={
					<Notice
						isDismissible={ false }
						className={ classnames(
							'wc-block-components-shipping-rates-control__no-results-notice',
							'woocommerce-error'
						) }
					>
						{ __(
							'No shipping options were found.',
							'woo-gutenberg-products-block'
						) }
					</Notice>
				}
				shippingRates={ shippingRates }
				isLoadingRates={ isLoadingRates }
				context="woocommerce/cart"
			/>
		</fieldset>
	);
};

export default ShippingRateSelector;
