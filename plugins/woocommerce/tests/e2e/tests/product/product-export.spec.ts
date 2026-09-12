/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test as baseTest, expect } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { getFakeProduct } from '../../utils/data';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	productsFixture: async ( { restApi }, use ) => {
		const products = {};

		let response = await restApi.post(
			`${ WC_API_PATH }/products`,
			getFakeProduct()
		);
		products.simple = await response.data;

		response = await restApi.post( `${ WC_API_PATH }/products`, {
			...getFakeProduct( { type: 'variable' } ),
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
	test( 'preserves multiple selection through export and clear', async ( {
		page,
		productsFixture,
	} ) => {
		const simpleProduct = productsFixture.simple;
		const variableProduct = productsFixture.variable;

		await test.step( 'Navigate to product list and select multiple products', async () => {
			await page.goto( 'wp-admin/edit.php?post_type=product' );
			await page.locator( `#cb-select-${ simpleProduct.id }` ).check();
			await page.locator( `#cb-select-${ variableProduct.id }` ).check();
		} );

		const exportButton = page.locator(
			'a.page-title-action[href*="page=product_exporter"]'
		);

		await test.step( 'Verify export button text and link for multiple selections', async () => {
			await expect( exportButton ).toHaveText( 'Export 2 selected' );
			const exportButtonHref = await exportButton.getAttribute( 'href' );
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
				.toSorted()
				.join( ',' );
			const actualIds = ( await productIdsInput.inputValue() )
				.split( ',' )
				.toSorted()
				.join( ',' );
			expect( actualIds ).toBe( expectedIds );
			await expect(
				page.locator( 'label[for="woocommerce-exporter-types"]' )
			).toBeHidden();
			await expect(
				page.locator( 'label[for="woocommerce-exporter-category"]' )
			).toBeHidden();
			await expect(
				page.locator( '.woocommerce-exporter header p' )
			).toHaveText(
				'This tool allows you to generate and download a CSV file containing the selected products.'
			);
		} );

		await test.step( 'Clear the selected products', async () => {
			await page
				.getByRole( 'link', { name: 'clear your selection' } )
				.click();
		} );

		await test.step( 'Verify the default export state', async () => {
			expect( page.url() ).not.toContain( 'product_ids=' );
			await expect(
				page.locator( '#selected-product-export-notice' )
			).toBeHidden();
			await expect(
				page.locator( 'input[name="product_ids"]' )
			).toHaveCount( 0 );
			await expect(
				page.locator( 'label[for="woocommerce-exporter-types"]' )
			).toBeVisible();
			await expect(
				page.locator( 'label[for="woocommerce-exporter-category"]' )
			).toBeVisible();
			await expect(
				page.locator( '.woocommerce-exporter header p' )
			).toHaveText(
				'This tool allows you to generate and download a CSV file containing a list of all products.'
			);
		} );
	} );
} );
