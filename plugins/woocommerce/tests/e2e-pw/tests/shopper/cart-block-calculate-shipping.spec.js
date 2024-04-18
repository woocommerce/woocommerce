const { test, expect } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const { disableWelcomeModal } = require( '../../utils/editor' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const firstProductName = 'First Product';
const firstProductPrice = '10.00';
const secondProductName = 'Second Product';
const secondProductPrice = '20.00';
const firstProductWithFlatRate = +firstProductPrice + 5;
const twoProductsTotal = +firstProductPrice + +secondProductPrice;
const twoProductsWithFlatRate = twoProductsTotal + 5;

const cartBlockPageTitle = 'Cart Block';
const cartBlockPageSlug = cartBlockPageTitle
	.replace( / /gi, '-' )
	.toLowerCase();

const shippingZoneNameES = 'Netherlands Free Shipping';
const shippingCountryNL = 'NL';
const shippingZoneNamePT = 'Portugal Flat Local';
const shippingCountryPT = 'PT';

test.describe( 'Cart Block Calculate Shipping', () => {
	test.use( { storageState: process.env.ADMINSTATE } );
	let product1Id, product2Id, shippingZoneNLId, shippingZonePTId;

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
				name: firstProductName,
				type: 'simple',
				regular_price: firstProductPrice,
			} )
			.then( ( response ) => {
				product1Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: secondProductName,
				type: 'simple',
				regular_price: secondProductPrice,
			} )
			.then( ( response ) => {
				product2Id = response.data.id;
			} );

		// create shipping zones
		await api
			.post( 'shipping/zones', {
				name: shippingZoneNameES,
			} )
			.then( ( response ) => {
				shippingZoneNLId = response.data.id;
			} );
		await api
			.post( 'shipping/zones', {
				name: shippingZoneNamePT,
			} )
			.then( ( response ) => {
				shippingZonePTId = response.data.id;
			} );

		// set shipping zone locations
		await api.put( `shipping/zones/${ shippingZoneNLId }/locations`, [
			{
				code: shippingCountryNL,
			},
		] );
		await api.put( `shipping/zones/${ shippingZonePTId }/locations`, [
			{
				code: shippingCountryPT,
			},
		] );

		// set shipping zone methods
		await api.post( `shipping/zones/${ shippingZoneNLId }/methods`, {
			method_id: 'free_shipping',
			settings: {
				title: 'Free shipping',
			},
		} );
		await api.post( `shipping/zones/${ shippingZonePTId }/methods`, {
			method_id: 'flat_rate',
			settings: {
				cost: '5.00',
				title: 'Flat rate',
			},
		} );
		await api.post( `shipping/zones/${ shippingZonePTId }/methods`, {
			method_id: 'local_pickup',
			settings: {
				title: 'Local pickup',
			},
		} );

		// confirm that we allow shipping to any country
		await api.put( 'settings/general/woocommerce_allowed_countries', {
			value: 'all',
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
			delete: [ product1Id, product2Id ],
		} );
		await api.delete( `shipping/zones/${ shippingZoneNLId }`, {
			force: true,
		} );
		await api.delete( `shipping/zones/${ shippingZonePTId }`, {
			force: true,
		} );
	} );

	test( 'create Cart Block page', async ( { page } ) => {
		// create a new page with cart block
		await page.goto( 'wp-admin/post-new.php?post_type=page' );

		await disableWelcomeModal( { page } );

		await page
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( cartBlockPageTitle );
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
			page.getByText( `${ cartBlockPageTitle } is now live.` )
		).toBeVisible();
	} );

	test( 'allows customer to calculate Free Shipping in cart block if in Netherlands', async ( {
		page,
		context,
	} ) => {
		await context.clearCookies();

		await page.goto( `/shop/?add-to-cart=${ product1Id }` );
		await page.waitForLoadState( 'networkidle' );

		await page.goto( cartBlockPageSlug );

		// Set shipping country to Netherlands
		await page
			.locator(
				'.wc-block-components-totals-shipping__change-address__link'
			)
			.click();
		await page.getByRole( 'combobox' ).first().fill( 'Netherlands' );
		await page.getByLabel( 'Postal code' ).fill( '1011AA' );
		await page.getByLabel( 'City' ).fill( 'Amsterdam' );
		await page.getByRole( 'button', { name: 'Update' } ).click();

		// Verify shipping costs
		await expect(
			page.getByRole( 'group' ).getByText( 'Free shipping' )
		).toBeVisible();
		await expect(
			page.locator(
				'.wc-block-components-radio-control__description > .wc-block-components-formatted-money-amount'
			)
		).toContainText( 'FREE' );
		await expect(
			page.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
		).toContainText( firstProductPrice );
	} );

	test( 'allows customer to calculate Flat rate and Local pickup in cart block if in Portugal', async ( {
		page,
		context,
	} ) => {
		await context.clearCookies();

		await page.goto( `/shop/?add-to-cart=${ product1Id }` );
		await page.waitForLoadState( 'networkidle' );

		await page.goto( cartBlockPageSlug );

		// Set shipping country to Portugal
		await page
			.locator(
				'.wc-block-components-totals-shipping__change-address__link'
			)
			.click();
		await page.getByRole( 'combobox' ).first().fill( 'Portugal' );
		await page.getByLabel( 'Postal code' ).fill( '1000-001' );
		await page.getByLabel( 'City' ).fill( 'Lisbon' );
		await page.getByRole( 'button', { name: 'Update' } ).click();

		// Verify shipping costs
		await expect(
			page.getByRole( 'group' ).getByText( 'Flat rate' )
		).toBeVisible();
		await expect(
			page.locator(
				'.wc-block-components-totals-shipping > .wc-block-components-totals-item'
			)
		).toContainText( '$5.00' );
		await expect(
			page.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
		).toContainText( firstProductWithFlatRate.toString() );

		// Set shipping to local pickup instead of flat rate
		await page.getByRole( 'group' ).getByText( 'Local pickup' ).click();

		// Verify updated shipping costs
		await expect(
			page.locator(
				'.wc-block-components-totals-shipping > .wc-block-components-totals-item'
			)
		).toContainText( '$0.00' );
		let totalPrice = await page
			.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
			.last()
			.textContent();
		totalPrice = Number( totalPrice.replace( /\$([\d.]+).*/, '$1' ) );
		await expect( totalPrice ).toBeGreaterThanOrEqual(
			Number( firstProductPrice )
		);
		await expect( totalPrice ).toBeLessThanOrEqual(
			Number( firstProductPrice * 1.25 )
		);
	} );

	test( 'should show correct total cart block price after updating quantity', async ( {
		page,
		context,
	} ) => {
		await context.clearCookies();

		await page.goto( `/shop/?add-to-cart=${ product1Id }` );
		await page.waitForLoadState( 'networkidle' );

		await page.goto( cartBlockPageSlug );

		// Set shipping country to Portugal
		await page
			.locator(
				'.wc-block-components-totals-shipping__change-address__link'
			)
			.click();
		await page.getByRole( 'combobox' ).first().fill( 'Portugal' );
		await page.getByLabel( 'Postal code' ).fill( '1000-001' );
		await page.getByLabel( 'City' ).fill( 'Lisbon' );
		await page.getByRole( 'button', { name: 'Update' } ).click();

		// Increase product quantity and verify the updated price
		await page
			.getByRole( 'button' )
			.filter( { hasText: '＋', exact: true } )
			.click();
		let totalPrice = await page
			.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
			.last()
			.textContent();
		totalPrice = Number( totalPrice.replace( /\$([\d.]+).*/, '$1' ) );
		await expect( totalPrice ).toBeGreaterThanOrEqual(
			Number( firstProductPrice )
		);
		await expect( totalPrice ).toBeLessThanOrEqual(
			Number( firstProductPrice * 1.25 )
		);
	} );

	test( 'should show correct total cart block price with 2 different products and flat rate/local pickup', async ( {
		page,
		context,
	} ) => {
		await context.clearCookies();

		await page.goto( `/shop/?add-to-cart=${ product1Id }` );
		await page.waitForLoadState( 'networkidle' );

		await page.goto( `/shop/?add-to-cart=${ product2Id }` );
		await page.waitForLoadState( 'networkidle' );

		await page.goto( cartBlockPageSlug );

		// Set shipping country to Portugal
		await page
			.locator(
				'.wc-block-components-totals-shipping__change-address__link'
			)
			.click();
		await page.getByRole( 'combobox' ).first().fill( 'Portugal' );
		await page.getByLabel( 'Postal code' ).fill( '1000-001' );
		await page.getByLabel( 'City' ).fill( 'Lisbon' );
		await page.getByRole( 'button', { name: 'Update' } ).click();

		// Verify shipping costs
		await expect(
			page.getByRole( 'group' ).getByText( 'Flat rate' )
		).toBeVisible();
		await expect(
			page.locator(
				'.wc-block-components-totals-shipping > .wc-block-components-totals-item'
			)
		).toContainText( '$5.00' );
		let totalPrice = await page
			.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
			.last()
			.textContent();
		totalPrice = Number( totalPrice.replace( /\$([\d.]+).*/, '$1' ) );
		await expect( totalPrice ).toBeGreaterThanOrEqual(
			Number( twoProductsWithFlatRate )
		);
		await expect( totalPrice ).toBeLessThanOrEqual(
			Number( twoProductsWithFlatRate * 1.25 )
		);

		// Set shipping to local pickup instead of flat rate
		await page.getByRole( 'group' ).getByText( 'Local pickup' ).click();

		// Verify updated shipping costs
		await expect(
			page.locator(
				'.wc-block-components-totals-shipping > .wc-block-components-totals-item'
			)
		).toContainText( '$0.00' );
		totalPrice = await page
			.locator(
				'.wc-block-components-totals-footer-item > .wc-block-components-totals-item__value'
			)
			.last()
			.textContent();
		totalPrice = Number( totalPrice.replace( /\$([\d.]+).*/, '$1' ) );
		await expect( totalPrice ).toBeGreaterThanOrEqual(
			Number( twoProductsTotal )
		);
		await expect( totalPrice ).toBeLessThanOrEqual(
			Number( twoProductsTotal * 1.25 )
		);
	} );
} );
