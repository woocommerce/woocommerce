/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { expect, test as baseTest } from '../../fixtures/fixtures';
import { getInstalledWordPressVersion } from '../../utils/wordpress';

const clickOnCommandPaletteOption = async ( {
	page,
	optionName,
}: {
	page: Page;
	optionName: string;
} ) => {
	// Using a regex here because Gutenberg changes the text of the placeholder
	const searchBox = page.getByPlaceholder(
		/Search (?:commands(?: and settings)?|for commands)/
	);

	// WordPress registers the command-palette shortcut via @wordpress/keycodes'
	// `isAppleOS()`, which inspects `navigator.platform` only. In Playwright's
	// Chromium on macOS these two disagree: `navigator.platform` is "MacIntel"
	// (Apple → Cmd+K) while `navigator.userAgentData.platform` is "Windows".
	// Deriving the modifier from `userAgentData` therefore sends Ctrl+K and the
	// palette never opens. Reuse `isAppleOS()` so the test always matches whatever
	// modifier WordPress actually registered (falling back to its logic if the
	// keycodes package is unavailable).
	const isApplePlatform = await page.evaluate( () => {
		const isAppleOS = (
			window as Window & {
				wp?: { keycodes?: { isAppleOS?: () => boolean } };
			}
		 ).wp?.keycodes?.isAppleOS;
		if ( typeof isAppleOS === 'function' ) {
			return isAppleOS();
		}
		const { platform } = navigator;
		return (
			platform.indexOf( 'Mac' ) !== -1 ||
			[ 'iPad', 'iPhone' ].includes( platform )
		);
	} );
	const cmdKeyCombo = isApplePlatform ? 'Meta+k' : 'Control+k';

	// Press `Ctrl`/`Cmd` + `K` to open the command palette.
	await page.keyboard.press( cmdKeyCombo );

	await searchBox.fill( optionName );

	// TODO: WP 7.0 compat - WP 7.0 appends "Action" to command palette option
	// accessible names. Simplify when WP 7.0 is the minimum supported version.
	const option = page.getByRole( 'option', {
		name: new RegExp( `^${ optionName }( Action)?$` ),
	} );
	await expect( option ).toBeVisible();
	await option.click();
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
		const adminEntryPoint =
			( await getInstalledWordPressVersion() ) === 6.8
				? 'wp-admin/post-new.php'
				: 'wp-admin';
		const waitForCommandPalette = page.waitForResponse( ( response ) => {
			return (
				response
					.url()
					.includes( '/wp-admin-scripts/command-palette' ) &&
				response.status() === 200
			);
		} );
		await Promise.all( [
			page.goto( adminEntryPoint ),
			waitForCommandPalette,
		] );
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
