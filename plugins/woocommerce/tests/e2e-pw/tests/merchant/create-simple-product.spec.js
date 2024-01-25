const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const virtualProductName = 'Virtual Product Name';
const nonVirtualProductName = 'Non Virtual Product Name';
const productPrice = '9.99';
const productTag = 'nonVirtualTag';
const productCategory = 'nonVirtualCategory';
const productDescription = 'Description of a non-virtual product.';
const productDescriptionShort = 'Short description';
let shippingZoneId, virtualProductId, nonVirtualProductId;

test.describe.serial( 'Add New Simple Product Page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		// need to add a shipping zone
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// and the flat rate shipping method to that zone
		await api
			.post( 'shipping/zones', {
				name: 'Somewhere',
			} )
			.then( ( response ) => {
				shippingZoneId = response.data.id;
				api.put( `shipping/zones/${ shippingZoneId }/locations`, [
					{ code: 'CN' },
				] );
				api.post( `shipping/zones/${ shippingZoneId }/methods`, {
					method_id: 'flat_rate',
				} );
			} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );

		// cleans up all products after run
		await api.delete( `products/${ virtualProductId }`, { force: true } );
		await api.delete( `products/${ nonVirtualProductId }`, {
			force: true,
		} );

		// clean up tag after run
		await api.get( 'products/tags' ).then( async ( response ) => {
			for ( let i = 0; i < response.data.length; i++ ) {
				if ( response.data[ i ].name === productTag ) {
					await api.delete(
						`products/tags/${ response.data[ i ].id }`,
						{
							force: true,
						}
					);
				}
			}
		} );

		// clean up category after run
		await api.get( 'products/categories' ).then( async ( response ) => {
			for ( let i = 0; i < response.data.length; i++ ) {
				if ( response.data[ i ].name === productCategory ) {
					await api.delete(
						`products/categories/${ response.data[ i ].id }`,
						{
							force: true,
						}
					);
				}
			}
		} );

		// delete the shipping zone
		await api.delete( `shipping/zones/${ shippingZoneId }`, {
			force: true,
		} );
	} );

	test( 'can create simple virtual product', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=product', {
			waitUntil: 'networkidle',
		} );
		await page.locator( '#title' ).fill( virtualProductName );
		await page.locator( '#_regular_price' ).fill( productPrice );
		await page.locator( '#_virtual' ).click();
		await page.locator( '#publish' ).click();
		await page.waitForLoadState( 'networkidle' );

		// When running in parallel, clicking the publish button sometimes saves products as a draft
		if (
			(
				await page.locator( '#post-status-display' ).innerText()
			 ).includes( 'Draft' )
		) {
			await page.locator( '#publish' ).click();
			await page.waitForLoadState( 'networkidle' );
		}

		await expect(
			page
				.locator( 'div.notice-success > p' )
				.filter( { hasText: 'Product published.' } )
		).toBeVisible();

		// Save product ID
		virtualProductId = page.url().match( /(?<=post=)\d+/ );
		expect( virtualProductId ).toBeDefined();
	} );

	test( 'can have a shopper add the simple virtual product to the cart', async ( {
		page,
	} ) => {
		await page.goto( `/?post_type=product&p=${ virtualProductId }`, {
			waitUntil: 'networkidle',
		} );
		await expect(
			page.getByRole( 'heading', { name: virtualProductName } )
		).toBeVisible();
		await expect( page.getByText( productPrice ).first() ).toBeVisible();
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();
		await page.getByRole( 'link', { name: 'View cart' } ).click();
		await expect( page.locator( 'td[data-title=Product]' ) ).toContainText(
			virtualProductName
		);
		await expect(
			page.locator( 'a.shipping-calculator-button' )
		).toBeHidden();
		await page
			.locator( `a.remove[data-product_id='${ virtualProductId }']` )
			.click();
		await page.waitForLoadState( 'networkidle' );
		await expect(
			page.locator( `a.remove[data-product_id='${ virtualProductId }']` )
		).toBeHidden();
	} );

	test( 'can create simple non-virtual product', async ( { page } ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=product', {
			waitUntil: 'networkidle',
		} );
		await page.getByLabel( 'Product name' ).fill( nonVirtualProductName );

		await page
			.frameLocator( '#content_ifr' )
			.locator( '.wp-editor' )
			.fill( productDescription );

		await page
			.getByRole( 'textbox', { name: 'Regular price ($)', exact: true } )
			.fill( productPrice );

		await page.getByText( 'Inventory' ).click();
		await page.getByLabel( 'SKU', { exact: true } ).fill( '11' );

		const productDimensions = {
			weight: '2',
			length: '20',
			width: '10',
			height: '30',
		};

		await page.getByRole( 'link', { name: 'Shipping' } ).click();
		await page.getByPlaceholder( '0' ).fill( productDimensions.weight );
		await page
			.getByPlaceholder( 'Length' )
			.fill( productDimensions.length );
		await page.getByPlaceholder( 'Width' ).fill( productDimensions.width );
		await page
			.getByPlaceholder( 'Height' )
			.fill( productDimensions.height );

		await page
			.frameLocator( '#excerpt_ifr' )
			.locator( '.wp-editor' )
			.fill( productDescriptionShort );

		await page.getByText( '+ Add new category' ).click();
		await page
			.getByLabel( 'Add new category', { exact: true } )
			.fill( productCategory );
		await page
			.getByRole( 'button', { name: 'Add new category', exact: true } )
			.click();

		await page
			.getByRole( 'combobox', { name: 'Add new tag' } )
			.fill( productTag );
		await page.getByRole( 'button', { name: 'Add', exact: true } ).click();

		await page
			.getByRole( 'button', {
				name: 'Publish',
				exact: true,
				disabled: false,
			} )
			.click();
		await page.waitForLoadState( 'networkidle' );

		// When running in parallel, clicking the publish button sometimes saves products as a draft
		if (
			(
				await page.locator( '#post-status-display' ).innerText()
			 ).includes( 'Draft' )
		) {
			await page
				.getByRole( 'button', {
					name: 'Publish',
					exact: true,
					disabled: false,
				} )
				.click();
			await page.waitForLoadState( 'networkidle' );
		}

		await expect(
			page
				.locator( 'div.notice-success > p' )
				.filter( { hasText: 'Product published.' } )
		).toBeVisible();

		// Save product ID
		nonVirtualProductId = page.url().match( /(?<=post=)\d+/ );
		expect( nonVirtualProductId ).toBeDefined();
	} );

	test( 'can have a shopper add the simple non-virtual product to the cart', async ( {
		page,
	} ) => {
		await page.goto( `/?post_type=product&p=${ nonVirtualProductId }`, {
			waitUntil: 'networkidle',
		} );
		await expect(
			page.getByRole( 'heading', { name: nonVirtualProductName } )
		).toBeVisible();
		await expect( page.getByText( productPrice ).first() ).toBeVisible();
		await page.getByRole( 'button', { name: 'Add to cart' } ).click();
		await page.getByRole( 'link', { name: 'View cart' } ).click();
		await expect( page.locator( 'td[data-title=Product]' ) ).toContainText(
			nonVirtualProductName
		);
		await expect(
			page.locator( 'a.shipping-calculator-button' )
		).toBeVisible();
		await page
			.locator( `a.remove[data-product_id='${ nonVirtualProductId }']` )
			.click();
		await page.waitForLoadState( 'networkidle' );
		await expect(
			page.locator(
				`a.remove[data-product_id='${ nonVirtualProductId }']`
			)
		).toBeHidden();
	} );
} );
