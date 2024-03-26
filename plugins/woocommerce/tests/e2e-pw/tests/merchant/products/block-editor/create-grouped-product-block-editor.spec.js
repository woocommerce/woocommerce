const { test } = require( '../../../../fixtures/block-editor-fixtures' );
const { expect } = require( '@playwright/test' );

const { clickOnTab } = require( '../../../../utils/simple-products' );
const { api } = require( '../../../../utils' );

const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fadd-product';

const isTrackingSupposedToBeEnabled = !! process.env.ENABLE_TRACKING;

const productData = {
	name: `Grouped product Name ${ new Date().getTime().toString() }`,
	summary: 'This is a product summary',
};

const groupedProductsData = [
	{
		name: `Product name 1 ${ new Date().getTime().toString() }`,
		productPrice: '400',
		type: 'simple',
	},
	{
		name: `Product name 2 ${ new Date().getTime().toString() }`,
		productPrice: '600',
		type: 'simple',
	},
];

test.describe.configure( { mode: 'serial' } );
const productIds = [];

test.describe( 'General tab', () => {
	test.describe( 'Grouped product', () => {
		test.beforeAll( async () => {
			for ( const product of groupedProductsData ) {
				const id = await api.create.product( product );
				productIds.push( id );
			}
		} );

		test.afterAll( async () => {
			for ( const productId of productIds ) {
				await api.deletePost.product( productId );
			}
		} );
		test.skip(
			isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test( 'can create a grouped product', async ( { page } ) => {
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
				.getByRole( 'button', {
					name: 'Change product type',
				} )
				.click();

			const groupedProductDropdown = page
				.locator( '.components-dropdown__content' )
				.getByText( 'Grouped product' )
				.first();
			await groupedProductDropdown.click();

			await page.waitForResponse(
				( response ) =>
					response.url().includes( '/wp-json/wc/v3/products/' ) &&
					response.status() === 200
			);

			await page
				.locator( '[data-title="Product section"]' )
				.getByText( 'Add products' )
				.click();

			await page
				.getByRole( 'heading', {
					name: 'Add products to this group',
				} )
				.isVisible();

			for ( const product of groupedProductsData ) {
				await page
					.locator(
						'.woocommerce-add-products-modal__form-group-content'
					)
					.getByPlaceholder( 'Search for products' )
					.fill( product.name );

				await page.getByText( product.name ).click();
			}

			await page
				.locator( '.woocommerce-add-products-modal__actions' )
				.getByRole( 'button', {
					name: 'Add',
				} )
				.click();

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

			await expect( textContent ).toMatch( 'Product type changed.' );

			const title = page.locator( '.woocommerce-product-header__title' );

			// Save product ID
			const productIdRegex = /product%2F(\d+)/;
			const url = page.url();
			const productIdMatch = productIdRegex.exec( url );
			const productId = productIdMatch ? productIdMatch[ 1 ] : null;

			await expect( productId ).toBeDefined();
			await expect( title ).toHaveText( productData.name );
		} );
	} );
} );
