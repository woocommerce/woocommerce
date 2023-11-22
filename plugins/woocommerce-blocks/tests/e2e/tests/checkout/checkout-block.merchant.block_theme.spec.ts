/**
 * External dependencies
 */
import { BlockData } from '@woocommerce/e2e-types';
import { test, expect } from '@woocommerce/e2e-playwright-utils';

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

test.describe( 'Merchant → Checkout', () => {
	// `as string` is safe here because we know the variable is a string, it is defined above.
	const blockSelectorInEditor = blockData.selectors.editor.block as string;

	test.describe( 'in page editor', () => {
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
			await expect( checkoutBlockButton ).toHaveAttribute(
				'aria-disabled',
				'true'
			);
		} );

		test( 'toggling shipping company hides and shows address field', async ( {
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
		} );

		test( 'toggling billing company hides and shows address field', async ( {
			editor,
		} ) => {
			await editor.canvas.click( 'body' );
			await editor.canvas
				.getByLabel( 'Use same address for billing' )
				.uncheck();

			await editor.selectBlocks(
				blockSelectorInEditor +
					'  [data-type="woocommerce/checkout-billing-address-block"]'
			);
			const checkbox = editor.page.getByRole( 'checkbox', {
				name: 'Company',
				exact: true,
			} );
			await checkbox.check();
			await expect( checkbox ).toBeChecked();
			await expect(
				editor.canvas.locator(
					'.wc-block-checkout__billing-fields .wc-block-components-address-form__company'
				)
			).toBeVisible();

			await checkbox.uncheck();
			await expect( checkbox ).not.toBeChecked();
			await expect(
				editor.canvas.locator(
					'div.wc-block-components-address-form__company'
				)
			).toBeHidden();
		} );
	} );
} );
