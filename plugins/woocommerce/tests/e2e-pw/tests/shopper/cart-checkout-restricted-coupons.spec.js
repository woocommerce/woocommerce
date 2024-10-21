const { test, expect } = require( '@playwright/test' );
const { getOrderIdFromUrl } = require( '../../utils/order' );
const { addAProductToCart } = require( '../../utils/cart' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const includedProductName = 'Included test product';
const excludedProductName = 'Excluded test product';
const includedCategoryName = 'Included Category';
const excludedCategoryName = 'Excluded Category';

// This applies a coupon and waits for the result to prevent flakiness.
const applyCoupon = async ( page, couponCode ) => {
	const responsePromise = page.waitForResponse(
		( response ) =>
			response.url().includes( '?wc-ajax=apply_coupon' ) &&
			response.status() === 200
	);
	await page.getByPlaceholder( 'Coupon code' ).fill( couponCode );
	await page.getByRole( 'button', { name: 'Apply coupon' } ).click();
	await responsePromise;
};

const expandCouponForm = async ( page ) => {
	await page
		.getByRole( 'link', {
			name: 'Click here to enter your code',
		} )
		.click();
	// This is to wait for the expand animation to finish, it avoids flakiness.
	await expect(
		page.locator( 'form.woocommerce-form-coupon' )
	).toHaveAttribute( 'style', '' );
};

test.describe(
	'Cart & Checkout Restricted Coupons',
	{
		tag: [
			'@payments',
			'@services',
			'@hpos',
			'@could-be-lower-level-test',
		],
	},
	() => {
		let firstProductId,
			secondProductId,
			firstCategoryId,
			secondCategoryId,
			shippingZoneId;
		const couponBatchId = [];

		test.beforeAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );

			// make sure the store address is US
			await api.post( 'settings/general/batch', {
				update: [
					{
						id: 'woocommerce_store_address',
						value: 'addr 1',
					},
					{
						id: 'woocommerce_store_city',
						value: 'San Francisco',
					},
					{
						id: 'woocommerce_default_country',
						value: 'US:CA',
					},
					{
						id: 'woocommerce_store_postcode',
						value: '94107',
					},
				],
			} );
			// make sure the currency is USD
			await api.put( 'settings/general/woocommerce_currency', {
				value: 'USD',
			} );
			// enable COD
			await api.put( 'payment_gateways/cod', {
				enabled: true,
			} );
			// add a shipping zone and method
			await api
				.post( 'shipping/zones', {
					name: 'Free Shipping',
				} )
				.then( ( response ) => {
					shippingZoneId = response.data.id;
				} );
			await api.post( `shipping/zones/${ shippingZoneId }/methods`, {
				method_id: 'free_shipping',
			} );
			// add categories
			await api
				.post( 'products/categories', {
					name: includedCategoryName,
				} )
				.then( ( response ) => {
					firstCategoryId = response.data.id;
				} );
			await api
				.post( 'products/categories', {
					name: excludedCategoryName,
				} )
				.then( ( response ) => {
					secondCategoryId = response.data.id;
				} );
			// add product
			await api
				.post( 'products', {
					name: includedProductName,
					type: 'simple',
					regular_price: '20.00',
					categories: [ { id: firstCategoryId } ],
				} )
				.then( ( response ) => {
					firstProductId = response.data.id;
				} );
			await api
				.post( 'products', {
					name: excludedProductName,
					type: 'simple',
					regular_price: '20.00',
					sale_price: '15.00',
					categories: [ { id: secondCategoryId } ],
				} )
				.then( ( response ) => {
					secondProductId = response.data.id;
				} );

			const restrictedCoupons = [
				{
					code: 'expired-coupon',
					discount_type: 'fixed_cart',
					amount: '10.00',
					description:
						'This coupon has expired and should not be usable by anyone.',
					date_expires: '2020-01-01T00:00:00',
				},
				{
					code: 'min-max-spend-individual',
					discount_type: 'fixed_cart',
					amount: '20.00',
					description:
						'This coupon requires an order amount between 50 and 200 dollars. It can only be used by itself.',
					minimum_amount: '50.00',
					maximum_amount: '200.00',
					individual_use: true,
				},
				{
					code: 'no-sale-use-limit',
					discount_type: 'fixed_cart',
					amount: '15.00',
					description:
						'This coupon can only be used twice, and only on items that are not on sale.',
					exclude_sale_items: true,
					usage_limit: 2,
				},
				{
					code: 'product-and-category-included',
					discount_type: 'fixed_cart',
					amount: '10.00',
					description:
						'This coupon can only be used for the specific products and categories.',
					product_ids: [ firstProductId ],
					product_categories: [ firstCategoryId ],
				},
				{
					code: 'product-and-category-excluded',
					discount_type: 'fixed_cart',
					amount: '20.00',
					description:
						'This coupon can not be used for specific products and categories.',
					excluded_product_ids: [ secondProductId ],
					excluded_product_categories: [ secondCategoryId ],
				},
				{
					code: 'email-restricted',
					discount_type: 'fixed_cart',
					amount: '25.00',
					description:
						'This coupon can only be used once by a specified user (email).',
					email_restrictions: [ 'homer@example.com' ],
					usage_limit_per_user: 1,
				},
			];

			// add coupons
			await api
				.post( 'coupons/batch', {
					create: restrictedCoupons,
				} )
				.then( ( response ) => {
					for ( let i = 0; i < response.data.create.length; i++ ) {
						couponBatchId.push( response.data.create[ i ].id );
					}
				} );
		} );

		test.beforeEach( async ( { context } ) => {
			// Shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();
		} );

		test.afterAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.delete( `products/${ firstProductId }`, {
				force: true,
			} );
			await api.delete( `products/${ secondProductId }`, {
				force: true,
			} );
			await api.delete( `products/categories/${ firstCategoryId }`, {
				force: true,
			} );
			await api.delete( `products/categories/${ secondCategoryId }`, {
				force: true,
			} );
			await api.post( 'coupons/batch', { delete: [ ...couponBatchId ] } );

			await api.put( 'payment_gateways/cod', {
				enabled: false,
			} );
			await api.delete( `shipping/zones/${ shippingZoneId }`, {
				force: true,
			} );
		} );

		test( 'expired coupon cannot be used', async ( { page, context } ) => {
			await test.step( 'Load cart page and try expired coupon usage', async () => {
				await addAProductToCart( page, firstProductId );
				await page.goto( '/cart/' );
				await applyCoupon( page, 'expired-coupon' );
				await expect(
					page.getByText( 'This coupon has expired.' )
				).toBeVisible();
			} );

			await context.clearCookies();

			await test.step( 'Load checkout page and try expired coupon usage', async () => {
				await addAProductToCart( page, firstProductId );
				await page.goto( '/checkout/' );
				await expandCouponForm( page );
				await applyCoupon( page, 'expired-coupon' );
				await expect(
					page.getByText( 'This coupon has expired.' )
				).toBeVisible();
			} );
		} );

		test( 'coupon requiring min and max amounts and can only be used alone can only be used within limits', async ( {
			page,
			context,
		} ) => {
			await test.step( 'Load cart page and try limited coupon usage', async () => {
				await addAProductToCart( page, firstProductId );
				await page.goto( '/cart/' );
				await applyCoupon( page, 'min-max-spend-individual' );
				// failed because we need to have at least $50 in cart (single product is only $20)
				await expect(
					page
						.getByRole( 'alert' )
						.getByText(
							'The minimum spend for this coupon is $50.00.'
						)
				).toBeVisible();

				// add a couple more in order to hit minimum spend
				await addAProductToCart( page, firstProductId, 2 );

				// passed because we're between 50 and 200 dollars
				await page.goto( '/cart/' );
				await applyCoupon( page, 'min-max-spend-individual' );
				await expect(
					page.getByText( 'Coupon code applied successfully.' )
				).toBeVisible();

				// fail because the min-max coupon can only be used by itself
				await page.goto( '/cart/' );
				await applyCoupon( page, 'no-sale-use-limit' );
				await expect(
					page.getByText(
						'Sorry, coupon "min-max-spend-individual" has already been applied and cannot be used in conjunction with other coupons.'
					)
				).toBeVisible();
			} );

			await context.clearCookies();

			await test.step( 'Load checkout page and try limited coupon usage', async () => {
				await addAProductToCart( page, firstProductId );

				await page.goto( '/checkout/' );
				await expandCouponForm( page );
				await applyCoupon( page, 'min-max-spend-individual' );
				// failed because we need to have at least $50 in cart (single product is only $20)
				await expect(
					page.getByText(
						'The minimum spend for this coupon is $50.00.'
					)
				).toBeVisible();

				// add a couple more in order to hit minimum spend
				await addAProductToCart( page, firstProductId, 2 );

				// passed because we're between 50 and 200 dollars
				await page.goto( '/checkout/' );
				await expandCouponForm( page );
				await applyCoupon( page, 'min-max-spend-individual' );
				await expect(
					page.getByText( 'Coupon code applied successfully.' )
				).toBeVisible();

				// fail because the min-max coupon can only be used by itself
				await page.goto( '/checkout/' );
				await expandCouponForm( page );
				await applyCoupon( page, 'no-sale-use-limit' );
				await expect(
					page.getByText(
						'Sorry, coupon "min-max-spend-individual" has already been applied and cannot be used in conjunction with other coupons.'
					)
				).toBeVisible();
			} );
		} );

		test( 'coupon cannot be used on sale item', async ( {
			page,
			context,
		} ) => {
			await test.step( 'Load cart page and try coupon usage on sale item', async () => {
				await addAProductToCart( page, secondProductId );
				await page.goto( '/cart/' );
				await applyCoupon( page, 'no-sale-use-limit' );
				// failed because this product is on sale.
				await expect(
					page.getByText(
						'Sorry, this coupon is not valid for sale items.'
					)
				).toBeVisible();
			} );

			await context.clearCookies();

			await test.step( 'Load checkout page and try coupon usage on sale item', async () => {
				await addAProductToCart( page, secondProductId );
				await page.goto( '/checkout/' );
				await expandCouponForm( page );
				await applyCoupon( page, 'no-sale-use-limit' );
				// failed because this product is on sale
				await expect(
					page.getByText(
						'Sorry, this coupon is not valid for sale items.'
					)
				).toBeVisible();
			} );
		} );

		test( 'coupon can only be used twice', async ( {
			page,
			context,
			baseURL,
		} ) => {
			const orderIds = [];

			// create 2 orders using the limited coupon
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			for ( let i = 0; i < 2; i++ ) {
				await api
					.post( 'orders', {
						status: 'completed',
						billing: {
							first_name: 'Marge',
							last_name: 'Simpson',
							email: 'marge.simpson@example.org',
						},
						line_items: [
							{
								product_id: firstProductId,
								quantity: 5,
							},
						],
						coupon_lines: [
							{
								code: 'no-sale-use-limit',
							},
						],
					} )
					.then( ( response ) => {
						orderIds.push = response.data.id;
					} );
			}

			await test.step( 'Load cart page and try over limit coupon usage', async () => {
				await addAProductToCart( page, firstProductId );
				await page.goto( '/cart/' );
				await applyCoupon( page, 'no-sale-use-limit' );
				// failed because this coupon code has been used too much
				await expect(
					page.getByText(
						'Coupon usage limit has been reached. Please try again after some time, or contact us for help.'
					)
				).toBeVisible();
			} );

			await context.clearCookies();

			await test.step( 'Load checkout page and try over limit coupon usage', async () => {
				await addAProductToCart( page, firstProductId );
				await page.goto( '/checkout/' );
				await expandCouponForm( page );
				await applyCoupon( page, 'no-sale-use-limit' );
				// failed because this coupon code has been used too much
				await expect(
					page.getByText(
						'Coupon usage limit has been reached. Please try again after some time, or contact us for help.'
					)
				).toBeVisible();
			} );

			await console.log( orderIds );
			// clean up the orders
			await api.post( 'orders/batch', { delete: [ ...orderIds ] } );
		} );

		test( 'coupon cannot be used on certain products/categories (included product/category)', async ( {
			page,
			context,
		} ) => {
			await test.step( 'Load cart page and try included certain items coupon usage', async () => {
				await addAProductToCart( page, secondProductId );
				await page.goto( '/cart/' );
				await applyCoupon( page, 'product-and-category-included' );
				// failed because this product is not included for coupon
				await expect(
					page.getByText(
						'Sorry, this coupon is not applicable to selected products.'
					)
				).toBeVisible();
			} );

			await context.clearCookies();

			await test.step( 'Load checkout page and try included certain items coupon usage', async () => {
				await addAProductToCart( page, secondProductId );
				await page.goto( '/checkout/' );
				await expandCouponForm( page );
				await applyCoupon( page, 'product-and-category-included' );
				// failed because this product is not included for coupon
				await expect(
					page.getByText(
						'Sorry, this coupon is not applicable to selected products.'
					)
				).toBeVisible();
			} );
		} );

		test( 'coupon can be used on certain products/categories', async ( {
			page,
			context,
		} ) => {
			await test.step( 'Load cart page and try on certain products coupon usage', async () => {
				await addAProductToCart( page, firstProductId );
				await page.goto( '/cart/' );
				await applyCoupon( page, 'product-and-category-included' );
				// succeeded
				await expect(
					page.getByText( 'Coupon code applied successfully.' )
				).toBeVisible();
			} );

			await context.clearCookies();

			await test.step( 'Load checkout page and try on certain products coupon usage', async () => {
				await addAProductToCart( page, firstProductId );

				await page.goto( '/checkout/' );
				await expandCouponForm( page );
				await applyCoupon( page, 'product-and-category-included' );
				// succeeded
				await expect(
					page.getByText( 'Coupon code applied successfully.' )
				).toBeVisible();
			} );
		} );

		test( 'coupon cannot be used on specific products/categories (excluded product/category)', async ( {
			page,
			context,
		} ) => {
			await test.step( 'Load cart page and try excluded items coupon usage', async () => {
				await addAProductToCart( page, secondProductId );
				await page.goto( '/cart/' );
				await applyCoupon( page, 'product-and-category-included' );
				// failed because this product is excluded from coupon
				await expect(
					page.getByText(
						'Sorry, this coupon is not applicable to selected products.'
					)
				).toBeVisible();
			} );

			await context.clearCookies();

			await test.step( 'Load checkout page and try excluded items coupon usage', async () => {
				await addAProductToCart( page, secondProductId );
				await page.goto( '/checkout/' );
				await expandCouponForm( page );
				await applyCoupon( page, 'product-and-category-included' );
				// failed because this product is excluded from coupon
				await expect(
					page.getByText(
						'Sorry, this coupon is not applicable to selected products.'
					)
				).toBeVisible();
			} );
		} );

		test( 'coupon can be used on other products/categories', async ( {
			page,
			context,
		} ) => {
			await test.step( 'Load cart page and try coupon usage on other items', async () => {
				await addAProductToCart( page, firstProductId );
				await page.goto( '/cart/' );
				await applyCoupon( page, 'product-and-category-included' );
				// succeeded
				await expect(
					page.getByText( 'Coupon code applied successfully.' )
				).toBeVisible();
			} );

			await context.clearCookies();

			await test.step( 'Load checkout page and try coupon usage on other items', async () => {
				await addAProductToCart( page, firstProductId );

				await page.goto( '/checkout/' );
				await expandCouponForm( page );
				await applyCoupon( page, 'product-and-category-included' );
				// succeeded
				await expect(
					page.getByText( 'Coupon code applied successfully.' )
				).toBeVisible();
			} );
		} );

		test( 'coupon cannot be used by any customer on cart (email restricted)', async ( {
			page,
		} ) => {
			await addAProductToCart( page, firstProductId );
			await page.goto( '/cart/' );
			await applyCoupon( page, 'email-restricted' );
			await expect(
				page.getByText(
					'Please enter a valid email at checkout to use coupon code "email-restricted".'
				)
			).toBeVisible();
		} );

		test( 'coupon cannot be used by any customer on checkout (email restricted)', async ( {
			page,
		} ) => {
			await addAProductToCart( page, firstProductId );

			await page.goto( '/checkout/' );

			await page.getByLabel( 'First name' ).first().fill( 'Marge' );
			await page.getByLabel( 'Last name' ).first().fill( 'Simpson' );
			await page
				.getByLabel( 'Street address' )
				.first()
				.fill( '123 Evergreen Terrace' );
			await page
				.getByLabel( 'Town / City' )
				.first()
				.fill( 'Springfield' );
			await page.getByLabel( 'ZIP Code' ).first().fill( '55555' );
			await page.getByLabel( 'Phone' ).first().fill( '555-555-5555' );
			await page
				.getByLabel( 'Email address' )
				.first()
				.fill( 'marge.simpson@example.org' );

			await expandCouponForm( page );
			await applyCoupon( page, 'email-restricted' );
			await expect(
				page.getByText(
					'Please enter a valid email to use coupon code "email-restricted".'
				)
			).toBeVisible();
		} );

		test( 'coupon can be used by the right customer (email restricted) but only once', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );

			await addAProductToCart( page, firstProductId );

			await page.goto( '/checkout/' );

			await page.getByLabel( 'First name' ).first().fill( 'Homer' );
			await page.getByLabel( 'Last name' ).first().fill( 'Simpson' );
			await page
				.getByLabel( 'Street address' )
				.first()
				.fill( '123 Evergreen Terrace' );
			await page
				.getByLabel( 'Town / City' )
				.first()
				.fill( 'Springfield' );
			await page.getByLabel( 'ZIP Code' ).first().fill( '55555' );
			await page.getByLabel( 'Phone' ).first().fill( '555-555-5555' );
			await page
				.getByLabel( 'Email address' )
				.first()
				.fill( 'homer@example.com' );

			await expandCouponForm( page );
			await applyCoupon( page, 'email-restricted' );
			await expect(
				page.getByText( 'Coupon code applied successfully.' )
			).toBeVisible();

			await page.getByRole( 'button', { name: 'Place order' } ).click();

			await expect(
				page.getByText( 'Your order has been received' )
			).toBeVisible();
			const newOrderId = getOrderIdFromUrl( page );

			// try to order a second time, but should get an error
			await addAProductToCart( page, firstProductId );

			await page.goto( '/checkout/' );

			await page.getByLabel( 'First name' ).first().fill( 'Homer' );
			await page.getByLabel( 'Last name' ).first().fill( 'Simpson' );
			await page
				.getByLabel( 'Street address' )
				.first()
				.fill( '123 Evergreen Terrace' );
			await page
				.getByLabel( 'Town / City' )
				.first()
				.fill( 'Springfield' );
			await page.getByLabel( 'ZIP Code' ).first().fill( '55555' );
			await page.getByLabel( 'Phone' ).first().fill( '555-555-5555' );
			await page
				.getByLabel( 'Email address' )
				.first()
				.fill( 'homer@example.com' );

			await expandCouponForm( page );
			await applyCoupon( page, 'email-restricted' );
			await expect(
				page.getByText( 'Coupon code applied successfully.' )
			).toBeVisible();

			await page.getByRole( 'button', { name: 'Place order' } ).click();

			await expect(
				page.getByText( 'Coupon usage limit has been reached.' )
			).toBeVisible();

			// clean up the order we just made
			await api.delete( `orders/${ newOrderId }`, { force: true } );
		} );
	}
);
