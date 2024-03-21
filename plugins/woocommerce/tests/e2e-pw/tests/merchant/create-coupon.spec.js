const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

baseTest.describe( 'Coupon management', () => {
	const couponData = {
		fixedCart: {
			code: `fixedCart-${ Date.now() }`,
			description: 'Simple fixed cart discount',
			amount: '25',
		},
		fixedProduct: {
			code: `fixedProduct-${ Date.now() }`,
			description: 'Simple fixed product discount',
			amount: '5',
		},
		percentage: {
			code: `percentage-${ Date.now() }`,
			description: 'Simple percentage discount',
			amount: '10',
		},
		expiryDate: {
			code: `expiryDate-${ Date.now() }`,
			description: 'Simple expiry date discount',
			amount: '15',
			expiryDate: '2023-12-31',
		},
		freeShipping: {
			code: `freeShipping-${ Date.now() }`,
			description: 'Simple free shipping discount',
			amount: '1',
			freeShipping: true,
		},
	};

	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
		coupon: async ( { api }, use ) => {
			const coupon = {};
			await use( coupon );
			await api.delete( `coupons/${ coupon.id }`, { force: true } );
		},
	} );

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
					.fill( couponData[ couponType ].code );
				await page
					.getByPlaceholder( 'Description (optional)' )
					.fill( couponData[ couponType ].description );
				await page
					.getByPlaceholder( '0' )
					.fill( couponData[ couponType ].amount );

				// set expiry date if it was provided
				if ( couponData[ couponType ].expiryDate ) {
					await page
						.getByPlaceholder( 'yyyy-mm-dd' )
						.fill( couponData[ couponType ].expiryDate );
				}

				// be explicit about whether free shipping is allowed
				if ( couponData[ couponType ].freeShipping ) {
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
				coupon.id = page.url().match( /(?<=post=)\d+/ )[ 0 ];
				expect( coupon.id ).toBeDefined();
			} );

			// verify the creation of the coupon and details
			await test.step( 'verify coupon creation', async () => {
				await page.goto( 'wp-admin/edit.php?post_type=shop_coupon' );
				await expect(
					page.getByRole( 'cell', {
						name: couponData[ couponType ].code,
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'cell', {
						name: couponData[ couponType ].description,
					} )
				).toBeVisible();
				await expect(
					page.getByRole( 'cell', {
						name: couponData[ couponType ].amount,
						exact: true,
					} )
				).toBeVisible();
			} );

			// check expiry date if it was set
			if ( couponData[ couponType ].expiryDate ) {
				await test.step( 'verify coupon expiry date', async () => {
					await page
						.getByText( couponData[ couponType ].code )
						.last()
						.click();
					await expect(
						page.getByPlaceholder( 'yyyy-mm-dd' )
					).toHaveValue( couponData[ couponType ].expiryDate );
				} );
			}

			// if it was a free shipping coupon check that
			if ( couponData[ couponType ].freeShipping ) {
				await test.step( 'verify free shipping', async () => {
					await page
						.getByText( couponData[ couponType ].code )
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
