/**
 * External dependencies
 */
import { expect, test as base } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	REGULAR_PRICED_PRODUCT_NAME,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
} from '../checkout/constants';
import ProductCollectionPage from '../product-collection/product-collection.page';

const test = base.extend< { productCollectionPage: ProductCollectionPage } >( {
	productCollectionPage: async ( { page, admin, editor }, use ) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Shopper → Notices', () => {
	test( 'Shopper sees SSR error notice in mini cart when product goes out of stock', async ( {
		page,
		browser,
		frontendUtils,
		requestUtils,
	} ) => {
		const productName = 'Limited Stock Product';

		// Create a product with only 1 in stock.
		const createdProduct = await requestUtils.rest< unknown >( {
			method: 'POST',
			path: 'wc/v2/products',
			data: {
				name: productName,
				regular_price: '10',
				manage_stock: true,
				stock_quantity: 1,
			},
		} );
		if (
			typeof createdProduct !== 'object' ||
			createdProduct === null ||
			! ( 'id' in createdProduct ) ||
			typeof createdProduct.id !== 'number' ||
			! Number.isInteger( createdProduct.id ) ||
			createdProduct.id <= 0
		) {
			throw new Error(
				`Failed to create a product through REST: ${ JSON.stringify(
					createdProduct
				) }`
			);
		}
		const productId = createdProduct.id;

		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( productName );

		// Set product to out of stock while it's in cart.
		const updatedProduct = await requestUtils.rest< unknown >( {
			method: 'POST',
			path: `wc/v2/products/${ productId }`,
			data: {
				stock_quantity: 0,
				in_stock: false,
			},
		} );
		if (
			typeof updatedProduct !== 'object' ||
			updatedProduct === null ||
			! ( 'id' in updatedProduct ) ||
			updatedProduct.id !== productId ||
			! ( 'manage_stock' in updatedProduct ) ||
			updatedProduct.manage_stock !== true ||
			! ( 'stock_quantity' in updatedProduct ) ||
			updatedProduct.stock_quantity !== 0 ||
			! ( 'in_stock' in updatedProduct ) ||
			updatedProduct.in_stock !== false
		) {
			throw new Error(
				`Failed to mark the product out of stock through REST: ${ JSON.stringify(
					updatedProduct
				) }`
			);
		}

		// Get the current URL to revisit with JS disabled.
		const currentUrl = page.url();

		// Create a new context with JavaScript disabled to verify SSR output.
		const noJsContext = await browser.newContext( {
			javaScriptEnabled: false,
		} );

		try {
			const noJsPage = await noJsContext.newPage();

			// Copy cookies to maintain cart session.
			const cookies = await page.context().cookies();
			await noJsContext.addCookies( cookies );

			await noJsPage.goto( currentUrl );

			// Verify error notice banner is rendered in SSR output (not client-side JS).
			// Note: The notice text content contains HTML and is rendered client-side via
			// data-wp-init callback, so we only verify the banner structure exists in SSR.
			const miniCartNotice = noJsPage.locator(
				'.wp-block-woocommerce-filled-mini-cart-contents-block .wc-block-components-notice-banner'
			);
			await expect( miniCartNotice ).toBeVisible();
		} finally {
			await noJsContext.close();
		}
	} );

	test( 'Shopper can add item to cart, and will not see a notice in the mini cart', async ( {
		page,
		editor,
		admin,
		productCollectionPage,
	} ) => {
		const checkMiniCartTitle = async ( itemCount: number ) => {
			try {
				// iAPI Mini Cart.
				const miniCartTitleLabelBlock = page.locator(
					'[data-block-name="woocommerce/mini-cart-title-label-block"]'
				);
				await expect( miniCartTitleLabelBlock ).toBeVisible( {
					timeout: 1000,
				} );
				const miniCartTitleItemsCounterBlock = page.locator(
					'[data-block-name="woocommerce/mini-cart-title-items-counter-block"]'
				);
				await expect( miniCartTitleLabelBlock ).toHaveText(
					'Your cart'
				);
				await expect( miniCartTitleItemsCounterBlock ).toBeVisible();
				await expect( miniCartTitleItemsCounterBlock ).toContainText(
					String( itemCount )
				);
			} catch ( e ) {
				// Legacy React Mini Cart.
				await expect( page.getByText( 'Your cart' ) ).toBeVisible();
				await expect(
					page.getByText(
						`(${ itemCount } item${ itemCount > 1 ? 's' : '' })`
					)
				).toBeVisible();
			}
		};

		await admin.visitSiteEditor( {
			postId: `twentytwentyfour//header`,
			postType: 'wp_template_part',
			canvas: 'edit',
		} );
		const miniCart = await editor.getBlockByName( 'woocommerce/mini-cart' );
		await editor.selectBlocks( miniCart );
		const openDrawerControl = editor.page.getByLabel(
			'Open drawer when adding'
		);
		await openDrawerControl.check();
		await editor.page
			.getByRole( 'button', { name: 'Save', exact: true } )
			.click();
		await productCollectionPage.createNewPostAndInsertBlock(
			'productCatalog'
		);
		await productCollectionPage.publishAndGoToFrontend();
		await page
			.getByLabel( `Add to cart: “${ SIMPLE_PHYSICAL_PRODUCT_NAME }”` )
			.click();

		await checkMiniCartTitle( 1 );

		await page.getByLabel( 'Close', { exact: true } ).click();
		// Mini cart gets out of sync if triggered to open and close very quickly. PW interacts too quickly
		// and this isn't something that you'll see often in real use. This waits for the mini cart to close.
		await expect( page.getByRole( 'dialog' ) ).toBeHidden();
		await page
			.getByLabel( `Add to cart: “${ SIMPLE_PHYSICAL_PRODUCT_NAME }”` )
			.click();

		await checkMiniCartTitle( 2 );

		await expect(
			page
				.getByRole( 'dialog' )
				.getByText(
					`The quantity of "${ SIMPLE_PHYSICAL_PRODUCT_NAME }" was`
				)
		).toBeHidden();
	} );
} );

test.describe( 'Shopper → Tax', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/tax/woocommerce_prices_include_tax',
			data: { value: 'no' },
		} );
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/tax/woocommerce_tax_display_cart',
			data: { value: 'incl' },
		} );
	} );

	test( 'User can see tax label and price including tax', async ( {
		frontendUtils,
		page,
		requestUtils,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToMiniCart();

		const miniCartLocator = page
			.getByTestId( 'mini-cart' )
			.getByLabel( 'Number of items in the cart: 1' );

		await expect( miniCartLocator ).toContainText( '(incl. tax)' );

		// Hovering over the mini cart should not change the label,
		// see https://github.com/woocommerce/woocommerce/issues/43691
		await miniCartLocator.dispatchEvent( 'mouseover' );

		await expect( miniCartLocator ).toContainText( '(incl. tax)' );

		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/tax/woocommerce_prices_include_tax',
			data: { value: 'yes' },
		} );
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/tax/woocommerce_tax_display_cart',
			data: { value: 'excl' },
		} );
		await page.reload();

		await expect( miniCartLocator ).toContainText( '(ex. tax)' );
	} );
} );
