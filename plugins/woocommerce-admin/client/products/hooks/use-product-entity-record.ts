/**
 * External dependencies
 */
import { AUTO_DRAFT_NAME } from '@woocommerce/product-editor';
import { Product } from '@woocommerce/data';
import { useDispatch, useSelect, select as WPSelect } from '@wordpress/data';

import { useEffect, useState, useMemo } from '@wordpress/element';

const useAutoDraft = () => {
	const { saveEntityRecord } = useDispatch( 'core' );
	const [ product, setProduct ] = useState< Product | undefined >(
		undefined
	);

	useEffect( () => {
		saveEntityRecord( 'postType', 'product', {
			title: AUTO_DRAFT_NAME,
			status: 'auto-draft',
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore Incorrect types.
		} ).then( ( autoDraftProduct: Product ) => {
			setProduct( autoDraftProduct );
		} );
	}, [] );

	return product;
};

const useProductEntity = ( productId: number ) => {
	const product = useSelect( ( select: typeof WPSelect ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore Missing types.
		const { getEntityRecord } = select( 'core' );

		return getEntityRecord( 'postType', 'product', productId ) as Product;
	} );

	return product;
};

export function useProductEntityRecord(
	productId: string | undefined
): Product | undefined {
	const selectedHook = useMemo( () => {
		return productId
			? useProductEntity.bind( {}, Number.parseInt( productId, 10 ) )
			: useAutoDraft;
	}, [ productId ] );

	return selectedHook();
}
