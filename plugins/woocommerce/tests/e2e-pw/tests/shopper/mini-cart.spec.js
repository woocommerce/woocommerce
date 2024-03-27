const { test, expect } = require( '@playwright/test' );
const { disableWelcomeModal } = require( '../../utils/editor' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const miniCartPageTitle = `Mini Cart ${ Date.now() }`;
const miniCartPageSlug = miniCartPageTitle.replace( / /gi, '-' ).toLowerCase();
const miniCartButton = 'main .wc-block-mini-cart__button';
const miniCartBadge = 'main .wc-block-mini-cart__badge';

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
				manage_stock: true,
				stock_quantity: 2,
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
	} );

	test( 'can see empty customized mini cart, add and remove product, increase to max quantity, calculate tax and see redirection', async ( {
		page,
		baseURL,
		context,
	} ) => {
		const colorField = '.components-input-control__input';
		const miniCartBlock = 'main .wp-block-woocommerce-mini-cart';
		const redColor = 'ff0000';
		const blueColor = '002eff';
		const greenColor = '00cc09';

		// go to create a new page
		await page.goto( 'wp-admin/post-new.php?post_type=page' );

		await disableWelcomeModal( { page } );

		// add page title and mini cart block
		await page
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( miniCartPageTitle );
		await page.getByLabel( 'Add block' ).click();
		await page
			.getByLabel( 'Search for blocks and patterns' )
			.fill( '/mini cart' );
		await page
			.getByRole( 'option' )
			.filter( { hasText: 'Mini-Cart' } )
			.click();
		await expect( page.getByLabel( 'Block: Mini-Cart' ) ).toBeVisible();

		// customize mini cart block
		await page.getByLabel( 'Block: Mini-Cart' ).click();
		// display total price
		await page.getByLabel( 'Display total price' ).click();
		// open drawer when a product
		await page.getByLabel( 'Open drawer when adding' ).click();
		// open styles in the sidebar
		await page.getByLabel( 'Styles', { exact: true } ).click();
		// customize price color
		await page.getByTitle( 'Price', { exact: true } ).click();
		await page
			.getByRole( 'button', { name: 'Custom color picker.' } )
			.click();
		await page.locator( colorField ).fill( redColor );
		await page.getByTitle( 'Price', { exact: true } ).click();
		// customize icon color
		await page.getByTitle( 'Icon', { exact: true } ).click();
		await page
			.getByRole( 'button', { name: 'Custom color picker.' } )
			.click();
		await page.locator( colorField ).fill( blueColor );
		await page.getByTitle( 'Icon', { exact: true } ).click();
		// customize product count color
		await page.getByTitle( 'Product Count', { exact: true } ).click();
		await page
			.getByRole( 'button', { name: 'Custom color picker.' } )
			.click();
		await page.locator( colorField ).fill( greenColor );
		await page.getByTitle( 'Product Count', { exact: true } ).click();
		// customize font size and weight
		await page.getByLabel( 'Large', { exact: true } ).click();
		await page.getByRole( 'button', { name: 'Font weight' } ).click();
		await page.getByRole( 'option' ).filter( { hasText: 'Black' } ).click();

		// publish created mini cart page
		await page
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await expect(
			page.getByText( `${ miniCartPageTitle } is now live.` )
		).toBeVisible();

		// go to created page and verify customized and empty mini cart
		await page.goto( miniCartPageSlug );
		await expect(
			page.getByRole( 'heading', { name: miniCartPageTitle } )
		).toBeVisible();
		await expect( page.locator( miniCartBlock ) ).toHaveAttribute(
			'data-price-color',
			`{"color":"#${ redColor }"}`
		);
		await expect( page.locator( miniCartBlock ) ).toHaveAttribute(
			'data-icon-color',
			`{"color":"#${ blueColor }"}`
		);
		await expect( page.locator( miniCartBlock ) ).toHaveAttribute(
			'data-product-count-color',
			`{"color":"#${ greenColor }"}`
		);
		await expect( page.locator( miniCartBlock ) ).toHaveAttribute(
			'data-font-size',
			'large'
		);
		await expect( page.locator( miniCartBlock ) ).toHaveAttribute(
			'data-style',
			'{"typography":{"fontWeight":"900"}}'
		);
		await page.locator( miniCartButton ).click();
		await expect(
			await page.getByText( 'Your cart is currently empty!' ).count()
		).toBeGreaterThan( 0 );
		await page.getByRole( 'link', { name: 'Start shopping' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Shop' } )
		).toBeVisible();

		// add product to cart
		await page.goto( `/shop/?add-to-cart=${ productId }` );

		// go to page with mini cart block and test with the product added
		await page.goto( miniCartPageSlug );
		await expect( page.locator( miniCartBadge ) ).toContainText( '1' );
		await page.locator( miniCartButton ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Your cart (1 item)' } )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: simpleProductName } )
		).toBeVisible();
		await expect(
			page.getByText( `Save $${ singleProductSalePrice }` )
		).toBeVisible();
		await expect( page.getByText( simpleProductDesc ) ).toBeVisible();
		// increase product quantity to its maximum
		await page
			.getByRole( 'button' )
			.filter( { hasText: '＋', exact: true } )
			.click();
		await expect(
			page.getByRole( 'heading', { name: 'Your cart (2 items)' } )
		).toBeVisible();
		await expect( page.locator( miniCartBadge ) ).toContainText( '2' );
		await expect(
			page.locator( '.wc-block-components-totals-item__value' )
		).toContainText( `$${ singleProductSalePrice * 2 }` );
		await expect(
			page.getByRole( 'button' ).filter( { hasText: '＋', exact: true } )
		).toBeDisabled();
		await page
			.getByRole( 'button' )
			.filter( { hasText: 'Remove item' } )
			.click();
		await expect(
			page.getByText( 'Your cart is currently empty!' )
		).toBeVisible();

		// add product to cart and redirect from mini to regular cart
		await page.goto( `/shop/?add-to-cart=${ productId }` );
		await page.goto( miniCartPageSlug );
		await page.locator( miniCartButton ).click();
		await page.getByRole( 'link', { name: 'View my cart' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Cart', exact: true } )
		).toBeVisible();
		await expect( page.locator( miniCartButton ) ).toBeHidden();

		// go to mini cart and test redirection from mini cart to checkout
		await page.goto( miniCartPageSlug );
		await page.locator( miniCartButton ).click();
		await page.getByRole( 'link', { name: 'Go to checkout' } ).click();
		await expect(
			page.getByRole( 'heading', { name: 'Checkout', exact: true } )
		).toBeVisible();
		await expect( page.locator( miniCartButton ) ).toBeHidden();

		// shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();

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
		await page.goto( `/shop/?add-to-cart=${ productId }` );

		// go to cart and add shipping details to calculate tax
		await page.goto( '/cart/' ); // we will use the old cart for this purpose
		await page.locator( '.shipping-calculator-button' ).click();
		await page.getByLabel( 'Town / City' ).fill( 'Sacramento' );
		await page.getByLabel( 'ZIP Code' ).fill( '96000' );
		await page
			.getByRole( 'button', { name: 'Update', exact: true } )
			.click();
		await expect(
			page.getByText( 'Shipping costs updated' )
		).toBeVisible();

		await page.goto( miniCartPageSlug );
		await expect(
			page.getByRole( 'heading', { name: miniCartPageTitle } )
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
