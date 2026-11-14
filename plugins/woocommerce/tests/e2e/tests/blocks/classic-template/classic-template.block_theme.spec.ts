/**
 * External dependencies
 */
import {
	test,
	expect,
	BlockData,
	Editor,
	BLOCK_THEME_SLUG,
} from '@woocommerce/e2e-utils';

const blockData: Partial< BlockData > = {
	name: 'woocommerce/legacy-template',
};

const getClassicTemplateBlocksInInserter = async ( {
	editor,
}: {
	editor: Editor;
} ) => {
	await editor.openGlobalBlockInserter();

	await editor.page
		.getByRole( 'searchbox', { name: 'Search' } )
		.fill( 'classic' );

	// Wait for blocks search to have finished.
	await expect(
		editor.page.getByRole( 'heading', {
			name: 'Available to install',
			exact: true,
		} )
	).toBeVisible();

	const inserterBlocks = editor.page.getByRole( 'listbox', {
		name: 'Blocks',
		exact: true,
	} );
	const options = inserterBlocks.locator( 'role=option' );

	// Filter out blocks that don't match one of the possible Classic Template block names (case-insensitive).
	const classicTemplateBlocks = await options.evaluateAll( ( elements ) => {
		const blockOptions = elements.filter( ( element ) => {
			return element.textContent === 'WooCommerce Classic Template';
		} );
		return blockOptions.map( ( element ) => element.textContent );
	} );

	return classicTemplateBlocks;
};

test.describe( `${ blockData.name } Block `, () => {
	test( `is not available in the inserter`, async ( { admin, editor } ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		const classicTemplateBlocks = await getClassicTemplateBlocksInInserter(
			{
				editor,
			}
		);

		expect( classicTemplateBlocks ).toHaveLength( 0 );
	} );
} );
