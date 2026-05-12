/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { fieldExtensions } from './field';

const renderTags = ( item: Partial< ProductEntityRecord > ) => {
	if ( ! fieldExtensions.render ) {
		throw new Error( 'tags render not implemented' );
	}

	const render = fieldExtensions.render as ( props: {
		item: ProductEntityRecord;
	} ) => unknown;

	return render( {
		item: item as ProductEntityRecord,
	} );
};

describe( 'tags field', () => {
	it( 'renders tag names instead of tag IDs', () => {
		expect(
			renderTags( {
				tags: [
					{ id: 12, name: 'Summer' },
					{ id: 34, name: 'Sale &amp; clearance' },
				],
			} )
		).toBe( 'Summer, Sale & clearance' );
	} );

	it( 'renders nothing when there are no tags', () => {
		expect( renderTags( { tags: [] } ) ).toBe( '' );
	} );
} );
