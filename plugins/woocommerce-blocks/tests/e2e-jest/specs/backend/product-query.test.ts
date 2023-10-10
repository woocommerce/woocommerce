/**
 * External dependencies
 */
import {
	getAllBlocks,
	switchUserToAdmin,
	canvas,
	openListView,
} from '@wordpress/e2e-test-utils';
import { visitBlockPage } from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import {
	insertBlockDontWaitForInsertClose,
	openSettingsSidebar,
} from '../../utils';

const block = {
	name: 'Products (Beta)',
	slug: 'woocommerce/product-query',
	class: '.wp-block-query',
};

// These tests are skipped and previously relied on GUTENBERG_EDITOR_CONTEXT.
describe.skip( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );
	} );

	it( 'can be inserted more than once', async () => {
		await insertBlockDontWaitForInsertClose( block.name );
		expect( await getAllBlocks() ).toHaveLength( 2 );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );

	/**
	 * We changed the “Show only products on sale” from a top-level toggle
	 * setting to a product filter, but tests for them haven't been updated
	 * yet. We will fix these tests in a follow-up PR.
	 */
	it.skip( 'Editor preview shows only on sale products after enabling `Show only products on sale`', async () => {
		await visitBlockPage( `${ block.name } Block` );
		const canvasEl = canvas();
		await openSettingsSidebar();
		await openListView();
		await page.click(
			'.block-editor-list-view-block__contents-container a.components-button'
		);
		const [ onSaleToggle ] = await page.$x(
			'//label[text()="Show only products on sale"]'
		);
		await onSaleToggle.click();
		await canvasEl.waitForSelector( `${ block.class } > p` );
		await canvasEl.waitForSelector(
			`${ block.class } > ul.wp-block-post-template`
		);
		const products = await canvasEl.$$(
			`${ block.class } ul.wp-block-post-template > li.block-editor-block-preview__live-content`
		);
		expect( products ).toHaveLength( 1 );
	} );
} );
