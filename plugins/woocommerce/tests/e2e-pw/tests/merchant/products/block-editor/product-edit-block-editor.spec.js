const {
	test: baseTest,
} = require( '../../../../fixtures/block-editor-fixtures' );
const { expect } = require( '../../../../fixtures/fixtures' );

const test = baseTest.extend( {
	product: async ( { api }, use ) => {
		let product;

		await api
			.post( 'products', {
				id: 0,
				name: `Product ${ Date.now() }`,
				type: 'simple',
				description: `This product is a longer description of the awesome product ${ Date.now() }`,
				short_description: `This product is pretty awesome ${ Date.now() }`,
				regular_price: '12.99',
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		// Cleanup
		await api.delete( `products/${ product.id }`, { force: true } );
	},
} );

test( 'can update the general information of a product', async ( {
	page,
	product,
} ) => {
	await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );

	const updatedProduct = {
		name: `Product ${ Date.now() }`,
		description: `Updated description for the awesome product ${ Date.now() }`,
		short_description: `Updated summary for the awesome product ${ Date.now() }`,
	};

	const nameTextbox = page.getByLabel( 'Name' ).getByRole( 'textbox' );
	const summaryTextbox = page
		.getByLabel( 'Block: Product textarea block' )
		.getByRole( 'textbox' );
	const descriptionTextbox = page
		.getByLabel( 'Block: Product description' )
		.getByRole( 'textbox' );

	await test.step( 'edit the product name', async () => {
		await nameTextbox.fill( updatedProduct.name );
	} );

	await test.step( 'edit the product description and summary', async () => {
		// Need to clear the textbox before filling it, otherwise the text will be appended.
		await descriptionTextbox.clear();
		await descriptionTextbox.fill( updatedProduct.description );

		await summaryTextbox.clear();
		await summaryTextbox.fill( updatedProduct.short_description );
	} );

	await test.step( 'publish the updated product', async () => {
		await page.getByRole( 'button', { name: 'Update' } ).click();

		await expect( page.getByLabel( 'Dismiss this notice' ) ).toContainText(
			'Product updated'
		);
	} );

	await test.step( 'verify the changes', async () => {
		await expect.soft( nameTextbox ).toHaveValue( updatedProduct.name );

		await expect
			.soft( summaryTextbox )
			.toHaveText( updatedProduct.short_description );

		await expect
			.soft( descriptionTextbox )
			.toHaveText( updatedProduct.description );
	} );
} );

test.describe( 'Publish dropdown options', () => {
	test( 'can schedule a product publication', async ( { page, product } ) => {
		await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );

		await page
			.locator( '.woocommerce-product-header__actions' )
			.first()
			.locator( 'button[aria-label="More options"]' )
			.click();

		await page.getByText( 'Schedule publish' ).click();

		await expect(
			page.getByRole( 'heading', { name: 'Schedule product' } )
		).toBeVisible();

		await page
			.locator( '.woocommerce-schedule-publish-modal' )
			.locator( 'button[aria-label="View next month"]' )
			.click();

		await page
			.locator( '.woocommerce-schedule-publish-modal' )
			.getByText( '14' )
			.click();

		await page.getByRole( 'button', { name: 'Schedule' } ).click();

		await expect(
			page.getByLabel( 'Dismiss this notice' ).first()
		).toContainText( 'Product scheduled for' );
	} );
	test( 'can duplicate a product', async ( { page, product } ) => {
		await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );
		await page
			.locator( '.woocommerce-product-header__actions' )
			.first()
			.locator( 'button[aria-label="More options"]' )
			.click();

		await page.getByText( 'Copy to a new draft' ).click();

		await expect(
			page.getByLabel( 'Dismiss this notice' ).first()
		).toContainText( 'Product successfully duplicated' );

		await expect(
			page.getByRole( 'heading', { name: `${ product.name } (Copy)` } )
		).toBeVisible();

		await expect(
			page
				.locator( '.woocommerce-product-header__visibility-tags' )
				.getByText( 'Draft' )
				.first()
		).toBeVisible();

		await page
			.locator( '.woocommerce-product-header__actions' )
			.first()
			.locator( 'button[aria-label="More options"]' )
			.click();

		await page.getByText( 'Move to trash' ).click();
	} );
	test( 'can delete a product', async ( { page, product } ) => {
		await page.goto( `wp-admin/post.php?post=${ product.id }&action=edit` );
		await page
			.locator( '.woocommerce-product-header__actions' )
			.first()
			.locator( 'button[aria-label="More options"]' )
			.click();

		await page.getByText( 'Move to trash' ).click();

		await expect(
			page.getByRole( 'heading', { name: 'Products' } ).first()
		).toBeVisible();
	} );
} );
