const { test, expect } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const simpleProductName = 'Single Simple Product';
const simpleProductDesc = 'Lorem ipsum dolor sit amet.';
const singleProductFullPrice = '110.00';
const singleProductSalePrice = '55.00';
const firstCrossSellProductPrice = '25.00';
const secondCrossSellProductPrice = '15.00';
const doubleProductsPrice = +singleProductSalePrice * 2;
const singleProductWithCrossSellProducts =
	+singleProductFullPrice +
	+firstCrossSellProductPrice +
	+secondCrossSellProductPrice;

const pageTitle = 'Cart Block';
const pageSlug = pageTitle.replace( / /gi, '-' ).toLowerCase();

let product1Id, product2Id, product3Id;

test.describe( 'Cart Block page', () => {
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
				name: `${ simpleProductName } Cross-Sell 1`,
				type: 'simple',
				regular_price: firstCrossSellProductPrice,
			} )
			.then( ( response ) => {
				product2Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: `${ simpleProductName } Cross-Sell 2`,
				type: 'simple',
				regular_price: secondCrossSellProductPrice,
			} )
			.then( ( response ) => {
				product3Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: simpleProductName,
				description: simpleProductDesc,
				type: 'simple',
				regular_price: singleProductFullPrice,
				sale_price: singleProductSalePrice,
				cross_sell_ids: [ product2Id, product3Id ],
				manage_stock: true,
				stock_quantity: 2,
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
			delete: [ product1Id, product2Id, product3Id ],
		} );
	} );

	test.beforeEach( async ( { context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();
	} );

	test( 'can see empty cart block', async ( { page } ) => {
		// create a new page with cart block
		await page.goto( 'wp-admin/post-new.php?post_type=page' );
		await page.waitForLoadState( 'networkidle' );
		await page.locator( 'input[name="log"]' ).fill( admin.username );
		await page.locator( 'input[name="pwd"]' ).fill( admin.password );
		await page.locator( 'text=Log In' ).click();

		// Close welcome popup if prompted
		try {
			await page
				.getByLabel( 'Close', { exact: true } )
				.click( { timeout: 5000 } );
		} catch ( error ) {
			console.log( "Welcome modal wasn't present, skipping action." );
		}

		await page
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( pageTitle );
		await page.getByRole( 'button', { name: 'Add default block' } ).click();
		await page
			.getByRole( 'document', {
				name: 'Empty block; start writing or type forward slash to choose a block',
			} )
			.fill( '/cart' );
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

		// go to the page to test empty cart block
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();
		await expect(
			page.getByText( 'Your cart is currently empty!' )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: 'Browse store' } )
		).toBeVisible();
		await page.getByRole( 'link', { name: 'Browse store' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Shop' } )
		).toBeVisible();
	} );

	test( 'can add product to cart block, increase quantity, manage cross-sell products and proceed to checkout', async ( {
		page,
	} ) => {
		// add product to cart block
		await page.goto( `/shop/?add-to-cart=${ product1Id }` );
		await page.waitForLoadState( 'networkidle' );
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: simpleProductName, exact: true } )
		).toBeVisible();
		await expect( page.getByText( simpleProductDesc ) ).toBeVisible();
		await expect(
			page.getByText( `Save $${ singleProductSalePrice }` )
		).toBeVisible();

		// increase product quantity to its maximum
		await expect( page.getByText( '2 left in stock' ) ).toBeVisible();
		await page
			.getByRole( 'button' )
			.filter( { hasText: '＋', exact: true } )
			.click();
		await expect(
			page.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
		).toContainText( `$${ doubleProductsPrice.toString() }` );
		await expect(
			page.getByRole( 'button' ).filter( { hasText: '＋', exact: true } )
		).toBeDisabled();

		// add cross-sell products to cart
		await expect(
			page.getByRole( 'heading', { name: 'You may be interested in…' } )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', {
				name: `${ simpleProductName } Cross-Sell 1`,
				exact: true,
			} )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', {
				name: `${ simpleProductName } Cross-Sell 2`,
				exact: true,
			} )
		).toBeVisible();
		await page
			.getByRole( 'link', {
				name: `${ simpleProductName } Cross-Sell 1`,
				exact: true,
			} )
			.click();
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();
		await page.goto( pageSlug );
		await expect(
			page.getByRole( 'heading', { name: pageTitle } )
		).toBeVisible();
		await page.locator( '.add_to_cart_button' ).click();
		await expect(
			page.getByRole( 'heading', { name: 'You may be interested in…' } )
		).toBeHidden();
		await expect(
			page.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
		).toContainText(
			`$${ singleProductWithCrossSellProducts.toString() }`
		);

		// remove cross-sell products from cart
		await page.locator( ':nth-match(:text("Remove item"), 3)' ).click();
		await page.locator( ':nth-match(:text("Remove item"), 2)' ).click();
		await expect(
			page.getByRole( 'heading', { name: 'You may be interested in…' } )
		).toBeVisible();

		// check if the link to proceed to the checkout exists
		await expect(
			page.getByRole( 'link', {
				name: 'Proceed to Checkout',
			} )
		).toBeVisible();

		// remove product from cart
		await page.locator( ':text("Remove item")' ).click();
		await expect(
			page.getByText( 'Your cart is currently empty!' )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: 'Browse store' } )
		).toBeVisible();
	} );
} );
