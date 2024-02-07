const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const productName = 'Cart product test';
const productPrice = '13.99';
const twoProductPrice = +productPrice * 2;
const fourProductPrice = +productPrice * 4;

test.describe( 'Cart page', () => {
	let productId, product2Id, product3Id;

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// make sure the currency is USD
		await api.put( 'settings/general/woocommerce_currency', {
			value: 'USD',
		} );
		// add 3 products
		await api
			.post( 'products', {
				name: `${ productName } cross-sell 1`,
				type: 'simple',
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				product2Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: `${ productName } cross-sell 2`,
				type: 'simple',
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				product3Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: productName,
				type: 'simple',
				regular_price: productPrice,
				cross_sell_ids: [ product2Id, product3Id ],
				manage_stock: true,
				stock_quantity: 2,
			} )
			.then( ( response ) => {
				productId = response.data.id;
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
		await api.post( 'products/batch', {
			delete: [ productId, product2Id, product3Id ],
		} );
	} );

	test( 'should display no item in the cart', async ( { page } ) => {
		await page.goto( '/cart/' );
		await expect(
			page.getByText( 'Your cart is currently empty.' )
		).toBeVisible();
	} );

	test( 'should add the product to the cart from the shop page', async ( {
		page,
	} ) => {
		await page.goto( '/shop/?orderby=date' );
		await page
			.locator( `a[data-product_id='${ productId }'][href*=add-to-cart]` )
			.click();
		await page.waitForLoadState( 'networkidle' );

		await page.goto( '/cart/' );
		await expect( page.locator( 'td.product-name' ) ).toContainText(
			productName
		);
	} );

	test( 'should increase item quantity when "Add to cart" of the same product is clicked', async ( {
		page,
	} ) => {
		let qty = 2;
		while ( qty-- ) {
			// (load the shop in case redirection enabled)
			await page.goto( '/shop/?orderby=date' );
			await page
				.locator(
					`a[data-product_id='${ productId }'][href*=add-to-cart]`
				)
				.click();
			await page.waitForLoadState( 'networkidle' );
		}

		await page.goto( '/cart/' );
		await expect( page.locator( 'input.qty' ) ).toHaveValue( '2' );
	} );

	test( 'should update quantity when updated via quantity input', async ( {
		page,
	} ) => {
		await page.goto( '/shop/?orderby=date' );
		await page
			.locator( `a[data-product_id='${ productId }'][href*=add-to-cart]` )
			.click();
		await page.waitForLoadState( 'networkidle' );

		await page.goto( '/cart/' );
		await page.locator( 'input.qty' ).fill( '2' );
		await page.locator( 'text=Update cart' ).click();

		await expect( page.locator( '.order-total .amount' ) ).toContainText(
			`$${ twoProductPrice }`
		);
	} );

	test( 'should remove the item from the cart when remove is clicked', async ( {
		page,
	} ) => {
		await page.goto( '/shop/?orderby=date' );
		await page
			.locator( `a[data-product_id='${ productId }'][href*=add-to-cart]` )
			.click();
		await page.waitForLoadState( 'networkidle' );
		await page.goto( '/cart/' );

		// make sure that the product is in the cart
		await expect( page.locator( '.order-total .amount' ) ).toContainText(
			`$${ productPrice }`
		);

		await page.locator( 'a.remove' ).click();

		await expect( page.locator( '.woocommerce-info' ) ).toContainText(
			'Your cart is currently empty.'
		);
	} );

	test( 'should update subtotal in cart totals when adding product to the cart', async ( {
		page,
	} ) => {
		await page.goto( '/shop/?orderby=date' );
		await page
			.locator( `a[data-product_id='${ productId }'][href*=add-to-cart]` )
			.click();
		await page.waitForLoadState( 'networkidle' );

		await page.goto( '/cart/' );
		await expect( page.locator( '.cart-subtotal .amount' ) ).toContainText(
			`$${ productPrice }`
		);

		await page.locator( 'input.qty' ).fill( '2' );
		await page.locator( 'text=Update cart' ).click();

		await expect( page.locator( '.order-total .amount' ) ).toContainText(
			`$${ twoProductPrice }`
		);
	} );

	test( 'should go to the checkout page when "Proceed to Checkout" is clicked', async ( {
		page,
	} ) => {
		await page.goto( '/shop/?orderby=date' );
		await page
			.locator( `a[data-product_id='${ productId }'][href*=add-to-cart]` )
			.click();
		await page.waitForLoadState( 'networkidle' );

		await page.goto( '/cart/' );

		await page.locator( '.checkout-button' ).click();

		await expect( page.locator( '#order_review' ) ).toBeVisible();
	} );

	test( 'can manage cross-sell products and maximum item quantity', async ( {
		page,
	} ) => {
		// add same product to cart twice time
		for ( let i = 1; i < 3; i++ ) {
			await page.goto( `/shop/?add-to-cart=${ productId }` );
			await page.waitForLoadState( 'networkidle' );
			await expect(
				page.locator( '.woocommerce-message' )
			).toContainText(
				`“${ productName }” has been added to your cart.`
			);
		}

		// add the same product the third time
		await page.goto( `/shop/?add-to-cart=${ productId }` );
		await page.waitForLoadState( 'networkidle' );
		await expect( page.locator( '.woocommerce-error' ) ).toContainText(
			'You cannot add that amount to the cart — we have 2 in stock and you already have 2 in your cart.'
		);
		await page.goto( '/cart/' );

		// attempt to increase quantity over quantity limit
		await page.getByLabel( 'Product quantity' ).fill( '3' );
		await page.locator( 'text=Update cart' ).click();
		await expect( page.locator( '.order-total .amount' ) ).toContainText(
			`$${ twoProductPrice }`
		);

		// add cross-sell products to cart
		await expect( page.locator( '.cross-sells' ) ).toContainText(
			'You may be interested in…'
		);
		await page
			.getByLabel( `Add to cart: “${ productName } cross-sell 1”` )
			.click();
		await page
			.getByLabel( `Add to cart: “${ productName } cross-sell 2”` )
			.click();
		await page.waitForLoadState( 'networkidle' );

		// reload page and confirm added products
		await page.reload();
		await page.waitForLoadState( 'networkidle' );
		await expect( page.locator( '.cross-sells' ) ).toBeHidden();
		await expect( page.locator( '.order-total .amount' ) ).toContainText(
			`$${ fourProductPrice }`
		);

		// remove cross-sell products from cart
		await page
			.getByLabel( `Remove ${ productName } cross-sell 1 from cart` )
			.click();
		await expect(
			page.locator( '.woocommerce-message' )
		).toContainText( `“${ productName } cross-sell 1” removed.` );
		await page
			.getByLabel( `Remove ${ productName } cross-sell 2 from cart` )
			.click();
		await expect(
			page
				.locator( '.woocommerce-message' )
				.first()
		).toContainText( `“${ productName } cross-sell 2” removed.` );

		// check if you see now cross-sell products
		await page.reload();
		await expect( page.locator( '.cross-sells' ) ).toContainText(
			'You may be interested in…'
		);
		await expect(
			page.getByLabel( `Add to cart: “${ productName } cross-sell 1”` )
		).toBeVisible();
		await expect(
			page.getByLabel( `Add to cart: “${ productName } cross-sell 2”` )
		).toBeVisible();
	} );
} );
