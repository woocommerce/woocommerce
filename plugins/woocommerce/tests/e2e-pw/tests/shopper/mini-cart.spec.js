const { test, expect, request } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const { closeWelcomeModal } = require( '../../utils/editor' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const pageTitle = 'Mini Cart';
const pageSlug = pageTitle.replace( / /gi, '-' ).toLowerCase();
const miniCartButton = '.wc-block-mini-cart__button';
const miniCartBadge = '.wc-block-mini-cart__badge';

const simpleProductName = 'Single Hundred Product';
const simpleProductDesc = 'Lorem ipsum dolor sit amet.';
const singleProductPrice = '100.00';
const singleProductSalePrice = '50.00';
const totalInclusiveTax = +singleProductSalePrice + 5 + 2.5;

let productId, countryTaxId, stateTaxId, shippingZoneId;

test.describe( 'Mini Cart block page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// add product
		await api
			.post( 'products', {
				name: simpleProductName,
				description: simpleProductDesc,
				type: 'simple',
				regular_price: singleProductPrice,
				sale_price: singleProductSalePrice,
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		// add tax
		await api.put( 'settings/general/woocommerce_calc_taxes', {
			value: 'yes',
		} );
		await api.put( 'settings/tax/woocommerce_tax_display_cart', {
			value: 'excl',
		} );
		await api
			.post( 'taxes', {
				country: 'US',
				state: '*',
				cities: '*',
				postcodes: '*',
				rate: '10',
				name: 'Country Tax',
				shipping: false,
				priority: 1,
			} )
			.then( ( response ) => {
				countryTaxId = response.data.id;
			} );
		await api
			.post( 'taxes', {
				country: '*',
				state: 'CA',
				cities: '*',
				postcodes: '*',
				rate: '5',
				name: 'State Tax',
				shipping: false,
				priority: 2,
			} )
			.then( ( response ) => {
				stateTaxId = response.data.id;
			} );
		// add shipping zone, location and method
		await api
			.post( 'shipping/zones', {
				name: 'US Free Shipping',
			} )
			.then( ( response ) => {
				shippingZoneId = response.data.id;
			} );
		await api.put( `shipping/zones/${ shippingZoneId }/locations`, [
			{
				code: 'US',
			},
		] );
		await api.post( `shipping/zones/${ shippingZoneId }/methods`, {
			method_id: 'free_shipping',
		} );
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
		await api.put( 'settings/general/woocommerce_calc_taxes', {
			value: 'no',
		} );
		await api.post( 'taxes/batch', {
			delete: [ countryTaxId, stateTaxId ],
		} );
		await api.delete( `shipping/zones/${ shippingZoneId }`, {
			force: true,
		} );
		const base64auth = Buffer.from(
			`${ admin.username }:${ admin.password }`
		).toString( 'base64' );
		const wpApi = await request.newContext( {
			baseURL: `${ baseURL }/wp-json/wp/v2/`,
			extraHTTPHeaders: {
				Authorization: `Basic ${ base64auth }`,
			},
		} );
		let response = await wpApi.get( `pages` );
		const allPages = await response.json();
		await allPages.forEach( async ( page ) => {
			if ( page.title.rendered === pageTitle ) {
				response = await wpApi.delete( `pages/${ page.id }`, {
					data: {
						force: true,
					},
				} );
			}
		} );
	} );

	test.beforeEach( async ( { context } ) => {
		// shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();
	} );

	test( 'can see empty mini cart', async ( { page } ) => {
		// create a new page with mini cart block
		await page.goto( 'wp-admin/post-new.php?post_type=page' );
		await page.waitForLoadState( 'networkidle' );
		await page.locator( 'input[name="log"]' ).fill( admin.username );
		await page.locator( 'input[name="pwd"]' ).fill( admin.password );
		await page.locator( 'text=Log In' ).click();

		await closeWelcomeModal( { page } );

		await page
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( pageTitle );
		await page.getByRole( 'button', { name: 'Add default block' } ).click();
		await page
			.getByRole( 'document', {
				name: 'Empty block; start writing or type forward slash to choose a block',
			} )
			.fill( '/mini' );
		await page.keyboard.press( 'Enter' );
		await page
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await expect(
			page.getByText( `${ pageTitle } is now live.` )
		).toBeVisible();

		// go to the page to test mini cart
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();
		await page.locator( miniCartButton ).click();
		await expect(
			page.getByText( 'Your cart is currently empty!' )
		).toBeVisible();
		await page.getByRole( 'link', { name: 'Start shopping' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Shop' } )
		).toBeVisible();
	} );

	test( 'can proceed to mini cart, observe it and proceed to the checkout', async ( {
		page,
	} ) => {
		// add product to cart
		await page.goto( `/shop/?add-to-cart=${ productId }`, {
			waitUntil: 'networkidle',
		} );

		// go to page with mini cart block and test with the product added
		await page.goto( pageSlug );
		await expect( page.locator( miniCartBadge ) ).toContainText( '1' );
		await page.locator( miniCartButton ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Your cart (1 item)' } )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: simpleProductName } )
		).toBeVisible();
		await expect(
			page.locator( '.wc-block-components-product-badge' )
		).toContainText( `Save $${ singleProductSalePrice }` );
		await expect( page.getByText( simpleProductDesc ) ).toBeVisible();
		await page.getByRole( 'button' ).filter( { hasText: '＋' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Your cart (2 items)' } )
		).toBeVisible();
		await expect( page.locator( miniCartBadge ) ).toContainText( '2' );
		await expect(
			page.locator( '.wc-block-components-totals-item__value' )
		).toContainText( `$${ singleProductSalePrice * 2 }` );
		await page
			.getByRole( 'button' )
			.filter( { hasText: 'Remove item' } )
			.click();
		await expect(
			page.getByText( 'Your cart is currently empty!' )
		).toBeVisible();

		// add product to cart and redirect from mini to regular cart
		await page.goto( `/shop/?add-to-cart=${ productId }`, {
			waitUntil: 'networkidle',
		} );
		await page.goto( pageSlug );
		await page.locator( miniCartButton ).click();
		await page.getByRole( 'link', { name: 'View my cart' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Cart', exact: true } )
		).toBeVisible();
		await expect( page.locator( miniCartButton ) ).toBeHidden();

		// go to mini cart and test redirection from mini cart to checkout
		await page.goto( pageSlug );
		await page.locator( miniCartButton ).click();
		await page.getByRole( 'link', { name: 'Go to checkout' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Checkout', exact: true } )
		).toBeVisible();
		await expect( page.locator( miniCartButton ) ).toBeHidden();
	} );

	test( 'can see mini cart total price inclusive with tax', async ( {
		page,
		baseURL,
	} ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// set inlcuding tax prices
		await api.put( 'settings/tax/woocommerce_tax_display_cart', {
			value: 'incl',
		} );

		// add product to cart
		await page.goto( `/shop/?add-to-cart=${ productId }`, {
			waitUntil: 'networkidle',
		} );

		// go to cart and add shipping details to calculate tax
		await page.goto( '/cart/' ); // we will use the old cart for this purpose
		await page.locator( '.shipping-calculator-button' ).click();
		await page.getByLabel( 'Town / City' ).fill( 'Sacramento' );
		await page.getByLabel( 'ZIP Code' ).fill( '96000' );
		await page
			.getByRole( 'button', { name: 'Update', exact: true } )
			.click();
		await expect( page.locator( '.is-info' ) ).toContainText(
			'Shipping costs updated.'
		);

		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();
		await expect( page.locator( miniCartBadge ) ).toContainText( '1' );
		await page.locator( miniCartButton ).click();
		await expect(
			page.locator( '.wc-block-components-totals-item__value' )
		).toContainText( `$${ totalInclusiveTax }` );
		await page
			.getByRole( 'button' )
			.filter( { hasText: 'Remove item' } )
			.click();
		await expect(
			page.getByText( 'Your cart is currently empty!' )
		).toBeVisible();
	} );
} );
