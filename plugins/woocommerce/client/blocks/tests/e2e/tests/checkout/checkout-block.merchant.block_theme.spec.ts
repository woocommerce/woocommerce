/**
 * External dependencies
 */
import {
	test as base,
	expect,
	BlockData,
	BLOCK_THEME_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CheckoutPage } from './checkout.page';
import { REGULAR_PRICED_PRODUCT_NAME } from './constants';

declare global {
	interface Window {
		wcSettings: {
			storePages: {
				terms: { permalink: string };
				privacy: { permalink: string };
			};
		};
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

	test.beforeEach( async ( { admin, editor } ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//page-checkout`,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		// Dismiss the "Get started" modal if it appears.
		const getStartedButton = admin.page.getByRole( 'button', {
			name: 'Get started',
		} );
		if ( await getStartedButton.isVisible() ) {
			await getStartedButton.click();
		}

		await editor.openDocumentSettingsSidebar();
	} );

	test( 'renders without crashing and can only be inserted once', async ( {
		page,
		editor,
	} ) => {
		const blockPresence = await editor.getBlockByName( blockData.slug );
		expect( blockPresence ).toBeTruthy();

		await editor.openGlobalBlockInserter();
		await page.getByPlaceholder( 'Search' ).fill( blockData.slug );
		const checkoutBlockButton = page.getByRole( 'option', {
			name: blockData.name,
			exact: true,
		} );

		const errorMessages = [
			/This block contains unexpected or invalid content/gi,
			/Your site doesn’t include support for/gi,
			/There was an error whilst rendering/gi,
			/This block has encountered an error and cannot be previewed/gi,
		];

		for ( const errorMessage of errorMessages ) {
			await expect(
				editor.canvas.getByText( errorMessage )
			).toBeHidden();
		}

		await expect(
			editor.canvas.locator( blockSelectorInEditor )
		).toBeVisible();
		await expect( checkoutBlockButton ).toHaveAttribute(
			'aria-disabled',
			'true'
		);
	} );

	test.describe( 'Can adjust T&S and Privacy Policy options', () => {
		test( 'Merchant can see T&S and Privacy Policy links without checkbox', async ( {
			page,
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

			const { termsPageUrl, privacyPageUrl } = await page.evaluate(
				() => {
					const { terms, privacy } = window.wcSettings.storePages;

					return {
						termsPageUrl: terms.permalink,
						privacyPageUrl: privacy.permalink,
					};
				}
			);
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
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//page-checkout`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
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
			postId: `${ BLOCK_THEME_SLUG }//page-checkout`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
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

	test.describe( 'Attributes', () => {
		test.beforeEach( async ( { editor } ) => {
			await editor.openDocumentSettingsSidebar();
			await editor.selectBlocks( blockSelectorInEditor );
		} );

		test( 'can enable dark mode inputs', async ( { editor, page } ) => {
			const toggleLabel = page.getByLabel( 'Dark mode inputs' );
			await toggleLabel.check();

			const shippingAddressBlock = await editor.getBlockByName(
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
			} ) => {
				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-shipping-address-block"]'
				);

				const shippingAddressBlock = await editor.getBlockByName(
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
				await expect( async () => {
					await shippingCompanyToggle.check();
				} ).toPass();

				// Verify that the company field is visible and the field is optional.
				await expect( shippingCompanyInput ).toBeVisible();
				await expect( shippingCompanyOptionalToggle ).toBeChecked();
				await expect( shippingCompanyInput ).not.toHaveAttribute(
					'required'
				);

				// Make the company field required.
				await expect( async () => {
					await shippingCompanyRequiredToggle.check();
				} ).toPass();

				// Verify that the company field is required.
				await expect( shippingCompanyRequiredToggle ).toBeChecked();

				// Disable the company field.
				await expect( async () => {
					await shippingCompanyToggle.uncheck();
				} ).toPass();

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

				const billingAddressBlock = await editor.getBlockByName(
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

				// Verify the company field on the billing address has the correct state from the shipping address.
				await expect( billingCompanyToggle ).not.toBeChecked();
				await expect( billingCompanyInput ).toBeHidden();
			} );

			test( 'Apartment input visibility and optional and required can be toggled', async ( {
				editor,
			} ) => {
				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-shipping-address-block"]'
				);

				const shippingAddressBlock = await editor.getBlockByName(
					'woocommerce/checkout-shipping-address-block'
				);

				const shippingApartmentInput = shippingAddressBlock.getByLabel(
					'Apartment, suite, etc.'
				);

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
					'.wc-block-components-require-address_2-field >> text="Optional"'
				);

				const shippingApartmentRequiredToggle = editor.page.locator(
					'.wc-block-components-require-address_2-field >> text="Required"'
				);

				// Verify that the apartment link is visible by default.
				await expect( shippingApartmentLink ).toBeVisible();

				// Verify that the apartment field is hidden by default and the field is optional.
				await expect( shippingApartmentInput ).not.toBeInViewport();
				await expect( shippingApartmentOptionalToggle ).toBeChecked();

				// Make the apartment number required.
				await expect( async () => {
					await shippingApartmentRequiredToggle.check();
				} ).toPass();

				// Verify that the apartment field is required.
				await expect( shippingApartmentRequiredToggle ).toBeChecked();
				await expect( shippingApartmentInput ).toHaveAttribute(
					'required'
				);

				// Disable the apartment field.
				await expect( async () => {
					await shippingApartmentToggle.uncheck();
				} ).toPass();

				// Verify that the apartment link and the apartment field are hidden.
				await expect( shippingApartmentLink ).toBeHidden();
				await expect( shippingApartmentInput ).not.toBeInViewport();

				// Display the billing address form.
				await editor.canvas
					.getByLabel( 'Use same address for billing' )
					.uncheck();

				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-billing-address-block"]'
				);

				const billingAddressBlock = await editor.getBlockByName(
					'woocommerce/checkout-billing-address-block'
				);

				const billingApartmentInput = billingAddressBlock.getByLabel(
					'Apartment, suite, etc.'
				);

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

				// Verify the apartment field on the billing address has the correct state from the shipping address.
				await expect( billingApartmentToggle ).not.toBeChecked();
				await expect( billingApartmentLink ).toBeHidden();
				await expect( billingApartmentInput ).not.toBeInViewport();
			} );

			test( 'Phone input visibility and optional and required can be toggled', async ( {
				editor,
			} ) => {
				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-shipping-address-block"]'
				);

				const shippingAddressBlock = await editor.getBlockByName(
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
					'required'
				);

				// Make the phone number required.
				await expect( async () => {
					await shippingPhoneRequiredToggle.check();
				} ).toPass();

				// Verify that the phone field is required.
				await expect( shippingPhoneRequiredToggle ).toBeChecked();
				await expect( shippingPhoneInput ).toHaveAttribute(
					'required'
				);

				// Disable the phone field.
				await expect( async () => {
					await shippingPhoneToggle.uncheck();
				} ).toPass();

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

				const billingAddressBlock = await editor.getBlockByName(
					'woocommerce/checkout-billing-address-block'
				);

				const billingPhoneInput =
					billingAddressBlock.getByLabel( 'Phone' );

				const billingPhoneToggle = editor.page.getByRole( 'checkbox', {
					name: 'Phone',
					exact: true,
				} );

				// Verify the phone field on the billing address has the correct state from the shipping address.
				await expect( billingPhoneToggle ).not.toBeChecked();
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
			const shippingAddressBlock = await editor.getBlockByName(
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
