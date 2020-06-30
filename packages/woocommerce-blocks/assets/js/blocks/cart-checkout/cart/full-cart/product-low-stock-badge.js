/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Returns a low stock badge for a line item.
 */
const ProductLowStockBadge = ( { lowStockRemaining } ) => {
	if ( ! lowStockRemaining ) {
		return null;
	}

	return (
		<div className="wc-block-cart-item__low-stock-badge">
			{ sprintf(
				/* translators: %s stock amount (number of items in stock for product) */
				__( '%s left in stock', 'woocommerce' ),
				lowStockRemaining
			) }
		</div>
	);
};

export default ProductLowStockBadge;
