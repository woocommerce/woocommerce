/**
 * External dependencies
 */
import { expect, test as base, wpCLI } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	REGULAR_PRICED_PRODUCT_NAME,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
} from '../checkout/constants';
import { getTestTranslation } from '../../utils/get-test-translation';
import { translations } from '../../test-data/data/data';
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
	test( 'Shopper can add item to cart, and will not see a notice in the mini cart', async ( {
		page,
		editor,
		admin,
		productCollectionPage,
	} ) => {
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

		await expect( page.getByText( 'Your cart' ) ).toBeVisible();
		await expect( page.getByText( '(1 item)' ) ).toBeVisible();

		await page.getByLabel( 'Close', { exact: true } ).click();
		// Mini cart gets out of sync if triggered to open and close very quickly. PW interacts too quickly
		// and this isn't something that you'll see often in real use. This waits for the mini cart to close.
		await expect( page.getByRole( 'dialog' ) ).toBeHidden();
		await page
			.getByLabel( `Add to cart: “${ SIMPLE_PHYSICAL_PRODUCT_NAME }”` )
			.click();

		await expect( page.getByText( 'Your cart' ) ).toBeVisible();
		await expect( page.getByText( '(2 items)' ) ).toBeVisible();
		await expect(
			page
				.getByRole( 'dialog' )
				.getByText(
					`The quantity of "${ SIMPLE_PHYSICAL_PRODUCT_NAME }" was`
				)
		).toBeHidden();
	} );
} );

test.describe( 'Shopper → Translations', () => {
	test.beforeEach( async () => {
		await wpCLI( `site switch-language ${ translations.locale }` );
	} );

	test( 'User can see translation in empty Mini-Cart', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await miniCartUtils.openMiniCart();

		await expect(
			page.getByRole( 'link', {
				name: getTestTranslation( 'Start shopping' ),
			} )
		).toBeVisible();
	} );

	test( 'User can see translation in filled Mini-Cart', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		await expect(
			page.getByRole( 'heading', {
				name: getTestTranslation( 'Your cart' ),
			} )
		).toBeVisible();

		await expect(
			page.getByRole( 'link', {
				name: getTestTranslation( 'View my cart' ),
			} )
		).toBeVisible();

		await expect(
			page.getByRole( 'link', {
				name: getTestTranslation( 'Go to checkout' ),
			} )
		).toBeVisible();
	} );
} );

test.describe( 'Shopper → Tax', () => {
	test.beforeEach( async () => {
		await wpCLI( 'option set woocommerce_prices_include_tax no' );
		await wpCLI( 'option set woocommerce_tax_display_cart incl' );
	} );

	test( 'User can see tax label and price including tax', async ( {
		frontendUtils,
		page,
	} ) => {
		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToMiniCart();

		await expect(
			page.getByTestId( 'mini-cart' ).getByLabel( '1 item in cart' )
		).toContainText( '(incl. tax)' );

		// Hovering over the mini cart should not change the label,
		// see https://github.com/woocommerce/woocommerce/issues/43691
		await page
			.getByTestId( 'mini-cart' )
			.getByLabel( '1 item in cart' )
			.dispatchEvent( 'mouseover' );

		await expect(
			page.getByTestId( 'mini-cart' ).getByLabel( '1 item in cart' )
		).toContainText( '(incl. tax)' );

		await wpCLI( 'option set woocommerce_prices_include_tax yes' );
		await wpCLI( 'option set woocommerce_tax_display_cart excl' );
		await page.reload();

		await expect(
			page.getByTestId( 'mini-cart' ).getByLabel( '1 item in cart' )
		).toContainText( '(ex. tax)' );
	} );
} );
