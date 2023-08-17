/**
 * External dependencies
 */
import classnames from 'classnames';
import { Pill } from '@woocommerce/components';
import {
	ProductsSelectors,
	PRODUCTS_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
import { useParams } from 'react-router-dom';
import { useSelect } from '@wordpress/data';
import {
	getProductStatus,
	PRODUCT_STATUS_LABELS,
} from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import './product-status-badge.scss';

export const ProductStatusBadge: React.FC = () => {
	const { productId } = useParams();
	const product = useSelect( ( select ) => {
		return productId
			? ( select( PRODUCTS_STORE_NAME ) as ProductsSelectors ).getProduct(
					parseInt( productId, 10 ),
					undefined
			  )
			: undefined;
	}, [] );

	const status = getProductStatus( product );

	return (
		<Pill
			className={ classnames(
				'woocommerce-product-status',
				`is-${ status }`
			) }
		>
			{ PRODUCT_STATUS_LABELS[ status ] }
		</Pill>
	);
};
