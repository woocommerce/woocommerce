/**
 * External dependencies
 */
import {
	switchUserToAdmin,
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
	searchForBlock,
	openSettingsSidebar,
	openWidgetEditor,
	closeModalIfExists,
} from '../../utils.js';
import { merchant as merchantUtils } from '../../../utils/merchant';

const block = {
	name: 'Checkout',
	slug: 'woocommerce/checkout',
	class: '.wp-block-woocommerce-checkout',
	selectors: {
		insertButton: "//button//span[text()='Checkout']",
	},
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
				'//button[contains(., "Remove Checkout")]'
			);
			await removeButton[ 0 ].click();
			// Expect block to have been removed.
			await expect( page ).not.toMatchElement( block.class );

			// Register a checkout filter to allow `core/table` block in the Checkout block's inner blocks.
			await page.evaluate(
				"wc.blocksCheckout.registerCheckoutFilters( 'woo-test-namespace'," +
					'{ additionalCartCheckoutInnerBlockTypes: ( value, extensions, { block } ) => {' +
					"    value.push('core/table');" +
					"    if ( block === 'woocommerce/checkout-shipping-address-block' ) {" +
					"        value.push( 'core/audio' );" +
					'    }' +
					'    return value;' +
					'}' +
					'}' +
					');'
			);

			await insertBlock( block.name );

			// Select the shipping address block and try to insert a block. Check the Table block is available.
			await selectBlockByName(
				'woocommerce/checkout-shipping-address-block'
			);
			const addBlockButton = await page.waitForXPath(
				'//div[@data-type="woocommerce/checkout-shipping-address-block"]//button[@aria-label="Add block"]'
			);
			expect( addBlockButton ).not.toBeNull();
			await addBlockButton.click();
			const tableButton = await page.waitForXPath(
				'//*[@role="option" and contains(., "Table")]'
			);
			const audioButton = await page.waitForXPath(
				'//*[@role="option" and contains(., "Audio")]'
			);
			expect( tableButton ).not.toBeNull();
			expect( audioButton ).not.toBeNull();

			// Now check the contact information block and expect only the Table block to be available there.
			await selectBlockByName(
				'woocommerce/checkout-contact-information-block'
			);
			const contactInformationAddBlockButton = await page.waitForXPath(
				'//div[@data-type="woocommerce/checkout-contact-information-block"]//button[@aria-label="Add block"]'
			);
			await contactInformationAddBlockButton.click();
			const contactInformationTableButton = await page.waitForXPath(
				'//*[@role="option" and contains(., "Table")]'
			);
			const contactInformationAudioButton = await page.$x(
				'//*[@role="option" and contains(., "Audio")]'
			);
			expect( contactInformationTableButton ).not.toBeNull();
			expect( contactInformationAudioButton ).toHaveLength( 0 );
		} );

		it( 'renders without crashing', async () => {
			await expect( page ).toRenderBlock( block );
		} );

		describe( 'attributes', () => {
			beforeEach( async () => {
				await openSettingsSidebar();
				await selectBlockByName( block.slug );
			} );

			it( 'can toggle "hide shipping costs until an address is entered"', async () => {
				await selectBlockByName(
					'woocommerce/checkout-shipping-methods-block'
				);
				const toggleLabel = await findLabelWithText(
					'Hide shipping costs until an address is entered'
				);
				await toggleLabel.click();
				const shippingOptionsRequireAddressText = await page.$x(
					'//p[contains(text(), "Shipping options will be displayed here after entering your full shipping address.")]'
				);
				await expect( shippingOptionsRequireAddressText ).toHaveLength(
					1
				);

				await toggleLabel.click();
				await expect( page ).toMatchElement(
					'.wc-block-components-shipping-rates-control'
				);
			} );

			it( 'toggles the same setting in shipping method and shipping methods blocks', async () => {
				await merchantUtils.goToLocalPickupSettingsPage();
				await merchantUtils.enableLocalPickup();
				await merchantUtils.saveLocalPickupSettingsPageWithRefresh();

				await visitBlockPage( `${ block.name } Block` );
				await expect( page ).toClick(
					'.wc-block-checkout__shipping-method button',
					{ text: 'Shipping' }
				);
				await openSettingsSidebar();
				const toggleLabel = await findLabelWithText(
					'Hide shipping costs until an address is entered'
				);
				await toggleLabel.click();
				const [ label ] = await page.$x(
					'//label[contains(., "Hide shipping costs until an address is entered")]'
				);
				const shippingMethodForValue = await page.evaluate(
					( passedLabel ) => passedLabel.getAttribute( 'for' ),
					label
				);
				const shippingMethodSettingIsChecked = await page.evaluate(
					( passedShippingMethodForValue ) =>
						document.getElementById( passedShippingMethodForValue )
							.checked,
					shippingMethodForValue
				);
				await expect( shippingMethodSettingIsChecked ).toBe( true );
				await selectBlockByName(
					'woocommerce/checkout-shipping-methods-block'
				);
				const [ shippingMethodsLabel ] = await page.$x(
					'//label[contains(., "Hide shipping costs until an address is entered")]'
				);
				const shippingMethodsLabelForValue = await page.evaluate(
					( passedShippingMethodsLabel ) =>
						passedShippingMethodsLabel.getAttribute( 'for' ),
					shippingMethodsLabel
				);
				const shippingMethodLabelIsChecked = await page.evaluate(
					( passedShippingMethodsLabelForValue ) =>
						document.getElementById(
							passedShippingMethodsLabelForValue
						).checked,
					shippingMethodsLabelForValue
				);
				expect( shippingMethodSettingIsChecked ).toBe(
					shippingMethodLabelIsChecked
				);
			} );

			it( 'can enable dark mode inputs', async () => {
				const toggleLabel = await findLabelWithText(
					'Dark mode inputs'
				);
				await toggleLabel.click();

				await expect( page ).toMatchElement(
					`.wc-block-checkout.has-dark-controls`
				);

				await toggleLabel.click();

				await expect( page ).not.toMatchElement(
					`.wc-block-checkout.has-dark-controls`
				);
			} );
		} );

		describe( 'shipping address block attributes', () => {
			beforeEach( async () => {
				await openSettingsSidebar();
				await selectBlockByName(
					'woocommerce/checkout-shipping-address-block'
				);
			} );

			describe( 'Company input', () => {
				const selector = `${ block.class } #shipping-company`;

				it( 'visibility can be toggled', async () => {
					await expect( 'Company' ).toToggleElement( selector );
				} );

				it( 'required attribute can be toggled', async () => {
					// Company is disabled by default, so first we need to enable it.
					const toggleLabel = await findLabelWithText( 'Company' );
					await toggleLabel.click();
					await expect(
						'Require company name?'
					).toToggleRequiredAttrOf( selector );
				} );
			} );

			describe( 'Apartment input', () => {
				it( 'visibility can be toggled', async () => {
					const selector = `${ block.class } #shipping-address_2`;
					await expect( 'Apartment, suite, etc.' ).toToggleElement(
						selector
					);
				} );
			} );

			describe( 'Phone input', () => {
				const selector = `${ block.class } #shipping-phone`;

				it( 'visibility can be toggled', async () => {
					await expect( 'Phone' ).toToggleElement( selector );
				} );

				it( 'required attribute can be toggled', async () => {
					await expect(
						'Require phone number?'
					).toToggleRequiredAttrOf( selector );
				} );
			} );
		} );

		describe( 'action block attributes', () => {
			beforeEach( async () => {
				await openSettingsSidebar();
				await selectBlockByName( 'woocommerce/checkout-actions-block' );
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
		} );
	} );

	describe( 'in widget editor', () => {
		it( "can't be inserted in a widget area", async () => {
			await merchant.login();
			await openWidgetEditor();
			await closeModalIfExists();
			await searchForBlock( block.name );
			const checkoutButton = await page.$x(
				`//button//span[text()='${ block.name }']`
			);

			expect( checkoutButton ).toHaveLength( 0 );
		} );
	} );
} );
