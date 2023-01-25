/**
 * External dependencies
 */
import {
	switchUserToAdmin,
	openDocumentSettingsSidebar,
} from '@wordpress/e2e-test-utils';

import {
	visitBlockPage,
	selectBlockByName,
	switchBlockInspectorTabWhenGutenbergIsInstalled,
} from '@woocommerce/blocks-test-utils';

const block = {
	name: 'Active Filters',
	slug: 'woocommerce/active-filters',
	class: '.wc-block-active-filters',
	title: 'Active filters',
};

describe( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );

	describe( 'attributes', () => {
		beforeEach( async () => {
			await openDocumentSettingsSidebar();
			await switchBlockInspectorTabWhenGutenbergIsInstalled( 'Settings' );
			await selectBlockByName( block.slug );
		} );

		it( "allows changing the block's title", async () => {
			const textareaSelector =
				'.wp-block-woocommerce-filter-wrapper .wp-block-heading';
			await expect( page ).toFill( textareaSelector, 'New Title' );
			await expect( page ).toMatchElement(
				'.wp-block-woocommerce-filter-wrapper .wp-block-heading',
				{ text: 'New Title' }
			);
			// reset
			await expect( page ).toFill( textareaSelector, block.title );
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
