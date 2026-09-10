/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

const blockData = {
	name: 'Product Categories List',
	slug: 'woocommerce/product-categories',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( 'can be inserted in Post Editor and it is visible on the frontend', async ( {
		editor,
		admin,
		frontendUtils,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.slug } );
		const blockLocator = await editor.getBlockByName( blockData.slug );
		await expect( blockLocator.getByRole( 'listitem' ) ).toHaveCount( 6 );
		await editor.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect(
			blockLocatorFrontend.getByRole( 'listitem' )
		).toHaveCount( 6 );
	} );

	test.describe( 'inspector controls', () => {
		test.beforeEach( async ( { admin, editor } ) => {
			await admin.createNewPost();
			await editor.insertBlock( { name: blockData.slug } );
			await editor.openDocumentSettingsSidebar();
		} );

		test( 'Display style renders the categories as a dropdown', async ( {
			page,
			editor,
			frontendUtils,
		} ) => {
			const dropdown = '.wc-block-product-categories__dropdown';
			const blockInEditor = await editor.getBlockByName( blockData.slug );
			await expect( blockInEditor.getByRole( 'listitem' ) ).toHaveCount(
				6
			);
			await expect( blockInEditor.locator( dropdown ) ).toHaveCount( 0 );

			await page
				.getByRole( 'region', { name: 'Editor settings' } )
				.getByRole( 'radio', { name: 'Dropdown' } )
				.click();
			await editor.publishAndVisitPost();

			const block = await frontendUtils.getBlockByName( blockData.slug );
			await expect( block.locator( dropdown ) ).toBeVisible();
			await expect( block.getByRole( 'listitem' ) ).toHaveCount( 0 );
		} );

		test( 'Show product count hides the counts when disabled', async ( {
			page,
			editor,
			frontendUtils,
		} ) => {
			const count = '.wc-block-product-categories-list-item-count';
			const blockInEditor = await editor.getBlockByName( blockData.slug );
			await expect(
				blockInEditor.locator( count ).first()
			).toBeVisible();

			await page
				.getByRole( 'region', { name: 'Editor settings' } )
				.getByLabel( 'Show product count' )
				.uncheck();
			await editor.publishAndVisitPost();

			const block = await frontendUtils.getBlockByName( blockData.slug );
			await expect( block.locator( count ) ).toHaveCount( 0 );
		} );

		test( 'Show category images renders an image per category', async ( {
			page,
			editor,
			frontendUtils,
		} ) => {
			const image = '.wc-block-product-categories-list-item__image';
			const blockInEditor = await editor.getBlockByName( blockData.slug );
			await expect( blockInEditor.locator( image ) ).toHaveCount( 0 );

			await page
				.getByRole( 'region', { name: 'Editor settings' } )
				.getByLabel( 'Show category images' )
				.check();
			await editor.publishAndVisitPost();

			const block = await frontendUtils.getBlockByName( blockData.slug );
			await expect( block.locator( image ) ).toHaveCount( 6 );
		} );

		test( 'Show hierarchy flattens the list when disabled', async ( {
			page,
			editor,
			frontendUtils,
		} ) => {
			const nested = '.wc-block-product-categories-list--depth-1';
			const blockInEditor = await editor.getBlockByName( blockData.slug );
			await expect( blockInEditor.locator( nested ) ).toBeVisible();

			await page
				.getByRole( 'region', { name: 'Editor settings' } )
				.getByLabel( 'Show hierarchy' )
				.uncheck();
			await editor.publishAndVisitPost();

			const block = await frontendUtils.getBlockByName( blockData.slug );
			await expect( block.locator( nested ) ).toHaveCount( 0 );
			await expect( block.getByRole( 'listitem' ) ).toHaveCount( 6 );
		} );
	} );
} );
