const { test, expect, request } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const pageTitle = 'Product Showcase';
const { goToPageEditor, getCanvas } = require( '../../utils/editor' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const singleProductPrice1 = '5.00';
const singleProductPrice2 = '10.00';
const singleProductPrice3 = '15.00';

const productTagName1 = 'product tag 1';
const productTagName2 = 'product tag 2';
const productTagName3 = 'product tag 3';

const productAttributeName = 'color';
const productAttributeTerm = 'red';

const simpleProductName = 'Single Product With Tags';

let product1Id,
	product2Id,
	product3Id,
	productTag1Id,
	productTag2Id,
	productTag3Id,
	attributeId;

test.describe(
	'Browse product tags and attributes from the product page',
	{ tag: [ '@payments', '@services' ] },
	() => {
		test.use( { storageState: process.env.ADMINSTATE } );

		test.beforeAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			// make sure the attribute term page is visible in the shop
			await api.put(
				'settings/products/woocommerce_attribute_lookup_enabled',
				{
					value: 'yes',
				}
			);

			// add product tags
			await api
				.post( 'products/tags', {
					name: productTagName1,
				} )
				.then( ( response ) => {
					productTag1Id = response.data.id;
				} );
			await api
				.post( 'products/tags', {
					name: productTagName2,
				} )
				.then( ( response ) => {
					productTag2Id = response.data.id;
				} );
			await api
				.post( 'products/tags', {
					name: productTagName3,
				} )
				.then( ( response ) => {
					productTag3Id = response.data.id;
				} );

			// add product attribute
			await api
				.post( 'products/attributes', {
					name: productAttributeName,
					has_archives: true,
				} )
				.then( ( response ) => {
					attributeId = response.data.id;
				} );

			// add product attribute term
			await api.post( `products/attributes/${ attributeId }/terms`, {
				name: productAttributeTerm,
			} );

			// add products
			await api
				.post( 'products', {
					name: simpleProductName + ' 1',
					type: 'simple',
					regular_price: singleProductPrice1,
					tags: [
						{ id: productTag1Id },
						{
							id: productTag2Id,
						},
						{
							id: productTag3Id,
						},
					],
					attributes: [
						{
							id: attributeId,
							visible: true,
							options: [ productAttributeTerm ],
						},
					],
				} )
				.then( ( response ) => {
					product1Id = response.data.id;
				} );
			await api
				.post( 'products', {
					name: simpleProductName + ' 2',
					type: 'simple',
					regular_price: singleProductPrice2,
					tags: [
						{ id: productTag1Id },
						{
							id: productTag2Id,
						},
					],
					attributes: [
						{
							id: attributeId,
							visible: true,
							options: [ productAttributeTerm ],
						},
					],
				} )
				.then( ( response ) => {
					product2Id = response.data.id;
				} );
			await api
				.post( 'products', {
					name: simpleProductName + ' 3',
					type: 'simple',
					regular_price: singleProductPrice3,
					tags: [ { id: productTag1Id } ],
					attributes: [
						{
							id: attributeId,
							visible: true,
							options: [ productAttributeTerm ],
						},
					],
				} )
				.then( ( response ) => {
					product3Id = response.data.id;
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
			await api.post( 'products/tags/batch', {
				delete: [ productTag1Id, productTag2Id, productTag3Id ],
			} );
			await api.post( 'products/attributes/batch', {
				delete: [ attributeId ],
			} );
			await api.put(
				'settings/products/woocommerce_attribute_lookup_enabled',
				{
					value: 'no',
				}
			);
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

		test( 'should see shop catalog with all its products', async ( {
			page,
		} ) => {
			await page.goto( 'shop/' );
			await expect(
				page.getByRole( 'heading', { name: 'Shop' } )
			).toBeVisible();
			await expect(
				page.locator( '.woocommerce-ordering' )
			).toBeVisible();

			const addToCart = page.getByRole( 'add_to_cart_button' );
			for ( let i = 0; i < addToCart.count(); ++i )
				await expect( addToCart.nth( i ) ).toBeVisible();

			const productPrice = page.getByRole( 'woocommerce-Price-amount' );
			for ( let i = 0; i < productPrice.count(); ++i )
				await expect( productPrice.nth( i ) ).toBeVisible();

			const productTitle = page.getByRole(
				'woocommerce-loop-product__title'
			);
			for ( let i = 0; i < productTitle.count(); ++i )
				await expect( productTitle.nth( i ) ).toBeVisible();

			const productImage = page.getByRole( 'wp-post-image' );
			for ( let i = 0; i < productImage.count(); ++i )
				await expect( productImage.nth( i ) ).toBeVisible();
		} );

		test( 'should see and sort tags page with all the products', async ( {
			page,
		} ) => {
			await page.goto( 'shop/' );
			await page.locator( `text=${ simpleProductName } 1` ).click();
			await page.getByRole( 'link', { name: productTagName1 } ).click();
			await expect(
				page.getByRole( 'heading', { name: productTagName1 } )
			).toBeVisible();
			await expect(
				page.getByText( `Products tagged “${ productTagName1 }”` )
			).toBeVisible();
			await expect(
				page.getByText( 'Showing all 3 results' )
			).toBeVisible();
		} );

		test( 'should see and sort attributes page with all its products', async ( {
			page,
		} ) => {
			// the api setting for enabling attribute term page doesn't apply for some reason
			// but I could see it as checked/enabled in the settings
			// workaround for the change to take effect is to just save the settings.
			await page.goto( 'wp-admin/admin.php?page=wc-settings' );
			// Modify a random unrelated settings so we can save the form.
			await page
				.locator( '#woocommerce_allowed_countries' )
				.selectOption( 'all' );
			await page.locator( 'text=Save changes' ).click();

			const slug = simpleProductName.replace( / /gi, '-' ).toLowerCase();
			await page.goto( `product/${ slug }` );
			await page
				.locator(
					'.woocommerce-product-attributes-item__value > p > a',
					{
						hasText: productAttributeTerm,
					}
				)
				.click();
			await expect(
				page.getByRole( 'heading', { name: productAttributeTerm } )
			).toBeVisible();
			await expect(
				page.locator( '.woocommerce-breadcrumb' )
			).toContainText(
				` / Product ${ productAttributeName } / ${ productAttributeTerm }`
			);
			await expect(
				page.getByText( 'Showing all 3 results' )
			).toBeVisible();
		} );

		test( 'can see products showcase', async ( { page } ) => {
			// create as a merchant a new page with Product Collection block
			await goToPageEditor( { page } );

			const canvas = await getCanvas( page );

			await canvas
				.getByRole( 'textbox', { name: 'Add Title' } )
				.fill( pageTitle );

			await canvas
				.getByRole( 'button', { name: 'Add default block' } )
				.click();

			await canvas
				.getByRole( 'document', {
					name: 'Empty block; start writing or type forward slash to choose a block',
				} )
				.pressSequentially( '/product collection' );
			await page.keyboard.press( 'Enter' );

			// Product Collection requires choosing some collection.
			await canvas
				.locator(
					'[data-type="woocommerce/product-collection"] .components-placeholder'
				)
				.getByRole( 'button', {
					name: 'create your own',
				} )
				.click();

			await page
				.getByRole( 'button', { name: 'Publish', exact: true } )
				.click();

			await page
				.getByRole( 'region', { name: 'Editor publish' } )
				.getByRole( 'button', { name: 'Publish', exact: true } )
				.click();

			await expect(
				// WP 6.6 updates the button text from "Update" to "Save", so we'll need to check for either.
				page.getByRole( 'button', { name: 'Update', exact: true } ).or(
					page.getByRole( 'button', {
						name: 'Save',
						exact: true,
					} )
				)
			).toBeVisible();

			// go to created page with products showcase
			await page.goto( 'product-showcase' );
			await expect(
				page.getByRole( 'heading', { name: pageTitle } )
			).toBeVisible();
			expect(
				await page
					.getByRole( 'button', { name: 'Add to cart' } )
					.count()
			).toBeGreaterThan( 0 );

			const productPrice = page.locator( '.woocommerce-Price-amount' );
			for ( let i = 0; i < productPrice.count(); ++i )
				await expect( productPrice.nth( i ) ).toBeVisible();

			const productTitle = page.locator(
				'.woocommerce-loop-product__title'
			);
			for ( let i = 0; i < productTitle.count(); ++i )
				await expect( productTitle.nth( i ) ).toBeVisible();

			const productImage = page.locator( '.wp-post-image' );
			for ( let i = 0; i < productImage.count(); ++i )
				await expect( productImage.nth( i ) ).toBeVisible();
		} );
	}
);
