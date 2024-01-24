/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import type { Page, Response } from '@playwright/test';
import type { FrontendUtils } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { SIMPLE_VIRTUAL_PRODUCT_NAME } from '../checkout/constants';
import { CheckoutPage } from '../checkout/checkout.page';

type TemplateCustomizationTest = {
	visitPage: ( props: {
		frontendUtils: FrontendUtils;
		page: Page;
	} ) => Promise< void | Response | null >;
	templateName: string;
	templatePath: string;
	templateType: string;
	defaultTemplate?: {
		templateName: string;
		templatePath: string;
	};
};

const templateUserCustomizationTests: TemplateCustomizationTest[] = [
	{
		visitPage: async ( { frontendUtils } ) =>
			await frontendUtils.goToShop(),
		templateName: 'Product Catalog',
		templatePath: 'woocommerce/woocommerce//archive-product',
		templateType: 'wp_template',
	},
	{
		visitPage: async ( { page } ) =>
			await page.goto( '/?s=shirt&post_type=product' ),
		templateName: 'Product Search Results',
		templatePath: 'woocommerce/woocommerce//product-search-results',
		templateType: 'wp_template',
	},
	{
		visitPage: async ( { page } ) => await page.goto( '/color/blue' ),
		templateName: 'Products by Attribute',
		templatePath: 'woocommerce/woocommerce//taxonomy-product_attribute',
		templateType: 'wp_template',
		defaultTemplate: {
			templateName: 'Product Catalog',
			templatePath: 'woocommerce/woocommerce//archive-product',
		},
	},
	{
		visitPage: async ( { page } ) =>
			await page.goto( '/product-category/clothing' ),
		templateName: 'Products by Category',
		templatePath: 'woocommerce/woocommerce//taxonomy-product_cat',
		templateType: 'wp_template',
		defaultTemplate: {
			templateName: 'Product Catalog',
			templatePath: 'woocommerce/woocommerce//archive-product',
		},
	},
	{
		visitPage: async ( { page } ) =>
			await page.goto( '/product-tag/recommended/' ),
		templateName: 'Products by Tag',
		templatePath: 'woocommerce/woocommerce//taxonomy-product_tag',
		templateType: 'wp_template',
		defaultTemplate: {
			templateName: 'Product Catalog',
			templatePath: 'woocommerce/woocommerce//archive-product',
		},
	},
	{
		visitPage: async ( { page } ) => await page.goto( '/product/hoodie' ),
		templateName: 'Single Product',
		templatePath: 'woocommerce/woocommerce//single-product',
		templateType: 'wp_template',
	},
	{
		visitPage: async ( { frontendUtils } ) =>
			await frontendUtils.goToCart(),
		templateName: 'Page: Cart',
		templatePath: 'woocommerce/woocommerce//page-cart',
		templateType: 'wp_template',
	},
	{
		visitPage: async ( { frontendUtils } ) => {
			await frontendUtils.goToShop();
			await frontendUtils.addToCart();
			await frontendUtils.goToCheckout();
		},
		templateName: 'Page: Checkout',
		templatePath: 'woocommerce/woocommerce//page-checkout',
		templateType: 'wp_template',
	},
	{
		visitPage: async ( { frontendUtils, page } ) => {
			const checkoutPage = new CheckoutPage( { page } );
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
			await frontendUtils.goToCheckout();
			await checkoutPage.fillInCheckoutWithTestData();
			await checkoutPage.placeOrder();
		},
		templateName: 'Order Confirmation',
		templatePath: 'woocommerce/woocommerce//order-confirmation',
		templateType: 'wp_template',
	},
];
const userText = 'Hello World in the template';
const defaultTemplateUserText = 'Hello World in the default template';

templateUserCustomizationTests.forEach( ( testData ) => {
	test.describe( `${ testData.templateName } template`, async () => {
		test( 'can be modified and reverted', async ( {
			admin,
			frontendUtils,
			editorUtils,
			page,
		} ) => {
			// Verify the template can be edited.
			await admin.visitSiteEditor( {
				postId: testData.templatePath,
				postType: testData.templateType,
			} );
			await editorUtils.enterEditMode();
			await editorUtils.closeWelcomeGuideModal();
			await editorUtils.editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: userText },
			} );
			await editorUtils.saveTemplate();
			await testData.visitPage( { frontendUtils, page } );
			await expect( page.getByText( userText ).first() ).toBeVisible();

			// Verify the edition can be reverted.
			await admin.visitAdminPage(
				'site-editor.php',
				`path=/${ testData.templateType }/all`
			);
			await editorUtils.revertTemplateCustomizations(
				testData.templateName
			);
			await testData.visitPage( { frontendUtils, page } );
			await expect( page.getByText( userText ) ).toHaveCount( 0 );
		} );

		if ( testData.defaultTemplate ) {
			test( `defaults to the ${ testData.defaultTemplate.templateName } template`, async ( {
				admin,
				frontendUtils,
				editorUtils,
				page,
			} ) => {
				// Edit default template and verify changes are visible.
				await admin.visitSiteEditor( {
					postId: testData.defaultTemplate?.templatePath || '',
					postType: testData.templateType,
				} );
				await editorUtils.enterEditMode();
				await editorUtils.closeWelcomeGuideModal();
				await editorUtils.editor.insertBlock( {
					name: 'core/paragraph',
					attributes: {
						content: defaultTemplateUserText,
					},
				} );
				await editorUtils.saveTemplate();
				await testData.visitPage( { frontendUtils, page } );
				await expect(
					page.getByText( defaultTemplateUserText ).first()
				).toBeVisible();

				// Verify the edition can be reverted.
				await admin.visitAdminPage(
					'site-editor.php',
					`path=/${ testData.templateType }/all`
				);
				await editorUtils.revertTemplateCustomizations(
					testData.defaultTemplate?.templateName || ''
				);
				await testData.visitPage( { frontendUtils, page } );
				await expect(
					page.getByText( defaultTemplateUserText )
				).toHaveCount( 0 );
			} );
		}
	} );
} );
