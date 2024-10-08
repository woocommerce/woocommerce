const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const {
	goToPageEditor,
	fillPageTitle,
	insertBlockByShortcut,
	publishPage,
} = require( '../../utils/editor' );
const { addAProductToCart } = require( '../../utils/cart' );
const { random } = require( '../../utils/helpers' );

const productName = 'First Product Cart Block Taxing';
const productPrice = '100.00';
const messyProductPrice = '13.47';
const secondProductName = 'Second Product Cart Block Taxing';

const cartBlockPageTitle = `Cart Block ${ random() }`;
const cartBlockPageSlug = cartBlockPageTitle
	.replace( / /gi, '-' )
	.toLowerCase();
const checkoutBlockPageTitle = `Checkout Block ${ random() }`;
const checkoutBlockPageSlug = checkoutBlockPageTitle
	.replace( / /gi, '-' )
	.toLowerCase();

let productId,
	productId2,
	nastyTaxId,
	seventeenTaxId,
	sixTaxId,
	countryTaxId,
	stateTaxId,
	cityTaxId,
	zipTaxId,
	shippingTaxId,
	shippingZoneId,
	shippingMethodId;

test.describe(
	'Shopper Cart & Checkout Block Tax Display',
	{
		tag: [
			'@payments',
			'@services',
			'@hpos',
			'@could-be-lower-level-test',
		],
	},
	() => {
		test.use( { storageState: process.env.ADMINSTATE } );
		test.beforeAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/general/woocommerce_calc_taxes', {
				value: 'yes',
			} );
			await api.put( 'settings/tax/woocommerce_tax_round_at_subtotal', {
				value: 'no',
			} );
			await api.put( 'settings/tax/woocommerce_tax_total_display', {
				value: 'itemized',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'incl',
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
			await api
				.post( 'taxes', {
					country: 'US',
					state: '*',
					cities: '*',
					postcodes: '*',
					rate: '25',
					name: 'Tax',
					shipping: false,
				} )
				.then( ( response ) => {
					nastyTaxId = response.data.id;
				} );
		} );

		test.afterAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'incl',
			} );
			await api.put( 'settings/tax/woocommerce_price_display_suffix', {
				value: '',
			} );
			await api.put( 'settings/general/woocommerce_calc_taxes', {
				value: 'no',
			} );
			await api.delete( `products/${ productId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ nastyTaxId }`, {
				force: true,
			} );
		} );

		// eslint-disable-next-line playwright/expect-expect
		test( 'can create Cart Block page', async ( { page } ) => {
			await goToPageEditor( { page } );
			await fillPageTitle( page, cartBlockPageTitle );
			await insertBlockByShortcut( page, 'Cart' );
			await publishPage( page, cartBlockPageTitle );
		} );

		// eslint-disable-next-line playwright/expect-expect
		test( 'can create Checkout Block page', async ( { page } ) => {
			await goToPageEditor( { page } );
			await fillPageTitle( page, checkoutBlockPageTitle );
			await insertBlockByShortcut( page, 'Checkout' );
			await publishPage( page, checkoutBlockPageTitle );
		} );

		test( 'that inclusive tax is displayed properly in block-based Cart & Checkout pages', async ( {
			page,
			context,
		} ) => {
			await context.clearCookies();

			await addAProductToCart( page, productId );

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( cartBlockPageSlug );
				await expect(
					page.getByRole( 'heading', { name: cartBlockPageTitle } )
				).toBeVisible();

				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-subtotal-block'
					)
				).toContainText( '$125.00' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$125.00' );
				await expect(
					page.locator(
						'.wc-block-components-totals-footer-item-tax'
					)
				).toHaveText( 'Including $25.00 Tax' );
			} );

			await test.step( 'Load checkout page and confirm price display', async () => {
				await page.goto( checkoutBlockPageSlug );
				await expect(
					page.getByRole( 'heading', {
						name: checkoutBlockPageTitle,
					} )
				).toBeVisible();

				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-subtotal-block'
					)
				).toContainText( '$125.00' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$125.00' );
				await expect(
					page.locator(
						'.wc-block-components-totals-footer-item-tax'
					)
				).toHaveText( 'Including $25.00 Tax' );
			} );
		} );

		test( 'that exclusive tax is displayed properly in block-based Cart & Checkout pages', async ( {
			page,
			baseURL,
			context,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'excl',
			} );

			await context.clearCookies();
			await addAProductToCart( page, productId );

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( cartBlockPageSlug );
				await expect(
					page.getByRole( 'heading', { name: cartBlockPageTitle } )
				).toBeVisible();

				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-subtotal-block'
					)
				).toContainText( '$100.00' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$125.00' );
				await expect(
					page.locator( '.wc-block-components-totals-taxes' )
				).toContainText( '$25.00' );
			} );

			await test.step( 'Load checkout page and confirm price display', async () => {
				await page.goto( checkoutBlockPageSlug );
				await expect(
					page.getByRole( 'heading', {
						name: checkoutBlockPageTitle,
					} )
				).toBeVisible();

				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-subtotal-block'
					)
				).toContainText( '$100.00' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$125.00' );
				await expect(
					page.locator( '.wc-block-components-totals-taxes' )
				).toContainText( '$25.00' );
			} );
		} );
	}
);

