/**
 * External dependencies
 */
import { ProductVariation } from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useEffect, useState } from '@wordpress/element';

export function useProductVariationEntityRecord(
	variationId: string
): ProductVariation | undefined {
	const [ product, setProduct ] = useState< ProductVariation | undefined >(
		undefined
	);

	useEffect( () => {
		// @ts-expect-error TODO react-18-upgrade: getEntityRecord type is not correctly typed yet
		const getRecordPromise: Promise< ProductVariation > = resolveSelect(
			coreStore
		).getEntityRecord(
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
