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

test.describe.configure( { mode: 'serial' } );

test.describe( 'General tab', () => {
	test.describe( 'Simple product form', () => {
		test( 'renders each block without error', async ( { page } ) => {
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			await clickOnTab( 'General', page );
			await page.getByPlaceholder( 'e.g. 12 oz Coffee Mug' ).isVisible();

			await expect( page.locator( '.block-editor-warning' ) ).toHaveCount(
				0
			);
		} );
	} );

	test.describe( 'Create product', () => {
		let productId;

		test.skip(
			isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test( 'can create a simple product', async ( { page } ) => {
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

			await page
				.locator( '.woocommerce-product-header__actions' )
				.getByRole( 'button', {
					name: 'Publish',
				} )
				.click();

			await page
				.locator( '.woocommerce-product-publish-panel__header' )
				.getByRole( 'button', {
					name: 'Publish',
				} )
				.click();

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
		} );

		test( 'can not create a product with duplicated SKU', async ( {
			page,
		} ) => {
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			await clickOnTab( 'General', page );
			await page
				.locator( '//input[@placeholder="e.g. 12 oz Coffee Mug"]' )
				.fill( productData.name );
			await page
				.locator(
					'[data-template-block-id="basic-details"] .components-summary-control'
				)
				.fill( productData.summary );

			await clickOnTab( 'Pricing', page );
			await page
				.locator(
					'[id^="wp-block-woocommerce-product-regular-price-field"]'
				)
				.first()
				.fill( productData.productPrice );
			await page
				.locator( '.woocommerce-product-header__actions' )
				.getByRole( 'button', {
					name: 'Publish',
				} )
				.click();

			await page
				.locator( '.woocommerce-product-publish-panel__header' )
				.getByRole( 'button', {
					name: 'Publish',
				} )
				.click();

			const element = page.locator( 'div.components-snackbar__content' );
			const textContent = await element.innerText();

			await expect( textContent ).toMatch( /Invalid or duplicated SKU./ );
		} );

		test( 'can a shopper add the simple product to the cart', async ( {
			page,
		} ) => {
			await page.goto( `/?post_type=product&p=${ productId }` );
			await expect(
				page.getByRole( 'heading', { name: productData.name } )
			).toBeVisible();
			const productPriceElements = await page
				.locator( '.summary .woocommerce-Price-amount' )
				.all();

			let foundProductPrice = false;
			let foundSalePrice = false;
			for ( const element of productPriceElements ) {
				const textContent = await element.innerText();
				if ( textContent.includes( productData.productPrice ) ) {
					foundProductPrice = true;
				}
				if ( textContent.includes( productData.salePrice ) ) {
					foundSalePrice = true;
				}
			}
			await expect( foundProductPrice && foundSalePrice ).toBeTruthy();

			await page.getByRole( 'button', { name: 'Add to cart' } ).click();
			await page.getByRole( 'link', { name: 'View cart' } ).click();
			await expect(
				page.locator( 'td[data-title=Product]' ).first()
			).toContainText( productData.name );
			await page
				.locator( `a.remove[data-product_id='${ productId }']` )
				.click();
			await expect(
				page.locator( `a.remove[data-product_id='${ productId }']` )
			).toBeHidden();
		} );
	} );
} );
