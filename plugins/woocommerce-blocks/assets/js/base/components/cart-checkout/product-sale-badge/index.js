/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { formatPrice } from '@woocommerce/base-utils';
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
 * @param {number} props.saleAmount Currency object.
 *
 * @return {*} The component.
 */
const ProductSaleBadge = ( { currency, saleAmount } ) => {
	if ( ! saleAmount ) {
		return null;
	}
	return (
		<div className="wc-block-sale-badge">
			{ sprintf(
				/* translators: %s discount amount */
				__( 'Save %s!', 'woo-gutenberg-products-block' ),
				formatPrice( saleAmount, currency )
			) }
		</div>
	);
};

ProductSaleBadge.propTypes = {
	currency: PropTypes.object,
	saleAmount: PropTypes.number,
};

export default ProductSaleBadge;
