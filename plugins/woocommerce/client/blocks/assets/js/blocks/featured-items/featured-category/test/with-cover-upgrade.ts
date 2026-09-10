/**
 * External dependencies
 */
import type { BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { bindCategoryButtonUrl } from '../upgrade-utils';

jest.mock( '@wordpress/blocks', () => ( {
	createBlock: (
		name: string,
		attributes: Record< string, unknown >,
		innerBlocks: BlockInstance[]
	) => ( { name, attributes, innerBlocks } ),
} ) );

const button = ( url: string ): BlockInstance =>
	( {
		name: 'core/button',
		attributes: { url },
		innerBlocks: [],
	} ) as unknown as BlockInstance;

describe( 'bindCategoryButtonUrl', () => {
	it( 'binds only the first category CTA and preserves custom buttons', () => {
		const categoryUrl = 'https://example.com/product-category/books/';
		const blocks = [
			button( 'https://example.com/custom/' ),
			button( categoryUrl ),
			button( categoryUrl ),
		];

		const result = bindCategoryButtonUrl( blocks, categoryUrl );

		expect( result[ 0 ].attributes ).toEqual( blocks[ 0 ].attributes );
		expect( result[ 1 ].attributes ).toMatchObject( {
			className: 'wc-block-featured-category__link',
			metadata: {
				bindings: {
					url: {
						source: 'core/term-data',
						args: { field: 'link' },
					},
				},
			},
		} );
		expect( result[ 2 ].attributes ).toEqual( blocks[ 2 ].attributes );
	} );
} );
