/**
 * External dependencies
 */
import {
	clickBlockToolbarButton,
	switchUserToAdmin,
	searchForBlock,
	openGlobalBlockInserter,
	insertBlock,
} from '@wordpress/e2e-test-utils';
import {
	findLabelWithText,
	visitBlockPage,
	selectBlockByName,
} from '@woocommerce/blocks-test-utils';
import { merchant } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	openSettingsSidebar,
	openWidgetEditor,
	closeModalIfExists,
} from '../../utils.js';

const block = {
	name: 'Cart',
	slug: 'woocommerce/cart',
	class: '.wp-block-woocommerce-cart',
	selectors: {
		insertButton: "//button//span[text()='Cart']",
	},
};

const filledCartBlock = {
	name: 'Filled Cart',
	slug: 'woocommerce/filled-cart-block',
	class: '.wp-block-woocommerce-filled-cart-block',
};

const emptyCartBlock = {
	name: 'Empty Cart',
	slug: 'woocommerce/empty-cart-block',
	class: '.wp-block-woocommerce-empty-cart-block',
};

const crossSellsBlock = {
	name: 'Cart Cross-Sells block',
	slug: 'woocommerce/cart-cross-sells-products-block',
	class: '.wp-block-woocommerce-cart-cross-sells-products-block',
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 ) {
	// eslint-disable-next-line jest/no-focused-tests, jest/expect-expect
	test.only( `skipping ${ block.name } tests`, () => {} );
}

describe( `${ block.name } Block`, () => {
	describe( 'in page editor', () => {
		beforeAll( async () => {
			await switchUserToAdmin();
			await visitBlockPage( `${ block.name } Block` );
		} );

		it( 'can only be inserted once', async () => {
			await openGlobalBlockInserter();
			await page.keyboard.type( block.name );
			const button = await page.$x( block.selectors.insertButton );
			expect( button ).toHaveLength( 0 );
		} );

		it( 'inner blocks can be added/removed by filters', async () => {
			// Begin by removing the block.
			await selectBlockByName( block.slug );
			const options = await page.$x(
				'//div[@class="block-editor-block-toolbar"]//button[@aria-label="Options"]'
			);
			await options[ 0 ].click();
			const removeButton = await page.$x(
				'//button[contains(., "Remove Cart")]'
			);
			await removeButton[ 0 ].click();
			// Expect block to have been removed.
			await expect( page ).not.toMatchElement( block.class );

			// Register a checkout filter to allow `core/table` block in the Checkout block's inner blocks, add
			// core/audio into the woocommerce/cart-order-summary-block and remove core/paragraph from all Cart inner
			// blocks.
			await page.evaluate(
				"wc.blocksCheckout.registerCheckoutFilters( 'woo-test-namespace'," +
					'{ additionalCartCheckoutInnerBlockTypes: ( value, extensions, { block } ) => {' +
					"    value.push('core/table');" +
					"    if ( block === 'woocommerce/cart-order-summary-block' ) {" +
					"        value.push( 'core/audio' );" +
					'    }' +
					'    return value;' +
					'}' +
					'}' +
					');'
			);

			await insertBlock( block.name );

			// Select the shipping address block and try to insert a block. Check the Table block is available.
			await selectBlockByName( 'woocommerce/cart-order-summary-block' );
			await page.waitForTimeout( 1000 );
			const addBlockButton = await page.waitForXPath(
				'//div[@data-type="woocommerce/cart-order-summary-block"]//button[@aria-label="Add block"]'
			);
			await addBlockButton.click();
			const tableButton = await page.waitForXPath(
				'//*[@role="option" and contains(., "Table")]'
			);
			const audioButton = await page.waitForXPath(
				'//*[@role="option" and contains(., "Audio")]'
			);
			expect( tableButton ).not.toBeNull();
			expect( audioButton ).not.toBeNull();

			// // Now check the filled cart block and expect only the Table block to be available there.
			await selectBlockByName( 'woocommerce/filled-cart-block' );
			const filledCartAddBlockButton = await page.waitForXPath(
				'//div[@data-type="woocommerce/filled-cart-block"]//button[@aria-label="Add block"]'
			);
			await filledCartAddBlockButton.click();
			const filledCartTableButton = await page.waitForXPath(
				'//*[@role="option" and contains(., "Table")]'
			);
			const filledCartAudioButton = await page.$x(
				'//*[@role="option" and contains(., "Audio")]'
			);
			expect( filledCartTableButton ).not.toBeNull();
			expect( filledCartAudioButton ).toHaveLength( 0 );
		} );

		it( 'renders without crashing', async () => {
			await expect( page ).toRenderBlock( block );
			await expect( page ).toRenderBlock( filledCartBlock );
			await expect( page ).toRenderBlock( crossSellsBlock );
			await expect( page ).toRenderBlock( emptyCartBlock );
		} );

		it( 'shows empty cart when changing the view', async () => {
			await page.waitForSelector( block.class ).catch( () => {
				throw new Error(
					`Could not find an element with class ${ block.class } - the block probably did not load correctly.`
				);
			} );
			await selectBlockByName( block.slug );
			await clickBlockToolbarButton( 'Switch view', 'ariaLabel' );
			const emptyCartButton = await page.waitForXPath(
				`//button[contains(@class,'components-dropdown-menu__menu-item')]//span[contains(text(), 'Empty Cart')]`
			);
			// Clicks the element by running the JavaScript HTMLElement.click() method on the given element in the
			// browser context, which fires a click event. It doesn't scroll the page or move the mouse and works
			// even if the element is off-screen.
			await emptyCartButton.evaluate( ( b ) => b.click() );

			await expect( page ).not.toMatchElement(
				`${ emptyCartBlock.class }[hidden]`
			);
			await expect( page ).toMatchElement(
				`${ filledCartBlock.class }[hidden]`
			);

			await selectBlockByName( block.slug );
			await clickBlockToolbarButton( 'Switch view', 'ariaLabel' );
			const filledCartButton = await page.waitForXPath(
				`//button[contains(@class,'components-dropdown-menu__menu-item')]//span[contains(text(), 'Filled Cart')]`
			);
			await filledCartButton.evaluate( ( b ) => b.click() );

			await expect( page ).toMatchElement(
				`${ emptyCartBlock.class }[hidden]`
			);
			await expect( page ).not.toMatchElement(
				`${ filledCartBlock.class }[hidden]`
			);
		} );

		describe( 'attributes', () => {
			beforeEach( async () => {
				await openSettingsSidebar();
				await selectBlockByName(
					'woocommerce/cart-order-summary-shipping-block'
				);
			} );

			it( 'can toggle Shipping calculator', async () => {
				const selector = `.wc-block-components-totals-shipping__change-address__link`;
				const toggleLabel = await findLabelWithText(
					'Shipping calculator'
				);
				await expect( toggleLabel ).toToggleElement( selector );
			} );
		} );
	} );

	describe( 'in widget editor', () => {
		it( "can't be inserted in a widget area", async () => {
			await merchant.login();
			await openWidgetEditor();
			await closeModalIfExists();
			await searchForBlock( block.name );
			await page.waitForXPath(
				`//button//span[text()='${ block.name }']`
			);
			const cartButton = await page.$x(
				`//button//span[text()='${ block.name }']`
			);

			// This one match is the Cart widget.
			expect( cartButton ).toHaveLength( 1 );
		} );
	} );
} );
