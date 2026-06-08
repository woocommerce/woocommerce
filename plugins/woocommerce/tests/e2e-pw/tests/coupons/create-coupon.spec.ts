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
	code: `coupon-wiring-${ Date.now() }`,
	description: `Coupon wiring ${ Date.now() }`,
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
} );

test.describe( 'Coupon management', { tag: tags.SERVICES }, () => {
	test( 'can create a coupon through the rendered admin form', async ( {
		page,
		coupon,
	} ) => {
		await test.step( 'fill the rendered coupon form', async () => {
			await page.goto( 'wp-admin/post-new.php?post_type=shop_coupon' );
			await page.getByLabel( 'Coupon code' ).fill( couponData.code );
			await page
				.getByPlaceholder( 'Description (optional)' )
				.fill( couponData.description );
			await page.getByLabel( 'Coupon amount' ).fill( couponData.amount );
		} );

		await test.step( 'publish the coupon', async () => {
			await expect(
				page.getByRole( 'link', { name: 'Move to Trash' } )
			).toBeVisible();
			await page
				.getByRole( 'button', { name: 'Publish', exact: true } )
				.click();
			await expect( page.getByText( 'Coupon updated.' ) ).toBeVisible();
			await expect( page ).toHaveURL( /[?&]post=\d+/ );

			coupon.id = page.url().match( /[?&]post=(\d+)/ )?.[ 1 ];
			expect( coupon.id ).toBeDefined();
		} );

		await test.step( 'verify persisted form values', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ coupon.id }&action=edit`
			);
			await expect( page.getByLabel( 'Coupon code' ) ).toHaveValue(
				couponData.code
			);
			await expect(
				page.getByPlaceholder( 'Description (optional)' )
			).toHaveValue( couponData.description );
			await expect( page.getByLabel( 'Coupon amount' ) ).toHaveValue(
				couponData.amount
			);
		} );
	} );
} );
