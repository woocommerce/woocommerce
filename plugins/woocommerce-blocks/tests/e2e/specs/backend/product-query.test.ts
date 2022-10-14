/**
 * External dependencies
 */
import {
	getAllBlocks,
	switchUserToAdmin,
	canvas,
	openDocumentSettingsSidebar,
	openListView,
	setPostContent,
	insertBlock,
} from '@wordpress/e2e-test-utils';
import { visitBlockPage } from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import {
	insertBlockDontWaitForInsertClose,
	GUTENBERG_EDITOR_CONTEXT,
	describeOrSkip,
} from '../../utils';

const block = {
	name: 'Product Query',
	slug: 'woocommerce/product-query',
	class: '.wp-block-query',
};

describeOrSkip( GUTENBERG_EDITOR_CONTEXT === 'gutenberg' )(
	`${ block.name } Block`,
	() => {
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

		it( 'Editor preview shows only on sale products after enabling `Show only products on sale`', async () => {
			await visitBlockPage( `${ block.name } Block` );
			const canvasEl = canvas();
			await openDocumentSettingsSidebar();
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

		describe( 'On Sale variation', () => {
			beforeAll( async () => {
				await visitBlockPage( `${ block.name } Block` );
				await setPostContent( '' );
				await insertBlock( 'Products on Sale' );
			} );

			it( 'Show only on sale products', async () => {
				const canvasEl = canvas();
				await canvasEl.waitForSelector(
					`${ block.class } > ul.wp-block-post-template`
				);
				const products = await canvasEl.$$(
					`${ block.class } ul.wp-block-post-template > li.block-editor-block-preview__live-content`
				);
				expect( products ).toHaveLength( 1 );
			} );

			it( 'Does not have on sale toggle', async () => {
				await openDocumentSettingsSidebar();
				await openListView();
				await page.click(
					'.block-editor-list-view-block__contents-container a.components-button'
				);
				await expect( page ).not.toMatchElement(
					'.block-editor-block-inspector',
					{
						text: 'Show only products on sale',
					}
				);
			} );
		} );
	}
);
