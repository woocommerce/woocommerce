/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	FormStep,
	ShippingRatesControl,
} from '@woocommerce/base-components/cart-checkout';
import {
	getShippingRatesPackageCount,
	getShippingRatesRateCount,
} from '@woocommerce/base-utils';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import {
	useCheckoutContext,
	useEditorContext,
	useShippingDataContext,
} from '@woocommerce/base-context';
import { decodeEntities } from '@wordpress/html-entities';
import { DISPLAY_CART_PRICES_INCLUDING_TAX } from '@woocommerce/block-settings';
import { Notice } from 'wordpress-components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import NoShippingPlaceholder from './no-shipping-placeholder';

/**
 * Renders a shipping rate control option.
 *
 * @param {Object} option Shipping Rate.
 */
const renderShippingRatesControlOption = ( option ) => {
	const priceWithTaxes = DISPLAY_CART_PRICES_INCLUDING_TAX
		? parseInt( option.price, 10 ) + parseInt( option.taxes, 10 )
		: parseInt( option.price, 10 );
	return {
		label: decodeEntities( option.name ),
		value: option.rate_id,
		description: decodeEntities( option.description ),
		secondaryLabel: (
			<FormattedMonetaryAmount
				currency={ getCurrencyFromPriceResponse( option ) }
				value={ priceWithTaxes }
			/>
		),
		secondaryDescription: decodeEntities( option.delivery_time ),
	};
};

const ShippingOptionsStep = () => {
	const { isProcessing: checkoutIsProcessing } = useCheckoutContext();
	const { isEditor } = useEditorContext();
	const {
		shippingRates,
		shippingRatesLoading,
		needsShipping,
		hasCalculatedShipping,
	} = useShippingDataContext();

	if ( ! needsShipping ) {
		return null;
	}

	return (
		<FormStep
			id="shipping-option"
			disabled={ checkoutIsProcessing }
			className="wc-block-checkout__shipping-option"
			title={ __( 'Shipping options', 'woocommerce' ) }
			description={
				getShippingRatesRateCount( shippingRates ) > 1
					? __(
							'Select shipping options below.',
							'woocommerce'
					  )
					: ''
			}
		>
			{ isEditor && ! getShippingRatesPackageCount( shippingRates ) ? (
				<NoShippingPlaceholder />
			) : (
				<ShippingRatesControl
					noResultsMessage={
						hasCalculatedShipping ? (
							<Notice
								isDismissible={ false }
								className={ classnames(
									'wc-block-components-shipping-rates-control__no-results-notice',
									'woocommerce-error'
								) }
							>
								{ __(
									'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.',
									'woocommerce'
								) }
							</Notice>
						) : (
							__(
								'Shipping options will appear here after entering your full shipping address.',
								'woocommerce'
							)
						)
					}
					renderOption={ renderShippingRatesControlOption }
					shippingRates={ shippingRates }
					shippingRatesLoading={ shippingRatesLoading }
				/>
			) }
		</FormStep>
	);
};

export default ShippingOptionsStep;
