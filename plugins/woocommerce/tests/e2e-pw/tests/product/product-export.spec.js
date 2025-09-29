/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test as baseTest, expect } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const simpleProductName = 'Export Simple Product';
const variableProductName = 'Export Variable Product';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	productsFixture: async ( { restApi }, use ) => {
		const products = {};

		let response = await restApi.post( `${ WC_API_PATH }/products`, {
			name: simpleProductName,
			type: 'simple',
			regular_price: '15',
		} );
		products.simple = await response.data;

		response = await restApi.post( `${ WC_API_PATH }/products`, {
			name: variableProductName,
			type: 'variable',
			attributes: [
				{
					name: 'Size',
					position: 0,
					visible: true,
					variation: true,
					options: [ 'Small', 'Large' ],
				},
			],
		} );
		products.variable = await response.data;

		await restApi.post(
			`${ WC_API_PATH }/products/${ products.variable.id }/variations`,
			{
				regular_price: '20',
				attributes: [ { name: 'Size', option: 'Small' } ],
			}
		);
		await restApi.post(
			`${ WC_API_PATH }/products/${ products.variable.id }/variations`,
			{
				regular_price: '25',
				attributes: [ { name: 'Size', option: 'Large' } ],
			}
		);

		await use( products );

		if ( products.simple ) {
			await restApi.delete(
				`${ WC_API_PATH }/products/${ products.simple.id }`,
				{ force: true }
			);
		}
		if ( products.variable ) {
			await restApi.delete(
				`${ WC_API_PATH }/products/${ products.variable.id }`,
				{ force: true }
			);
		}
	},
} );

