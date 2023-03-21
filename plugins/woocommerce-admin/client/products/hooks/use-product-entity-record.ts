/**
 * External dependencies
 */
import { AUTO_DRAFT_NAME } from '@woocommerce/product-editor';
import { Product } from '@woocommerce/data';
import { useDispatch, useSelect, select as WPSelect } from '@wordpress/data';

import { useEffect, useState } from '@wordpress/element';

export function useProductEntityRecord(
	productId: string | undefined
): Product | undefined {
	const { saveEntityRecord } = useDispatch( 'core' );
	const [ newProduct, setNewProduct ] = useState< Product | undefined >(
		undefined
	);

	useEffect( () => {
		if ( ! productId ) {
			saveEntityRecord( 'postType', 'product', {
				title: AUTO_DRAFT_NAME,
				status: 'auto-draft',
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore Incorrect types.
			} ).then( ( autoDraftProduct: Product ) => {
				setNewProduct( autoDraftProduct );
			} );
		}
	}, [ productId ] );

	const product = useSelect(
		( select: typeof WPSelect ) => {
			if ( ! productId ) {
				return undefined;
			}
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore Missing types.
			const { getEntityRecord } = select( 'core' );

			return getEntityRecord(
				'postType',
				'product',
				Number.parseInt( productId, 10 )
			) as Product;
		},
		[ productId ]
	);

	return productId !== undefined ? product : newProduct;
}
