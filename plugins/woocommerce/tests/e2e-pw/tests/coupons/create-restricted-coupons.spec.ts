/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const couponData = {
	code: `restricted-wiring-${ Date.now() }`,
	description: `Restricted coupon wiring ${ Date.now() }`,
	amount: `${ Math.floor( Math.random() * 50 ) + 1 }`,
};

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	coupon: async ( { restApi }, use ) => {
		const coupon = {};
		await use( coupon );
		await restApi.delete( `${ WC_API_PATH }/coupons/${ coupon.id }`, {
			force: true,
		} );
	},

	product: async ( { restApi }, use ) => {
		let product = {};
		const productName = `Product ${ Date.now() }`;

		await restApi
			.post( `${ WC_API_PATH }/products`, {
				name: productName,
				regular_price: '100',
			} )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
			force: true,
		} );
	},
} );

test.describe( 'Restricted coupon management', { tag: tags.SERVICES }, () => {
	test( 'can create a product-restricted coupon through the rendered admin form', async ( {
		page,
		coupon,
		product,
	} ) => {
		await test.step( 'fill the rendered coupon form', async () => {
			await page.goto( 'wp-admin/post-new.php?post_type=shop_coupon' );
			await page.getByLabel( 'Coupon code' ).fill( couponData.code );
			await page
				.getByPlaceholder( 'Description (optional)' )
				.fill( couponData.description );
			await page.getByLabel( 'Coupon amount' ).fill( couponData.amount );
			await page
				.getByRole( 'link', { name: 'Usage restriction' } )
				.click();
			await page
				.getByPlaceholder( 'Search for a product…' )
				.pressSequentially( product.name );
			await page.getByRole( 'option', { name: product.name } ).click();
		} );

		await test.step( 'publish the coupon', async () => {
			await expect( page.getByText( 'Move to Trash' ) ).toBeVisible();
			await page
				.getByRole( 'button', { name: 'Publish', exact: true } )
				.click();
			await expect( page.getByText( 'Coupon updated.' ) ).toBeVisible();
			await expect( page ).toHaveURL( /[?&]post=\d+/ );

			coupon.id = page.url().match( /[?&]post=(\d+)/ )?.[ 1 ];
			expect( coupon.id ).toBeDefined();
		} );

		await test.step( 'verify persisted product restriction', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ coupon.id }&action=edit`
			);
			await page
				.getByRole( 'link', { name: 'Usage restriction' } )
				.click();
			await expect(
				page.getByRole( 'listitem', { name: product.name } )
			).toBeVisible();
		} );
	} );
} );
