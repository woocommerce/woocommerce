/**
 * External dependencies
 */
import { BlockData } from '@woocommerce/e2e-types';
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from './checkout.page';
import { REGULAR_PRICED_PRODUCT_NAME } from './constants';

declare global {
	interface Window {
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		wcSettings: { storePages: any };
	}
}
const blockData: BlockData = {
	name: 'Checkout',
	slug: 'woocommerce/checkout',
	mainClass: '.wp-block-woocommerce-checkout',
	selectors: {
		editor: {
			block: '.wp-block-woocommerce-checkout',
			insertButton: "//button//span[text()='Checkout']",
		},
		frontend: {},
	},
};

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page }, use ) => {
		const pageObject = new CheckoutPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Merchant → Checkout', () => {
	// `as string` is safe here because we know the variable is a string, it is defined above.
	const blockSelectorInEditor = blockData.selectors.editor.block as string;

	test.beforeEach( async ( { editorUtils, admin, editor } ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//page-checkout',
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
		await editor.openDocumentSettingsSidebar();
	} );

	test( 'renders without crashing and can only be inserted once', async ( {
		page,
		editorUtils,
		editor,
	} ) => {
		const blockPresence = await editorUtils.getBlockByName(
			blockData.slug
		);
		expect( blockPresence ).toBeTruthy();

		await editorUtils.openGlobalBlockInserter();
		await page.getByPlaceholder( 'Search' ).fill( blockData.slug );
		const checkoutBlockButton = page.getByRole( 'option', {
			name: blockData.name,
			exact: true,
		} );
		expect( await editorUtils.ensureNoErrorsOnBlockPage() ).toBe( true );
		await expect(
			editor.canvas.locator( blockSelectorInEditor )
		).toBeVisible();
		await expect( checkoutBlockButton ).toHaveAttribute(
			'aria-disabled',
			'true'
		);
	} );

	test.describe( 'Can adjust T&S and Privacy Policy options', () => {
		test.beforeEach( async ( { browser } ) => {
			const page = await browser.newPage();
			await page.goto(
				`${ process.env.WORDPRESS_BASE_URL }/?setup_terms_and_privacy`
			);
			await expect(
				page.getByText( 'Terms & Privacy pages set up.' )
			).toBeVisible();
			await page.close();
		} );

		test( 'Merchant can see T&S and Privacy Policy links without checkbox', async ( {
			frontendUtils,
			checkoutPageObject,
		} ) => {
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
			await frontendUtils.goToCheckout();
			await expect(
				frontendUtils.page.getByText(
					'By proceeding with your purchase you agree to our Terms and Conditions and Privacy Policy'
				)
			).toBeVisible();

			const termsAndConditions = frontendUtils.page
				.getByRole( 'link' )
				.getByText( 'Terms and Conditions' )
				.first();
			const privacyPolicy = frontendUtils.page
				.getByRole( 'link' )
				.getByText( 'Privacy Policy' )
				.first();

			const { termsPageUrl, privacyPageUrl } =
				await frontendUtils.page.evaluate( () => {
					return {
						termsPageUrl:
							window.wcSettings.storePages.terms.permalink,
						privacyPageUrl:
							window.wcSettings.storePages.privacy.permalink,
					};
				} );
			await expect( termsAndConditions ).toHaveAttribute(
				'href',
				termsPageUrl
			);
			await expect( privacyPolicy ).toHaveAttribute(
				'href',
				privacyPageUrl
			);
			await checkoutPageObject.fillInCheckoutWithTestData();
			await checkoutPageObject.placeOrder();
			await expect(
				frontendUtils.page.getByText(
					'Thank you. Your order has been received.'
				)
			).toBeVisible();
		} );
	} );
	test( 'Merchant can see T&S and Privacy Policy links with checkbox', async ( {
		frontendUtils,
		checkoutPageObject,
		editorUtils,
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//page-checkout',
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
		await editor.openDocumentSettingsSidebar();
		await editor.selectBlocks(
			blockSelectorInEditor +
				'  [data-type="woocommerce/checkout-terms-block"]'
		);
		let requireTermsCheckbox = editor.page.getByRole( 'checkbox', {
			name: 'Require checkbox',
			exact: true,
		} );
		await requireTermsCheckbox.check();
		await editor.saveSiteEditorEntities();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCheckout();
		await checkoutPageObject.fillInCheckoutWithTestData();
		await checkoutPageObject.placeOrder( false );

		const checkboxWithError = frontendUtils.page.getByLabel(
			'You must accept our Terms and Conditions and Privacy Policy to continue with your purchase.'
		);
		await expect( checkboxWithError ).toHaveAttribute(
			'aria-invalid',
			'true'
		);

		await frontendUtils.page
			.getByLabel(
				'You must accept our Terms and Conditions and Privacy Policy to continue with your purchase.'
			)
			.check();

		await checkoutPageObject.placeOrder();
		await expect(
			frontendUtils.page.getByText(
				'Thank you. Your order has been received'
			)
		).toBeVisible();

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//page-checkout',
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();
		await editor.openDocumentSettingsSidebar();
		await editor.selectBlocks(
			blockSelectorInEditor +
				'  [data-type="woocommerce/checkout-terms-block"]'
		);
		requireTermsCheckbox = editor.page.getByRole( 'checkbox', {
			name: 'Require checkbox',
			exact: true,
		} );
		await requireTermsCheckbox.uncheck();
		await editor.saveSiteEditorEntities();
	} );

	test( 'inner blocks can be added/removed by filters', async ( {
		page,
		editor,
		editorUtils,
	} ) => {
		// Begin by removing the block.
		await editor.selectBlocks( blockSelectorInEditor );
		const options = page
			.getByRole( 'toolbar', { name: 'Block tools' } )
			.getByRole( 'button', { name: 'Options' } );
		await options.click();
		const removeButton = page.getByRole( 'menuitem', {
			name: 'Delete',
		} );
		await removeButton.click();
		// Expect block to have been removed.
		await expect(
			await editorUtils.getBlockByName( blockData.slug )
		).toHaveCount( 0 );

		// Register a checkout filter to allow `core/table` block in the Checkout block's inner blocks, add
		// core/audio into the woocommerce/checkout-fields-block.
		await page.evaluate(
			`wc.blocksCheckout.registerCheckoutFilters( 'woo-test-namespace', {
					additionalCartCheckoutInnerBlockTypes: ( value, extensions, { block } ) => {
						value.push( 'core/table' );
						if ( block === 'woocommerce/checkout-totals-block' ) {
							value.push( 'core/audio' );
						}
						return value;
					},
				} );`
		);

		await editor.insertBlock( { name: 'woocommerce/checkout' } );
		await expect(
			await editorUtils.getBlockByName( blockData.slug )
		).not.toHaveCount( 0 );

		// Select the checkout-fields-block block and try to insert a block. Check the Table block is available.
		await editor.selectBlocks(
			blockData.selectors.editor.block +
				' .wp-block-woocommerce-checkout-fields-block'
		);

		const addBlockButton = editor.canvas
			.locator( '.wp-block-woocommerce-checkout-totals-block' )
			.getByRole( 'button', { name: 'Add block' } );
		await addBlockButton.dispatchEvent( 'click' );

		const tableButton = editor.page.getByRole( 'option', {
			name: 'Table',
		} );
		await expect( tableButton ).toBeVisible();

		const audioButton = editor.page.getByRole( 'option', {
			name: 'Audio',
		} );
		await test.expect( audioButton ).toBeVisible();

		// Now check the filled Checkout order summary block and expect only the Table block to be available there.
		await editor.selectBlocks(
			blockSelectorInEditor +
				' [data-type="woocommerce/checkout-order-summary-block"]'
		);
		const orderSummaryAddBlockButton = editor.canvas
			.getByRole( 'document', { name: 'Block: Order Summary' } )
			.getByRole( 'button', { name: 'Add block' } )
			.first();
		await orderSummaryAddBlockButton.dispatchEvent( 'click' );

		const orderSummaryTableButton = editor.page.getByRole( 'option', {
			name: 'Table',
		} );
		await expect( orderSummaryTableButton ).toBeVisible();

		const orderSummaryAudioButton = editor.page.getByRole( 'option', {
			name: 'Audio',
		} );
		await expect( orderSummaryAudioButton ).toBeHidden();
	} );

	test.describe( 'Attributes', () => {
		test.beforeEach( async ( { editor } ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.selectBlocks( blockSelectorInEditor );
		} );

		test( 'can enable dark mode inputs', async ( {
			editorUtils,
			page,
		} ) => {
			const toggleLabel = page.getByLabel( 'Dark mode inputs' );
			await toggleLabel.check();

			const shippingAddressBlock = await editorUtils.getBlockByName(
				'woocommerce/checkout'
			);

			const darkControls = shippingAddressBlock.locator(
				'.wc-block-checkout.has-dark-controls'
			);
			await expect( darkControls ).toBeVisible();
			await toggleLabel.uncheck();
			await expect( darkControls ).toBeHidden();
		} );

		test.describe( 'Shipping and billing addresses', () => {
			test.beforeEach( async ( { editor } ) => {
				await editor.openDocumentSettingsSidebar();
				await editor.selectBlocks( blockSelectorInEditor );
			} );

			test( 'Company input visibility and optional and required can be toggled', async ( {
				editor,
				editorUtils,
			} ) => {
				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-shipping-address-block"]'
				);

				const shippingAddressBlock = await editorUtils.getBlockByName(
					'woocommerce/checkout-shipping-address-block'
				);

				const shippingCompanyInput =
					shippingAddressBlock.getByLabel( 'Company' );

				const shippingCompanyToggle = editor.page.getByRole(
					'checkbox',
					{
						name: 'Company',
						exact: true,
					}
				);

				const shippingCompanyOptionalToggle = editor.page.locator(
					'.wc-block-components-require-company-field >> text="Optional"'
				);

				const shippingCompanyRequiredToggle = editor.page.locator(
					'.wc-block-components-require-company-field >> text="Required"'
				);

				// Verify that the company field is hidden by default.
				await expect( shippingCompanyInput ).toBeHidden();

				// Enable the company field.
				await shippingCompanyToggle.check();

				// Verify that the company field is visible and the field is optional.
				await expect( shippingCompanyInput ).toBeVisible();
				await expect( shippingCompanyOptionalToggle ).toBeChecked();
				await expect( shippingCompanyInput ).not.toHaveAttribute(
					'required',
					''
				);

				// Make the company field required.
				await shippingCompanyRequiredToggle.check();

				// Verify that the company field is required.
				await expect( shippingCompanyRequiredToggle ).toBeChecked();

				// Disable the company field.
				await shippingCompanyToggle.uncheck();

				// Verify that the company field is hidden.
				await expect( shippingCompanyInput ).toBeHidden();

				// Display the billing address form.
				await editor.canvas
					.getByLabel( 'Use same address for billing' )
					.uncheck();

				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-billing-address-block"]'
				);

				const billingAddressBlock = await editorUtils.getBlockByName(
					'woocommerce/checkout-billing-address-block'
				);

				const billingCompanyInput =
					billingAddressBlock.getByLabel( 'Company' );

				const billingCompanyToggle = editor.page.getByRole(
					'checkbox',
					{
						name: 'Company',
						exact: true,
					}
				);

				const billingCompanyOptionalToggle = editor.page.locator(
					'.wc-block-components-require-company-field >> text="Optional"'
				);

				const billingCompanyRequiredToggle = editor.page.locator(
					'.wc-block-components-require-company-field >> text="Required"'
				);

				// Enable the company field.
				await billingCompanyToggle.check();

				// Verify that the company field is visible.
				await expect( billingCompanyInput ).toBeVisible();

				// Verify that the company field is currently required.
				await expect( billingCompanyRequiredToggle ).toBeChecked();
				await expect( billingCompanyInput ).toHaveAttribute(
					'required',
					''
				);

				// Make the company field optional.
				await billingCompanyOptionalToggle.check();

				// Verify that the company field is optional.
				await expect( billingCompanyOptionalToggle ).toBeChecked();
				await expect( billingCompanyInput ).not.toHaveAttribute(
					'required',
					''
				);

				// Disable the company field.
				await billingCompanyToggle.uncheck();

				// Verify that the company field is hidden.
				await expect( billingCompanyInput ).toBeHidden();
			} );

			test( 'Apartment input visibility and optional and required can be toggled', async ( {
				editor,
				editorUtils,
			} ) => {
				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-shipping-address-block"]'
				);

				const shippingAddressBlock = await editorUtils.getBlockByName(
					'woocommerce/checkout-shipping-address-block'
				);

				const shippingApartmentInput =
					shippingAddressBlock.getByLabel( 'Apartment' );

				const shippingApartmentLink = shippingAddressBlock.getByRole(
					'button',
					{
						name: '+ Add apartment, suite, etc.',
					}
				);

				const shippingApartmentToggle = editor.page.getByRole(
					'checkbox',
					{
						name: 'Address line 2',
						exact: true,
					}
				);

				const shippingApartmentOptionalToggle = editor.page.locator(
					'.wc-block-components-require-apartment-field >> text="Optional"'
				);

				const shippingApartmentRequiredToggle = editor.page.locator(
					'.wc-block-components-require-apartment-field >> text="Required"'
				);

				// Verify that the apartment link is visible by default.
				await expect( shippingApartmentLink ).toBeVisible();

				// Verify that the apartment field is hidden by default and the field is optional.
				await expect( shippingApartmentInput ).toBeHidden();
				await expect( shippingApartmentOptionalToggle ).toBeChecked();

				// Make the apartment number required.
				await shippingApartmentRequiredToggle.check();

				// Verify that the apartment field is required.
				await expect( shippingApartmentRequiredToggle ).toBeChecked();
				await expect( shippingApartmentInput ).toHaveAttribute(
					'required',
					''
				);

				// Disable the apartment field.
				await shippingApartmentToggle.uncheck();

				// Verify that the apartment link and the apartment field are hidden.
				await expect( shippingApartmentLink ).toBeHidden();
				await expect( shippingApartmentInput ).toBeHidden();

				// Display the billing address form.
				await editor.canvas
					.getByLabel( 'Use same address for billing' )
					.uncheck();

				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-billing-address-block"]'
				);

				const billingAddressBlock = await editorUtils.getBlockByName(
					'woocommerce/checkout-billing-address-block'
				);

				const billingApartmentInput =
					billingAddressBlock.getByLabel( 'Apartment' );

				const billingApartmentLink = billingAddressBlock.getByRole(
					'button',
					{
						name: '+ Add apartment, suite, etc.',
					}
				);

				const billingApartmentToggle = editor.page.getByRole(
					'checkbox',
					{
						name: 'Address line 2',
						exact: true,
					}
				);

				const billingApartmentOptionalToggle = editor.page.locator(
					'.wc-block-components-require-apartment-field >> text="Optional"'
				);

				const billingApartmentRequiredToggle = editor.page.locator(
					'.wc-block-components-require-apartment-field >> text="Required"'
				);

				// Enable the apartment field.
				await billingApartmentToggle.check();

				// Verify that the apartment link is hidden.
				await expect( billingApartmentLink ).toBeHidden();

				// Verify that the apartment field is visible.
				await expect( billingApartmentInput ).toBeVisible();

				// Verify that the apartment field is currently required.
				await expect( billingApartmentRequiredToggle ).toBeChecked();
				await expect( billingApartmentInput ).toHaveAttribute(
					'required',
					''
				);

				// Make the apartment field optional.
				billingApartmentOptionalToggle.check();

				// Verify that the apartment link is visible.
				await expect( billingApartmentLink ).toBeVisible();

				// Verify that the apartment field is hidden and optional.
				await expect( billingApartmentInput ).toBeHidden();
				await expect( billingApartmentOptionalToggle ).toBeChecked();

				// Disable the apartment field.
				await billingApartmentToggle.uncheck();

				// Verify that the apartment link and the apartment field are hidden.
				await expect( billingApartmentLink ).toBeHidden();
				await expect( billingApartmentInput ).toBeHidden();
			} );

			test( 'Phone input visibility and optional and required can be toggled', async ( {
				editor,
				editorUtils,
			} ) => {
				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-shipping-address-block"]'
				);

				const shippingAddressBlock = await editorUtils.getBlockByName(
					'woocommerce/checkout-shipping-address-block'
				);

				const shippingPhoneInput =
					shippingAddressBlock.getByLabel( 'Phone' );

				const shippingPhoneToggle = editor.page.getByRole( 'checkbox', {
					name: 'Phone',
					exact: true,
				} );

				const shippingPhoneOptionalToggle = editor.page.locator(
					'.wc-block-components-require-phone-field >> text="Optional"'
				);

				const shippingPhoneRequiredToggle = editor.page.locator(
					'.wc-block-components-require-phone-field >> text="Required"'
				);

				// Verify that the phone field is visible by default and the field is optional.
				await expect( shippingPhoneInput ).toBeVisible();
				await expect( shippingPhoneOptionalToggle ).toBeChecked();
				await expect( shippingPhoneInput ).not.toHaveAttribute(
					'required',
					''
				);

				// Make the phone number required.
				await shippingPhoneRequiredToggle.check();

				// Verify that the phone field is required.
				await expect( shippingPhoneRequiredToggle ).toBeChecked();
				await expect( shippingPhoneInput ).toHaveAttribute(
					'required',
					''
				);

				// Disable the phone field.
				await shippingPhoneToggle.uncheck();

				// Verify that the phone field is hidden.
				await expect( shippingPhoneInput ).toBeHidden();

				// Display the billing address form.
				await editor.canvas
					.getByLabel( 'Use same address for billing' )
					.uncheck();

				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-billing-address-block"]'
				);

				const billingAddressBlock = await editorUtils.getBlockByName(
					'woocommerce/checkout-billing-address-block'
				);

				const billingPhoneInput =
					billingAddressBlock.getByLabel( 'Phone' );

				const billingPhoneToggle = editor.page.getByRole( 'checkbox', {
					name: 'Phone',
					exact: true,
				} );

				const billingPhoneOptionalToggle = editor.page.locator(
					'.wc-block-components-require-phone-field >> text="Optional"'
				);

				const billingPhoneRequiredToggle = editor.page.locator(
					'.wc-block-components-require-phone-field >> text="Required"'
				);

				// Enable the phone field.
				await billingPhoneToggle.check();

				// Verify that the phone field is visible.
				await expect( billingPhoneInput ).toBeVisible();

				// Verify that the phone field is currently required.
				await expect( billingPhoneRequiredToggle ).toBeChecked();
				await expect( billingPhoneInput ).toHaveAttribute(
					'required',
					''
				);

				// Make the phone field optional.
				billingPhoneOptionalToggle.check();

				// Verify that the phone field is optional.
				await expect( billingPhoneOptionalToggle ).toBeChecked();
				await expect( billingPhoneInput ).not.toHaveAttribute(
					'required',
					''
				);

				// Disable the phone field.
				await billingPhoneToggle.uncheck();

				// Verify that the phone field is hidden.
				await expect( billingPhoneInput ).toBeHidden();
			} );
		} );
	} );

	test.describe( 'Checkout actions', () => {
		test.beforeEach( async ( { editor } ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.selectBlocks( blockSelectorInEditor );
		} );

		test( 'Return to cart link is visible and can be toggled', async ( {
			editor,
			editorUtils,
		} ) => {
			await editor.selectBlocks(
				`${ blockSelectorInEditor } .wp-block-woocommerce-checkout-actions-block`
			);

			// Turn on return to cart link and check it's visible in the block.
			const returnToCartLinkToggle = editor.page.getByLabel(
				'Show a "Return to Cart" link',
				{ exact: true }
			);
			await returnToCartLinkToggle.check();
			const shippingAddressBlock = await editorUtils.getBlockByName(
				'woocommerce/checkout-actions-block'
			);

			// Turn on return to cart link and check it shows in the block.
			const returnToCartLink = shippingAddressBlock.getByText(
				'Return to Cart',
				{ exact: true }
			);

			// Turn off return to cart link and check it's not visible in the block.
			await expect( returnToCartLink ).toBeVisible();

			await returnToCartLinkToggle.uncheck();

			await expect( returnToCartLink ).toBeHidden();
		} );
	} );
} );
