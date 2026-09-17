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

	test( 'Merchant must accept T&S before checkout', async ( {
		frontendUtils,
		checkoutPageObject,
		editor,
	} ) => {
		await editor.selectBlocks(
			blockSelectorInEditor +
				'  [data-type="woocommerce/checkout-terms-block"]'
		);
		const requireTermsCheckbox = editor.page.getByRole( 'checkbox', {
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
		await checkboxWithError.check();

		await checkoutPageObject.placeOrder();
		await expect(
			frontendUtils.page.getByText(
				'Thank you. Your order has been received'
			)
		).toBeVisible();
	} );

	test( 'Merchant can persist a required Company field', async ( {
		frontendUtils,
		admin,
		editor,
		requestUtils,
	} ) => {
		await requestUtils.rest( {
			method: 'POST',
			path: 'e2e-options/update',
			data: {
				option_name: 'woocommerce_checkout_company_field',
				option_value: 'hidden',
			},
		} );

		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//page-checkout`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.openDocumentSettingsSidebar();
		await editor.selectBlocks(
			blockSelectorInEditor +
				'  [data-type="woocommerce/checkout-shipping-address-block"]'
		);

		const shippingAddressBlock = await editor.getBlockByName(
			'woocommerce/checkout-shipping-address-block'
		);
		const shippingCompanyInput =
			shippingAddressBlock.getByLabel( 'Company' );
		const shippingCompanyToggle = editor.page.getByRole( 'checkbox', {
			name: 'Company',
			exact: true,
		} );
		const companyRequirement = editor.page.locator(
			'.wc-block-components-require-company-field'
		);

		await expect( shippingCompanyToggle ).not.toBeChecked();
		await expect( shippingCompanyInput ).toBeHidden();
		await expect( async () => {
			await shippingCompanyToggle.check();
		} ).toPass();
		await expect( shippingCompanyInput ).toBeVisible();
		await expect(
			companyRequirement.getByRole( 'radio', { name: 'Optional' } )
		).toBeChecked();
		await expect( shippingCompanyInput ).not.toHaveAttribute( 'required' );

		const requiredCompany = companyRequirement.getByRole( 'radio', {
			name: 'Required',
		} );
		await expect( async () => {
			await requiredCompany.check();
		} ).toPass();
		await expect( requiredCompany ).toBeChecked();
		await expect( shippingCompanyInput ).toHaveAttribute( 'required' );
		await editor.saveSiteEditorEntities();

		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//page-checkout`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.openDocumentSettingsSidebar();
		await editor.selectBlocks(
			blockSelectorInEditor +
				'  [data-type="woocommerce/checkout-shipping-address-block"]'
		);
		await expect(
			editor.page
				.locator( '.wc-block-components-require-company-field' )
				.getByRole( 'radio', { name: 'Required' } )
		).toBeChecked();

		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		const shippingCompany = frontendUtils.page
			.getByRole( 'group', { name: 'Shipping address' } )
			.getByLabel( 'Company' );
		await expect( shippingCompany ).toBeVisible();
		await expect( shippingCompany ).toHaveAttribute( 'required' );

		await frontendUtils.page
			.getByLabel( 'Use same address for billing' )
			.uncheck();
		const billingCompany = frontendUtils.page
			.getByRole( 'group', { name: 'Billing address' } )
			.getByLabel( 'Company' );
		await expect( billingCompany ).toBeVisible();
		await expect( billingCompany ).toHaveAttribute( 'required' );
	} );
} );
