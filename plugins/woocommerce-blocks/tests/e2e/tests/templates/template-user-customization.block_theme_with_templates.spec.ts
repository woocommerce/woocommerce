/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { BLOCK_THEME_WITH_TEMPLATES_SLUG } from '@woocommerce/e2e-utils';
import type { Page, Response } from '@playwright/test';
import type { FrontendUtils } from '@woocommerce/e2e-utils';

type TemplateUserCustomizationTest = {
	visitPage: ( props: {
		frontendUtils: FrontendUtils;
		page: Page;
	} ) => Promise< void | Response | null >;
	templateName: string;
	templatePath: string;
	templateType: string;
};

const templateUserCustomizationTests: TemplateUserCustomizationTest[] = [
	{
		visitPage: async ( { frontendUtils } ) =>
			await frontendUtils.goToShop(),
		templateName: 'Product Catalog',
		templatePath: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//archive-product`,
		templateType: 'wp_template',
	},
	{
		visitPage: async ( { page } ) =>
			await page.goto( '/?s=shirt&post_type=product' ),
		templateName: 'Product Search Results',
		templatePath: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//product-search-results`,
		templateType: 'wp_template',
	},
	{
		visitPage: async ( { page } ) => await page.goto( '/color/blue' ),
		templateName: 'Products by Attribute',
		templatePath: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//taxonomy-product_attribute`,
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
		templatePath: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//taxonomy-product_cat`,
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
		templatePath: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//taxonomy-product_tag`,
		templateType: 'wp_template',
		defaultTemplate: {
			templateName: 'Product Catalog',
			templatePath: 'woocommerce/woocommerce//archive-product',
		},
	},
	{
		visitPage: async ( { page } ) => await page.goto( '/product/hoodie' ),
		templateName: 'Single Product',
		templatePath: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//single-product`,
		templateType: 'wp_template',
	},
	{
		visitPage: async ( { frontendUtils } ) =>
			await frontendUtils.goToCart(),
		templateName: 'Page: Cart',
		templatePath: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//page-cart`,
		templateType: 'wp_template',
	},
	{
		visitPage: async ( { frontendUtils } ) => {
			await frontendUtils.goToShop();
			await frontendUtils.addToCart();
			await frontendUtils.goToCheckout();
		},
		templateName: 'Page: Checkout',
		templatePath: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//page-checkout`,
		templateType: 'wp_template',
	},
];
const userText = 'Hello World in the template';
const defaultTemplateUserText = 'Hello World in the default template';

templateUserCustomizationTests.forEach( ( testData ) => {
	test.describe( `${ testData.templateName } template`, async () => {
		test( "theme template has priority over WooCommerce's and can be modified", async ( {
			admin,
			editorUtils,
			frontendUtils,
			page,
		} ) => {
			// Edit the theme template.
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

			// Verify the template is the one modified by the user.
			await testData.visitPage( { frontendUtils, page } );
			await expect( page.getByText( userText ).first() ).toBeVisible();

			// Revert edition and verify the template from the theme is used.
			await admin.visitAdminPage(
				'site-editor.php',
				`path=/${ testData.templateType }/all`
			);
			await editorUtils.revertTemplateCustomizations(
				testData.templateName
			);
			await testData.visitPage( { frontendUtils, page } );

			await expect(
				page
					.getByText(
						`${ testData.templateName } template loaded from theme`
					)
					.first()
			).toBeVisible();
			await expect( page.getByText( userText ) ).toHaveCount( 0 );
		} );

		if ( testData.defaultTemplate ) {
			test( `theme template has priority over user-modified ${ testData.defaultTemplate.templateName } template`, async ( {
				admin,
				editorUtils,
				page,
			} ) => {
				// Edit default template and verify changes are not visible, as the theme template has priority.
				await admin.visitSiteEditor( {
					postId: testData.defaultTemplate.templatePath,
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
				await page.goto( testData.permalink );
				await expect(
					page.getByText( defaultTemplateUserText )
				).toHaveCount( 0 );

				// Revert the edit.
				await admin.visitAdminPage(
					'site-editor.php',
					`path=/${ testData.templateType }/all`
				);
				await editorUtils.revertTemplateCustomizations(
					testData.defaultTemplate.templateName
				);
			} );
		}
	} );
} );
