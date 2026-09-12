/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test, expect } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { getFakeProduct } from '../../utils/data';

let productId: number;
const testProduct = getFakeProduct( { regular_price: '9.99' } );

test.describe( 'Products > Search and View a product', () => {
	test.use( { storageState: ADMIN_STATE_PATH } );

	test.beforeAll( async ( { restApi } ) => {
		await restApi
			.post( `${ WC_API_PATH }/products`, testProduct )
			.then( ( response ) => {
				productId = response.data.id;
			} );
	} );

	test.afterAll( async ( { restApi } ) => {
		await restApi.delete( `${ WC_API_PATH }/products/${ productId }`, {
			force: true,
		} );
	} );

	test( 'can find and open a product from a partial search', async ( {
		page,
	} ) => {
		const searchString = testProduct.name.substring(
			0,
			testProduct.name.length / 2
		);

		await page.goto( 'wp-admin/edit.php?post_type=product' );

		await expect( page.locator( '#post-search-input' ) ).toBeVisible();
		await page.locator( '#post-search-input' ).fill( searchString );
		await page.locator( '#search-submit' ).click();

		const productLink = page.getByRole( 'link', {
			name: testProduct.name,
			exact: true,
		} );
		const productRow = page.locator( '#the-list tr' ).filter( {
			has: productLink,
		} );
		await expect( productRow ).toHaveCount( 1 );
		await expect(
			productRow.getByRole( 'link', {
				name: testProduct.name,
				exact: true,
			} )
		).toHaveCount( 1 );
		await productRow
			.getByRole( 'link', { name: testProduct.name, exact: true } )
			.click();

		await expect( page ).toHaveURL( /wp-admin\/post\.php/ );
		expect( new URL( page.url() ).searchParams.get( 'post' ) ).toBe(
			String( productId )
		);
		await expect( page.locator( '#title' ) ).toHaveValue(
			testProduct.name
		);
		await expect( page.locator( '#_regular_price' ) ).toHaveValue(
			testProduct.regular_price
		);
	} );
} );
