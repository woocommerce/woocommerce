/**
 * External dependencies
 */
import { isLatestMinusOneWordPress } from '@woocommerce/jest-wordpress-version-compat';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

import { fieldExtensions } from './field';

const describeForCurrentWordPressTarget = isLatestMinusOneWordPress()
	? describe.skip
	: describe;

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

describeForCurrentWordPressTarget( 'tags field', () => {
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
