/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ProductBadge from '../product-badge';

/**
 * Returns a backorder badge.
 */
const ProductBackorderBadge = () => {
	return (
		<ProductBadge className="wc-block-components-product-backorder-badge">
			{ __( 'Available on backorder', 'woocommerce' ) }
		</ProductBadge>
	);
};

export default ProductBackorderBadge;
