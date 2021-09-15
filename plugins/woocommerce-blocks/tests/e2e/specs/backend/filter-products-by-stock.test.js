/**
 * Internal dependencies
 */
import { closeInserter, insertBlockDontWaitForInsertClose } from '../../utils';
import { findLabelWithText } from '../../../utils';

/**
 * External dependencies
 */
import {
	switchUserToAdmin,
	clickButton,
	getAllBlocks,
	openDocumentSettingsSidebar,
} from '@wordpress/e2e-test-utils';
import { visitBlockPage } from '@woocommerce/blocks-test-utils';

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

	it( 'can only be inserted once', async () => {
		await insertBlockDontWaitForInsertClose( block.name );
		await closeInserter();
		expect( await getAllBlocks() ).toHaveLength( 1 );
	} );

	describe( 'attributes', () => {
		beforeEach( async () => {
			await openDocumentSettingsSidebar();
			await page.click( block.class );
		} );

		it( 'product count can be toggled', async () => {
			const toggleLabel = await findLabelWithText( 'Product count' );
			await expect( toggleLabel ).toToggleElement(
				`${ block.class } .wc-filter-element-label-list-count`
			);
		} );

		it( 'filter button can be toggled', async () => {
			const toggleLabel = await findLabelWithText( 'Filter button' );
			await expect( toggleLabel ).toToggleElement(
				`${ block.class } .wc-block-filter-submit-button`
			);
		} );
	} );
} );
