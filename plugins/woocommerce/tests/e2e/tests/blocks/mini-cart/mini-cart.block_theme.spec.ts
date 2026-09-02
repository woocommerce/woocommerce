/**
 * External dependencies
 */
import { test, expect, BlockData, wpCLI } from '@woocommerce/e2e-utils';
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

const blockData: BlockData = {
	name: 'Mini-Cart',
	slug: 'woocommerce/mini-cart',
	mainClass: '.wc-block-minicart',
	selectors: {
		frontend: {},
		editor: {},
	},
};

test.describe( `${ blockData.name } Block`, () => {
	const checkMiniCartTitle = async ( page, itemCount ) => {
		try {
			// iAPI Mini Cart.
			const miniCartTitleBlock = page.locator(
				'[data-block-name="woocommerce/mini-cart-title-block"]'
			);
			await expect( miniCartTitleBlock ).toBeVisible( { timeout: 1000 } );
			const titleText = await miniCartTitleBlock.innerText();
			expect(
				titleText?.includes(
					`(${ itemCount } item${ itemCount > 1 ? 's' : '' })`
				) || titleText?.includes( `(items: ${ itemCount })` )
			).toBeTruthy();
		} catch {
			// Legacy React Mini Cart.
			if ( itemCount > 0 ) {
				await expect(
					page.getByRole( 'heading', {
						name: `Your cart (${ itemCount } item${
							itemCount > 1 ? 's' : ''
						})`,
					} )
				).toBeVisible();
			} else {
				await expect( page.getByRole( 'dialog' ) ).toContainText(
					'Your cart is currently empty!'
				);
			}
		}
	};

	const checkProductLink = async ( page ) => {
		try {
			// iAPI Mini Cart.
			await expect(
				page
					.getByRole( 'link', { name: REGULAR_PRICED_PRODUCT_NAME } )
					.filter( { has: page.locator( ':visible' ) } )
			).toBeVisible( { timeout: 1000 } );
		} catch {
			// Legacy React Mini Cart.
			await expect(
				page.getByRole( 'link', { name: REGULAR_PRICED_PRODUCT_NAME } )
			).toBeVisible();
		}
	};
	/**
	 * This is a workaround to run tests in isolation.
	 * Ideally, the test should be run in isolation by default. But we're
	 * overriding the storageState in config which make all tests run with admin
	 * user.
	 */
	test.use( {
		storageState: {
			origins: [],
			cookies: [],
		},
	} );

	test( 'should the Mini Cart block be present near the navigation block', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		const block = await frontendUtils.getBlockByName( blockData.slug );

		const navigationBlock = page.locator(
			`//div[@data-block-name='${ blockData.slug }']/preceding-sibling::nav[contains(@class, 'wp-block-navigation')]`
		);

		await expect( navigationBlock ).toBeVisible();
		await expect( block ).toBeVisible();
	} );

	test( 'should open the empty cart drawer', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await miniCartUtils.openMiniCart();

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your shopping cart is empty'
		);
		await expect(
			page.getByRole( 'link', { name: 'Return to shop' } )
		).toBeVisible();
	} );

	test( 'should close the drawer when clicking on the close button', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await miniCartUtils.openMiniCart();

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your shopping cart is empty'
		);

		await page.getByRole( 'button', { name: 'Close' } ).click();
		await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
	} );

	test( 'should close the drawer when clicking outside the drawer', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await miniCartUtils.openMiniCart();

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your shopping cart is empty'
		);

		await page.mouse.click( 0, 0 );
		await expect( page.getByRole( 'dialog' ) ).toHaveCount( 0 );
	} );

	test( 'should open the filled cart drawer', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await page.click( 'text=Add to cart' );
		await miniCartUtils.openMiniCart();

		await checkMiniCartTitle( page, 1 );
	} );

	test( 'should show the correct cart items count', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		await checkMiniCartTitle( page, 1 );

		await page.getByRole( 'button', { name: 'Close' } ).click();

		// Mini cart gets out of sync if triggered to open and close very quickly. PW interacts too quickly
		// and this isn't something that you'll see often in real use. This waits for the mini cart to close.
		await expect( page.getByRole( 'dialog' ) ).toBeHidden();

		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		await checkMiniCartTitle( page, 2 );
	} );

	test( 'should show the correct cart item name', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		await checkProductLink( page );
	} );

	test( 'should render the product table with no client-side error for a product with no item data', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		const pageErrors: string[] = [];
		const consoleErrors: string[] = [];

		// Register listeners before navigating so nothing that happens during
		// the mini-cart's render is missed. This product has no variation and
		// no `woocommerce_get_item_data` additions (no fixture plugin is
		// activated in this describe block), which is the plain rendering
		// path the item-data/separator getters must not throw on.
		page.on( 'pageerror', ( error ) => {
			pageErrors.push( error.message );
		} );
		page.on( 'console', ( message ) => {
			// Exclude the browser's own "failed to load resource" network
			// diagnostics: they are not JS errors raised by page code (no
			// `console.error` call is involved) and can fire for reasons
			// entirely unrelated to the item-data/separator logic under
			// test, e.g. an unrelated missing stylesheet.
			if (
				message.type() === 'error' &&
				! message.text().includes( 'Failed to load resource' )
			) {
				consoleErrors.push( message.text() );
			}
		} );

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		// The product table renders normally: name, price, quantity.
		await checkProductLink( page );
		await expect(
			page.locator( '.wc-block-components-product-price' ).first()
		).toBeVisible();
		await expect(
			page.getByLabel( 'Quantity of Polo in your cart.' )
		).toHaveValue( '1' );

		expect( pageErrors ).toEqual( [] );
		expect( consoleErrors ).toEqual( [] );
	} );

	test( 'should show subtotal, view cart button and checkout button', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();
		const miniCartDialog = page.getByRole( 'dialog' );

		await expect( page.getByText( 'Subtotal' ) ).toBeVisible();

		await expect(
			miniCartDialog.getByRole( 'link', { name: 'View cart' } )
		).toBeVisible();

		await expect(
			miniCartDialog.getByRole( 'link', {
				name: 'Go to checkout',
			} )
		).toBeVisible();
	} );

	test( 'should allow to update the product quantity', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		await expect(
			page.getByLabel( 'Quantity of Polo in your cart.' )
		).toHaveValue( '1' );

		// Set up waitForResponse BEFORE the click to avoid race condition.
		let batchPromise = page.waitForResponse(
			'**/wp-json/wc/store/v1/batch**'
		);
		await page
			.getByRole( 'button', { name: 'Increase quantity of Polo' } )
			.click();

		await batchPromise;

		await expect(
			page.getByLabel( 'Quantity of Polo in your cart.' )
		).toHaveValue( '2' );

		batchPromise = page.waitForResponse( '**/wp-json/wc/store/v1/batch**' );
		await page
			.getByRole( 'button', { name: 'Reduce quantity of Polo' } )
			.click();

		await batchPromise;

		await expect(
			page.getByLabel( 'Quantity of Polo in your cart.' )
		).toHaveValue( '1' );

		await expect(
			page.getByRole( 'button', { name: 'Reduce quantity of Polo' } )
		).toBeDisabled();
	} );

	test( 'should allow to remove a product from the cart', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		await checkProductLink( page );

		await page
			.getByRole( 'button', { name: 'Remove Polo from cart' } )
			.click();

		await expect(
			page.getByText( 'Your shopping cart is empty' )
		).toBeVisible();
	} );

	test( 'should allow to proceed to the cart page', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();
		await page
			.getByRole( 'dialog' )
			.getByRole( 'link', { name: 'View cart' } )
			.click();
		await expect( page ).toHaveURL( /\/cart\/?$/ );
	} );

	test( 'should allow to proceed to the checkout page', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();
		await page
			.getByRole( 'dialog' )
			.getByRole( 'link', { name: 'Go to checkout' } )
			.click();
		await expect( page ).toHaveURL( /\/checkout\/?$/ );
	} );

	// eslint-disable-next-line playwright/no-skipped-test
	test.skip( 'should process badge colors on load', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );

		// Get the badge element and verify colors are computed.
		const badge = page.locator( '.wc-block-mini-cart__badge' );
		await expect( badge ).toBeVisible();

		// Wait for colors to be computed (they start as transparent).
		await expect( badge ).toHaveCSS(
			'background-color',
			/.+(?<!transparent)/
		);

		// Get the initial computed colors.
		const initialBgColor = await badge.evaluate(
			( el ) => window.getComputedStyle( el ).backgroundColor
		);
		const initialTextColor = await badge.evaluate(
			( el ) => window.getComputedStyle( el ).color
		);

		// Verify colors are not transparent (they should be computed).
		expect( initialBgColor ).not.toBe( 'transparent' );
		expect( initialBgColor ).not.toBe( 'rgba(0, 0, 0, 0)' );
		expect( initialTextColor ).not.toBe( 'transparent' );
		expect( initialTextColor ).not.toBe( 'rgba(0, 0, 0, 0)' );
	} );
} );

