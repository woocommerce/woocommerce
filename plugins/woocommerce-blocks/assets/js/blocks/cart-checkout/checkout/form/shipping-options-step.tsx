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
	useEditorContext,
	useShippingDataContext,
} from '@woocommerce/base-context';
import { useCheckoutSubmit } from '@woocommerce/base-context/hooks';
import { decodeEntities } from '@wordpress/html-entities';
import { Notice } from 'wordpress-components';
import classnames from 'classnames';
import { getSetting } from '@woocommerce/settings';
import type { PackageRateOption } from '@woocommerce/type-defs/shipping';
import type { CartShippingPackageShippingRate } from '@woocommerce/type-defs/cart';

/**
 * Internal dependencies
 */
import NoShippingPlaceholder from './no-shipping-placeholder';

/**
 * Renders a shipping rate control option.
 *
 * @param {Object} option Shipping Rate.
 */
const renderShippingRatesControlOption = (
	option: CartShippingPackageShippingRate
): PackageRateOption => {
	const priceWithTaxes = getSetting( 'displayCartPricesIncludingTax', false )
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

const ShippingOptionsStep = (): JSX.Element | null => {
	const { isDisabled } = useCheckoutSubmit();
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
			disabled={ isDisabled }
			className="wc-block-checkout__shipping-option"
			title={ __( 'Shipping options', 'woo-gutenberg-products-block' ) }
			description={
				getShippingRatesRateCount( shippingRates ) > 1
					? __(
							'Select shipping options below.',
							'woo-gutenberg-products-block'
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
									'woo-gutenberg-products-block'
								) }
							</Notice>
						) : (
							__(
								'Shipping options will appear here after entering your full shipping address.',
								'woo-gutenberg-products-block'
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
