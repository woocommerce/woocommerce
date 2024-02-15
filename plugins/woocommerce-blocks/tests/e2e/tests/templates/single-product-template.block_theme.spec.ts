/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

test.describe( 'Single Product template', async () => {
	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
	} );

	test( 'shows password form in products protected with password', async ( {
		page,
	} ) => {
		// Sunglasses are defined as requiring password in /bin/scripts/products.sh.
		await page.goto( '/product/sunglasses/' );
		await expect(
			page.getByText( 'This content is password protected.' ).first()
		).toBeVisible();

		// Verify after introducing the password, the page is visible.
		await page.getByLabel( 'Password:' ).fill( 'password' );
		await page.getByRole( 'button', { name: 'Enter' } ).click();
		await expect(
			page.getByRole( 'link', { name: 'Description' } )
		).toBeVisible();
	} );

	test( 'loads the Single Product template for a specific product', async ( {
		admin,
		editorUtils,
		page,
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
		await admin.visitAdminPage(
			'site-editor.php',
			`path=/${ testData.templateType }`
		);
		await page.getByLabel( 'Add New Template' ).click();
		await page
			.getByRole( 'button', { name: 'Single item: Product' } )
			.click();
		await page
			.getByPlaceholder( 'Search products' )
			.fill( testData.productName );
		await page
			.getByRole( 'option', { name: testData.productName } )
			.click();
		await page.getByLabel( 'Fallback content' ).click();

		// Edit the template.
		await editorUtils.editor.insertBlock( {
			name: 'core/paragraph',
			attributes: { content: userText },
		} );
		await editorUtils.saveTemplate();

		// Verify edits are visible.
		await page.goto( testData.permalink );
		await expect( page.getByText( userText ).first() ).toBeVisible();

		// Revert edition.
		await admin.visitAdminPage(
			'site-editor.php',
			`path=/${ testData.templateType }/all`
		);
		await editorUtils.revertTemplateCreation( testData.templateName );
		await page.goto( testData.permalink );

		// Verify the edits are no longer visible.
		await expect( page.getByText( userText ) ).toHaveCount( 0 );
	} );
} );
