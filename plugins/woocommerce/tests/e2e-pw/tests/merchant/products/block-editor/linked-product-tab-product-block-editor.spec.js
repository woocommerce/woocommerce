const { test } = require( '../../../../fixtures/block-editor-fixtures' );
const { expect } = require( '@playwright/test' );

const { clickOnTab } = require( '../../../../utils/simple-products' );
const { api } = require( '../../../../utils' );

const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fadd-product';

const isTrackingSupposedToBeEnabled = !! process.env.ENABLE_TRACKING;

const productData = {
	name: `Linked product Name ${ new Date().getTime().toString() }`,
	summary: 'This is a product summary',
};

const linkedProductsData = [],
	productIds = [];
let productId = 0;

test.describe( 'General tab', () => {
	test.describe( 'Linked product', () => {
		test.beforeAll( async () => {
			for ( let i = 1; i <= 5; i++ ) {
				const product = {
					name: `Product name ${ i } ${ new Date()
						.getTime()
						.toString() }`,
					productPrice: `${ i }00`,
					type: 'simple',
				};
				linkedProductsData.push( product );
				const id = await api.create.product( product );
				productIds.push( id );
			}
		} );

		test.afterAll( async () => {
			for ( const aProductId of productIds ) {
				await api.deletePost.product( aProductId );
			}
			await api.deletePost.product( productId );
		} );
		test.skip(
			isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test( 'can create a product with linked products', async ( {
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

			await clickOnTab( 'Linked products', page );

			await page
				.getByRole( 'heading', {
					name: 'Cross-sells',
				} )
				.isVisible();

			await page
				.locator(
					'.wp-block-woocommerce-product-linked-list-field__form-group-content'
				)
				.first()
				.getByPlaceholder( 'Search for products' )
				.fill( linkedProductsData[ 0 ].name );

			await page.getByText( linkedProductsData[ 0 ].name ).click();

			await page.getByText( 'Choose products for me' ).first().click();

			await page.waitForResponse(
				( response ) =>
					response
						.url()
						.includes(
							'/wp-json/wc/v3/products?search=&orderby=title&order=asc&per_page='
						) && response.status() === 200
			);

			await page.waitForFunction(
				( [ selector, expectedCount ] ) =>
					document.querySelectorAll( selector ).length ===
					expectedCount,
				[ '.woocommerce-product-list div[role="row"]', 5 ]
			);

			const upsellsRows = page.locator(
				'div.woocommerce-product-list div[role="table"] div[role="rowgroup"] div[role="row"]'
			);

			const upsellsRowsCount = await upsellsRows.count();

			await expect( upsellsRowsCount ).toBe( 5 );

			await page
				.locator(
					'.wp-block-woocommerce-product-linked-list-field__form-group-content'
				)
				.last()
				.getByPlaceholder( 'Search for products' )
				.fill( linkedProductsData[ 1 ].name );

			await page
				.getByText( linkedProductsData[ 1 ].name )
				.first()
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

			await expect(
				page.getByLabel( 'Dismiss this notice' )
			).toContainText( 'Product published' );

			const title = page.locator( '.woocommerce-product-header__title' );

			// Save product ID
			const productIdRegex = /product%2F(\d+)/;
			const url = page.url();
			const productIdMatch = productIdRegex.exec( url );
			productId = productIdMatch ? productIdMatch[ 1 ] : null;

			await expect( productId ).toBeDefined();
			await expect( title ).toHaveText( productData.name );

			await page.goto( `/?post_type=product&p=${ productId }` );

			await expect(
				page.getByRole( 'heading', { name: productData.name } )
			).toBeVisible();

			const productsList = page.locator(
				'section.upsells.products ul > li'
			);

			const productsCount = await productsList.count();

			await expect( productsCount ).toBe( 5 );
		} );
	} );
} );
