/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */

const blockData = {
	slug: 'woocommerce/breadcrumbs',
	name: 'Store Breadcrumbs',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( "block can't be inserted in Post Editor", async ( {
		admin,
		editor,
	} ) => {
		await admin.createNewPost();
		await expect(
			editor.insertBlock( { name: blockData.slug } )
		).rejects.toThrow(
			new RegExp( `Block type '${ blockData.slug }' is not registered.` )
		);
	} );

	test( 'block should be already added in the Product Catalog Template', async ( {
		editorUtils,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
		const alreadyPresentBlock = await editorUtils.getBlockByName(
			blockData.slug
		);

		await expect( alreadyPresentBlock ).toHaveText(
			'Breadcrumbs / Navigation / Path'
		);
	} );

	test( 'block can be inserted in the Site Editor', async ( {
		admin,
		requestUtils,
		editorUtils,
		editor,
	} ) => {
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: 'sorter',
			title: 'Sorter',
			content: 'howdy',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
		} );

		await editorUtils.enterEditMode();

		await editor.insertBlock( {
			name: blockData.slug,
		} );

		const block = await editorUtils.getBlockByName( blockData.slug );

		await expect( block ).toHaveText( 'Breadcrumbs / Navigation / Path' );
	} );
} );
