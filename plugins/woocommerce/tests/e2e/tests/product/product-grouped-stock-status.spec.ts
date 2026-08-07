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
	outOfStockProduct: async ( { restApi }, fixtureUse ) => {
		const response = await restApi.post( `${ WC_API_PATH }/products`, {
			...getFakeProduct(),
			stock_status: 'outofstock',
		} );

		await fixtureUse( response.data );

		await restApi.delete(
			`${ WC_API_PATH }/products/${ response.data.id }`,
			{ force: true }
		);
	},
	outOfStockGroupedProduct: async ( { restApi }, fixtureUse ) => {
		const response = await restApi.post( `${ WC_API_PATH }/products`, {
			...getFakeProduct( { type: 'grouped' } ),
			stock_status: 'outofstock',
		} );

		await fixtureUse( response.data );

		await restApi.delete(
			`${ WC_API_PATH }/products/${ response.data.id }`,
			{ force: true }
		);
	},
} );

test( 'resets stock status when converting a product to grouped', async ( {
	page,
	restApi,
	outOfStockProduct,
} ) => {
	await page.goto(
		`wp-admin/post.php?post=${ outOfStockProduct.id }&action=edit`
	);

	await expect(
		page.locator( 'input[name="_stock_status"][value="outofstock"]' )
	).toBeChecked();

	await page.locator( '#product-type' ).selectOption( 'grouped' );

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
		`${ WC_API_PATH }/products/${ outOfStockProduct.id }`
	);

	expect( response.data.type ).toBe( 'grouped' );
	expect( response.data.stock_status ).toBe( 'instock' );
} );

test( 'resets stock status when loading an out-of-stock grouped product', async ( {
	page,
	restApi,
	outOfStockGroupedProduct,
} ) => {
	expect( outOfStockGroupedProduct.type ).toBe( 'grouped' );
	expect( outOfStockGroupedProduct.stock_status ).toBe( 'outofstock' );

	await page.goto(
		`wp-admin/post.php?post=${ outOfStockGroupedProduct.id }&action=edit`
	);

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
		`${ WC_API_PATH }/products/${ outOfStockGroupedProduct.id }`
	);

	expect( response.data.type ).toBe( 'grouped' );
	expect( response.data.stock_status ).toBe( 'instock' );
} );
