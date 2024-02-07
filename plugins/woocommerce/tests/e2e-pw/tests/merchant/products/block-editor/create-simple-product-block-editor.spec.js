const { test, expect } = require( '@playwright/test' );

const {
	clickOnTab,
	isBlockProductEditorEnabled,
	toggleBlockProductEditor,
} = require( '../../../../utils/simple-products' );

const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fadd-product';

let isNewProductEditorEnabled = false;

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
		test.use( { storageState: process.env.ADMINSTATE } );

		test.beforeEach( async ( { browser } ) => {
			const context = await browser.newContext();
			const page = await context.newPage();
			isNewProductEditorEnabled = await isBlockProductEditorEnabled(
				page
			);
			if ( ! isNewProductEditorEnabled ) {
				await toggleBlockProductEditor( 'enable', page );
			}
		} );

		test.afterEach( async ( { browser } ) => {
			const context = await browser.newContext();
			const page = await context.newPage();
			isNewProductEditorEnabled = await isBlockProductEditorEnabled(
				page
			);
			if ( isNewProductEditorEnabled ) {
				await toggleBlockProductEditor( 'disable', page );
			}
		} );

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
		test.use( { storageState: process.env.ADMINSTATE } );

		test.skip(
			isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test.beforeEach( async ( { browser } ) => {
			const context = await browser.newContext();
			const page = await context.newPage();
			isNewProductEditorEnabled = await isBlockProductEditorEnabled(
				page
			);
			if ( ! isNewProductEditorEnabled ) {
				await toggleBlockProductEditor( 'enable', page );
			}
		} );

		test.afterEach( async ( { browser } ) => {
			const context = await browser.newContext();
			const page = await context.newPage();
			isNewProductEditorEnabled = await isBlockProductEditorEnabled(
				page
			);
			if ( isNewProductEditorEnabled ) {
				await toggleBlockProductEditor( 'disable', page );
			}
		} );

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
			await page
				.locator(
					'[id^="wp-block-woocommerce-product-regular-price-field"]'
				)
				.first()
				.fill( productData.productPrice );
			await page
				.locator(
					'[id^="wp-block-woocommerce-product-sale-price-field"]'
				)
				.first()
				.fill( productData.salePrice );

			await page
				.locator( '.woocommerce-product-header__actions' )
				.getByRole( 'button', {
					name: 'Add',
				} )
				.click();

			const element = page.locator( 'div.components-snackbar__content' );
			const textContent = await element.innerText();

			await expect( textContent ).toMatch( /Product added/ );

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
			await page
				.locator(
					'[id^="wp-block-woocommerce-product-regular-price-field"]'
				)
				.first()
				.fill( productData.productPrice );
			await page
				.locator( '.woocommerce-product-header__actions' )
				.getByRole( 'button', {
					name: 'Add',
				} )
				.click();

			const element = page.locator( 'div.components-snackbar__content' );
			const textContent = await element.innerText();

			await expect( textContent ).toMatch( /Invalid or duplicated SKU./ );
		} );
		test( 'can a shopper add the simple product to the cart', async ( {
			page,
		} ) => {
			await page.goto( `/?post_type=product&p=${ productId }`, {
				waitUntil: 'networkidle',
			} );
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
			await page.waitForLoadState( 'networkidle' );
			await expect(
				page.locator( `a.remove[data-product_id='${ productId }']` )
			).toBeHidden();
		} );
	} );
} );
