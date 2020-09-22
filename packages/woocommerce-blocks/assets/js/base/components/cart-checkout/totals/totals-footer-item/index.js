/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DISPLAY_CART_PRICES_INCLUDING_TAX } from '@woocommerce/block-settings';
import { __experimentalCreateInterpolateElement } from 'wordpress-element';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import TotalsItem from '../totals-item';
import './style.scss';

const TotalsFooterItem = ( { currency, values } ) => {
	const { total_price: totalPrice, total_tax: totalTax } = values;

	return (
		<TotalsItem
			className="wc-block-components-totals-footer-item"
			currency={ currency }
			label={ __( 'Total', 'woocommerce' ) }
			value={ parseInt( totalPrice, 10 ) }
			description={
				DISPLAY_CART_PRICES_INCLUDING_TAX && (
					<p className="wc-block-components-totals-footer-item-tax">
						{ __experimentalCreateInterpolateElement(
							__(
								'Including <TaxAmount/> in taxes',
								'woocommerce'
							),
							{
								TaxAmount: (
									<FormattedMonetaryAmount
										className="wc-block-components-totals-footer-item-tax-value"
										currency={ currency }
										displayType="text"
										value={ parseInt( totalTax, 10 ) }
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
