const { test } = require( '../../../../fixtures/block-editor-fixtures' );
const { expect } = require( '@playwright/test' );

const { clickOnTab } = require( '../../../../utils/simple-products' );

const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fadd-product';

const isTrackingSupposedToBeEnabled = !! process.env.ENABLE_TRACKING;

const productData = {
	name: `Simple product Name ${ new Date().getTime().toString() }`,
	summary: 'This is a product summary',
	productPrice: '100',
	salePrice: '90',
};

const categoryName = `my-category-${ new Date().getTime().toString() }`;

const tagName = `my-tag-${ new Date().getTime().toString() }`;

test.describe.configure( { mode: 'serial' } );

test.describe( 'General tab', () => {
	test.describe( 'Create product - Organization tab', () => {
		let productId;

		test.skip(
			isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test( 'can create a simple product with categories, tags and with password required', async ( {
			page,
		} ) => {
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			await clickOnTab( 'General', page );
			await page
				.getByPlaceholder( 'e.g. 12 oz Coffee Mug' )
				.fill( productData.name );
			await page
				.locator(
					'[data-template-block-id="basic-details"] .components-summary-control'
				)
				.last()
				.fill( productData.summary );

			await clickOnTab( 'Pricing', page );

			const regularPrice = page
				.locator( 'input[name="regular_price"]' )
				.first();
			await regularPrice.waitFor( { state: 'visible' } );
			await regularPrice.click();
			await regularPrice.fill( productData.productPrice );

			const salePrice = page
				.locator( 'input[name="sale_price"]' )
				.first();
			await salePrice.waitFor( { state: 'visible' } );
			await salePrice.click();
			await salePrice.fill( productData.salePrice );

			await clickOnTab( 'Organization', page );

			await page
				.locator( '[id^="woocommerce-taxonomy-select-"]' )
				.click();

			await page.locator( 'text=Create new' ).click();

			await page
				.locator( '[id^="taxonomy_name-"]' )
				.first()
				.fill( categoryName );

			await page
				.locator( '.woocommerce-create-new-taxonomy-modal__buttons' )
				.getByRole( 'button', {
					name: 'Create',
				} )
				.click();

			await page.locator( '[id^="tag-field-"]' ).click();

			await page.locator( 'text=Create new' ).click();

			await page
				.locator( '[id^="inspector-text-control-"]' )
				.first()
				.fill( tagName );

			await page
				.locator( '.woocommerce-create-new-tag-modal__buttons' )
				.getByRole( 'button', {
					name: 'Save',
				} )
				.click();

			await page
				.getByRole( 'checkbox', { name: 'Require a password' } )
				.first()
				.check();

			await page
				.locator( '[id^="post_password-"]' )
				.first()
				.fill( 'password' );

			await page
				.locator( '.woocommerce-product-header__actions' )
				.getByRole( 'button', {
					name: 'Publish',
				} )
				.click();

			// await page
			// 	.locator( '.woocommerce-product-publish-panel__header' )
			// 	.getByRole( 'button', {
			// 		name: 'Publish',
			// 	} )
			// 	.click();

			const element = page.locator( 'div.components-snackbar__content' );
			const textContent = await element.innerText();

			await expect( textContent ).toMatch( /Product published/ );

			const title = page.locator( '.woocommerce-product-header__title' );

			// Save product ID
			const productIdRegex = /product%2F(\d+)/;
			const url = page.url();
			const productIdMatch = productIdRegex.exec( url );
			productId = productIdMatch ? productIdMatch[ 1 ] : null;

			await expect( productId ).toBeDefined();
			await expect( title ).toHaveText( productData.name );

			await page.goto( `/?post_type=product&p=${ productId }` );

			await page
				.locator( 'input[name="post_password"]' )
				.fill( 'password' );

			await page.getByRole( 'button', { name: 'Enter' } ).click();

			await expect(
				page.getByRole( 'heading', { name: productData.name } )
			).toBeVisible();

			await expect(
				await page.getByRole( 'link', { name: categoryName } ).count()
			).toBeGreaterThan( 0 );

			await expect(
				page.getByRole( 'link', { name: tagName } )
			).toBeVisible();
		} );
	} );
} );
