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
	fixedCart: {
		code: `fixedCart-${ Date.now() }`,
		description: `Simple fixed cart discount ${ Date.now() }`,
		amount: `${ Math.floor( Math.random() * 50 ) + 1 }`,
	},
	fixedProduct: {
		code: `fixedProduct-${ Date.now() }`,
		description: `Simple fixed product discount ${ Date.now() }`,
		amount: `${ Math.floor( Math.random() * 50 ) + 1 }`,
	},
	percentage: {
		code: `percentage-${ Date.now() }`,
		description: `Simple percentage discount ${ Date.now() }`,
		amount: `${ Math.floor( Math.random() * 50 ) + 1 }`,
	},
	expiryDate: {
		code: `expiryDate-${ Date.now() }`,
		description: `Simple expiry date discount ${ Date.now() }`,
		amount: `${ Math.floor( Math.random() * 50 ) + 1 }`,
		expiryDate: '2023-12-31',
	},
	freeShipping: {
		code: `freeShipping-${ Date.now() }`,
		description: `Simple free shipping discount ${ Date.now() }`,
		amount: `${ Math.floor( Math.random() * 50 ) + 1 }`,
		freeShipping: true,
	},
};

interface Coupon {
	id?: string;
}

const test = baseTest.extend< { coupon: Coupon } >( {
	storageState: ADMIN_STATE_PATH,
	coupon: async ( { restApi }, use ) => {
		const coupon: Coupon = {};
		await use( coupon );
		await restApi.delete( `${ WC_API_PATH }/coupons/${ coupon.id }`, {
			force: true,
		} );
	},
} );

test.describe( 'Coupon management', { tag: tags.SERVICES }, () => {
	for ( const couponType of Object.keys( couponData ) ) {
		test( `can create new ${ couponType } coupon`, async ( {
			page,
			coupon,
		} ) => {
			await test.step( 'add new coupon', async () => {
				await page.goto(
					'wp-admin/post-new.php?post_type=shop_coupon'
				);
				await page
					.getByLabel( 'Coupon code' )
					.fill(
						couponData[ couponType as keyof typeof couponData ].code
					);
				await page
					.getByPlaceholder( 'Description (optional)' )
					.fill(
						couponData[ couponType as keyof typeof couponData ]
							.description
					);
				await page
					.getByPlaceholder( '0' )
					.fill(
						couponData[ couponType as keyof typeof couponData ]
							.amount
					);

				// set expiry date if it was provided
				if (
					'expiryDate' in
					couponData[ couponType as keyof typeof couponData ]
				) {
					await page
						.getByPlaceholder( 'yyyy-mm-dd' )
						.fill(
							(
								couponData[
									couponType as keyof typeof couponData
								] as { expiryDate: string }
							 ).expiryDate
						);
				}

				// be explicit about whether free shipping is allowed
				if (
					'freeShipping' in
						couponData[ couponType as keyof typeof couponData ] &&
					(
						couponData[ couponType as keyof typeof couponData ] as {
							freeShipping: boolean;
						}
					 ).freeShipping
				) {
					await page.getByLabel( 'Allow free shipping' ).check();
				} else {
					await page.getByLabel( 'Allow free shipping' ).uncheck();
				}
			} );

			// publish the coupon and retrieve the id
			await test.step( 'publish the coupon', async () => {
				await expect(
					page.getByRole( 'link', { name: 'Move to Trash' } )
				).toBeVisible();
				await page
					.getByRole( 'button', { name: 'Publish', exact: true } )
					.click();
				await expect(
					page.getByText( 'Coupon updated.' )
				).toBeVisible();
				const match = page.url().match( /(?<=post=)\d+/ );
				coupon.id = match ? match[ 0 ] : undefined;
				expect( coupon.id ).toBeDefined();
			} );

			// verify the creation of the coupon and details
			await test.step( 'verify coupon creation', async () => {
				await page.goto( 'wp-admin/edit.php?post_type=shop_coupon' );
				await expect(
					page.getByRole( 'cell', {
						name: couponData[
							couponType as keyof typeof couponData
						].code,
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'cell', {
						name: couponData[
							couponType as keyof typeof couponData
						].description,
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'cell', {
						name: couponData[
							couponType as keyof typeof couponData
						].amount,
						exact: true,
					} )
				).toBeVisible();
			} );

			// check expiry date if it was set
			if (
				'expiryDate' in
				couponData[ couponType as keyof typeof couponData ]
			) {
				await test.step( 'verify coupon expiry date', async () => {
					await page
						.getByText(
							couponData[ couponType as keyof typeof couponData ]
								.code
						)
						.last()
						.click();
					await expect(
						page.getByPlaceholder( 'yyyy-mm-dd' )
					).toHaveValue(
						(
							couponData[
								couponType as keyof typeof couponData
							] as { expiryDate: string }
						 ).expiryDate
					);
				} );
			}

			// if it was a free shipping coupon check that
			if (
				'freeShipping' in
					couponData[ couponType as keyof typeof couponData ] &&
				(
					couponData[ couponType as keyof typeof couponData ] as {
						freeShipping: boolean;
					}
				 ).freeShipping
			) {
				await test.step( 'verify free shipping', async () => {
					await page
						.getByText(
							couponData[ couponType as keyof typeof couponData ]
								.code
						)
						.last()
						.click();
					await expect(
						page.getByLabel( 'Allow free shipping' )
					).toBeChecked();
				} );
			}
		} );
	}
} );
