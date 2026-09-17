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

	/* eslint-disable playwright/no-conditional-in-test -- Fixture validation and cleanup must handle partial setup failures. */
	test( 'image can be edited through Cover and persists after saving', async ( {
		editor,
		admin,
		requestUtils,
		frontendUtils,
	} ) => {
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
			const cover = await editor.getBlockByName( 'core/cover' );
			const image = cover.locator(
				'img.wp-block-cover__image-background'
			);
			await expect( image ).toBeVisible();
			const originalSrc = await image.getAttribute( 'src' );
			await editor.selectBlocks( cover );
			await editor.clickBlockToolbarButton( 'Replace' );
			await editor.page
				.getByRole( 'menuitem', { name: 'Open Media Library' } )
				.click();
			const mediaDialog = editor.page.getByRole( 'dialog' );
			await mediaDialog
				.getByRole( 'tab', { name: 'Media Library' } )
				.click();
			await mediaDialog
				.getByRole( 'link', { name: 'Edit Image', exact: true } )
				.click();
			// Wait for the image editor's initial focus, which otherwise closes the rotation menu.
			await expect(
				mediaDialog.getByRole( 'button', { name: /^Crop/ } )
			).toBeFocused();
			await mediaDialog
				.getByRole( 'button', { name: /Image Rotation/ } )
				.click();
			await mediaDialog
				.getByRole( 'button', { name: /Rotate 90° left/ } )
				.click();
			await mediaDialog
				.getByRole( 'button', { name: 'Save Edits', exact: true } )
				.click();
			await mediaDialog
				.getByRole( 'button', { name: 'Select', exact: true } )
				.click();
			await expect( image ).toHaveAttribute( 'src', /-e\d+/ );
			await expect( image ).not.toHaveAttribute( 'src', originalSrc! );
			const editedSrc = await image.getAttribute( 'src' );

			const postId = await editor.publishPost();
			await editor.page.goto( `/?p=${ postId }` );
			const frontendBlock = await frontendUtils.getBlockByName(
				blockData.slug
			);
			const frontendImage = frontendBlock.locator(
				'img.wp-block-cover__image-background'
			);
			await expect( frontendImage ).toBeVisible();
			await expect( frontendImage ).toHaveAttribute( 'src', editedSrc! );

			await admin.editPost( postId );
			await expect( image ).toBeVisible();
			await expect( image ).toHaveAttribute( 'src', editedSrc! );
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
					// The Media Library editor changes the uploaded attachment in place.
					await requestUtils.deleteMedia( media.id );
				}
			}
		}
	} );
	/* eslint-enable playwright/no-conditional-in-test */
} );
