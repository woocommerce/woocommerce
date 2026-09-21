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
		let productId: string | undefined;
		let originalThumbnailId: string | undefined;
		const media = await requestUtils.uploadMedia(
			path.resolve( __dirname, '../../../test-data/images/image-01.png' )
		);

		try {
			const productCliOutput = await wpCLI(
				'post list --post_type=product --title=Album --field=ID'
			);
			productId = productCliOutput.stdout.match( /^\d+$/m )?.[ 0 ];
			if ( ! productId ) {
				throw new Error(
					`Failed to find Album product: ${ productCliOutput.stdout }`
				);
			}

			// The uploaded media is deleted below, so Album's own thumbnail has to go
			// back or the product is left pointing at an attachment that no longer
			// exists. `get_post_thumbnail_id` returns 0 rather than erroring when the
			// meta key is absent, unlike `wp post meta get`.
			const thumbnailCliOutput = await wpCLI(
				`eval 'echo (string) get_post_thumbnail_id( ${ productId } );'`
			);
			// npm writes its own banner to stdout ahead of the command output, so
			// match the digits on their own line the way the product lookup above
			// does. Trimming the whole buffer keeps the banner, and feeding that
			// back into a shell command makes the restore fail on its own `>`.
			originalThumbnailId =
				thumbnailCliOutput.stdout.match( /^\d+$/m )?.[ 0 ];
			if ( ! originalThumbnailId ) {
				throw new Error(
					`Failed to read Album's thumbnail ID: ${ thumbnailCliOutput.stdout }`
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
				if ( productId && originalThumbnailId !== undefined ) {
					// get_post_thumbnail_id() echoes 0 when the meta key is absent.
					const hadThumbnail = originalThumbnailId !== '0';

					await wpCLI(
						hadThumbnail
							? `post meta update ${ productId } _thumbnail_id ${ originalThumbnailId }`
							: `post meta delete ${ productId } _thumbnail_id`
					);
				}
			} finally {
				try {
					if ( editedMediaId !== undefined ) {
						await requestUtils.deleteMedia( editedMediaId );
					}
				} finally {
					await requestUtils.deleteMedia( media.id );
				}
			}
		}
	} );
} );
