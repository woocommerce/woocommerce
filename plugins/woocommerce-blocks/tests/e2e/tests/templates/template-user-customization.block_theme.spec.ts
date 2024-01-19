/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

const templateUserCustomizationTests = [
	{
		permalink: '/shop',
		templateName: 'Product Catalog',
		templatePath: 'woocommerce/woocommerce//archive-product',
		templateType: 'wp_template',
	},
	{
		permalink: '/?s=shirt&post_type=product',
		templateName: 'Product Search Results',
		templatePath: 'woocommerce/woocommerce//product-search-results',
		templateType: 'wp_template',
	},
	{
		permalink: '/color/blue',
		templateName: 'Products by Attribute',
		templatePath: 'woocommerce/woocommerce//taxonomy-product_attribute',
		templateType: 'wp_template',
		defaultTemplate: {
			templateName: 'Product Catalog',
			templatePath: 'woocommerce/woocommerce//archive-product',
		},
	},
	{
		permalink: '/product-category/clothing',
		templateName: 'Products by Category',
		templatePath: 'woocommerce/woocommerce//taxonomy-product_cat',
		templateType: 'wp_template',
		defaultTemplate: {
			templateName: 'Product Catalog',
			templatePath: 'woocommerce/woocommerce//archive-product',
		},
	},
	{
		permalink: '/product-tag/recommended/',
		templateName: 'Products by Tag',
		templatePath: 'woocommerce/woocommerce//taxonomy-product_tag',
		templateType: 'wp_template',
		defaultTemplate: {
			templateName: 'Product Catalog',
			templatePath: 'woocommerce/woocommerce//archive-product',
		},
	},
	{
		permalink: '/product/hoodie',
		templateName: 'Single Product',
		templatePath: 'woocommerce/woocommerce//single-product',
		templateType: 'wp_template',
	},
];
const userText = 'Hello World in the template';
const defaultTemplateUserText = 'Hello World in the default template';

templateUserCustomizationTests.forEach( ( testData ) => {
	test.describe( `${ testData.templateName } template`, async () => {
		test( 'can be modified and reverted', async ( {
			admin,
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
			await page.goto( testData.permalink );
			await expect( page.getByText( userText ).first() ).toBeVisible();

			// Verify the edition can be reverted.
			await admin.visitAdminPage(
				'site-editor.php',
				`path=/${ testData.templateType }/all`
			);
			await editorUtils.revertTemplateCustomizations(
				testData.templateName
			);
			await page.goto( testData.permalink );
			await expect( page.getByText( userText ) ).toHaveCount( 0 );
		} );

		if ( testData.defaultTemplate ) {
			test( `defaults to the ${ testData.defaultTemplate.templateName } template`, async ( {
				admin,
				editorUtils,
				page,
			} ) => {
				// Edit default template and verify changes are visible.
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
					page.getByText( defaultTemplateUserText ).first()
				).toBeVisible();

				// Verify the edition can be reverted.
				await admin.visitAdminPage(
					'site-editor.php',
					`path=/${ testData.templateType }/all`
				);
				await editorUtils.revertTemplateCustomizations(
					testData.defaultTemplate.templateName
				);
				await page.goto( testData.permalink );
				await expect(
					page.getByText( defaultTemplateUserText )
				).toHaveCount( 0 );
			} );
		}
	} );
} );
