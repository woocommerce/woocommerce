/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */

const blockData = {
	name: 'Product Details',
	slug: 'woocommerce/product-details',
};

test.describe( `${ blockData.slug } Block`, () => {
	test( "block can't be inserted in Post Editor", async ( {
		editor,
		admin,
	} ) => {
		await admin.createNewPost();

		try {
			await editor.insertBlock( { name: blockData.slug } );
		} catch ( _error ) {
			// noop
		}

		await expect(
			await editor.getBlockByName( blockData.slug )
		).toBeHidden();
	} );

	test( 'block can be inserted in the Site Editor', async ( {
		admin,
		requestUtils,
		editor,
	} ) => {
		const template = await requestUtils.createTemplate( 'wp_template', {
			// Single Product Details block is addable only in Single Product Templates
			slug: 'single-product-v-neck-t-shirt',
			title: 'Sorter',
			content: 'howdy',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await expect( editor.canvas.getByText( 'howdy' ) ).toBeVisible();

		await editor.insertBlock( {
			name: blockData.slug,
		} );

		const block = await editor.getBlockByName( blockData.slug );

		await expect( block ).toHaveText(
			/This block lists description, attributes and reviews for a single product./
		);
	} );

	test( 'block hides tab title in content when toggle is off', async ( {
		admin,
		requestUtils,
		editor,
		page,
		frontendUtils,
	} ) => {
		let template;
		const maxRetries = 3;
		let retryCount = 0;
		let lastError;

		while ( retryCount < maxRetries ) {
			try {
				template = await requestUtils.createTemplate( 'wp_template', {
					slug: 'single-product-v-neck-t-shirt',
					title: 'Product Details Test',
					content: '',
				} );
			} catch ( verifyError ) {
				lastError = verifyError;
			}

			retryCount++;
			if ( retryCount < maxRetries ) {
				// Exponential backoff for retries
				await new Promise( ( resolve ) =>
					setTimeout( resolve, Math.pow( 2, retryCount ) * 200 )
				);
			}
		}

		if ( lastError ) {
			throw lastError;
		}

		if ( ! template ) {
			throw new Error( 'Template was not created.' );
		}

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( {
			name: blockData.slug,
		} );

		const block = await editor.getBlockByName( blockData.slug );
		await editor.selectBlocks( block );

		// Verify the "Description" h2 heading is visible by default.
		await expect(
			editor.canvas.locator( 'h2:has-text("Description")' )
		).toBeVisible();

		await editor.openDocumentSettingsSidebar();
		await page.getByText( 'Show tab title in content' ).click();

		// Verify the "Description" h2 heading has been hidden from the canvas.
		await expect(
			editor.canvas.locator( 'h2:has-text("Description")' )
		).toBeHidden();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await frontendUtils.goToShop();
		await page.getByText( 'V-Neck T-Shirt' ).click();

		// Verify the "Description" h2 heading is not in the page.
		await expect(
			page.locator( 'h2:has-text("Description")' )
		).toBeHidden();
	} );
} );
