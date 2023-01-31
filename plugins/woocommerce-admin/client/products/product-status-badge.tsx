/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Pill } from '@woocommerce/components';
import { PRODUCTS_STORE_NAME, WCDataSelector } from '@woocommerce/data';
import { useParams } from 'react-router-dom';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	getProductStatus,
	PRODUCT_STATUS_LABELS,
} from './utils/get-product-status';
import './product-status-badge.scss';

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
