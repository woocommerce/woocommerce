const { test, expect, request } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const pageTitle = 'Mini Cart';
const miniCartButton = '.wc-block-mini-cart__button';

const simpleProductName = 'Single Hundred Product';
const simpleProductDesc = 'Lorem ipsum dolor sit amet.';
const singleProductPrice = '100.00';
const singleProductSalePrice = '50.00';

let product1Id;

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
				product1Id = response.data.id;
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
			delete: [ product1Id ],
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

	test( 'can see empty mini cart', async ( { page } ) => {
		// create a new page with mini cart block
		await page.goto( 'wp-admin/post-new.php?post_type=page' );

		const welcomeModalVisible = await page
			.getByRole( 'heading', {
				name: 'Welcome to the block editor',
			} )
			.isVisible();

		if ( welcomeModalVisible ) {
			await page.getByRole( 'button', { name: 'Close' } ).click();
		}

		await page
			.getByRole( 'textbox', { name: 'Add Title' } )
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
		await page.goto( '/mini-cart' );
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
		const slug = simpleProductName.replace( / /gi, '-' ).toLowerCase();
		// add product to cart
		await page.goto( `product/${ slug }` );
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();

		// go to page with mini cart block and test with the product added
		await page.goto( '/mini-cart' );
		await expect(
			page.locator( '.wc-block-mini-cart__button' )
		).toContainText( `$${ singleProductSalePrice }` );
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
		await expect(
			page.locator( '.wc-block-mini-cart__button' )
		).toContainText( `$${ singleProductSalePrice * 2 }` );
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
		await page.goto( `product/${ slug }` );
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();
		await page.goto( '/mini-cart' );
		await page.locator( miniCartButton ).click();
		await page.getByRole( 'link', { name: 'View my cart' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Cart', exact: true } )
		).toBeVisible();
		await expect( page.locator( miniCartButton ) ).toBeHidden();

		// go to mini cart and test redirection from mini cart to checkout
		await page.goto( '/mini-cart' );
		await page.locator( miniCartButton ).click();
		await page.getByRole( 'link', { name: 'Go to checkout' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Checkout', exact: true } )
		).toBeVisible();
		await expect( page.locator( miniCartButton ) ).toBeHidden();
	} );
} );
