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
		test.beforeAll( async ( { browser } ) => {
			const page = await browser.newPage();
			await page.goto(
				`${ process.env.WORDPRESS_BASE_URL }/?setup_terms_and_privacy`
			);
			await expect(
				page.getByText( 'Terms & Privacy pages set up.' )
			).toBeVisible();
			await page.close();
		} );

		test.afterAll( async ( { browser } ) => {
			const page = await browser.newPage();
			await page.goto(
				`${ process.env.WORDPRESS_BASE_URL }/?teardown_terms_and_privacy`
			);
			await expect(
				page.getByText( 'Terms & Privacy pages teared down.' )
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

			test( 'Company input visibility and required can be toggled in shipping and billing', async ( {
				editor,
			} ) => {
				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-shipping-address-block"]'
				);
				const checkbox = editor.page.getByRole( 'checkbox', {
					name: 'Company',
					exact: true,
				} );

				await checkbox.check();
				await expect( checkbox ).toBeChecked();
				await expect(
					editor.canvas.locator(
						'div.wc-block-components-address-form__company'
					)
				).toBeVisible();

				await checkbox.uncheck();
				await expect( checkbox ).not.toBeChecked();
				await expect(
					editor.canvas.locator(
						'.wc-block-checkout__shipping-fields .wc-block-components-address-form__company'
					)
				).toBeHidden();

				await editor.canvas
					.getByLabel( 'Use same address for billing' )
					.uncheck();

				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-billing-address-block"]'
				);
				const billingCheckbox = editor.page.getByRole( 'checkbox', {
					name: 'Company',
					exact: true,
				} );
				await billingCheckbox.check();
				await expect( billingCheckbox ).toBeChecked();
				await expect(
					editor.canvas.locator(
						'.wc-block-checkout__billing-fields .wc-block-components-address-form__company'
					)
				).toBeVisible();

				await billingCheckbox.uncheck();
				await expect( billingCheckbox ).not.toBeChecked();
				await expect(
					editor.canvas.locator(
						'div.wc-block-components-address-form__company'
					)
				).toBeHidden();
			} );

			test( 'Apartment input visibility can be toggled in shipping and billing', async ( {
				editor,
				editorUtils,
			} ) => {
				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-shipping-address-block"]'
				);

				// Turn on apartment field and check it's visible in the fields.
				const apartmentToggleSelector = editor.page.getByLabel(
					'Apartment, suite, etc.',
					{ exact: true }
				);
				await apartmentToggleSelector.check();
				const shippingAddressBlock = await editorUtils.getBlockByName(
					'woocommerce/checkout-shipping-address-block'
				);

				const apartmentInput = shippingAddressBlock.getByLabel(
					'Apartment, suite, etc. (optional)'
				);
				// Turn off apartment field and check it's not visible in the fields.
				await expect( apartmentInput ).toBeVisible();

				await apartmentToggleSelector.uncheck();

				await expect( apartmentInput ).toBeHidden();

				await editor.canvas
					.getByLabel( 'Use same address for billing' )
					.uncheck();

				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-billing-address-block"]'
				);

				// Turn on apartment field and check it's visible in the fields.
				const billingApartmentToggleSelector = editor.page.getByLabel(
					'Apartment, suite, etc.',
					{ exact: true }
				);
				await billingApartmentToggleSelector.check();
				const billingAddressBlock = await editorUtils.getBlockByName(
					'woocommerce/checkout-billing-address-block'
				);

				const billingApartmentInput = billingAddressBlock.getByLabel(
					'Apartment, suite, etc. (optional)'
				);
				// Turn off apartment field and check it's not visible in the fields.
				await expect( billingApartmentInput ).toBeVisible();

				await billingApartmentToggleSelector.uncheck();

				await expect( billingApartmentInput ).toBeHidden();
			} );

			test( 'Phone input visibility and required can be toggled', async ( {
				editor,
				editorUtils,
			} ) => {
				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-shipping-address-block"]'
				);

				// Turn on phone field and check it's visible in the fields.
				const phoneToggleSelector = editor.page.getByLabel( 'Phone', {
					exact: true,
				} );
				await phoneToggleSelector.check();
				const shippingAddressBlock = await editorUtils.getBlockByName(
					'woocommerce/checkout-shipping-address-block'
				);

				// Turn on Require phone number? option and check it becomes required in the fields.
				const phoneRequiredSelector = editor.page.getByLabel(
					'Require phone number?',
					{ exact: true }
				);
				await phoneRequiredSelector.check();
				const phoneInput = shippingAddressBlock.getByLabel( 'Phone' );
				await expect( phoneInput ).toHaveAttribute( 'required', '' );
				await phoneRequiredSelector.uncheck();
				await expect( phoneInput ).not.toHaveAttribute(
					'required',
					''
				);

				// Turn off phone field and check it's not visible in the fields.
				await expect( phoneInput ).toBeVisible();

				await phoneToggleSelector.uncheck();

				await expect( phoneInput ).toBeHidden();

				await editor.canvas
					.getByLabel( 'Use same address for billing' )
					.uncheck();

				await editor.selectBlocks(
					blockSelectorInEditor +
						'  [data-type="woocommerce/checkout-billing-address-block"]'
				);

				// Turn on phone field and check it's visible in the fields.
				const billingPhoneToggleSelector = editor.page.getByLabel(
					'Phone',
					{
						exact: true,
					}
				);
				await billingPhoneToggleSelector.check();
				const billingAddressBlock = await editorUtils.getBlockByName(
					'woocommerce/checkout-billing-address-block'
				);

				// Turn on Require phone number? option and check it becomes required in the fields.
				const billingPhoneRequiredSelector = editor.page.getByLabel(
					'Require phone number?',
					{ exact: true }
				);
				await billingPhoneRequiredSelector.check();
				const billingPhoneInput =
					billingAddressBlock.getByLabel( 'Phone' );
				await expect( billingPhoneInput ).toHaveAttribute(
					'required',
					''
				);
				await billingPhoneRequiredSelector.uncheck();
				await expect( billingPhoneInput ).not.toHaveAttribute(
					'required',
					''
				);

				// Turn off phone field and check it's not visible in the fields.
				await expect( billingPhoneInput ).toBeVisible();

				await billingPhoneToggleSelector.uncheck();

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
