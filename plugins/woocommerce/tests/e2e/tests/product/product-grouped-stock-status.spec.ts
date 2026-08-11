/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { type ApiClient, WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test as baseTest, expect } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { getFakeCategory, getFakeProduct } from '../../utils/data';

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

type ProductType = 'grouped' | 'external';
type Product = {
	id: number;
	name: string;
};

async function convertProductType( page: Page, productType: ProductType ) {
	await page.locator( '#product-type' ).selectOption( productType );
	await page
		.locator( '#publishing-action' )
		.getByRole( 'button', { name: 'Update' } )
		.click();
}

async function expectProductStockReset(
	page: Page,
	restApi: ApiClient,
	product: Product,
	productType: ProductType
) {
	await expect( page.locator( 'input#_manage_stock' ) ).not.toBeChecked();
	await expect(
		page.locator( 'input[name="_stock_status"][value="instock"]' )
	).toBeChecked();
	await expect(
		page
			.locator( 'div.notice-success > p' )
			.filter( { hasText: 'Product updated' } )
	).toBeVisible();

	const response = await restApi.get(
		`${ WC_API_PATH }/products/${ product.id }`
	);

	expect( response.data.type ).toBe( productType );
	expect( response.data.manage_stock ).toBe( false );
	expect( response.data.stock_status ).toBe( 'instock' );

	const productSearch = encodeURIComponent( product.name );
	await page.goto(
		`wp-admin/edit.php?post_type=product&stock_status=instock&s=${ productSearch }`
	);
	await expect( page.locator( `#post-${ product.id }` ) ).toBeVisible();

	await page.goto(
		`wp-admin/edit.php?post_type=product&stock_status=outofstock&s=${ productSearch }`
	);
	await expect( page.locator( `#post-${ product.id }` ) ).toHaveCount( 0 );
}

test( 'resets stock settings when converting a product to grouped', async ( {
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

	await convertProductType( page, 'grouped' );
	await expectProductStockReset(
		page,
		restApi,
		managedOutOfStockProduct,
		'grouped'
	);
} );

test( 'resets external product stock settings and keeps it visible when out-of-stock products are hidden', async ( {
	page,
	restApi,
	managedOutOfStockProduct,
} ) => {
	const hideOutOfStockSettingEndpoint = `${ WC_API_PATH }/settings/products/woocommerce_hide_out_of_stock_items`;
	const hideOutOfStockSetting = await restApi.get(
		hideOutOfStockSettingEndpoint
	);
	const productSearch = encodeURIComponent( managedOutOfStockProduct.name );
	const category = await restApi
		.post(
			`${ WC_API_PATH }/products/categories`,
			getFakeCategory( { extraRandomTerm: true } )
		)
		.then( ( response ) => response.data );
	const categoryEndpoint = `${ WC_API_PATH }/products/categories?include=${ category.id }&hide_empty=false`;

	try {
		await restApi.put( hideOutOfStockSettingEndpoint, { value: 'yes' } );
		await restApi.put(
			`${ WC_API_PATH }/products/${ managedOutOfStockProduct.id }`,
			{ categories: [ { id: category.id } ] }
		);

		const categoryBeforeConversion = await restApi.get( categoryEndpoint );
		expect( categoryBeforeConversion.data[ 0 ].count ).toBe( 0 );

		await page.goto( `shop/?s=${ productSearch }` );
		await expect(
			page.getByRole( 'heading', {
				name: managedOutOfStockProduct.name,
				exact: true,
			} )
		).toHaveCount( 0 );

		await page.goto(
			`wp-admin/post.php?post=${ managedOutOfStockProduct.id }&action=edit`
		);
		await expect( page.locator( 'input#_manage_stock' ) ).toBeChecked();
		await expect(
			page.locator( 'input[name="_stock_status"][value="outofstock"]' )
		).toBeChecked();

		await convertProductType( page, 'external' );
		await expectProductStockReset(
			page,
			restApi,
			managedOutOfStockProduct,
			'external'
		);

		await page.goto( `shop/?s=${ productSearch }` );
		await expect(
			page.getByRole( 'heading', {
				name: managedOutOfStockProduct.name,
				exact: true,
			} )
		).toBeVisible();

		const categoryAfterConversion = await restApi.get( categoryEndpoint );
		expect( categoryAfterConversion.data[ 0 ].count ).toBe( 1 );
	} finally {
		await restApi.put( hideOutOfStockSettingEndpoint, {
			value: hideOutOfStockSetting.data.value,
		} );
		await restApi.delete(
			`${ WC_API_PATH }/products/categories/${ category.id }`,
			{ force: true }
		);
	}
} );
