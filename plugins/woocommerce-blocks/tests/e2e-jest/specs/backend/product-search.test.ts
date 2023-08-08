/**
 * External dependencies
 */
import {
	switchUserToAdmin,
	createNewPost,
	insertBlock,
} from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { GUTENBERG_EDITOR_CONTEXT, describeOrSkip } from '../../utils';

const block = {
	name: 'Product Search',
	slug: 'core/search',
	class: '.wp-block-search',
};

describeOrSkip( GUTENBERG_EDITOR_CONTEXT === 'gutenberg' )(
	`${ block.name } Block`,
	() => {
		it( 'inserting Product Search block renders the core/search variation', async () => {
			await switchUserToAdmin();

			await createNewPost( {
				postType: 'page',
			} );

			await insertBlock( block.name );

			await page.waitForSelector( block.class );

			await expect( page ).toRenderBlock( block );

			await expect( page ).toMatchElement( '.wp-block-search__label', {
				text: 'Search',
			} );

			await expect( page ).toMatchElement(
				'.wp-block-search__input[value="Search products…"]'
			);
		} );
	}
);
