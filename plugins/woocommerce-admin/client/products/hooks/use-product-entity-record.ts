/**
 * External dependencies
 */
import { AUTO_DRAFT_NAME } from '@woocommerce/product-editor';
import { Product } from '@woocommerce/data';
import { useDispatch, resolveSelect } from '@wordpress/data';
import { getQuery } from '@woocommerce/navigation';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const isProductEditor = () => {
	const query: { page?: string; path?: string } = getQuery();
	return (
		query?.page === 'wc-admin' &&
		[ '/add-product', '/product/' ].some( ( path ) =>
			query?.path?.startsWith( path )
		)
	);
};

export function useProductEntityRecord(
	productId: string | undefined
): Product | undefined {
	const { saveEntityRecord } = useDispatch( 'core' );
	const [ product, setProduct ] = useState< Product | undefined >(
		undefined
	);

	useEffect( () => {
		// This is needed to ensure that we use the correct namespace for the entity data store
		// without disturbing the rest_namespace outside of the product block editor.
		apiFetch.use( ( options, next ) => {
			const versionTwoRegex = new RegExp( '^/wp/v2/product' );
			if (
				options.path &&
				versionTwoRegex.test( options?.path ) &&
				isProductEditor()
			) {
				options.path = options.path.replace(
					versionTwoRegex,
					'/wc/v3/products'
				);
			}
			return next( options );
		} );
	}, [] );

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
