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
	managedOutOfStockProduct: async ( { restApi }, fixtureUse ) => {
		const response = await restApi.post( `${ WC_API_PATH }/products`, {
			...getFakeProduct(),
			manage_stock: true,
			stock_quantity: 0,
			stock_status: 'outofstock',
		} );

		await fixtureUse( response.data );

		await restApi.delete(
			`${ WC_API_PATH }/products/${ response.data.id }`,
			{ force: true }
		);
	},
} );

for ( const productType of [ 'grouped', 'external' ] ) {
	test( `resets stock settings when converting a product to ${ productType }`, async ( {
		page,
		restApi,
		managedOutOfStockProduct,
	} ) => {
		await page.goto(
			`wp-admin/post.php?post=${ managedOutOfStockProduct.id }&action=edit`
		);

		await expect( page.locator( 'input#_manage_stock' ) ).toBeChecked();
		await expect(
			page.locator( 'input[name="_stock_status"][value="outofstock"]' )
		).toBeChecked();

		await page.locator( '#product-type' ).selectOption( productType );

		await expect( page.locator( 'input#_manage_stock' ) ).not.toBeChecked();
		await expect(
			page.locator( 'input[name="_stock_status"][value="instock"]' )
		).toBeChecked();

		await page
			.locator( '#publishing-action' )
			.getByRole( 'button', { name: 'Update' } )
			.click();
		await expect(
			page
				.locator( 'div.notice-success > p' )
				.filter( { hasText: 'Product updated' } )
		).toBeVisible();

		const response = await restApi.get(
			`${ WC_API_PATH }/products/${ managedOutOfStockProduct.id }`
		);

		expect( response.data.type ).toBe( productType );
		expect( response.data.manage_stock ).toBe( false );
		expect( response.data.stock_status ).toBe( 'instock' );

		const productSearch = encodeURIComponent(
			managedOutOfStockProduct.name
		);
		await page.goto(
			`wp-admin/edit.php?post_type=product&stock_status=instock&s=${ productSearch }`
		);
		await expect(
			page.locator( `#post-${ managedOutOfStockProduct.id }` )
		).toBeVisible();

		await page.goto(
			`wp-admin/edit.php?post_type=product&stock_status=outofstock&s=${ productSearch }`
		);
		await expect(
			page.locator( `#post-${ managedOutOfStockProduct.id }` )
		).toHaveCount( 0 );
	} );
}
