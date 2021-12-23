/**
 * External dependencies
 */
import {
	getEditedPostContent,
	getAllBlocks,
	switchUserToAdmin,
	openDocumentSettingsSidebar,
} from '@wordpress/e2e-test-utils';

import {
	visitBlockPage,
	selectBlockByName,
} from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import { insertBlockDontWaitForInsertClose } from '../../utils';

const block = {
	name: 'Active Product Filters',
	slug: 'woocommerce/active-filters',
	class: '.wc-block-active-filters',
};

describe( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );
	} );

	it( 'can only be inserted once', async () => {
		await insertBlockDontWaitForInsertClose( block.name );
		expect( await getAllBlocks() ).toHaveLength( 1 );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );

	describe( 'attributes', () => {
		beforeEach( async () => {
			await openDocumentSettingsSidebar();
			await selectBlockByName( block.slug );
		} );

		it( "allows changing the block's title", async () => {
			const textareaSelector = `.wp-block[data-type="${ block.slug }"] textarea.wc-block-editor-components-title`;
			await expect( page ).toFill( textareaSelector, 'New Title' );
			await page.click(
				'.components-toolbar button[aria-label="Heading 6"]'
			);
			await expect(
				page
			).toMatchElement(
				`.wp-block[data-type="${ block.slug }"] h6 textarea`,
				{ text: 'New Title' }
			);
			// reset
			await expect( page ).toFill( textareaSelector, block.name );
			await page.click(
				'.components-toolbar button[aria-label="Heading 3"]'
			);
		} );

		it( 'allows changing the Display Style', async () => {
			// Click the button to convert the display style to Chips.
			await expect( page ).toClick( 'button', { text: 'Chips' } );
			await expect( page ).toMatchElement(
				'.wc-block-active-filters__list.wc-block-active-filters__list--chips'
			);

			// Click the button to convert the display style to List.
			await expect( page ).toClick( 'button', { text: 'List' } );
			await expect( page ).not.toMatchElement(
				'.wc-block-active-filters__list.wc-block-active-filters__list--chips'
			);
		} );
	} );
} );
