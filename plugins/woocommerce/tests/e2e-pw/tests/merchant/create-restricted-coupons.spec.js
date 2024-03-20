const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

baseTest.describe( 'Restricted coupon management', () => {
	const couponData = {
		minimumSpend: {
			code: `minSpend-${ new Date().getTime().toString() }`,
			description: 'Minimum spend coupon',
			amount: '10',
			minSpend: '100',
		},
		maximumSpend: {
			code: `maxSpend-${ new Date().getTime().toString() }`,
			description: 'Maximum spend coupon',
			amount: '20',
			maxSpend: '100',
		},
		individualUse: {
			code: `individualUse-${ new Date().getTime().toString() }`,
			description: 'Individual use coupon',
			amount: '30',
			individualUse: true,
		},
		excludeSaleItems: {
			code: `excludeSaleItems-${ new Date().getTime().toString() }`,
			description: 'Exclude sale items coupon',
			amount: '40',
			excludeSaleItems: true,
		},
		productCategories: {
			code: `productCategories-${ new Date().getTime().toString() }`,
			description: 'Product categories coupon',
			amount: '50',
			productCategories: [ 'Uncategorized' ],
		},
		excludeProductCategories: {
			code: `excludeProductCategories-${ new Date()
				.getTime()
				.toString() }`,
			description: 'Exclude product categories coupon',
			amount: '60',
			excludeProductCategories: [ 'Uncategorized' ],
		},
		products: {
			code: `products-${ new Date().getTime().toString() }`,
			description: 'Products coupon',
			amount: '70',
			products: [ 'Product' ],
		},
		excludeProducts: {
			code: `excludeProducts-${ new Date().getTime().toString() }`,
			description: 'Exclude products coupon',
			amount: '80',
			excludeProducts: [ 'Product' ],
		},
		allowedEmails: {
			code: `allowedEmails-${ new Date().getTime().toString() }`,
			description: 'Allowed emails coupon',
			amount: '90',
			allowedEmails: [ 'allowed@example.com' ],
		},
		usageLimitPerCoupon: {
			code: `usageLimit-${ new Date().getTime().toString() }`,
			description: 'Usage limit coupon',
			amount: '100',
			usageLimit: '1',
		},
		usageLimitPerUser: {
			code: `usageLimitPerUser-${ new Date().getTime().toString() }`,
			description: 'Usage limit per user coupon',
			amount: '110',
			usageLimitPerUser: '2',
		},
	};

	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
		coupon: async ( { api }, use ) => {
			const coupon = {};
			await use( coupon );
			await api.delete( `coupons/${ coupon.id }`, { force: true } );
		},

		product: async ( { api }, use ) => {
			let product = {};

			await api
				.post( 'products', {
					name: 'Product',
					regular_price: '100',
				} )
				.then( ( response ) => {
					product = response.data;
				} );

			await use( product );

			// Product cleanup
			await api.delete( `products/${ product.id }`, { force: true } );
		},
	} );

	for ( const couponType of Object.keys( couponData ) ) {
		test( `can create new ${ couponType } coupon`, async ( {
			page,
			coupon,
			product,
		} ) => {
			// create basics for the coupon
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
				await expect( page.getByText( 'Move to Trash' ) ).toBeVisible();
			} );

			// set up the restrictions for each coupon type
			// set minimum spend
			if ( couponType === 'minimumSpend' ) {
				await test.step( 'set minimum spend coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await page
						.getByPlaceholder( 'No minimum' )
						.fill( couponData[ couponType ].minSpend );
				} );
			}
			// set maximum spend
			if ( couponType === 'maximumSpend' ) {
				await test.step( 'set maximum spend coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await page
						.getByPlaceholder( 'No maximum' )
						.fill( couponData[ couponType ].maxSpend );
				} );
			}
			// set individual use
			if ( couponType === 'individualUse' ) {
				await test.step( 'set individual use coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await page.getByLabel( 'Individual use only' ).check();
				} );
			}
			// set exclude sale items
			if ( couponType === 'excludeSaleItems' ) {
				await test.step( 'set exclude sale items coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await page.getByLabel( 'Exclude sale items' ).check();
				} );
			}
			// set product categories
			if ( couponType === 'productCategories' ) {
				await test.step( 'set product categories coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await page
						.getByPlaceholder( 'Any category' )
						.pressSequentially( 'Uncategorized' );
					await page
						.getByRole( 'option', { name: 'Uncategorized' } )
						.click();
				} );
			}
			// set exclude product categories
			if ( couponType === 'excludeProductCategories' ) {
				await test.step( 'set exclude product categories coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await page
						.getByPlaceholder( 'No categories' )
						.pressSequentially( 'Uncategorized' );
					await page
						.getByRole( 'option', { name: 'Uncategorized' } )
						.click();
				} );
			}
			// set products
			if ( couponType === 'products' ) {
				await test.step( 'set products coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await page
						.getByPlaceholder( 'Search for a product…' )
						.first()
						.pressSequentially( product.name );
					await page
						.getByRole( 'option', { name: product.name } )
						.click();
				} );
			}
			// set exclude products
			if ( couponType === 'excludeProducts' ) {
				await test.step( 'set exclude products coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await page
						.getByPlaceholder( 'Search for a product…' )
						.last()
						.pressSequentially( product.name );
					await page
						.getByRole( 'option', { name: product.name } )
						.click();
				} );
			}
			// set allowed emails
			if ( couponType === 'allowedEmails' ) {
				await test.step( 'set allowed emails coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await page
						.getByPlaceholder( 'No restrictions' )
						.fill( couponData[ couponType ].allowedEmails[ 0 ] );
				} );
			}
			// set usage limit
			if ( couponType === 'usageLimitPerCoupon' ) {
				await test.step( 'set usage limit coupon', async () => {
					await page
						.getByRole( 'link', { name: 'Usage limits' } )
						.click();
					await page
						.getByLabel( 'Usage limit per coupon' )
						.fill( couponData[ couponType ].usageLimit );
				} );
			}
			// set usage limit per user
			if ( couponType === 'usageLimitPerUser' ) {
				await test.step( 'set usage limit per user coupon', async () => {
					await page
						.getByRole( 'link', { name: 'Usage limits' } )
						.click();
					await page
						.getByLabel( 'Usage limit per user' )
						.fill( couponData[ couponType ].usageLimitPerUser );
				} );
			}

			// publish the coupon and retrieve the id
			await test.step( 'publish the coupon', async () => {
				await page
					.getByRole( 'button', { name: 'Publish', exact: true } )
					.click();
				await expect(
					page.getByText( 'Coupon updated.' )
				).toBeVisible();
				coupon.id = page.url().match( /(?<=post=)\d+/ )[ 0 ];
				expect( coupon.id ).toBeDefined();
			} );

			// verify the creation of the coupon and basic details
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

				await page
					.getByRole( 'link', {
						name: couponData[ couponType ].code,
					} )
					.first()
					.click();
			} );

			// verify the restrictions for each coupon type
			// verify minimum spend
			if ( couponType === 'minimumSpend' ) {
				await test.step( 'verify minimum spend coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await expect(
						page.getByPlaceholder( 'No minimum' )
					).toHaveValue( couponData[ couponType ].minSpend );
				} );
			}

			// verify maximum spend
			if ( couponType === 'maximumSpend' ) {
				await test.step( 'verify maximum spend coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await expect(
						page.getByPlaceholder( 'No maximum' )
					).toHaveValue( couponData[ couponType ].maxSpend );
				} );
			}

			// verify individual use
			if ( couponType === 'individualUse' ) {
				await test.step( 'verify individual use coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await expect(
						page.getByLabel( 'Individual use only' )
					).toBeChecked();
				} );
			}

			// verify exclude sale items
			if ( couponType === 'excludeSaleItems' ) {
				await test.step( 'verify exclude sale items coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await expect(
						page.getByLabel( 'Exclude sale items' )
					).toBeChecked();
				} );
			}

			// verify product categories
			if ( couponType === 'productCategories' ) {
				await test.step( 'verify product categories coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await expect(
						page.getByRole( 'listitem', { name: 'Uncategorized' } )
					).toBeVisible();
				} );
			}

			// verify exclude product categories
			if ( couponType === 'excludeProductCategories' ) {
				await test.step( 'verify exclude product categories coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await expect(
						page.getByRole( 'listitem', { name: 'Uncategorized' } )
					).toBeVisible();
				} );
			}

			// verify products
			if ( couponType === 'products' ) {
				await test.step( 'verify products coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await expect(
						page.getByRole( 'listitem', { name: product.name } )
					).toBeVisible();
				} );
			}

			// verify exclude products
			if ( couponType === 'excludeProducts' ) {
				await test.step( 'verify exclude products coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await expect(
						page.getByRole( 'listitem', { name: product.name } )
					).toBeVisible();
				} );
			}

			// verify allowed emails
			if ( couponType === 'allowedEmails' ) {
				await test.step( 'verify allowed emails coupon', async () => {
					await page
						.getByRole( 'link', {
							name: 'Usage restriction',
						} )
						.click();
					await expect(
						page.getByPlaceholder( 'No restrictions' )
					).toHaveValue(
						couponData[ couponType ].allowedEmails[ 0 ]
					);
				} );
			}

			// verify usage limit
			if ( couponType === 'usageLimitPerCoupon' ) {
				await test.step( 'verify usage limit coupon', async () => {
					await page
						.getByRole( 'link', { name: 'Usage limits' } )
						.click();
					await expect(
						page.getByLabel( 'Usage limit per coupon' )
					).toHaveValue( couponData[ couponType ].usageLimit );
				} );
			}

			// verify usage limit per user
			if ( couponType === 'usageLimitPerUser' ) {
				await test.step( 'verify usage limit per user coupon', async () => {
					await page
						.getByRole( 'link', { name: 'Usage limits' } )
						.click();
					await expect(
						page.getByLabel( 'Usage limit per user' )
					).toHaveValue( couponData[ couponType ].usageLimitPerUser );
				} );
			}
		} );
	}
} );
