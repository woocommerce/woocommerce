/**
 * External dependencies
 */
import {
	isLatestMinusOneWordPress,
	isLatestWordPress,
} from '@woocommerce/jest-wordpress-version-compat';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

if ( isLatestWordPress() || isLatestMinusOneWordPress() ) {
	describe.skip( 'tags field', () => {
		it( 'skips because this feature works only when Gutenberg is installed', () => {} );
	} );
} else {
	describe( 'tags field', () => {
		let fieldExtensions: typeof import('./field').fieldExtensions;

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
		beforeAll( async () => {
			( { fieldExtensions } = await import( './field' ) );
		} );

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
}
