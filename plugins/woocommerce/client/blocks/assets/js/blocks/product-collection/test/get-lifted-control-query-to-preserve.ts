/**
 * Internal dependencies
 */
import { getLiftedControlQueryToPreserve } from '../utils';
import { CoreCollectionNames, ProductCollectionQuery } from '../types';

const buildQuery = (
	overrides: Partial< ProductCollectionQuery > = {}
): ProductCollectionQuery =>
	( {
		exclude: [],
		inherit: false,
		offset: 0,
		order: 'desc',
		orderBy: 'date',
		pages: 0,
		perPage: 9,
		postType: 'product',
		search: '',
		taxQuery: {},
		featured: false,
		woocommerceOnSale: false,
		woocommerceStockStatus: [],
		woocommerceAttributes: [],
		woocommerceHandPickedProducts: [],
		filterable: false,
		...overrides,
	} as unknown as ProductCollectionQuery );

describe( 'getLiftedControlQueryToPreserve', () => {
	it( 'returns null when collection is undefined', () => {
		expect(
			getLiftedControlQueryToPreserve( undefined, buildQuery() )
		).toBeNull();
	} );

	it( 'returns null for a collection without a lifted control', () => {
		expect(
			getLiftedControlQueryToPreserve(
				CoreCollectionNames.ON_SALE,
				buildQuery( {
					woocommerceHandPickedProducts: [ '1', '2' ],
				} )
			)
		).toBeNull();
	} );

	it( 'returns null when Hand-Picked collection has no selected products', () => {
		expect(
			getLiftedControlQueryToPreserve(
				CoreCollectionNames.HAND_PICKED,
				buildQuery()
			)
		).toBeNull();
	} );

	it( 'preserves Hand-Picked product IDs for the Hand-Picked collection', () => {
		const ids = [ '10', '20', '30' ];
		const result = getLiftedControlQueryToPreserve(
			CoreCollectionNames.HAND_PICKED,
			buildQuery( { woocommerceHandPickedProducts: ids } )
		);
		expect( result ).toEqual( {
			woocommerceHandPickedProducts: ids,
		} );
	} );

	it( 'preserves the product_cat tax query for the BY_CATEGORY collection', () => {
		const result = getLiftedControlQueryToPreserve(
			CoreCollectionNames.BY_CATEGORY,
			buildQuery( {
				taxQuery: { product_cat: [ 5, 6 ], product_tag: [ 9 ] },
			} )
		);
		expect( result ).toEqual( {
			taxQuery: { product_cat: [ 5, 6 ], product_tag: [ 9 ] },
		} );
	} );

	it( 'preserves the product_tag tax query for the BY_TAG collection', () => {
		const result = getLiftedControlQueryToPreserve(
			CoreCollectionNames.BY_TAG,
			buildQuery( {
				taxQuery: { product_tag: [ 42 ] },
			} )
		);
		expect( result ).toEqual( {
			taxQuery: { product_tag: [ 42 ] },
		} );
	} );

	it( 'preserves the product_brand tax query for the BY_BRAND collection', () => {
		const result = getLiftedControlQueryToPreserve(
			CoreCollectionNames.BY_BRAND,
			buildQuery( {
				taxQuery: { product_brand: [ 7 ] },
			} )
		);
		expect( result ).toEqual( {
			taxQuery: { product_brand: [ 7 ] },
		} );
	} );

	it( 'returns null when the taxonomy collection has no terms selected', () => {
		expect(
			getLiftedControlQueryToPreserve(
				CoreCollectionNames.BY_CATEGORY,
				buildQuery( { taxQuery: { product_cat: [] } } )
			)
		).toBeNull();

		expect(
			getLiftedControlQueryToPreserve(
				CoreCollectionNames.BY_TAG,
				buildQuery( { taxQuery: {} } )
			)
		).toBeNull();
	} );

	it( 'preserves both Hand-Picked products and tax query when applicable', () => {
		// Although Hand-Picked and taxonomy-based collections are distinct,
		// guard against future overlap by exercising both branches together.
		const result = getLiftedControlQueryToPreserve(
			CoreCollectionNames.HAND_PICKED,
			buildQuery( {
				woocommerceHandPickedProducts: [ '1' ],
				taxQuery: { product_cat: [ 5 ] },
			} )
		);
		// Hand-Picked collection only preserves the product list.
		expect( result ).toEqual( {
			woocommerceHandPickedProducts: [ '1' ],
		} );
	} );
} );
