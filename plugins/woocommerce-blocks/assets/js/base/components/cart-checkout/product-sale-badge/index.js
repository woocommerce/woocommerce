/**
 * External dependencies
 */
import { createInterpolateElement } from 'wordpress-element';
import { __, sprintf } from '@wordpress/i18n';
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
 * @param {string} [props.format]   Format to change the price.
 * @return {*} The component.
 */
const ProductSaleBadge = ( { currency, saleAmount, format = '<price/>' } ) => {
	if ( ! saleAmount || saleAmount <= 0 ) {
		return null;
	}
	if ( ! format.includes( '<price/>' ) ) {
		format = '<price/>';
		// eslint-disable-next-line no-console
		console.error( 'Price formats need to include the `<price/>` tag.' );
	}

	const formattedMessage = sprintf(
		/* translators: %s will be replaced by the discount amount */
		__( `Save %s`, 'woo-gutenberg-products-block' ),
		format
	);

	return (
		<ProductBadge className="wc-block-components-sale-badge">
			{ createInterpolateElement( formattedMessage, {
				price: (
					<FormattedMonetaryAmount
						currency={ currency }
						value={ saleAmount }
					/>
				),
			} ) }
		</ProductBadge>
	);
};

ProductSaleBadge.propTypes = {
	currency: PropTypes.object,
	saleAmount: PropTypes.number,
};

export default ProductSaleBadge;
