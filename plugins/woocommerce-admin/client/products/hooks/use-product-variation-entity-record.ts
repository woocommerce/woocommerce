/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

export function useProductVariationEntityRecord(
	variationId: string
): ProductVariation | undefined {
	const [ product, setProduct ] = useState< ProductVariation | undefined >(
		undefined
	);

	useEffect( () => {
		const getRecordPromise: Promise< ProductVariation > = resolveSelect(
			'core'
		).getEntityRecord< ProductVariation >(
			'postType',
			'product_variation',
			Number.parseInt( variationId, 10 )
		);
		getRecordPromise
			.then( ( autoDraftProduct: ProductVariation ) => {
				setProduct( autoDraftProduct );
			} )
			.catch( ( e ) => {
				setProduct( undefined );
				throw e;
			} );
	}, [ variationId ] );

	return product;
}
