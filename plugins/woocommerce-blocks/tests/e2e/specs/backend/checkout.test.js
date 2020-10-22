/**
 * External dependencies
 */
import {
	insertBlock,
	getAllBlocks,
	openDocumentSettingsSidebar,
	switchUserToAdmin,
} from '@wordpress/e2e-test-utils';
import {
	findLabelWithText,
	visitBlockPage,
} from '@woocommerce/blocks-test-utils';

const block = {
	name: 'Checkout',
	slug: 'woocommerce/checkout',
	class: '.wc-block-checkout',
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

	describe( 'attributes', () => {
		beforeEach( async () => {
			await openDocumentSettingsSidebar();
			await page.click( block.class );
		} );

		describe( 'Company input', () => {
			const selector = `${ block.class } .wc-block-components-address-form__company input`;

			it( 'visibility can be toggled', async () => {
				const toggleLabel = await findLabelWithText( 'Company' );
				await expect( toggleLabel ).toToggleElement( selector );
			} );

			it( 'required attribute can be toggled', async () => {
				// Company is disabled by default, so first we need to enable it.
				const toggleLabel = await findLabelWithText( 'Company' );
				await toggleLabel.click();
				const checkboxLabel = await findLabelWithText(
					'Require company name?'
				);

				await expect( checkboxLabel ).toToggleRequiredAttrOf(
					selector
				);
			} );
		} );

		describe( 'Apartment input', () => {
			it( 'visibility can be toggled', async () => {
				const selector = `${ block.class } .wc-block-components-address-form__address_2 input`;
				const toggleLabel = await findLabelWithText(
					'Apartment, suite, etc.'
				);
				await expect( toggleLabel ).toToggleElement( selector );
			} );
		} );

		describe( 'Phone input', () => {
			const selector = `${ block.class } #phone`;

			it( 'visibility can be toggled', async () => {
				const toggleLabel = await findLabelWithText( 'Phone' );
				await expect( toggleLabel ).toToggleElement( selector );
			} );

			it( 'required attribute can be toggled', async () => {
				const checkboxLabel = await findLabelWithText(
					'Require phone number?'
				);
				await expect( checkboxLabel ).toToggleRequiredAttrOf(
					selector
				);
			} );
		} );

		describe( 'Order notes checkbox', () => {
			it( 'visibility can be toggled', async () => {
				const selector = `${ block.class } .wc-block-checkout__add-note`;
				const toggleLabel = await findLabelWithText(
					'Allow shoppers to optionally add order notes'
				);
				await expect( toggleLabel ).toToggleElement( selector );
			} );
		} );

		describe( 'Links to polices', () => {
			it( 'visibility can be toggled', async () => {
				const selector = `${ block.class } .wc-block-components-checkout-policies`;
				const toggleLabel = await findLabelWithText(
					'Show links to policies'
				);
				await expect( toggleLabel ).toToggleElement( selector );
			} );
		} );

		describe( 'Return to cart link', () => {
			it( 'visibility can be toggled', async () => {
				const selector = `${ block.class } .wc-block-components-checkout-return-to-cart-button`;
				const toggleLabel = await findLabelWithText(
					'Show a "Return to Cart" link'
				);
				await expect( toggleLabel ).toToggleElement( selector );
			} );
		} );

		it( 'can enable dark mode inputs', async () => {
			await openDocumentSettingsSidebar();
			await page.click( block.class );
			const toggleLabel = await findLabelWithText( 'Dark mode inputs' );
			await toggleLabel.click();

			await expect( page ).toMatchElement(
				`${ block.class }.has-dark-controls`
			);

			await toggleLabel.click();

			await expect( page ).not.toMatchElement(
				`${ block.class }.has-dark-controls`
			);
		} );
	} );
} );
