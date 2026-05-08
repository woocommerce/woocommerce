/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import { getProductsWithEmbeddedVariations } from './utils';

function createProduct(
	id: number,
	parentId?: number,
	variations?: ProductEntityRecord[]
): ProductEntityRecord {
	return {
		id,
		parent_id: parentId,
		_embedded: variations ? { variations } : undefined,
	} as ProductEntityRecord;
}

describe( 'product list utils', () => {
	describe( 'getProductsWithEmbeddedVariations', () => {
		it( 'adds embedded variations after their parent product', () => {
			const variation = createProduct( 2, 1 );
			const parent = createProduct( 1, undefined, [ variation ] );
			const otherProduct = createProduct( 3 );

			expect(
				getProductsWithEmbeddedVariations( [ parent, otherProduct ] )
			).toEqual( [ parent, variation, otherProduct ] );
		} );

		it( 'does not duplicate embedded variations already present in the list', () => {
			const variation = createProduct( 2, 1 );
			const parent = createProduct( 1, undefined, [ variation ] );

			expect(
				getProductsWithEmbeddedVariations( [ parent, variation ] )
			).toEqual( [ parent, variation ] );
		} );

		it( 'keeps top-level variation data when available', () => {
			const embeddedVariation = createProduct( 2, 1 );
			const listedVariation = {
				...embeddedVariation,
				name: 'Listed variation',
			} as ProductEntityRecord;
			const parent = createProduct( 1, undefined, [ embeddedVariation ] );

			expect(
				getProductsWithEmbeddedVariations( [ parent, listedVariation ] )
			).toEqual( [ parent, listedVariation ] );
		} );
	} );
} );
