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
		// The inner blocks are added after the category loads. Publishing
		// before that saves the block without its title and button.
		await expect( blockLocator.getByText( 'Shop now' ) ).toBeVisible();
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
			const cliOutput = await wpCLI(
				'term list product_cat --slug=music --field=term_id'
			);
			const categoryId = Number(
				cliOutput.stdout.match( /^[1-9]\d*$/m )?.[ 0 ]
			);
			if ( ! categoryId ) {
				throw new Error(
					`Failed to find the Music category: ${ cliOutput.stdout }`
				);
			}

			// The block's own image overrides the category image, so the test
			// does not change any category that other specs read.
			await admin.createNewPost();
			await editor.insertBlock( {
				name: blockData.slug,
				attributes: {
					categoryId,
					mediaId: media.id,
					mediaSrc: media.source_url,
				},
			} );
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
				editor.canvas.locator( 'img[alt="Music"][src*="-edited"]' )
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
