/**
 * External dependencies
 */
import {
	switchBlockInspectorTab,
	switchUserToAdmin,
} from '@wordpress/e2e-test-utils';
import {
	visitBlockPage,
	selectBlockByName,
} from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import { openSettingsSidebar } from '../../utils.js';

const block = {
	name: 'Active Filters',
	slug: 'woocommerce/active-filters',
	class: '.wc-block-active-filters',
	title: 'Active filters',
};

describe.skip( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );

	describe( 'attributes', () => {
		beforeEach( async () => {
			await openSettingsSidebar();
			await selectBlockByName( block.slug );
			await switchBlockInspectorTab( 'Settings' );
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
