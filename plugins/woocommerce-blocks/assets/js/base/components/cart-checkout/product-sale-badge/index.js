/**
 * External dependencies
 */
import { __experimentalCreateInterpolateElement } from 'wordpress-element';
import { __ } from '@wordpress/i18n';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * ProductSaleBadge
 *
 * @param {Object} props            Incoming props.
 * @param {Object} props.currency   Currency object.
 * @param {number} props.saleAmount Discounted amount.
 *
 * @return {*} The component.
 */
const ProductSaleBadge = ( { currency, saleAmount } ) => {
	if ( ! saleAmount ) {
		return null;
	}
	return (
		<div className="wc-block-components-sale-badge">
			{ __experimentalCreateInterpolateElement(
				/* translators: <price/> will be replaced by the discount amount */
				__( 'Save <price/>', 'woo-gutenberg-products-block' ),
				{
					price: (
						<FormattedMonetaryAmount
							currency={ currency }
							value={ saleAmount }
						/>
					),
				}
			) }
		</div>
	);
};

ProductSaleBadge.propTypes = {
	currency: PropTypes.object,
	saleAmount: PropTypes.number,
};

export default ProductSaleBadge;
