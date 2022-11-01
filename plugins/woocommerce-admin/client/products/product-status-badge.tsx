/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Pill } from '@woocommerce/components';
import { PRODUCTS_STORE_NAME, WCDataSelector } from '@woocommerce/data';
import { useParams } from 'react-router-dom';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getProductStatus } from './utils/get-product-status';

export const ProductStatusBadge: React.FC = () => {
	const { productId } = useParams();
	const product = useSelect( ( select: WCDataSelector ) => {
		return productId
			? select( PRODUCTS_STORE_NAME ).getProduct(
					parseInt( productId, 10 ),
					undefined
			  )
			: undefined;
	} );

	const status = getProductStatus( product );

	return <Pill>{ status }</Pill>;
};
