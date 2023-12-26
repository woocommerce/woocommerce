const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;
const { goToPostEditor } = require( '../../utils/editor' );

// need to figure out whether tests are being run on a mac
const macOS = process.platform === 'darwin';
const cmdKeyCombo = macOS ? 'Meta+k' : 'Control+k';

const clickOnCommandPaletteOption = async ( { page, optionName } ) => {
	// Press `Ctrl` + `K` to open the command palette.
	await page.keyboard.press( cmdKeyCombo );

	await page.getByLabel( 'Command palette' ).fill( optionName );

	// Click on the relevant option.
	const option = page.getByRole( 'option', {
		name: optionName,
		exact: true,
	} );
	await expect( option ).toBeVisible();
	option.click();
};

test.describe( 'Use Command Palette commands', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	let productId;

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api
			.post( 'products', {
				name: 'Product to search',
				type: 'simple',
				regular_price: '12.99',
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ productId }`, {
			force: true,
		} );
	} );

	test( 'can use the "Add new product" command', async ( { page } ) => {
		await goToPostEditor( { page } );

		await clickOnCommandPaletteOption( {
			page,
			optionName: 'Add new product',
		} );

		// Verify that the page has loaded.
		await expect(
			page.getByRole( 'heading', { name: 'Add new product' } )
		).toBeVisible();
	} );

	test( 'can use the "Add new order" command', async ( { page } ) => {
		await goToPostEditor( { page } );

		await clickOnCommandPaletteOption( {
			page,
			optionName: 'Add new order',
		} );

		// Verify that the page has loaded.
		await expect(
			page.getByRole( 'heading', { name: 'Add new order' } )
		).toBeVisible();
	} );

	test( 'can use the "Products" command', async ( { page } ) => {
		await goToPostEditor( { page } );

		await clickOnCommandPaletteOption( {
			page,
			optionName: 'Products',
		} );

		// Verify that the page has loaded.
		await expect(
			page.locator( 'h1' ).filter( { hasText: 'Products' } ).first()
		).toBeVisible();
	} );

	test( 'can use the "Orders" command', async ( { page } ) => {
		await goToPostEditor( { page } );

		await clickOnCommandPaletteOption( {
			page,
			optionName: 'Orders',
		} );

		// Verify that the page has loaded.
		await expect(
			page.locator( 'h1' ).filter( { hasText: 'Orders' } ).first()
		).toBeVisible();
	} );

	test( 'can use the product search command', async ( { page } ) => {
		await goToPostEditor( { page } );

		await clickOnCommandPaletteOption( {
			page,
			optionName: 'Product to search',
		} );

		// Verify that the page has loaded.
		await expect( page.getByLabel( 'Product name' ) ).toHaveValue(
			'Product to search'
		);
	} );

	test( 'can use a settings command', async ( { page } ) => {
		await goToPostEditor( { page } );

		await clickOnCommandPaletteOption( {
			page,
			optionName: 'WooCommerce Settings: Products',
		} );

		// Verify that the page has loaded.
		await expect( page.getByText( 'Shop pages' ) ).toBeVisible();
	} );

	test( 'can use an analytics command', async ( { page } ) => {
		await goToPostEditor( { page } );

		await clickOnCommandPaletteOption( {
			page,
			optionName: 'WooCommerce Analytics: Products',
		} );

		// Verify that the page has loaded.
		await expect(
			page.locator( 'h1' ).filter( { hasText: 'Products' } )
		).toBeVisible();
		const pageTitle = await page.title();
		expect( pageTitle.includes( 'Products ‹ Analytics' ) ).toBeTruthy();
	} );
} );
