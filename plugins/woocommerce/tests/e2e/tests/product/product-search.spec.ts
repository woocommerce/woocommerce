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

	test( 'can do a partial search for a product', async ( { page } ) => {
		// create a partial search string
		const searchString = testProduct.name.substring(
			0,
			testProduct.name.length / 2
		);

		await page.goto( 'wp-admin/edit.php?post_type=product' );

		await expect( page.locator( '#post-search-input' ) ).toBeVisible();
		await page.locator( '#post-search-input' ).fill( searchString );
		await page.locator( '#search-submit' ).click();

		// A partial search can match products that parallel workers create from
		// this same spec, so scope the assertion to this test's product instead
		// of asserting on every `.row-title` match.
		await expect(
			page.locator( '.row-title', { hasText: testProduct.name } )
		).toBeVisible();
	} );

	test( "can view a product's details after search", async ( { page } ) => {
		const productIdInURL = new RegExp( `post=${ productId }` );

		await page.goto( 'wp-admin/edit.php?post_type=product' );

		await page.locator( '#post-search-input' ).fill( testProduct.name );
		await page.locator( '#search-submit' ).click();

		await page
			.locator( '.row-title', { hasText: testProduct.name } )
			.click();

		await expect( page ).toHaveURL( productIdInURL );
		await expect( page.locator( '#title' ) ).toHaveValue(
			testProduct.name
		);
		await expect( page.locator( '#_regular_price' ) ).toHaveValue(
			testProduct.regular_price
		);
	} );

	test( 'returns no results for non-existent product search', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/edit.php?post_type=product' );

		await page.locator( '#post-search-input' ).fill( 'abcd1234' );
		await page.locator( '#search-submit' ).click();

		await expect( page.locator( '.no-items' ) ).toContainText(
			'No products found'
		);
	} );
} );
