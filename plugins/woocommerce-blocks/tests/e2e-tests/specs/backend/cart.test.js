/**
 * External dependencies
 */
import {
	clickButton,
	getAllBlocks,
	insertBlock,
	openDocumentSettingsSidebar,
	switchUserToAdmin,
} from '@wordpress/e2e-test-utils';

import {
	findToggleWithLabel,
	visitBlockPage,
} from '@woocommerce/blocks-test-utils';

const block = {
	name: 'Cart',
	slug: 'woocommerce/cart',
	class: '.wc-block-cart',
};

const closeInserter = async () => {
	await page.click( '.edit-post-header [aria-label="Add block"]' );
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
		await insertBlock( block.name );
		await closeInserter();
		expect( await getAllBlocks() ).toHaveLength( 1 );
	} );

	it( 'renders without crashing', async () => {
		await expect( page ).toRenderBlock( block );
	} );

	it( 'can toggle Shipping calculator', async () => {
		await openDocumentSettingsSidebar();
		await page.click( block.class );
		const toggle = await findToggleWithLabel( 'Shipping calculator' );
		await toggle.click();
		await expect( page ).not.toMatchElement(
			`${ block.class } .wc-block-components-totals-shipping__change-address-button`
		);
		await toggle.click();
		await expect( page ).toMatchElement(
			`${ block.class } .wc-block-components-totals-shipping__change-address-button`
		);
	} );

	it( 'can toggle shipping costs', async () => {
		await openDocumentSettingsSidebar();
		await page.click( block.class );
		const toggle = await findToggleWithLabel(
			'Hide shipping costs until an address is entered'
		);
		await toggle.click();
		await expect( page ).not.toMatchElement(
			`${ block.class } .wc-block-components-totals-shipping__fieldset`
		);
		await toggle.click();
		await expect( page ).toMatchElement(
			`${ block.class } .wc-block-components-totals-shipping__fieldset`
		);
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
