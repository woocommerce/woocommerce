/**
 * External dependencies
 */
import { disableWelcomeModal } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { expect, test as baseTest } from '../../fixtures/fixtures';
import { WC_API_PATH } from '../../utils/api-client';

// need to figure out whether tests are being run on a mac
const macOS = process.platform === 'darwin';
const cmdKeyCombo = macOS ? 'Meta+k' : 'Control+k';

const clickOnCommandPaletteOption = async ( { page, optionName } ) => {
	// Press `Ctrl` + `K` to open the command palette.
	await page.keyboard.press( cmdKeyCombo );

	// Using a regex here because Gutenberg changes the text of the placeholder
	await page
		.getByPlaceholder(
			/Search (?:commands(?: and settings)?|for commands)/
		)
		.fill( optionName );

	// Click on the relevant option.
	const option = page.getByRole( 'option', {
		name: optionName,
		exact: true,
	} );
	await expect( option ).toBeVisible();
	option.click();
};

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	product: async ( { restApi }, use ) => {
		let product = {
			id: 0,
			name: `Product ${ Date.now() }`,
			type: 'simple',
		};

		await restApi
			.post( `${ WC_API_PATH }/products`, product )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		// Cleanup
		await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
			force: true,
		} );
	},
	page: async ( { page }, use ) => {
		await page.goto( 'wp-admin/post-new.php' );
		await disableWelcomeModal( { page } );
		await use( page );
	},
} );

test( 'can use the "Add new product" command', async ( { page } ) => {
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
	await clickOnCommandPaletteOption( {
		page,
		optionName: 'Orders',
	} );

	// Verify that the page has loaded.
	await expect(
		page.locator( 'h1' ).filter( { hasText: 'Orders' } ).first()
	).toBeVisible();
} );

test( 'can use the product search command', async ( { page, product } ) => {
	await clickOnCommandPaletteOption( {
		page,
		optionName: product.name,
	} );

	// Verify that the page has loaded.
	await expect( page.getByLabel( 'Product name' ) ).toHaveValue(
		`${ product.name }`
	);
} );

test( 'can use a settings command', async ( { page } ) => {
	await clickOnCommandPaletteOption( {
		page,
		optionName: 'WooCommerce Settings: Products',
	} );

	// Verify that the page has loaded.
	await expect( page.getByText( 'Shop pages' ) ).toBeVisible();
} );

test( 'can use an analytics command', async ( { page } ) => {
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
