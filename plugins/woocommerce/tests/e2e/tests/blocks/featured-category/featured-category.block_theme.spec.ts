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
		let productId: string | undefined;
		let categoryId: string | undefined;
		let originalCategoryIds: string[] | undefined;
		const media = await requestUtils.uploadMedia(
			path.resolve( __dirname, '../../../test-data/images/image-01.png' )
		);

		try {
			await test.step( 'Create a product category with an image', async () => {
				const productCliOutput = await wpCLI(
					`post list --post_type=product --title=Cap --field=ID`
				);
				productId = productCliOutput.stdout.match( /^\d+$/m )?.[ 0 ];
				if ( ! productId ) {
					throw new Error(
						`Failed to find Cap product: ${ productCliOutput.stdout }`
					);
				}

				// `wc product update --categories` replaces the list rather than
				// appending to it, so Cap's own categories have to come back below.
				//
				// npm writes its own banner to stdout ahead of the command output,
				// and a product with no categories prints nothing at all, so the
				// value goes inside a marker that can be matched on its own line.
				// The banner echoes the command back, marker text included, but
				// always behind a "> " prefix, which the anchor excludes.
				const categoriesCliOutput = await wpCLI(
					`eval 'echo "CATEGORY_IDS[" . implode( ",", wp_get_post_terms( ${ productId }, "product_cat", array( "fields" => "ids" ) ) ) . "]";'`
				);
				const capturedCategoryIds = categoriesCliOutput.stdout.match(
					/^CATEGORY_IDS\[([\d,]*)\]$/m
				);
				if ( ! capturedCategoryIds ) {
					throw new Error(
						`Failed to read Cap's product categories: ${ categoriesCliOutput.stdout }`
					);
				}
				originalCategoryIds = capturedCategoryIds[ 1 ]
					.split( ',' )
					.filter( Boolean );

				// --porcelain prints only the new term id. Match it on its own line,
				// like productId above: without that, the last number anywhere in
				// stdout wins, and this id is force-deleted in the finally below.
				const categoryCliOutput = await wpCLI(
					`wc product_cat create --name="Test Category" --slug="test-category" --image='{ "id": ${ media.id } }' --porcelain --user=1`
				);
				categoryId =
					categoryCliOutput.stdout.match( /^[1-9]\d*$/m )?.[ 0 ];
				if ( ! categoryId ) {
					throw new Error(
						`Failed to read the created category id: ${ categoryCliOutput.stdout }`
					);
				}
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
				if ( productId && originalCategoryIds ) {
					const categories = originalCategoryIds
						.map( ( id ) => `{ "id": ${ id } }` )
						.join( ', ' );
					await wpCLI(
						`wc product update ${ productId } --categories='[ ${ categories } ]' --user=1`
					);
				}
			} finally {
				try {
					if ( categoryId ) {
						await wpCLI(
							`wc product_cat delete ${ categoryId } --force=true --user=1`
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
		}
	} );
} );
