/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from 'wordpress-components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import ShippingRatesControl from '../../shipping-rates-control';

const ShippingRateSelector = ( {
	hasRates,
	shippingRates,
	shippingRatesLoading,
} ) => {
	const legend = hasRates
		? __( 'Shipping options', 'woo-gutenberg-products-block' )
		: __( 'Choose a shipping option', 'woo-gutenberg-products-block' );
	return (
		<fieldset className="wc-block-components-totals-shipping__fieldset">
			<legend className="screen-reader-text">{ legend }</legend>
			<ShippingRatesControl
				className="wc-block-components-totals-shipping__options"
				collapsible={ true }
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
				shippingRatesLoading={ shippingRatesLoading }
			/>
		</fieldset>
	);
};

export default ShippingRateSelector;
