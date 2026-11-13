/**
 * External dependencies
 */
import { randomUUID } from 'crypto';
import {
	test,
	expect,
	BlockData,
	enableTestPlugin,
	wpCLI,
} from '@woocommerce/e2e-utils';

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
					'Your cart is empty'
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
			'Your cart is empty'
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
			'Your cart is empty'
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
			'Your cart is empty'
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

	test( 'should render the product table and its item data with no client-side error', async ( {
		page,
		context,
		frontendUtils,
		miniCartUtils,
	} ) => {
		// The fixture plugin adds `woocommerce_get_item_data` entries to every
		// cart item, so this test covers the whole wiring: the PHP filter, the
		// Store API `item_data` field, and the Interactivity API render. How
		// each entry is normalized is covered by the unit tests for
		// `mini-cart/utils/item-data`.
		await enableTestPlugin( context, 'item-data-display' );

		const pageErrors: string[] = [];
		const consoleErrors: string[] = [];

		// Register listeners before navigating so nothing that happens during
		// the mini-cart's render is missed.
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

		// The filtered entries reach the rendered table.
		const dialog = page.getByRole( 'dialog' );
		await expect(
			dialog.locator( '.wc-block-components-product-details__name' )
		).toContainText( [ 'Gift Message' ] );
		await expect(
			dialog.locator( '.wc-block-components-product-details__value' )
		).toContainText( [ 'Happy Birthday!' ] );

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
			miniCartDialog.getByRole( 'link', { name: 'Cart' } )
		).toBeVisible();

		await expect(
			miniCartDialog.getByRole( 'link', {
				name: 'Checkout',
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

		await expect( page.getByText( 'Your cart is empty' ) ).toBeVisible();
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
			.getByRole( 'link', { name: 'Cart' } )
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
			.getByRole( 'link', { name: 'Checkout' } )
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

test.describe( `${ blockData.name } Block (variation attributes)`, () => {
	test.use( {
		storageState: {
			origins: [],
			cookies: [],
		},
	} );

	test( 'should render variation attributes in the products table', async ( {
		page,
		frontendUtils,
		miniCartUtils,
		cleanup,
	} ) => {
		// The API returns the ampersand as "&amp;", so this also checks that
		// the variation branch of the table decodes it.
		const cliOutput = await wpCLI(
			`wc product create --user=1 --name="Test Variable ${ randomUUID() }" --type="variable" --catalog_visibility="hidden" --attributes='${ JSON.stringify(
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
		expect( productId ).toBeDefined();
		cleanup( async () => {
			await wpCLI(
				`wc product delete ${ productId } --force=true --user=1`
			);
		} );

		const variationOutput = await wpCLI(
			`wc product_variation create --user=1 "${ productId }" --regular_price="10" --attributes='${ JSON.stringify(
				[ { name: 'Shade', option: 'Red & Blue' } ]
			) }'`
		);
		const variationId = variationOutput.stdout.match(
			/Created product_variation (\d+)/
		)?.[ 1 ];

		// Add the variation through the classic add-to-cart URL, since the
		// product page form is not what this test covers.
		await page.goto(
			`/?add-to-cart=${ productId }&variation_id=${ variationId }`
		);
		await frontendUtils.goToShop();
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect(
			dialog.locator( '.wc-block-components-product-details__name' )
		).toContainText( [ 'Shade' ] );
		await expect(
			dialog.locator( '.wc-block-components-product-details__value' )
		).toContainText( [ 'Red & Blue' ] );
	} );
} );