test.describe( `${ blockData.name } Block (admin)`, () => {
	// eslint-disable-next-line playwright/no-skipped-test
	test.skip( 'should update badge colors when header background changes', async ( {
		page,
		admin,
		editor,
		frontendUtils,
	} ) => {
		// First, change the header background color in the site editor.
		await admin.visitSiteEditor( {
			postId: 'twentytwentyfour//header',
			postType: 'wp_template_part',
			canvas: 'edit',
		} );

		// Select the mini-cart block to get access to its parent (the header row).
		const miniCartBlock = editor.canvas.locator(
			'[data-type="woocommerce/mini-cart"]'
		);
		await miniCartBlock.click();

		// Select the parent Row block that contains the mini-cart.
		// Use the block toolbar to select parent.
		await editor.clickBlockToolbarButton( 'Select parent block: Row' );

		// Now open the Styles panel and set background color.
		await editor.openDocumentSettingsSidebar();

		// Click on the Styles tab.
		const stylesTab = page.getByRole( 'tab', { name: 'Styles' } );
		if ( await stylesTab.isVisible() ) {
			await stylesTab.click();
		}

		// Find and click the background color control.
		const bgColorButton = page
			.getByRole( 'button', { name: 'Background' } )
			.first();
		await bgColorButton.click();

		// Select "Contrast" preset color (black).
		await page
			.getByRole( 'option', { name: 'Contrast', exact: true } )
			.click();

		// Extract the background color hex value from the editor UI.
		const parentBgColorHex = await page
			.locator( '.components-color-palette__custom-color-value' )
			.textContent();

		// Close the background color popover by clicking outside.
		await stylesTab.click();

		// Find and click the text color control.
		const textColorButton = page
			.getByRole( 'button', { name: 'Text' } )
			.first();
		await textColorButton.click();

		// Select "Base" preset color (white).
		await page.getByRole( 'option', { name: 'Base', exact: true } ).click();

		// Extract the text color hex value from the editor UI.
		const parentTextColorHex = await page
			.locator( '.components-color-palette__custom-color-value' )
			.textContent();

		// Save the changes.
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		// Add an item to cart (use a product that's on the first page).
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );

		// Verify color values were extracted from the editor.
		expect( parentBgColorHex ).toBeTruthy();
		expect( parentTextColorHex ).toBeTruthy();

		// Helper to convert hex color to rgb format.
		const hexToRgb = ( hex: string ) => {
			const cleanHex = hex.replace( '#', '' );
			const r = parseInt( cleanHex.slice( 0, 2 ), 16 );
			const g = parseInt( cleanHex.slice( 2, 4 ), 16 );
			const b = parseInt( cleanHex.slice( 4, 6 ), 16 );
			return `rgb(${ r }, ${ g }, ${ b })`;
		};

		// Verify the badge has the correct colors:
		// - Badge background = parent's text color (inverted)
		// - Badge text = parent's background color (inverted)
		const badge = page.locator( '.wc-block-mini-cart__badge' );
		await expect( badge ).toHaveCSS(
			'background-color',
			hexToRgb( parentTextColorHex as string )
		);
		await expect( badge ).toHaveCSS(
			'color',
			hexToRgb( parentBgColorHex as string )
		);

		// Navigate to the next page using client-side navigation.
		await page.getByRole( 'link', { name: 'Next Page' } ).click();

		// Await for the navigation to happen.
		await expect( page ).toHaveURL( /page\/2\/?$/ );

		// Verify the badge colors persist after navigation.
		await expect( badge ).toHaveCSS(
			'background-color',
			hexToRgb( parentTextColorHex as string )
		);
		await expect( badge ).toHaveCSS(
			'color',
			hexToRgb( parentBgColorHex as string )
		);
	} );
} );

