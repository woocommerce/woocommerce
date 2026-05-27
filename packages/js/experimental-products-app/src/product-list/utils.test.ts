/**
 * External dependencies
 */
import type { View } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import {
	getProductEditPostId,
	hasActiveProductListSearchOrFilters,
	getProductListNavigationPath,
	getProductsWithEmbeddedVariations,
} from './utils';

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
	describe( 'getProductListNavigationPath', () => {
		it( 'preserves existing query args when adding new params', () => {
			expect(
				getProductListNavigationPath(
					'woocommerce-products-dashboard?post_type=product',
					{
						activeView: 'draft',
					}
				)
			).toBe(
				'woocommerce-products-dashboard?post_type=product&activeView=draft'
			);
		} );

		it( 'removes invalid undefined params from the existing query and new params', () => {
			expect(
				getProductListNavigationPath(
					'woocommerce-products-dashboard?undefined=%2F&post_type=product',
					{
						undefined: '/',
						activeView: 'draft',
					}
				)
			).toBe(
				'woocommerce-products-dashboard?post_type=product&activeView=draft'
			);
		} );

		it( 'removes existing query args when a param is set to undefined', () => {
			expect(
				getProductListNavigationPath(
					'woocommerce-products-dashboard?post_type=product&postId=12&quickEdit=true',
					{
						postId: undefined,
						quickEdit: undefined,
					}
				)
			).toBe( 'woocommerce-products-dashboard?post_type=product' );
		} );
	} );

	describe( 'getProductEditPostId', () => {
		it( 'returns the product ID for parent products', () => {
			expect( getProductEditPostId( createProduct( 1 ) ) ).toBe( 1 );
		} );

		it( 'returns the parent product ID for variations', () => {
			expect( getProductEditPostId( createProduct( 2, 1 ) ) ).toBe( 1 );
		} );

		it( 'falls back to the variation ID when a parent ID is missing', () => {
			expect( getProductEditPostId( createProduct( 2, 0 ) ) ).toBe( 2 );
		} );
	} );

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

	describe( 'hasActiveProductListSearchOrFilters', () => {
		const baseView = {
			type: 'table',
			page: 1,
			perPage: 20,
			filters: [],
		} as View;

		it( 'returns true when the view has a search query', () => {
			expect(
				hasActiveProductListSearchOrFilters( {
					...baseView,
					search: ' hoodie ',
				} )
			).toBe( true );
		} );

		it( 'returns true when the view has a filter value', () => {
			expect(
				hasActiveProductListSearchOrFilters( {
					...baseView,
					filters: [
						{
							field: 'stock_quantity',
							operator: 'is',
							value: 0,
						},
					],
				} as View )
			).toBe( true );
		} );

		it( 'ignores empty search and filter values', () => {
			expect(
				hasActiveProductListSearchOrFilters( {
					...baseView,
					search: ' ',
					filters: [
						{
							field: 'categories',
							operator: 'isAny',
							value: [],
						},
						{
							field: 'tags',
							operator: 'isAny',
							value: [ '', undefined ],
						},
					],
				} as View )
			).toBe( false );
		} );
	} );
} );
