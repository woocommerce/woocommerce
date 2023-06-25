const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const productName = 'Cart product test';
const productPrice = '13.99';
const twoProductPrice = +productPrice * 2;

test.describe( 'Cart page', () => {
	let productId;

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
		// add products
		await api
			.post( 'products', {
				name: productName,
				type: 'simple',
				regular_price: productPrice,
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
		await api.delete( `products/${ productId }`, {
			force: true,
		} );
	} );

	test( 'should display no item in the cart', async ( { page } ) => {
		await page.goto( '/cart/' );
		await expect( page.locator( '.cart-empty' ) ).toContainText(
			'Your cart is currently empty.'
		);
	} );

	test( 'should add the product to the cart from the shop page', async ( {
		page,
	} ) => {
		await page.goto( '/shop/?orderby=date' );
		await page.click(
			`a[data-product_id='${ productId }'][href*=add-to-cart]`
		);
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
			await page.click(
				`a[data-product_id='${ productId }'][href*=add-to-cart]`
			);
			await page.waitForLoadState( 'networkidle' );
		}

		await page.goto( '/cart/' );
		await expect( page.locator( 'input.qty' ) ).toHaveValue( '2' );
	} );

	test( 'should update quantity when updated via quantity input', async ( {
		page,
	} ) => {
		await page.goto( '/shop/?orderby=date' );
		await page.click(
			`a[data-product_id='${ productId }'][href*=add-to-cart]`
		);
		await page.waitForLoadState( 'networkidle' );

		await page.goto( '/cart/' );
		await page.fill( 'input.qty', '2' );
		await page.click( 'text=Update cart' );

		await expect( page.locator( '.order-total .amount' ) ).toContainText(
			`$${ twoProductPrice }`
		);
	} );

	test( 'should remove the item from the cart when remove is clicked', async ( {
		page,
	} ) => {
		await page.goto( '/shop/?orderby=date' );
		await page.click(
			`a[data-product_id='${ productId }'][href*=add-to-cart]`
		);
		await page.waitForLoadState( 'networkidle' );
		await page.goto( '/cart/' );

		// make sure that the product is in the cart
		await expect( page.locator( '.order-total .amount' ) ).toContainText(
			`$${ productPrice }`
		);

		await page.click( 'a.remove' );

		await expect( page.locator( '.woocommerce-info' ) ).toContainText(
			'Your cart is currently empty.'
		);
	} );

	test( 'should update subtotal in cart totals when adding product to the cart', async ( {
		page,
	} ) => {
		await page.goto( '/shop/?orderby=date' );
		await page.click(
			`a[data-product_id='${ productId }'][href*=add-to-cart]`
		);
		await page.waitForLoadState( 'networkidle' );

		await page.goto( '/cart/' );
		await expect( page.locator( '.cart-subtotal .amount' ) ).toContainText(
			`$${ productPrice }`
		);

		await page.fill( 'input.qty', '2' );
		await page.click( 'text=Update cart' );

		await expect( page.locator( '.order-total .amount' ) ).toContainText(
			`$${ twoProductPrice }`
		);
	} );

	test( 'should go to the checkout page when "Proceed to Checkout" is clicked', async ( {
		page,
	} ) => {
		await page.goto( '/shop/?orderby=date' );
		await page.click(
			`a[data-product_id='${ productId }'][href*=add-to-cart]`
		);
		await page.waitForLoadState( 'networkidle' );

		await page.goto( '/cart/' );

		await page.click( '.checkout-button' );

		await expect( page.locator( '#order_review' ) ).toBeVisible();
	} );
} );
