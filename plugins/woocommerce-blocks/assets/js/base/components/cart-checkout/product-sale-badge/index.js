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

const ProductSaleBadge = ( { currency, saleAmount } ) => {
	return (
		saleAmount > 0 && (
			<div className="wc-block-sale-badge">
				{ sprintf(
					/* translators: %s discount amount */
					__( 'Save %s!', 'woo-gutenberg-products-block' ),
					formatPrice( saleAmount, currency )
				) }
			</div>
		)
	);
};

ProductSaleBadge.propTypes = {
	currency: PropTypes.object,
	saleAmount: PropTypes.number,
};

export default ProductSaleBadge;
