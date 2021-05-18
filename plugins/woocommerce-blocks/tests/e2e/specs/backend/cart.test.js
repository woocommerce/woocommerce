/**
 * External dependencies
 */
import {
	clickButton,
	openDocumentSettingsSidebar,
	switchUserToAdmin,
	getAllBlocks,
} from '@wordpress/e2e-test-utils';
import {
	findLabelWithText,
	visitBlockPage,
} from '@woocommerce/blocks-test-utils';

import {
	insertBlockDontWaitForInsertClose,
	closeInserter,
} from '../../utils.js';

const block = {
	name: 'Cart',
	slug: 'woocommerce/cart',
	class: '.wc-block-cart',
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 ) {
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );
}

describe( `${ block.name } Block`, () => {
	describe( `before compatibility notice is dismissed`, () => {
		beforeAll( async () => {
			// make sure CartCheckoutCompatibilityNotice will appear
			await page.evaluate( () => {
				localStorage.removeItem(
					'wc-blocks_dismissed_compatibility_notices'
				);
			} );
			await visitBlockPage( `${ block.name } Block` );
		} );

		it( 'shows compatibility notice', async () => {
			const compatibilityNoticeTitle = await page.$x(
				`//h1[contains(text(), 'Compatibility notice')]`
			);
			expect( compatibilityNoticeTitle.length ).toBe( 1 );
		} );
	} );

	describe( 'after compatibility notice is dismissed', () => {
		beforeAll( async () => {
			await page.evaluate( () => {
				localStorage.setItem(
					'wc-blocks_dismissed_compatibility_notices',
					'["cart"]'
				);
			} );
			await switchUserToAdmin();
			await visitBlockPage( `${ block.name } Block` );
		} );

		afterAll( async () => {
			await page.evaluate( () => {
				localStorage.removeItem(
					'wc-blocks_dismissed_compatibility_notices'
				);
			} );
		} );

		it( 'can only be inserted once', async () => {
			await insertBlockDontWaitForInsertClose( block.name );
			expect( await getAllBlocks() ).toHaveLength( 1 );
		} );

		it( 'renders without crashing', async () => {
			await expect( page ).toRenderBlock( block );
		} );

		it( 'shows empty cart when changing the view', async () => {
			await page.waitForSelector( block.class ).catch( () => {
				throw new Error(
					`Could not find an element with class ${ block.class } - the block probably did not load correctly.`
				);
			} );
			await page.click( block.class );
			await expect( page ).toMatchElement(
				'[hidden] .wc-block-cart__empty-cart__title'
			);
			await clickButton( 'Empty Cart' );
			await expect( page ).not.toMatchElement(
				'[hidden] .wc-block-cart__empty-cart__title'
			);
			// Simulate user scrolling up so the block toolbar doesn't cover
			// the `Full Cart` button.
			await page.evaluate( () => {
				document
					.querySelector( '.wc-block-view-switch-control' )
					.scrollIntoView( { block: 'center', inline: 'center' } );
			} );
			await clickButton( 'Full Cart' );
			await expect( page ).toMatchElement(
				'[hidden] .wc-block-cart__empty-cart__title'
			);
		} );

		describe( 'attributes', () => {
			beforeEach( async () => {
				await openDocumentSettingsSidebar();
				await page.click( block.class );
			} );

			it( 'can toggle Shipping calculator', async () => {
				const selector = `${ block.class } .wc-block-components-totals-shipping__change-address-button`;
				const toggleLabel = await findLabelWithText(
					'Shipping calculator'
				);
				await expect( toggleLabel ).toToggleElement( selector );
			} );
		} );
	} );
} );
