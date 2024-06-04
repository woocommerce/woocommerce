const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { addAProductToCart } = require( '../../utils/cart' );

const productName = `Cart product test ${ Date.now() }`;
const productPrice = '13.99';

test.describe( 'Add to Cart behavior', () => {
	let productId;

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
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
		await api.post( 'products/batch', {
			delete: [ productId ],
		} );
	} );

	test( 'should add only one product to the cart with AJAX add to cart buttons disabled and "Geolocate (with page caching support)" as the default customer location', async ( {
		page,
		baseURL,
	} ) => {
		// Set settings combination that allowed reproducing the bug.
		// @see https://github.com/woocommerce/woocommerce/issues/33077
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.put(
			'settings/general/woocommerce_default_customer_address',
			{
				value: 'geolocation_ajax',
			}
		);
		await api.put(
			'settings/products/woocommerce_enable_ajax_add_to_cart',
			{
				value: 'no',
			}
		);
		await addAProductToCart( page, productId );
		await page.goto( '/cart/' );
		await expect( page.locator( 'td.product-name' ) ).toContainText(
			productName
		);
		await expect( page.getByLabel( 'Product quantity' ) ).toHaveValue(
			'1'
		);

		// Reset settings.
		await api.put(
			'settings/general/woocommerce_default_customer_address',
			{
				value: 'base',
			}
		);
		await api.put(
			'settings/products/woocommerce_enable_ajax_add_to_cart',
			{
				value: 'yes',
			}
		);
	} );
} );
