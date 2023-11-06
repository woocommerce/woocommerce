/**
 * External dependencies
 */
import { DEFAULT_QUERY } from '@woocommerce/blocks/product-collection/constants';

/**
 * Internal dependencies
 */
import { sharedParams, mappedParams, formatQuery } from '../utils';

describe( 'formatQuery: transform Product Collection Block query to Product Collection Data Store API query', () => {
	it( 'shared param is carried over', () => {
		const formattedQuery = formatQuery( DEFAULT_QUERY );
		sharedParams.forEach( ( key ) => {
			expect( formattedQuery ).toHaveProperty(
				key,
				DEFAULT_QUERY[ key ]
			);
		} );
	} );

	it( 'mapped param key is transformed', () => {
		const formattedQuery = formatQuery( DEFAULT_QUERY );
		mappedParams.forEach( ( { key, map } ) => {
			expect( formattedQuery ).toHaveProperty(
				map,
				DEFAULT_QUERY[ key ]
			);
		} );
	} );

	it( 'taxQuery is transformed', () => {
		const queryWithTax = Object.assign( {}, DEFAULT_QUERY, {
			taxQuery: {
				product_cat: [ 1, 2 ],
				product_tag: [ 3, 4 ],
				custom_taxonomy: [ 5, 6 ],
			},
		} );
		const formattedQuery = formatQuery( queryWithTax );
		expect( formattedQuery ).toHaveProperty( 'cat', [ 1, 2 ] );
		expect( formattedQuery ).toHaveProperty( 'tag', [ 3, 4 ] );
		expect( formattedQuery ).toHaveProperty(
			'_unstable_tax_custom_taxonomy',
			[ 5, 6 ]
		);
	} );

	it( 'attribute query is transformed', () => {
		const woocommerceAttributes = [
			{ termId: 11, taxonomy: 'pa_size' },
			{ termId: 12, taxonomy: 'pa_color' },
			{ termId: 13, taxonomy: 'pa_custom' },
			{ termId: 14, taxonomy: 'pa_custom' },
		];
		const queryWithAttributes = Object.assign( {}, DEFAULT_QUERY, {
			woocommerceAttributes,
		} );
		const formattedQuery = formatQuery( queryWithAttributes );

		expect( formattedQuery ).toHaveProperty( 'attributes' );

		woocommerceAttributes.forEach( ( { termId, taxonomy } ) => {
			expect( formattedQuery.attributes ).toEqual(
				expect.arrayContaining( [
					{ term_id: termId, attribute: taxonomy },
				] )
			);
		} );
	} );
} );
