/**
 * External dependencies
 */
import { AUTO_DRAFT_NAME } from '@woocommerce/product-editor';
import { Product } from '@woocommerce/data';
import { useDispatch, resolveSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

export function useProductEntityRecord(
	productId: string | undefined
): Product | undefined {
	console.log( productId );
	const { saveEntityRecord } = useDispatch( 'core' );
	const [ product, setProduct ] = useState< Product | undefined >(
		undefined
	);

	useEffect( () => {
		const getRecordPromise: Promise< Product > = productId
			? resolveSelect( 'core' ).getEntityRecord< Product >(
					'postType',
					'product',
					Number.parseInt( productId, 10 )
			  )
			: // eslint-disable-next-line @typescript-eslint/ban-ts-comment
			  // @ts-ignore Incorrect types.
			  ( saveEntityRecord( 'postType', 'product', {
					title: AUTO_DRAFT_NAME,
					status: 'auto-draft',
			  } ) as Promise< Product > );
		getRecordPromise
			.then( ( autoDraftProduct: Product ) => {
				setProduct( autoDraftProduct );
			} )
			.catch( ( e ) => {
				setProduct( undefined );
				throw e;
			} );
	}, [ productId ] );

	return product;
}
