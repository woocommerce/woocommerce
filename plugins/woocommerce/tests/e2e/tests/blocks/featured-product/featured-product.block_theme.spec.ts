/**
 * External dependencies
 */
import path from 'path';
import { expect, test, wpCLI } from '@woocommerce/e2e-utils';

const blockData = {
	slug: 'woocommerce/featured-product',
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
		await blockLocator.getByText( 'Album' ).click();
		await blockLocator.getByText( 'Done' ).click();
		await editor.publishAndVisitPost();
		const blockLocatorFrontend = await frontendUtils.getBlockByName(
			blockData.slug
		);
		await expect( blockLocatorFrontend ).toBeVisible();
		await expect( blockLocatorFrontend.getByText( 'Album' ) ).toBeVisible();
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
			const productCliOutput = await wpCLI(
				'post list --post_type=product --title=Album --field=ID'
			);
			const productId = productCliOutput.stdout.match( /^\d+$/m )?.[ 0 ];
			if ( ! productId ) {
				throw new Error(
					`Failed to find Album product: ${ productCliOutput.stdout }`
				);
			}

			await wpCLI(
				`post meta update ${ productId } _thumbnail_id ${ media.id }`
			);

			await admin.createNewPost();
			await editor.insertBlock( { name: blockData.slug } );
			const blockLocator = await editor.getBlockByName( blockData.slug );
			await blockLocator.getByText( 'Album' ).click();
			await blockLocator.getByText( 'Done' ).click();
			await editor.clickBlockToolbarButton( 'Edit product image' );
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
				editor.canvas.locator( 'img[alt="Album"][src*="-edited"]' )
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
