const { test, expect } = require( '@playwright/test' );
const { variableProducts: utils, api } = require( '../../../../utils' );
const { showVariableProductTour } = utils;
const productPageURL = 'wp-admin/post-new.php?post_type=product';
const variableProductName = 'Variable Product with Three Variations';

let productId;

test.describe( 'Add variable product', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { browser } ) => {
		await showVariableProductTour( browser, true );
	} );

	test.afterAll( async ( { browser } ) => {
		await showVariableProductTour( browser, false );
		await api.deletePost.product( productId );
	} );

	test( 'can create a variable product', async ( { page } ) => {
		await test.step( 'Go to the "Add new product" page', async () => {
			await page.goto( productPageURL );
		} );

		await test.step( `Type "${ variableProductName }" into the "Product name" input field.`, async () => {
			const productNameTextbox = page.getByLabel( 'Product name' );
			const permalink = page.locator( '#sample-permalink' );

			await productNameTextbox.fill( variableProductName );
			await productNameTextbox.blur();
			await expect( permalink ).toBeVisible();
		} );

		await test.step( 'Select the "Variable product" product type.', async () => {
			await page.locator( '#product-type' ).selectOption( 'variable' );
		} );

		await test.step( 'Scroll into the "Attributes" tab and click it.', async () => {
			const attributesTab = page
				.locator( '.attribute_tab' )
				.getByRole( 'link', { name: 'Attributes' } );

			await attributesTab.scrollIntoViewIfNeeded();

			await attributesTab.click();
		} );

		// the tour only seems to display when not running headless, so just make sure
		const tourWasDisplayed =
			await test.step( 'See if the tour was displayed.', async () => {
				return await page
					.locator( '.woocommerce-tour-kit-step__heading' )
					.isVisible();
			} );

		if ( tourWasDisplayed ) {
			await test.step( 'Tour was displayed, so dismiss it.', async () => {
				await page
					.getByRole( 'button', { name: 'Close Tour' } )
					.click();
			} );

			await test.step( "Wait for the tour's dismissal to be saved", async () => {
				await page.waitForResponse(
					( response ) =>
						response.url().includes( '/users/' ) &&
						response.status() === 200
				);
			} );
		}

		await test.step( `Expect the "Variations" tab to appear`, async () => {
			const variationsTab = page.locator( 'li.variations_tab' );

			await expect( variationsTab ).toBeVisible();
		} );

		await test.step( 'Save draft.', async () => {
			await page.locator( '#save-post' ).click();
		} );

		await test.step( 'Expect the "Product draft updated." notice to appear.', async () => {
			await expect(
				page.getByText( 'Product draft updated.' )
			).toBeVisible();
		} );

		await test.step( 'Expect the product type to be "Variable product"', async () => {
			const selectedProductType = page.locator(
				'select#product-type [selected]'
			);

			await expect( selectedProductType ).toHaveText(
				'Variable product'
			);
		} );

		await test.step( 'Save product ID for clean up.', async () => {
			productId = page.url().match( /(?<=post=)\d+/ );
		} );
	} );
} );
