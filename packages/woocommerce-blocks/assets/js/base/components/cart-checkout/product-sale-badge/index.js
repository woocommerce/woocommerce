/**
 * External dependencies
 */
import { createInterpolateElement } from 'wordpress-element';
import { __ } from '@wordpress/i18n';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import ProductBadge from '../product-badge';

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
	if ( ! saleAmount || saleAmount <= 0 ) {
		return null;
	}
	return (
		<ProductBadge className="wc-block-components-sale-badge">
			{ createInterpolateElement(
				/* translators: <price/> will be replaced by the discount amount */
				__( 'Save <price/>', 'woocommerce' ),
				{
					price: (
						<FormattedMonetaryAmount
							currency={ currency }
							value={ saleAmount }
						/>
					),
				}
			) }
		</ProductBadge>
	);
};

ProductSaleBadge.propTypes = {
	currency: PropTypes.object,
	saleAmount: PropTypes.number,
};

export default ProductSaleBadge;
