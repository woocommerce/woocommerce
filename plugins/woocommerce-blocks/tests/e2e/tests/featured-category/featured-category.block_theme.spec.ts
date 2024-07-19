/**
 * External dependencies
 */
import { test, expect, wpCLI } from '@woocommerce/e2e-utils';

const blockData = {
	slug: 'woocommerce/featured-category',
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
		await blockLocator.getByText( 'Music' ).click();
		await blockLocator.getByText( 'Done' ).click();
		await editor.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect( blockLocatorFrontend ).toBeVisible();
		await expect( blockLocatorFrontend.getByText( 'Music' ) ).toBeVisible();
		await expect(
			blockLocatorFrontend.getByText( 'Shop now' )
		).toBeVisible();
	} );

	test( 'image can be edited', async ( { editor, admin } ) => {
		await test.step( 'Create a product category with an image', async () => {
			// Get the id of the image associated to the Cap product (for example).
			const productCliOutput = await wpCLI(
				`post list --post_type=product --title=Cap --field=ID`
			);
			const productId = productCliOutput.stdout.match( /\d+/g )?.pop();
			const mediaCliOutput = await wpCLI(
				`post meta get ${ productId } _thumbnail_id`
			);
			const mediaId = mediaCliOutput.stdout.match( /\d+/g )?.pop();

			// Create a product category with that image.
			const categoryCliOutput = await wpCLI(
				`wc product_cat create --name="Test Category" --slug="test-category" --image='{ "id": ${ mediaId } }' --user=1`
			);
			const categoryId = categoryCliOutput.stdout.match( /\d+/g )?.pop();
			await wpCLI(
				`wc product update ${ productId } --categories='[ { "id": ${ categoryId } } ]' --user=1`
			);
		} );

		await admin.createNewPost();
		await editor.insertBlock( { name: blockData.slug } );
		const blockLocator = await editor.getBlockByName( blockData.slug );
		await blockLocator.getByText( 'Test Category' ).click();
		await blockLocator.getByText( 'Done' ).click();
		await editor.page.getByLabel( 'Edit category image' ).click();
		await editor.page.getByLabel( 'Rotate' ).click();
		await editor.page.getByRole( 'button', { name: 'Apply' } ).click();
		await expect(
			editor.page.locator( 'img[alt="Test Category"][src*="-edited"]' )
		).toBeVisible();
	} );
} );
