/**
 * External dependencies
 */
import { AUTO_DRAFT_NAME } from '@woocommerce/product-editor';
import { type Product } from '@woocommerce/data';
import { dispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

export function useProductEntityRecord(
	productId: string | undefined
): number | undefined {
	const [ id, setId ] = useState< number | undefined >( undefined );

	useEffect( () => {
		if ( productId ) {
			setId( Number.parseInt( productId, 10 ) );
			return;
		}

		const createProductPromise = dispatch( 'core' ).saveEntityRecord(
			'postType',
			'product',
			{
				title: AUTO_DRAFT_NAME,
				status: 'auto-draft',
			}
		) as never as Promise< Product >;

		createProductPromise
			.then( ( autoDraftProduct: Product ) =>
				setId( autoDraftProduct.id )
			)
			.catch( ( e ) => {
				setId( undefined );
				throw e;
			} );
	}, [ productId ] );

	return id;
}
