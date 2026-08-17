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
			// Use an uploaded fixture whose source file is present in wp-env.
			const mediaCliOutput = await wpCLI(
				'post list --post_type=attachment --title=image-01 --field=ID'
			);
			const mediaId = mediaCliOutput.stdout.match( /^\d+$/m )?.[ 0 ];
			if ( ! mediaId ) {
				throw new Error(
					`Failed to find image-01 attachment: ${ mediaCliOutput.stdout }`
				);
			}

			const productCliOutput = await wpCLI(
				`post list --post_type=product --title=Cap --field=ID`
			);
			const productId = productCliOutput.stdout.match( /^\d+$/m )?.[ 0 ];
			if ( ! productId ) {
				throw new Error(
					`Failed to find Cap product: ${ productCliOutput.stdout }`
				);
			}

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
		await editor.clickBlockToolbarButton( 'Edit category image' );
		await editor.clickBlockToolbarButton( 'Rotate' );
		await editor.page
			.getByRole( 'button', { name: 'Apply', exact: true } )
			.click();
		await expect(
			editor.canvas.locator( 'img[alt="Test Category"][src*="-edited"]' )
		).toBeVisible();
	} );
} );