/**
 * Registers page-error and console-error listeners for a defect scenario.
 * Must be called before navigating so Mini-Cart render errors are observed.
 *
 * @param {Page} page Playwright page to observe.
 * @return {{pageErrors: string[], consoleErrors: string[]}} Error accumulators.
 */
const trackErrors = ( page: Page ) => {
	const pageErrors: string[] = [];
	const consoleErrors: string[] = [];

	page.on( 'pageerror', ( error ) => {
		pageErrors.push( error.message );
	} );
	page.on( 'console', ( message ) => {
		if (
			message.type() === 'error' &&
			! message.text().includes( 'Failed to load resource' )
		) {
			consoleErrors.push( message.text() );
		}
	} );

	return { pageErrors, consoleErrors };
};

test.describe( `${ blockData.name } Block (item data)`, () => {
	test.use( {
		storageState: {
			origins: [],
			cookies: [],
		},
	} );

	// Activate in beforeEach because the DB is reset after every test.
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-item-data-display'
		);
	} );

	test( 'should render item data safely with display markup and no trailing separator after malformed or hidden entries', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		const { pageErrors, consoleErrors } = trackErrors( page );

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		const details = dialog
			.locator( '.wc-block-components-product-details' )
			.filter( { hasText: 'Gift Message:' } );
		await expect( details ).toHaveCount( 1 );

		await test.step( 'renders every exact fixture value', async () => {
			const entries = details.locator( ':scope > span' );
			await expect( entries ).toHaveCount( 7 );
			await expect(
				details.locator( ':scope > span:visible' )
			).toHaveCount( 4 );
			await expect( entries.nth( 4 ) ).toBeHidden();
			await expect( entries.nth( 5 ) ).toBeHidden();
			await expect( entries.nth( 6 ) ).toBeHidden();
			await expect(
				entries.locator( '.wc-block-components-product-details__name' )
			).toHaveText( [
				'Gift Message:',
				'Engraving:',
				'Size:',
				'Note:',
				'',
				'',
				'Secret:',
			] );
			await expect(
				entries.locator( '.wc-block-components-product-details__value' )
			).toHaveText( [
				'Happy Birthday!',
				'Best Wishes',
				'1 < 2',
				'<b>important</b>',
				'',
				'',
				'v',
			] );
		} );

		await test.step( 'distinguishes allowed markup from encoded tag text', async () => {
			const values = details.locator(
				'.wc-block-components-product-details__value'
			);
			await expect( values.nth( 1 ).locator( 'em' ) ).toHaveText(
				'Best Wishes'
			);
			await expect( values.nth( 3 ) ).toHaveText( '<b>important</b>' );
			await expect( values.nth( 3 ).locator( 'b' ) ).toHaveCount( 0 );
		} );

		await test.step( 'shows separators only between visible entries', async () => {
			const entries = details.locator( ':scope > span' );
			await expect( entries ).toHaveCount( 7 );
			const separators = entries.locator(
				':scope > span[aria-hidden="true"]'
			);
			await expect( separators ).toHaveCount( 7 );
			await expect( separators.nth( 0 ) ).toBeVisible();
			await expect( separators.nth( 1 ) ).toBeVisible();
			await expect( separators.nth( 2 ) ).toBeVisible();
			await expect( separators.nth( 3 ) ).toBeHidden();
			await expect( separators.nth( 4 ) ).toBeHidden();
			await expect( separators.nth( 5 ) ).toBeHidden();
			await expect( separators.nth( 6 ) ).toBeHidden();
		} );

		expect( pageErrors ).toEqual( [] );
		expect( consoleErrors ).toEqual( [] );
	} );
} );

