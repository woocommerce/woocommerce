/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

test.describe( 'Single Product template', () => {
	test( 'loads the Single Product template for a specific product', async ( {
		admin,
		editor,
		page,
		wpCoreVersion,
	} ) => {
		const testData = {
			productName: 'Belt',
			permalink: '/product/belt',
			templateName: 'Product: Belt',
			templatePath: 'single-product-belt',
			templateType: 'wp_template',
		};
		const userText = 'Hello World in the Belt template';

		// Create the specific product template.
		await admin.visitSiteEditor( { path: `/${ testData.templateType }` } );

		await page
			.getByRole( 'button', {
				name:
					wpCoreVersion >= 6.8 ? 'Add Template' : 'Add New Template',
			} )
			.click();

		await page
			.getByRole( 'button', { name: 'Single item: Product' } )
			.click();

		if ( wpCoreVersion >= 6.9 ) {
			await page
				.getByRole( 'button', {
					name: 'Product For a specific item',
				} )
				.click();
		}

		await page
			.getByPlaceholder( 'Search products' )
			.fill( testData.productName );
		await page
			.getByRole( 'option', { name: testData.productName } )
			.click();
		await page.getByLabel( 'Close', { exact: true } ).click();

		// Edit the template.
		await editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: userText },
		} );
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		// Verify edits are visible.
		await page.goto( testData.permalink );
		await expect( page.getByText( userText ).first() ).toBeVisible();

		// Revert edition.
		await admin.visitSiteEditor( {
			postType: testData.templateType,
		} );
		// Templates added via "New template" display the path as the name since WP 6.9.
		const templateName =
			wpCoreVersion >= 6.9
				? testData.templatePath
				: testData.templateName;
		await editor.revertTemplate( { templateName } );
		await page.goto( testData.permalink );

		// Verify the edits are no longer visible.
		await expect( page.getByText( userText ) ).toHaveCount( 0 );
	} );
} );
