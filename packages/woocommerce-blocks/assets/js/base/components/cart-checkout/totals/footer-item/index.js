/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import PropTypes from 'prop-types';
import {
	__experimentalApplyCheckoutFilter,
	TotalsItem,
} from '@woocommerce/blocks-checkout';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './style.scss';

const TotalsFooterItem = ( { currency, values } ) => {
	const SHOW_TAXES =
		getSetting( 'taxesEnabled', true ) &&
		getSetting( 'displayCartPricesIncludingTax', false );

	const { total_price: totalPrice, total_tax: totalTax } = values;

	// Prepare props to pass to the __experimentalApplyCheckoutFilter filter.
	// We need to pluck out receiveCart.
	// eslint-disable-next-line no-unused-vars
	const { receiveCart, ...cart } = useStoreCart();
	const label = __experimentalApplyCheckoutFilter( {
		filterName: 'totalLabel',
		defaultValue: __( 'Total', 'woocommerce' ),
		extensions: cart.extensions,
		arg: { cart },
	} );

	const parsedTaxValue = parseInt( totalTax, 10 );

	return (
		<TotalsItem
			className="wc-block-components-totals-footer-item"
			currency={ currency }
			label={ label }
			value={ parseInt( totalPrice, 10 ) }
			description={
				SHOW_TAXES &&
				parsedTaxValue !== 0 && (
					<p className="wc-block-components-totals-footer-item-tax">
						{ createInterpolateElement(
							__(
								'Including <TaxAmount/> in taxes',
								'woocommerce'
							),
							{
								TaxAmount: (
									<FormattedMonetaryAmount
										className="wc-block-components-totals-footer-item-tax-value"
										currency={ currency }
										value={ parsedTaxValue }
									/>
								),
							}
						) }
					</p>
				)
			}
		/>
	);
};

TotalsFooterItem.propTypes = {
	currency: PropTypes.object.isRequired,
	values: PropTypes.shape( {
		total_price: PropTypes.string,
		total_tax: PropTypes.string,
	} ).isRequired,
};

export default TotalsFooterItem;
