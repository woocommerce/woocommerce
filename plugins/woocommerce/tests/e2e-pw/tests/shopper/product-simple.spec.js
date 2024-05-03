const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const productPrice = '18.16';
const simpleProductName = 'Simple single product';

let simpleProductId,
	simpleProduct1Id,
	simpleProduct2Id,
	productCategory1Id,
	productCategory2Id;

test.describe( 'Single Product Page', () => {
	const simpleProduct1 = simpleProductName + ' Related';
	const simpleProduct2 = simpleProductName + ' Upsell';
	const productCategoryName1 = 'Hoodies';
	const productCategoryName2 = 'Jumpers';
	const productDescription = 'Lorem ipsum dolor sit amet.';

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );

		// add product categories
		await api
			.post( 'products/categories', {
				name: productCategoryName1,
			} )
			.then( ( response ) => {
				productCategory1Id = response.data.id;
			} );
		await api
			.post( 'products/categories', {
				name: productCategoryName2,
			} )
			.then( ( response ) => {
				productCategory2Id = response.data.id;
			} );

		// add products
		await api
			.post( 'products', {
				name: simpleProduct1,
				type: 'simple',
				categories: [
					{
						id: productCategory1Id,
						name: productCategoryName1,
					},
				],
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				simpleProduct1Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: simpleProduct2,
				type: 'simple',
				categories: [
					{
						id: productCategory2Id,
						name: productCategoryName2,
					},
				],
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				simpleProduct2Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: simpleProductName,
				description: productDescription,
				type: 'simple',
				categories: [
					{
						id: productCategory1Id,
						name: productCategoryName1,
					},
				],
				regular_price: productPrice,
				related_ids: simpleProduct1Id,
				upsell_ids: simpleProduct2Id,
			} )
			.then( ( response ) => {
				simpleProductId = response.data.id;
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
		await api.delete( `products/${ simpleProductId }`, {
			force: true,
		} );
		await api.delete( `products/${ simpleProduct1Id }`, {
			force: true,
		} );
		await api.delete( `products/${ simpleProduct2Id }`, {
			force: true,
		} );
		await api.post( 'products/categories/batch', {
			delete: [ productCategory1Id, productCategory2Id ],
		} );
	} );

	test( 'should be able to see upsell and related products', async ( {
		page,
	} ) => {
		const slug = simpleProductName.replace( / /gi, '-' ).toLowerCase();
		await page.goto( `product/${ slug }` );

		await expect( page.locator( '.upsells > h2' ) ).toContainText(
			'You may also like…'
		);
		await expect( page.locator( '.related > h2' ) ).toContainText(
			'Related products'
		);
		await expect(
			page.locator(
				'.upsells > .products > li > a > .woocommerce-loop-product__title'
			)
		).toContainText( simpleProduct2 );
		await expect(
			page.locator(
				'.related > .products > li > a > .woocommerce-loop-product__title'
			)
		).toContainText( simpleProduct1 );
	} );

	test( 'should be able to post a review and see it after', async ( {
		page,
	} ) => {
		await page.goto( 'my-account' );
		await page.locator( '#username' ).fill( 'admin' );
		await page.locator( '#password' ).fill( 'password' );
		await page.locator( 'text=Log in' ).click();

		const slug = simpleProductName.replace( / /gi, '-' ).toLowerCase();
		await page.goto( `product/${ slug }` );

		await expect( page.locator( '.reviews_tab' ) ).toContainText(
			'Reviews (0)'
		);
		await page.locator( '.reviews_tab' ).click();
		await page.locator( '.star-4' ).click();
		await page.locator( '#comment' ).fill( 'This product is great!' );
		await page.locator( 'text=Submit' ).click();
		await expect(
			page.locator( '.woocommerce-Reviews-title' )
		).toContainText( `1 review for ${ simpleProductName }` );
		await expect( page.locator( '.reviews_tab' ) ).toContainText(
			'Reviews (1)'
		);
	} );

	test( 'should be able to see product description and image', async ( {
		page,
	} ) => {
		const slug = simpleProductName.replace( / /gi, '-' ).toLowerCase();
		await page.goto( `product/${ slug }` );

		await expect( page.locator( '#tab-description > p' ) ).toContainText(
			productDescription
		);
		await expect(
			page.locator(
				'.woocommerce-product-gallery__image--placeholder > .wp-post-image'
			)
		).toBeVisible();
	} );

	test( 'should be able to add simple products to the cart', async ( {
		page,
	} ) => {
		const slug = simpleProductName.replace( / /gi, '-' ).toLowerCase();
		await page.goto( `product/${ slug }` );

		await page.locator( 'input.qty' ).fill( '5' );
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();

		await expect( page.locator( '.is-success' ) ).toContainText(
			'have been added to your cart.'
		);

		await page.goto( 'cart/' );
		await expect( page.locator( 'td.product-name' ) ).toContainText(
			simpleProductName
		);
		await expect( page.locator( 'input.qty' ) ).toHaveValue( '5' );
		await expect( page.locator( 'td.product-subtotal' ) ).toContainText(
			( 5 * +productPrice ).toString()
		);
	} );

	test( 'should be able to remove simple products from the cart', async ( {
		page,
	} ) => {
		await page.goto( `/shop/?add-to-cart=${ simpleProductId }` );
		await page.waitForLoadState( 'networkidle' );

		await page.goto( 'cart/' );
		await page.locator( 'a.remove' ).click();

		await expect( page.locator( '.is-info' ) ).toContainText(
			'Your cart is currently empty.'
		);
	} );
} );
