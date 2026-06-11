/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	coupon: async ( { restApi }, use ) => {
		const coupon = {
			code: `restricted-wiring-${ Date.now() }`,
			description: `Restricted coupon wiring ${ Date.now() }`,
			amount: `${ Math.floor( Math.random() * 50 ) + 1 }`,
		};
		await use( coupon );
		if ( coupon.id ) {
			await restApi.delete( `${ WC_API_PATH }/coupons/${ coupon.id }`, {
				force: true,
			} );
		}
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

test.describe( 'Coupon management', { tag: tags.SERVICES }, () => {
	test( 'can create a product-restricted coupon through the admin form', async ( {
		page,
		coupon,
		product,
	} ) => {
		await test.step( 'fill the rendered coupon form', async () => {
			await page.goto( 'wp-admin/post-new.php?post_type=shop_coupon' );
			await page.getByLabel( 'Coupon code' ).fill( coupon.code );
			await page
				.getByPlaceholder( 'Description (optional)' )
				.fill( coupon.description );
			await page.getByLabel( 'Coupon amount' ).fill( coupon.amount );
			await page
				.getByRole( 'link', { name: 'Usage restriction' } )
				.click();
			await page
				.locator( '#usage_restriction_coupon_data p' )
				.filter( {
					has: page.getByText( 'Products', { exact: true } ),
				} )
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

			const searchParams = new URL( page.url() ).searchParams;
			coupon.id = searchParams.get( 'post' );
			expect( coupon.id ).toBeDefined();
			expect( coupon.id ).toMatch( /^\d+$/ );
		} );

		await test.step( 'verify persisted product restriction', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ coupon.id }&action=edit`
			);
			await expect( page.getByLabel( 'Coupon code' ) ).toHaveValue(
				coupon.code
			);
			await expect(
				page.getByPlaceholder( 'Description (optional)' )
			).toHaveValue( coupon.description );
			await expect( page.getByLabel( 'Coupon amount' ) ).toHaveValue(
				coupon.amount
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
