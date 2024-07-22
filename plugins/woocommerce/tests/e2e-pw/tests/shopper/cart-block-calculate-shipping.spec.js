const { test: baseTest, expect } = require( '../../fixtures/fixtures' );
const {
	goToPageEditor,
	fillPageTitle,
	insertBlockByShortcut,
	publishPage,
} = require( '../../utils/editor' );
const { addAProductToCart } = require( '../../utils/cart' );

const firstProductName = 'First Product';
const firstProductPrice = '10.00';
const secondProductName = 'Second Product';
const secondProductPrice = '20.00';
const firstProductWithFlatRate = +firstProductPrice + 5;

const shippingZoneNameES = 'Netherlands Free Shipping';
const shippingCountryNL = 'NL';
const shippingZoneNamePT = 'Portugal Flat Local';
const shippingCountryPT = 'PT';

const test = baseTest.extend( {
	storageState: process.env.ADMINSTATE,
	testPageTitlePrefix: 'Cart Block',
	cartBlockPage: async ( { page, testPage }, use ) => {
		await goToPageEditor( { page } );
		await fillPageTitle( page, testPage.title );
		await insertBlockByShortcut( page, 'Cart' );
		await publishPage( page, testPage.title );

		await use( testPage );
	},
} );

test.describe(
	'Cart Block Calculate Shipping',
	{ tag: [ '@payments', '@services' ] },
	() => {
		let product1Id, product2Id, shippingZoneNLId, shippingZonePTId;

		test.beforeAll( async ( { api } ) => {
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

		test.afterAll( async ( { api } ) => {
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

		test( 'allows customer to calculate Free Shipping in cart block if in Netherlands', async ( {
			page,
			context,
			cartBlockPage,
		} ) => {
			await context.clearCookies();

			await addAProductToCart( page, product1Id );
			await page.goto( cartBlockPage.slug );

			// Set shipping country to Netherlands
			await page.getByLabel( 'Add an address for shipping' ).click();
			await page
				.getByRole( 'combobox' )
				.first()
				.selectOption( 'Netherlands' );
			await page.getByLabel( 'Postal code' ).fill( '1011AA' );
			await page.getByLabel( 'City' ).fill( 'Amsterdam' );
			await page.getByRole( 'button', { name: 'Update' } ).click();

			// Verify shipping costs
			await expect(
				page.getByRole( 'group' ).getByText( 'Free shipping' )
			).toBeVisible();
			await expect(
				page.getByRole( 'strong' ).getByText( 'Free', { exact: true } )
			).toBeVisible();
			await expect( page.getByText( '$' ).nth( 2 ) ).toContainText(
				firstProductPrice
			);
		} );

		test( 'allows customer to calculate Flat rate and Local pickup in cart block if in Portugal', async ( {
			page,
			context,
			cartBlockPage,
		} ) => {
			await context.clearCookies();

			await addAProductToCart( page, product1Id );
			await page.goto( cartBlockPage.slug );

			// Set shipping country to Portugal
			await page.getByLabel( 'Add an address for shipping' ).click();
			await page
				.getByRole( 'combobox' )
				.first()
				.selectOption( 'Portugal' );
			await page.getByLabel( 'Postal code' ).fill( '1000-001' );
			await page.getByLabel( 'City' ).fill( 'Lisbon' );
			await page.getByRole( 'button', { name: 'Update' } ).click();

			// Verify shipping costs
			await expect(
				page.getByRole( 'group' ).getByText( 'Flat rate' )
			).toBeVisible();
			await expect( page.getByText( 'Shipping$5.00Flat' ) ).toBeVisible();
			await expect(
				page.getByText( `$${ firstProductWithFlatRate }` )
			).toBeVisible();

			// Set shipping to local pickup instead of flat rate
			await page.getByRole( 'group' ).getByText( 'Local pickup' ).click();

			// Verify updated shipping costs
			await expect( page.getByText( 'ShippingFreeLocal' ) ).toBeVisible();
			await expect( page.getByText( '$' ).nth( 2 ) ).toContainText(
				firstProductPrice
			);
		} );

		test( 'should show correct total cart block price after updating quantity', async ( {
			page,
			context,
			cartBlockPage,
		} ) => {
			await context.clearCookies();

			await addAProductToCart( page, product1Id );
			await page.goto( cartBlockPage.slug );

			// Set shipping country to Portugal
			await page.getByLabel( 'Add an address for shipping' ).click();
			await page
				.getByRole( 'combobox' )
				.first()
				.selectOption( 'Portugal' );
			await page.getByLabel( 'Postal code' ).fill( '1000-001' );
			await page.getByLabel( 'City' ).fill( 'Lisbon' );
			await page.getByRole( 'button', { name: 'Update' } ).click();

			// Increase product quantity and verify the updated price
			await page.getByLabel( 'Increase quantity of First' ).click();
			await expect(
				page.getByText(
					`$${
						parseInt( firstProductPrice, 10 ) +
						parseInt( firstProductPrice, 10 ) +
						5
					}`.toString()
				)
			).toBeVisible();
		} );

		test( 'should show correct total cart block price with 2 different products and flat rate/local pickup', async ( {
			page,
			context,
			cartBlockPage,
		} ) => {
			await context.clearCookies();

			await addAProductToCart( page, product1Id );
			await addAProductToCart( page, product2Id );
			await page.goto( cartBlockPage.slug );

			// Set shipping country to Portugal
			await page.getByLabel( 'Add an address for shipping' ).click();
			await page
				.getByRole( 'combobox' )
				.first()
				.selectOption( 'Portugal' );
			await page.getByLabel( 'Postal code' ).fill( '1000-001' );
			await page.getByLabel( 'City' ).fill( 'Lisbon' );
			await page.getByRole( 'button', { name: 'Update' } ).click();

			// Verify shipping costs
			await expect(
				page.getByRole( 'group' ).getByText( 'Flat rate' )
			).toBeVisible();
			await expect( page.getByText( 'Shipping$5.00Flat' ) ).toBeVisible();
			await expect(
				page.getByText(
					`$${
						parseInt( firstProductPrice, 10 ) +
						parseInt( secondProductPrice, 10 ) +
						5
					}`.toString()
				)
			).toBeVisible();

			// Set shipping to local pickup instead of flat rate
			await page.getByRole( 'group' ).getByText( 'Local pickup' ).click();

			// Verify updated shipping costs
			await expect( page.getByText( 'ShippingFreeLocal' ) ).toBeVisible();
			await expect(
				page
					.locator( 'div' )
					.filter( { hasText: /^\$30\.00$/ } )
					.locator( 'span' )
			).toBeVisible();
		} );
	}
);
