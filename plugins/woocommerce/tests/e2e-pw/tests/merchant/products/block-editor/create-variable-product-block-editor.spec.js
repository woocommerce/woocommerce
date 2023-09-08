const { test, expect } = require( '@playwright/test' );

const {
	clickOnTab,
	isBlockProductEditorEnabled,
	toggleBlockProductEditor,
} = require( '../../../../utils/simple-products' );
const { toggleBlockProductTour } = require( '../../../../utils/tours' );

const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fadd-product';

let isNewProductEditorEnabled = false;

const isTrackingSupposedToBeEnabled = !! process.env.ENABLE_TRACKING;

const productData = {
	name: `Variable product Name ${ new Date().getTime().toString() }`,
	summary: 'This is a product summary',
	attributeName: 'Color',
	term1: 'Red',
	term2: 'Blue',
};

test.describe.configure( { mode: 'serial' } );

test.describe( 'Variations tab', () => {
	test.describe( 'Create variable product', () => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.skip(
			isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test.beforeEach( async ( { browser, request } ) => {
			await toggleBlockProductTour( request, false );
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

		test( 'can create a variable product', async ( { page } ) => {
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );

			const variationsTab = await page
				.locator( '.woocommerce-product-tabs' )
				.getByRole( 'button', {
					name: 'Variations',
				} );
			if ( ! variationsTab.isVisible() ) {
				test.skip(
					true,
					'The Variations tab visibility is not being tested'
				);
			}

			await clickOnTab( 'General', page );

			await page
				.locator( '//input[@placeholder="e.g. 12 oz Coffee Mug"]' )
				.fill( productData.name );

			await page
				.locator( '.components-summary-control' )
				.fill( productData.summary );

			await clickOnTab( 'Variations', page );

			await page
				.locator( '.wp-block-woocommerce-product-variations-fields' )
				.getByRole( 'button', {
					name: 'Add variation options',
				} )
				.click();

			await page
				.locator( '//input[@placeholder="Search or create attribute"]' )
				.isVisible();

			await page
				.locator( '.components-modal__header' )
				.getByRole( 'button' )
				.click();

			await page
				.locator( '.wp-block-woocommerce-product-variations-fields' )
				.getByRole( 'button', {
					name: 'Add variation options',
				} )
				.click();

			await page.waitForResponse(
				( response ) =>
					response
						.url()
						.includes( '/wp-json/wc/v3/products/attributes' ) &&
					response.status() === 200
			);

			await page
				.locator( '//input[@placeholder="Search or create attribute"]' )
				.type( productData.attributeName );

			await page
				.locator(
					`.woocommerce-experimental-select-control__menu-item >> text=Create "${ productData.attributeName }"`
				)
				.click();

			await page.waitForResponse(
				( response ) =>
					response
						.url()
						.includes( '/wp-json/wc/v3/products/attributes' ) &&
					response.status() === 200
			);

			await page
				.locator( '//input[@placeholder="Search or create value"]' )
				.fill( productData.term1 );

			await page.waitForResponse(
				( response ) =>
					response
						.url()
						.includes( `/terms?search=${ productData.term1 }` ) &&
					response.status() === 200
			);

			await page
				.locator(
					`.woocommerce-experimental-select-control__menu-item >> text=Create "${ productData.term1 }"`
				)
				.click();

			await page.waitForResponse(
				( response ) =>
					( response.request().method() === 'POST' &&
						response
							.url()
							.includes( `terms?name=${ productData.term1 }` ) &&
						response.status() === 200 ) ||
					response.status() === 201
			);

			await page
				.locator( '//input[@placeholder="Search or create value"]' )
				.fill( productData.term2 );

			await page
				.locator(
					`.woocommerce-experimental-select-control__menu-item >> text=Create "${ productData.term2 }"`
				)
				.click();

			await page.waitForResponse(
				( response ) =>
					( response.request().method() === 'POST' &&
						response
							.url()
							.includes( `terms?name=${ productData.term2 }` ) &&
						response.status() === 200 ) ||
					response.status() === 201
			);

			await page
				.locator( '.woocommerce-new-attribute-modal__buttons' )
				.getByRole( 'button', {
					name: 'Add',
				} )
				.click();

			const attribute = await page.locator(
				`.woocommerce-attribute-list-item >> text="${ productData.attributeName }"`
			);
			const term1 = await page.locator(
				`.woocommerce-attribute-list-item >> text="${ productData.term1 }"`
			);
			const term2 = await page.locator(
				`.woocommerce-attribute-list-item >> text="${ productData.term2 }"`
			);

			await expect( attribute ).toHaveText( productData.attributeName );
			await expect( term1 ).toHaveText( productData.term1 );
			await expect( term2 ).toHaveText( productData.term2 );
		} );
		test( 'can see Variations tab content', async ( { page } ) => {
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );

			const variationsTab = await page
				.locator( '.woocommerce-product-tabs' )
				.getByRole( 'button', {
					name: 'Variations',
				} );
			if ( ! variationsTab.isVisible() ) {
				test.skip(
					true,
					'The Variations tab visibility is not being tested'
				);
			}

			await clickOnTab( 'Variations', page );

			const button = await page
				.locator( '.wp-block-woocommerce-product-variations-fields' )
				.getByRole( 'button', {
					name: 'Add variation options',
				} );
			await expect( button ).toHaveText( 'Add variation options' );
		} );
	} );
} );