test.describe(
	'Shopper Cart & Checkout Block Tax Rounding',
	{ tag: [ '@payments', '@services', '@could-be-lower-level-test' ] },
	() => {
		test.beforeAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/general/woocommerce_calc_taxes', {
				value: 'yes',
			} );
			await api
				.post( 'products', {
					name: productName,
					type: 'simple',
					regular_price: messyProductPrice,
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );
			await api
				.post( 'products', {
					name: secondProductName,
					type: 'simple',
					regular_price: messyProductPrice,
				} )
				.then( ( response ) => {
					productId2 = response.data.id;
				} );
			await api
				.post( 'taxes', {
					country: 'US',
					state: '*',
					cities: '*',
					postcodes: '*',
					rate: '17',
					name: 'Seventeen Tax',
					shipping: false,
					compound: true,
					priority: 1,
				} )
				.then( ( response ) => {
					seventeenTaxId = response.data.id;
				} );
			await api
				.post( 'taxes', {
					country: 'US',
					state: '*',
					cities: '*',
					postcodes: '*',
					rate: '6',
					name: 'Six Tax',
					shipping: false,
					compound: true,
					priority: 2,
				} )
				.then( ( response ) => {
					sixTaxId = response.data.id;
				} );
		} );

		test.beforeEach( async ( { page, context } ) => {
			// shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();

			// all tests use the same products
			await addAProductToCart( page, productId );
			await addAProductToCart( page, productId2, 2 );
		} );

		test.afterAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'incl',
			} );
			await api.put( 'settings/tax/woocommerce_tax_round_at_subtotal', {
				value: 'no',
			} );
			await api.put( 'settings/general/woocommerce_calc_taxes', {
				value: 'no',
			} );
			await api.put( 'settings/tax/woocommerce_tax_total_display', {
				value: 'single',
			} );
			await api.delete( `products/${ productId }`, {
				force: true,
			} );
			await api.delete( `products/${ productId2 }`, {
				force: true,
			} );
			await api.delete( `taxes/${ seventeenTaxId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ sixTaxId }`, {
				force: true,
			} );
		} );

		test( 'that tax rounding is present at subtotal level in block-based Cart & Checkout pages', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'excl',
			} );
			await api.put( 'settings/tax/woocommerce_tax_round_at_subtotal', {
				value: 'yes',
			} );
			await api.put( 'settings/tax/woocommerce_tax_total_display', {
				value: 'single',
			} );

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( cartBlockPageSlug );
				await expect(
					page.getByRole( 'heading', { name: cartBlockPageTitle } )
				).toBeVisible();

				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-subtotal-block'
					)
				).toContainText( '$40.41' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$50.12' );
				await expect(
					page.locator( '.wc-block-components-totals-taxes' )
				).toContainText( '$9.71' );
			} );

			await test.step( 'Load checkout page and confirm price display', async () => {
				await page.goto( checkoutBlockPageSlug );
				await expect(
					page.getByRole( 'heading', {
						name: checkoutBlockPageTitle,
					} )
				).toBeVisible();

				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-subtotal-block'
					)
				).toContainText( '$40.41' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$50.12' );
				await expect(
					page.locator( '.wc-block-components-totals-taxes' )
				).toContainText( '$9.71' );
			} );
		} );

		test( 'that tax rounding is off at subtotal level in block-based Cart & Checkout pages', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'excl',
			} );
			await api.put( 'settings/tax/woocommerce_tax_round_at_subtotal', {
				value: 'no',
			} );
			await api.put( 'settings/tax/woocommerce_tax_total_display', {
				value: 'itemized',
			} );

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( cartBlockPageSlug );
				await expect(
					page.getByRole( 'heading', { name: cartBlockPageTitle } )
				).toBeVisible();

				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-subtotal-block'
					)
				).toContainText( '$40.41' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$50.12' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-taxes-block'
					)
				).toContainText( '$6.87' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-taxes-block'
					)
				).toContainText( '$2.84' );
			} );

			await test.step( 'Load checkout page and confirm price display', async () => {
				await page.goto( checkoutBlockPageSlug );
				await expect(
					page.getByRole( 'heading', {
						name: checkoutBlockPageTitle,
					} )
				).toBeVisible();

				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-subtotal-block'
					)
				).toContainText( '$40.41' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$50.12' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-taxes-block'
					)
				).toContainText( '$6.87' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-taxes-block'
					)
				).toContainText( '$2.84' );
			} );
		} );
	}
);

