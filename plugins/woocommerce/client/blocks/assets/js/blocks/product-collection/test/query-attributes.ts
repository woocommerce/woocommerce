/**
 * Internal dependencies
 */
import { DEFAULT_QUERY } from '../constants';
import { ProductCollectionQuery } from '../types';
import { getUpdatedQuery } from '../utils';

const getQuery = (
	query: Partial< ProductCollectionQuery > = {}
): ProductCollectionQuery => ( {
	...DEFAULT_QUERY,
	...query,
} );

describe( 'getUpdatedQuery', () => {
	it( 'updates query attributes', () => {
		expect(
			getUpdatedQuery(
				getQuery( {
					search: 'shirt',
					woocommerceOnSale: true,
				} ),
				{
					woocommerceOnSale: false,
				}
			)
		).toEqual(
			getQuery( {
				search: 'shirt',
				woocommerceOnSale: false,
			} )
		);
	} );

	it( 'merges taxQuery updates into the current taxQuery', () => {
		expect(
			getUpdatedQuery(
				getQuery( {
					taxQuery: {
						product_cat: [ 1 ],
						product_tag: [ 2 ],
					},
				} ),
				{
					taxQuery: {
						product_tag: [],
					},
				}
			).taxQuery
		).toEqual( {
			product_cat: [ 1 ],
			product_tag: [],
		} );
	} );

	it( 'preserves taxQuery when an update does not include taxonomy changes', () => {
		expect(
			getUpdatedQuery(
				getQuery( {
					taxQuery: {
						product_cat: [ 1 ],
						product_tag: [ 2 ],
					},
				} ),
				{
					woocommerceOnSale: true,
				}
			).taxQuery
		).toEqual( {
			product_cat: [ 1 ],
			product_tag: [ 2 ],
		} );
	} );

	it( 'preserves taxQuery when a taxonomy update is invalid', () => {
		const query = getQuery( {
			taxQuery: {
				product_cat: [ 1 ],
				product_tag: [ 2 ],
			},
		} );

		expect(
			getUpdatedQuery( query, {
				taxQuery: undefined,
			} ).taxQuery
		).toEqual( {
			product_cat: [ 1 ],
			product_tag: [ 2 ],
		} );

		expect(
			getUpdatedQuery( query, {
				taxQuery:
					null as unknown as ProductCollectionQuery[ 'taxQuery' ],
			} ).taxQuery
		).toEqual( {
			product_cat: [ 1 ],
			product_tag: [ 2 ],
		} );
	} );
} );
