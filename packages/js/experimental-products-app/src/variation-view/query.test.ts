/**
 * External dependencies
 */
import type { View } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { buildVariationViewQuery } from './query';

describe( 'buildVariationViewQuery', () => {
	it( 'maps the parent product and pagination params', () => {
		const query = buildVariationViewQuery(
			{
				type: 'table',
				page: 2,
				perPage: 15,
				filters: [],
			} as View,
			99
		);

		expect( query ).toEqual(
			expect.objectContaining( {
				product_id: 99,
				page: 2,
				per_page: 15,
			} )
		);
	} );

	it( 'maps search and supported sorting params', () => {
		const query = buildVariationViewQuery(
			{
				type: 'table',
				page: 1,
				perPage: 20,
				search: 'blue',
				sort: {
					field: 'name',
					direction: 'asc',
				},
				filters: [],
			} as View,
			42
		);

		expect( query ).toEqual(
			expect.objectContaining( {
				search: 'blue',
				order: 'asc',
				orderby: 'title',
			} )
		);
	} );
} );