test.describe( 'Product > Export Selected Products', () => {
	test( 'should allow exporting a single selected simple product', async ( {
		page,
		productsFixture,
	} ) => {
		const simpleProduct = productsFixture.simple;

		await test.step( 'Navigate to product list and select product', async () => {
			await page.goto( 'wp-admin/edit.php?post_type=product' );
			await page.locator( `#cb-select-${ simpleProduct.id }` ).check();
		} );

		const exportButton = page.locator(
			'a.page-title-action[href*="page=product_exporter"]'
		);

		await test.step( 'Verify export button text and link for single selection', async () => {
			await expect( exportButton ).toHaveText( 'Export 1 selected' );
			const exportButtonHref = await exportButton.getAttribute( 'href' );
			expect( exportButtonHref ).toContain(
				`product_ids=${ simpleProduct.id }`
			);
			expect( exportButtonHref ).toContain( '_wpnonce=' );
		} );

		await test.step( 'Navigate to export page and verify UI elements', async () => {
			await exportButton.click();
			await expect( page.locator( '.wrap.woocommerce h1' ) ).toHaveText(
				'Export Products'
			);
			await expect(
				page.locator( '#selected-product-export-notice p' )
			).toContainText(
				'You are about to export 1 product. To export all products, clear your selection.'
			);
			await expect(
				page.locator( 'input[name="product_ids"]' )
			).toHaveValue( String( simpleProduct.id ) );
			await expect(
				page.locator( 'label[for="woocommerce-exporter-types"]' )
			).toBeHidden();
			await expect(
				page.locator( 'label[for="woocommerce-exporter-category"]' )
			).toBeHidden();
		} );
	} );

	test( 'should allow exporting multiple selected products (simple and variable)', async ( {
		page,
		productsFixture,
	} ) => {
		const simpleProduct = productsFixture.simple;
		const variableProduct = productsFixture.variable;

		await test.step( 'Navigate to product list and select multiple products', async () => {
			await page.goto( 'wp-admin/edit.php?post_type=product' ); // Changed to page.goto
			await page.locator( `#cb-select-${ simpleProduct.id }` ).check();
			await page.locator( `#cb-select-${ variableProduct.id }` ).check();
		} );

		const exportButton = page.locator(
			'a.page-title-action[href*="page=product_exporter"]'
		);

		await test.step( 'Verify export button text and link for multiple selections', async () => {
			await expect( exportButton ).toHaveText( 'Export 2 selected' );
			const exportButtonHref = await exportButton.getAttribute( 'href' );
			// Use a regex to match product_ids in any order, allowing for both comma and URL-encoded comma.
			expect( exportButtonHref ).toMatch(
				new RegExp(
					`product_ids=(${ simpleProduct.id }(,|%2C)${ variableProduct.id }|${ variableProduct.id }(,|%2C)${ simpleProduct.id })`
				)
			);
			expect( exportButtonHref ).toContain( '_wpnonce=' );
		} );

		await test.step( 'Navigate to export page and verify UI elements for multiple products', async () => {
			await exportButton.click();
			await expect( page.locator( '.wrap.woocommerce h1' ) ).toHaveText(
				'Export Products'
			);
			await expect(
				page.locator( '#selected-product-export-notice p' )
			).toContainText(
				'You are about to export 2 products. To export all products, clear your selection.'
			);
			const productIdsInput = page.locator( 'input[name="product_ids"]' );
			const expectedIds = [
				String( simpleProduct.id ),
				String( variableProduct.id ),
			]
				.sort()
				.join( ',' );
			const actualIds = ( await productIdsInput.inputValue() )
				.split( ',' )
				.sort()
				.join( ',' );
			expect( actualIds ).toBe( expectedIds );
			await expect(
				page.locator( 'label[for="woocommerce-exporter-types"]' )
			).toBeHidden();
			await expect(
				page.locator( 'label[for="woocommerce-exporter-category"]' )
			).toBeHidden();
		} );
	} );

	test( 'should allow clearing selection from the export page', async ( {
		page,
		productsFixture,
	} ) => {
		const simpleProduct = productsFixture.simple;

		await test.step( 'Navigate to product list, select product, and go to export page', async () => {
			await page.goto( 'wp-admin/edit.php?post_type=product' );
			await page.locator( `#cb-select-${ simpleProduct.id }` ).check();
			await page
				.locator( 'a.page-title-action[href*="page=product_exporter"]' )
				.click();
		} );

		await test.step( 'Verify export page notice and URL for selected product', async () => {
			await expect(
				page.locator( '#selected-product-export-notice p' )
			).toContainText( 'You are about to export 1 product.' );
			await expect( page.url() ).toContain(
				`product_ids=${ simpleProduct.id }`
			);
		} );

		await test.step( "Click 'clear your selection' link", async () => {
			await page
				.locator( '.notice-info p a:has-text("clear your selection")' )
				.click();
		} );

		await test.step( 'Verify redirect to general export page and UI elements', async () => {
			await expect( page.url() ).not.toContain( 'product_ids=' );
			await expect(
				page.locator(
					'.notice-info p:has-text("You are about to export")'
				)
			).toBeHidden();
			await expect(
				page.locator( 'label[for="woocommerce-exporter-types"]' )
			).toBeVisible();
			await expect(
				page.locator( 'label[for="woocommerce-exporter-category"]' )
			).toBeVisible();
		} );
	} );

	test( 'should show the default export screen when no products are selected', async ( {
		page,
		productsFixture,
	} ) => {
		expect( productsFixture ).toBeDefined();
		await test.step( 'Navigate to product list', async () => {
			await page.goto( 'wp-admin/edit.php?post_type=product' );
		} );

		const exportButton = page.locator(
			'a.page-title-action[href*="page=product_exporter"]'
		);

		await test.step( 'Verify default export button state and navigate to export page', async () => {
			await expect( exportButton ).toHaveText( 'Export' );
			await exportButton.click();
		} );

		await test.step( 'Verify UI elements for default export', async () => {
			await expect( page.url() ).not.toContain( 'product_ids=' );
			// Verify the selection-specific notice is NOT present
			await expect(
				page.locator( '#selected-product-export-notice' )
			).toBeHidden();
			// Verify the standard filters ARE present
			await expect(
				page.locator( 'label[for="woocommerce-exporter-types"]' )
			).toBeVisible();
			await expect(
				page.locator( 'label[for="woocommerce-exporter-category"]' )
			).toBeVisible();
		} );
	} );
} );
