/**
 * External dependencies
 */
import {
	clickButton,
	getAllBlocks,
	openDocumentSettingsSidebar,
	switchUserToAdmin,
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

if ( process.env.WP_VERSION < 5.3 || process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );

describe( `${ block.name } Block`, () => {
	beforeAll( async () => {
		await switchUserToAdmin();
		await visitBlockPage( `${ block.name } Block` );
	} );

	it( 'can only be inserted once', async () => {
		await insertBlockDontWaitForInsertClose( block.name );
		await closeInserter();
		expect( await getAllBlocks() ).toHaveLength( 1 );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
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

	it( 'shows empty cart when changing the view', async () => {
		await openDocumentSettingsSidebar();
		await page.click( block.class );
		await expect( page ).toMatchElement(
			'[hidden] .wc-block-cart__empty-cart__title'
		);
		await clickButton( 'Empty Cart' );
		await expect( page ).not.toMatchElement(
			'[hidden] .wc-block-cart__empty-cart__title'
		);
		await clickButton( 'Full Cart' );
		await expect( page ).toMatchElement(
			'[hidden] .wc-block-cart__empty-cart__title'
		);
	} );
} );