test.describe(
	'Shopper Cart & Checkout Block Tax Levels',
	{ tag: [ '@payments', '@services', '@could-be-lower-level-test' ] },
	() => {
		test.beforeAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/general/woocommerce_calc_taxes', {
				value: 'yes',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'excl',
			} );

			// add product
			await api
				.post( 'products', {
					name: productName,
					type: 'simple',
					regular_price: productPrice,
				} )
				.then( ( response ) => {
					productId = response.data.id;
				} );

			// create shipping zone
			await api
				.post( 'shipping/zones', {
					name: 'All',
				} )
				.then( ( response ) => {
					shippingZoneId = response.data.id;
				} );

			// set shipping zone location
			await api.put( `shipping/zones/${ shippingZoneId }/locations`, [
				{
					code: 'US',
				},
			] );

			// set shipping zone method
			await api
				.post( `shipping/zones/${ shippingZoneId }/methods`, {
					method_id: 'free_shipping',
				} )
				.then( ( response ) => {
					shippingMethodId = response.data.id;
				} );

			// confirm that we allow shipping to any country
			await api.put( 'settings/general/woocommerce_allowed_countries', {
				value: 'all',
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
			await api
				.post( 'taxes', {
					country: '*',
					state: '*',
					cities: 'Sacramento',
					postcodes: '*',
					rate: '2.5',
					name: 'City Tax',
					shipping: false,
					priority: 3,
				} )
				.then( ( response ) => {
					cityTaxId = response.data.id;
				} );
			await api
				.post( 'taxes', {
					country: '*',
					state: '*',
					cities: '*',
					postcodes: '55555',
					rate: '1.25',
					name: 'Zip Tax',
					shipping: false,
					priority: 4,
				} )
				.then( ( response ) => {
					zipTaxId = response.data.id;
				} );
		} );

		test.beforeEach( async ( { page, context } ) => {
			// shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();

			// all tests use the first product
			await addAProductToCart( page, productId );
		} );

		test.afterAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_total_display', {
				value: 'single',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'incl',
			} );
			await api.delete( `products/${ productId }`, {
				force: true,
			} );
			await api.delete( `shipping/zones/${ shippingZoneId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ countryTaxId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ stateTaxId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ cityTaxId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ zipTaxId }`, {
				force: true,
			} );
		} );

		test( 'that applying taxes in cart block of 4 different levels calculates properly', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_total_display', {
				value: 'itemized',
			} );
			// workaround to fill shipping details since there is an issue with showing
			// shipping calculator on the cart block-based experience for logged out user
			await page.goto( '/cart/' ); // we will use the old cart for this purpose
			await page.locator( '.shipping-calculator-button' ).click();
			await page.getByLabel( 'Town / City' ).fill( 'Sacramento' );
			await page.getByLabel( 'ZIP Code' ).fill( '55555' );
			await page
				.getByRole( 'button', { name: 'Update', exact: true } )
				.click();
			await expect(
				page.getByText( 'Shipping costs updated' )
			).toBeVisible();

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( cartBlockPageSlug );
				await expect(
					page.getByRole( 'heading', { name: cartBlockPageTitle } )
				).toBeVisible();
				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-subtotal-block'
					)
				).toContainText( '$100.00' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$118.75' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-taxes-block'
					)
				).toContainText( '$10.00' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-taxes-block'
					)
				).toContainText( '$5.00' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-taxes-block'
					)
				).toContainText( '$2.50' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-taxes-block'
					)
				).toContainText( '$1.25' );
			} );

			await test.step( 'Load checkout page and confirm price display', async () => {
				await page.goto( checkoutBlockPageSlug );
				await expect(
					page.getByRole( 'heading', {
						name: checkoutBlockPageTitle,
					} )
				).toBeVisible();
				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-subtotal-block'
					)
				).toContainText( '$100.00' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$118.75' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-taxes-block'
					)
				).toContainText( '$10.00' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-taxes-block'
					)
				).toContainText( '$5.00' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-taxes-block'
					)
				).toContainText( '$2.50' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-taxes-block'
					)
				).toContainText( '$1.25' );
			} );
		} );

		test( 'that applying taxes in block-based Cart & Checkout of 2 different levels (2 excluded) calculates properly', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_total_display', {
				value: 'itemized',
			} );

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( cartBlockPageSlug );
				await expect(
					page.getByRole( 'heading', { name: cartBlockPageTitle } )
				).toBeVisible();
				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-subtotal-block'
					)
				).toContainText( '$100.00' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$115.00' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-taxes-block'
					)
				).toContainText( '$10.00' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-taxes-block'
					)
				).toContainText( '$5.00' );
			} );

			await test.step( 'Load checkout page and confirm price display', async () => {
				await page.goto( checkoutBlockPageSlug );
				await expect(
					page.getByRole( 'heading', {
						name: checkoutBlockPageTitle,
					} )
				).toBeVisible();
				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-subtotal-block'
					)
				).toContainText( '$100.00' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$115.00' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-taxes-block'
					)
				).toContainText( '$10.00' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-taxes-block'
					)
				).toContainText( '$5.00' );
			} );
		} );
	}
);

