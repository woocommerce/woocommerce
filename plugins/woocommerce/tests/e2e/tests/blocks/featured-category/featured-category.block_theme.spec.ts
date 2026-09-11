/**
 * External dependencies
 */
import path from 'path';
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

	test( 'image can be edited', async ( { editor, admin, requestUtils } ) => {
		let editedMediaId: number | undefined;
		const media = await requestUtils.uploadMedia(
			path.resolve( __dirname, '../../../test-data/images/image-01.png' )
		);

		try {
			await test.step( 'Create a product category with an image', async () => {
				const productCliOutput = await wpCLI(
					`post list --post_type=product --title=Cap --field=ID`
				);
				const productId =
					productCliOutput.stdout.match( /^\d+$/m )?.[ 0 ];
				if ( ! productId ) {
					throw new Error(
						`Failed to find Cap product: ${ productCliOutput.stdout }`
					);
				}

				const categoryCliOutput = await wpCLI(
					`wc product_cat create --name="Test Category" --slug="test-category" --image='{ "id": ${ media.id } }' --user=1`
				);
				const categoryId = categoryCliOutput.stdout
					.match( /\d+/g )
					?.pop();
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
			const editImageResponse = editor.page.waitForResponse(
				( response ) =>
					response.request().method() === 'POST' &&
					new URL( response.url() ).pathname ===
						`/wp-json/wp/v2/media/${ media.id }/edit`
			);
			await editor.page
				.getByRole( 'button', { name: 'Apply', exact: true } )
				.click();
			const editResponse = await editImageResponse;
			expect( editResponse.status() ).toBe( 201 );
			const editedMedia = await editResponse.json();
			if (
				typeof editedMedia.id !== 'number' ||
				! Number.isInteger( editedMedia.id )
			) {
				throw new Error( 'The image edit did not return a media ID.' );
			}
			editedMediaId = editedMedia.id;
			await expect(
				editor.canvas.locator(
					'img[alt="Test Category"][src*="-edited"]'
				)
			).toBeVisible();
		} finally {
			try {
				if ( editedMediaId !== undefined ) {
					await requestUtils.deleteMedia( editedMediaId );
				}
			} finally {
				await requestUtils.deleteMedia( media.id );
			}
		}
	} );
} );
