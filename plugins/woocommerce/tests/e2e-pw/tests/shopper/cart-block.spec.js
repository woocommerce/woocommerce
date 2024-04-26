const { test: baseTest, expect } = require( '../../fixtures/fixtures' );
const {
	goToPageEditor,
	fillPageTitle,
	insertBlockByShortcut,
	publishPage,
} = require( '../../utils/editor' );
const { addAProductToCart } = require( '../../utils/cart' );
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

let product1Id, product2Id, product3Id;

baseTest.describe( 'Cart Block page', () => {
	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
		testPageTitlePrefix: 'Cart Block',
	} );

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

	test( 'can see empty cart, add and remove simple & cross sell product, increase to max quantity', async ( {
		page,
		testPage,
	} ) => {
		await goToPageEditor( { page } );
		await fillPageTitle( page, testPage.title );
		await insertBlockByShortcut( page, '/cart' );
		await publishPage( page, testPage.title );

		// go to the page to test empty cart block
		await page.goto( testPage.slug );
		await expect(
			page.getByRole( 'heading', { name: testPage.title } )
		).toBeVisible();
		await expect(
			await page.getByText( 'Your cart is currently empty!' ).count()
		).toBeGreaterThan( 0 );
		await expect(
			page.getByRole( 'link', { name: 'Browse store' } )
		).toBeVisible();
		await page.getByRole( 'link', { name: 'Browse store' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Shop' } )
		).toBeVisible();

		await addAProductToCart( page, product1Id );
		await page.goto( testPage.slug );
		await expect(
			page.getByRole( 'heading', { name: testPage.title } )
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
		await page
			.getByLabel( `Add to cart: “${ simpleProductName } Cross-Sell 1”` )
			.click();
		await expect(
			page
				.locator( '.wc-block-cart-items' )
				.getByText( `${ simpleProductName } Cross-Sell 1` )
		).toBeVisible();
		await page
			.getByLabel( `Add to cart: “${ simpleProductName } Cross-Sell 2”` )
			.click();
		await expect(
			page
				.locator( '.wc-block-cart-items' )
				.getByText( `${ simpleProductName } Cross-Sell 2` )
		).toBeVisible();

		await page.goto( testPage.slug );
		await expect(
			page.getByRole( 'heading', { name: testPage.title } )
		).toBeVisible();
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
