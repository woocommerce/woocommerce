/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Product,
	PRODUCTS_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
import { useFormContext } from '@woocommerce/components';
import { useParams } from 'react-router-dom';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getProductTitle } from './utils/get-product-title';
import { WooHeaderPageTitle } from '~/header/utils';

export const ProductTitle: React.FC = () => {
	const { values } = useFormContext< Product >();
	const { productId } = useParams();
	const { persistedName } = useSelect( ( select: WCDataSelector ) => {
		const product = productId
			? select( PRODUCTS_STORE_NAME ).getProduct(
					parseInt( productId, 10 ),
					undefined
			  )
			: null;

		return {
			persistedName: product?.name,
		};
	} );

	const title = getProductTitle( values.name, values.type, persistedName );

	return <WooHeaderPageTitle>{ title }</WooHeaderPageTitle>;
};
