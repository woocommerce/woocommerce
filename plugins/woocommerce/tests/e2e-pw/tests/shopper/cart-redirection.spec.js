const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

test.describe( 'Cart > Redirect to cart from shop', () => {
	let productId;
	const productName = 'A redirect product test';

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// add products
		await api
			.post( 'products', {
				name: productName,
				type: 'simple',
				regular_price: '17.99',
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		await api.put(
			'settings/products/woocommerce_cart_redirect_after_add',
			{
				value: 'yes',
			}
		);
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
		await api.put(
			'settings/products/woocommerce_cart_redirect_after_add',
			{
				value: 'no',
			}
		);
	} );

	test( 'can redirect user to cart from shop page', async ( { page } ) => {
		await page.goto( '/shop/' );
		await page
			.locator( `a[data-product_id='${ productId }'][href*=add-to-cart]` )
			.click();
		await page.waitForLoadState( 'networkidle' );

		await expect( page ).toHaveURL( /.*\/cart/ );
		await expect( page.locator( 'td.product-name' ) ).toContainText(
			productName
		);
	} );

	test( 'can redirect user to cart from detail page', async ( { page } ) => {
		await page.goto( '/shop/' );
		await page.locator( `text=${ productName }` ).click();
		await page.waitForLoadState( 'networkidle' );

		await page.getByRole( 'button', { name: 'Add to cart' } ).click();

		await expect( page ).toHaveURL( /.*\/cart/ );
		await expect( page.locator( 'td.product-name' ) ).toContainText(
			productName
		);
	} );
} );
