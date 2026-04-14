/**
 * External dependencies
 */
import { test, expect, BlockData, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';
import config from '../../../../../admin/config/core.json';

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
		} catch ( e ) {
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
		} catch ( e ) {
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
			'Your cart is currently empty!'
		);
	} );

	test( 'should close the drawer when clicking on the close button', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await miniCartUtils.openMiniCart();

		await expect( page.getByRole( 'dialog' ) ).toContainText(
			'Your cart is currently empty!'
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
			'Your cart is currently empty!'
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

	test( 'should show subtotal, view cart button and checkout button', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		await expect( page.getByText( 'Subtotal' ) ).toBeVisible();

		await expect(
			page.getByRole( 'link', { name: 'View my cart' } )
		).toBeVisible();

		await expect(
			page.getByRole( 'link', { name: 'Go to checkout' } )
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

		// iAPI cart uses batch requests, legacy cart uses individual endpoints.
		// Set up waitForResponse BEFORE the click to avoid race condition.
		const useBatch = config.features[ 'experimental-iapi-mini-cart' ];
		let batchPromise = useBatch
			? page.waitForResponse( '**/wp-json/wc/store/v1/batch**' )
			: null;
		await page
			.getByRole( 'button', { name: 'Increase quantity of Polo' } )
			.click();

		if ( batchPromise ) {
			await batchPromise;
		}

		await expect(
			page.getByLabel( 'Quantity of Polo in your cart.' )
		).toHaveValue( '2' );

		batchPromise = useBatch
			? page.waitForResponse( '**/wp-json/wc/store/v1/batch**' )
			: null;
		await page
			.getByRole( 'button', { name: 'Reduce quantity of Polo' } )
			.click();

		if ( batchPromise ) {
			await batchPromise;
		}

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
			page.getByText( 'Your cart is currently empty!' )
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
		await page.getByRole( 'link', { name: 'View my cart' } ).click();
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
		await page.getByRole( 'link', { name: 'Go to checkout' } ).click();
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

	test( 'should render plain text item data', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		// Verify the plain text name and value are rendered.
		await expect(
			dialog.locator( '.wc-block-components-product-details__name' )
		).toContainText( [ 'Gift Message' ] );
		await expect(
			dialog.locator( '.wc-block-components-product-details__value' )
		).toContainText( [ 'Happy Birthday!' ] );
	} );

	test( 'should use display field value when present', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		// The display field contains "<em>Best Wishes</em>".
		// Verify the <em> tag is rendered as an actual HTML element.
		const engravingValue = dialog
			.locator( '.wc-block-components-product-details__value' )
			.filter( { hasText: 'Best Wishes' } );
		await expect( engravingValue ).toBeVisible();
		await expect( engravingValue.locator( 'em' ) ).toBeVisible();
		await expect( engravingValue.locator( 'em' ) ).toHaveText(
			'Best Wishes'
		);
	} );

	test( 'should decode HTML entities in item data values', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		// The value "1 &lt; 2" should display as "1 < 2"
		// (entity decoded), not as literal "1 &lt; 2".
		const sizeValue = dialog
			.locator( '.wc-block-components-product-details__value' )
			.filter( { hasText: '1' } );
		await expect( sizeValue ).toBeVisible();
		await expect( sizeValue ).toContainText( '1 < 2' );
	} );

	test( 'should not render entity-encoded HTML tags as DOM elements', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		const noteValue = dialog
			.locator( '.wc-block-components-product-details__value' )
			.filter( { hasText: 'important' } );
		await expect( noteValue ).toBeVisible();
		await expect( noteValue.locator( 'b' ) ).toHaveCount( 0 );
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
		await page
			.getByRole( 'button', { name: 'Add to cart', exact: true } )
			.click();

		// Open the mini-cart.
		await frontendUtils.goToShop();
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		// Variation attributes are rendered via data-wp-text (textContent).
		// The entity "&amp;" should be decoded to "&".
		await expect(
			dialog.locator( '.wc-block-components-product-details__name' )
		).toContainText( [ 'Shade' ] );
		await expect(
			dialog.locator( '.wc-block-components-product-details__value' )
		).toContainText( [ 'Red & Blue' ] );
	} );
} );