test.describe(
	'Shipping Cart & Checkout Block Tax',
	{ tag: [ '@payments', '@services', '@could-be-lower-level-test' ] },
	() => {
		test.beforeAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/general/woocommerce_calc_taxes', {
				value: 'yes',
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
			await api
				.post( 'taxes', {
					country: 'US',
					state: '*',
					cities: '*',
					postcodes: '*',
					rate: '15',
					name: 'Shipping Tax',
					shipping: true,
				} )
				.then( ( response ) => {
					shippingTaxId = response.data.id;
				} );
			await api
				.post( 'shipping/zones', {
					name: 'All',
				} )
				.then( ( response ) => {
					shippingZoneId = response.data.id;
				} );
			await api
				.post( `shipping/zones/${ shippingZoneId }/methods`, {
					method_id: 'flat_rate',
					settings: {
						title: 'Flat rate',
					},
				} )
				.then( ( response ) => {
					shippingMethodId = response.data.id;
				} );
			await api.put(
				`shipping/zones/${ shippingZoneId }/methods/${ shippingMethodId }`,
				{
					settings: {
						cost: '20.00',
					},
				}
			);
			await api.put( 'payment_gateways/cod', {
				enabled: true,
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'incl',
			} );
		} );

		test.beforeEach( async ( { page, context } ) => {
			// shopping cart is very sensitive to cookies, so be explicit
			await context.clearCookies();

			// all tests use the first product
			await addAProductToCart( page, productId );
		} );

		test.afterAll( async ( { baseURL } ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/general/woocommerce_calc_taxes', {
				value: 'no',
			} );
			await api.delete( `products/${ productId }`, {
				force: true,
			} );
			await api.delete( `taxes/${ shippingTaxId }`, {
				force: true,
			} );
			await api.put( 'payment_gateways/cod', {
				enabled: false,
			} );
			await api.delete( `shipping/zones/${ shippingZoneId }`, {
				force: true,
			} );
		} );

		test( 'that tax is applied in Cart Block to shipping as well as order', async ( {
			page,
			baseURL,
		} ) => {
			const api = new wcApi( {
				url: baseURL,
				consumerKey: process.env.CONSUMER_KEY,
				consumerSecret: process.env.CONSUMER_SECRET,
				version: 'wc/v3',
			} );
			await api.put( 'settings/tax/woocommerce_tax_display_cart', {
				value: 'incl',
			} );

			await test.step( 'Load cart page and confirm price display', async () => {
				await page.goto( cartBlockPageSlug );
				await expect(
					page.getByRole( 'heading', { name: cartBlockPageTitle } )
				).toBeVisible();

				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-subtotal-block'
					)
				).toContainText( '$115.00' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-cart-order-summary-shipping-block'
					)
				).toContainText( '$23.00' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$138.00' );
			} );

			await test.step( 'Load checkout page and confirm price display', async () => {
				await page.goto( checkoutBlockPageSlug );
				await expect(
					page.getByRole( 'heading', {
						name: checkoutBlockPageTitle,
					} )
				).toBeVisible();

				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-subtotal-block'
					)
				).toContainText( '$115.00' );
				await expect(
					page.locator(
						'.wp-block-woocommerce-checkout-order-summary-shipping-block'
					)
				).toContainText( '$23.00' );
				await expect(
					page.locator( '.wc-block-components-totals-footer-item' )
				).toContainText( '$138.00' );
			} );
		} );
	}
);
