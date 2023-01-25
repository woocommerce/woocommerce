/**
 * External dependencies
 */
import {
	switchUserToAdmin,
	openDocumentSettingsSidebar,
} from '@wordpress/e2e-test-utils';
import {
	visitBlockPage,
	switchBlockInspectorTabWhenGutenbergIsInstalled,
} from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import { findLabelWithText } from '../../../utils';

const block = {
	name: 'Filter Products by Stock',
	slug: 'woocommerce/stock-filter',
	class: '.wc-block-stock-filter',
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
			await page.click( block.class );
		} );

		it( 'product count can be toggled', async () => {
			const toggleLabel = await findLabelWithText(
				'Display product count'
			);
			await expect( toggleLabel ).toToggleElement(
				`${ block.class } .wc-filter-element-label-list-count`
			);
		} );

		it( 'filter button can be toggled', async () => {
			const toggleLabel = await findLabelWithText( 'Apply filters' );
			await expect( toggleLabel ).toToggleElement(
				`${ block.class } .wc-block-filter-submit-button`
			);
		} );

		it( 'allows changing the Display Style', async () => {
			// Turn the display style to Dropdown
			await expect( page ).toClick( 'button', { text: 'Dropdown' } );

			await expect( page ).toMatchElement(
				'.wc-block-stock-filter.style-dropdown'
			);
			// Turn the display style to List
			await expect( page ).toClick( 'button', {
				text: 'List',
			} );

			await expect( page ).toMatchElement(
				'.wc-block-stock-filter.style-list'
			);
		} );
	} );
} );
