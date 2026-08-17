/**
 * External dependencies
 */
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

	test( 'image can be edited', async ( { editor, admin } ) => {
		const productCliOutput = await wpCLI(
			'post list --post_type=product --title=Album --field=ID'
		);
		const productId = productCliOutput.stdout.match( /^\d+$/m )?.[ 0 ];
		if ( ! productId ) {
			throw new Error(
				`Failed to find Album product: ${ productCliOutput.stdout }`
			);
		}

		const mediaCliOutput = await wpCLI(
			'post list --post_type=attachment --title=image-01 --field=ID'
		);
		const mediaId = mediaCliOutput.stdout.match( /^\d+$/m )?.[ 0 ];
		if ( ! mediaId ) {
			throw new Error(
				`Failed to find image-01 attachment: ${ mediaCliOutput.stdout }`
			);
		}

		const originalMediaOutput = await wpCLI(
			`post meta get ${ productId } _thumbnail_id`
		);
		const originalMediaId =
			originalMediaOutput.stdout.match( /^\d+$/m )?.[ 0 ];
		if ( ! originalMediaId ) {
			throw new Error(
				`Failed to find Album thumbnail: ${ originalMediaOutput.stdout }`
			);
		}

		await wpCLI(
			`post meta update ${ productId } _thumbnail_id ${ mediaId }`
		);

		try {
			await admin.createNewPost();
			await editor.insertBlock( { name: blockData.slug } );
			const blockLocator = await editor.getBlockByName( blockData.slug );
			await blockLocator.getByText( 'Album' ).click();
			await blockLocator.getByText( 'Done' ).click();
			await editor.clickBlockToolbarButton( 'Edit product image' );
			await editor.clickBlockToolbarButton( 'Rotate' );
			await editor.page
				.getByRole( 'button', { name: 'Apply', exact: true } )
				.click();
			await expect(
				editor.canvas.locator( 'img[alt="Album"][src*="-edited"]' )
			).toBeVisible();
		} finally {
			await wpCLI(
				`post meta update ${ productId } _thumbnail_id ${ originalMediaId }`
			);
		}
	} );
} );