test.describe( `${ blockData.name } Block (variation attributes)`, () => {
	test.use( {
		storageState: {
			origins: [],
			cookies: [],
		},
	} );

	test( 'should decode entities in variation attributes rendered via data-wp-text', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		// Create a variable product with an attribute value containing an
		// ampersand, which the API returns as "&amp;". The data-wp-text
		// path (textContent) relies on the textarea entity-decode step to
		// display "Red & Blue" instead of literal "Red &amp; Blue".
		const cliOutput = await wpCLI(
			`wc product create --user=1 --name="Test Variable" --type="variable" --attributes='${ JSON.stringify(
				[
					{
						name: 'Shade',
						options: [ 'Red & Blue' ],
						variation: true,
						visible: true,
					},
				]
			) }'`
		);
		const productId = cliOutput.stdout.match(
			/Created product (\d+)/
		)?.[ 1 ];
		await wpCLI(
			`wc product_variation create --user=1 "${ productId }" --regular_price="10" --attributes='${ JSON.stringify(
				[ { name: 'Shade', option: 'Red & Blue' } ]
			) }'`
		);

		// Navigate to the product page and add the variation to cart.
		await page.goto( `/product/test-variable/` );
		await page
			.getByLabel( 'Shade', { exact: true } )
			.selectOption( 'Red & Blue' );

		// The classic variable-product Add to cart button only registers a
		// selection once the variations script has processed it. Until then the
		// hidden `variation_id` input stays at "0" and clicking is a silent
		// no-op (the disabled state is class-based, which Playwright's
		// actionability checks ignore). Wait for the variation to be registered
		// (a positive, non-zero variation id) before clicking.
		// That is necessary but not sufficient: the script sets `variation_id`
		// synchronously and only drops the `disabled` class ~300ms later, from a
		// setTimeout, so wait for the button to be enabled as well. Clicking in
		// that window hits the guard that raises a window.alert Playwright
		// silently dismisses, and the form never submits.
		await expect( page.locator( 'input.variation_id' ) ).toHaveValue(
			/^[1-9][0-9]*$/
		);
		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart',
			exact: true,
		} );
		await expect( addToCartButton ).not.toHaveClass( /\bdisabled\b/ );
		await addToCartButton.click();

		// Wait for a definitive "added to cart" confirmation before navigating
		// to the shop. The single-product Add to cart is a form submission; if
		// we navigate away before it settles the item may not be in the cart
		// when the mini-cart opens, which is what makes the assertions below
		// race the add-to-cart request. Scope to the success notice (role=alert)
		// so we don't match unrelated page text.
		await expect(
			page.getByRole( 'alert' ).getByText( /added to your cart/i )
		).toBeVisible();

		// Open the mini-cart.
		await frontendUtils.goToShop();
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		const variationDetails = dialog
			.locator( '.wc-block-components-product-details' )
			.filter( { hasText: 'Shade:' } );
		await expect( variationDetails ).toHaveCount( 1 );

		const variationEntry = variationDetails.locator(
			':scope > span:visible'
		);
		await expect( variationEntry ).toHaveCount( 1 );
		await expect(
			variationEntry.locator(
				'.wc-block-components-product-details__name'
			)
		).toHaveText( 'Shade:' );
		const variationValue = variationEntry.locator(
			'.wc-block-components-product-details__value'
		);
		await expect( variationValue ).toHaveText( 'Red & Blue' );
		await expect( variationValue ).not.toHaveText( 'Red &amp; Blue' );
	} );
} );
