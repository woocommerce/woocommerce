/**
 * External dependencies
 */
import {
	test as base,
	expect,
	BLOCK_THEME_SLUG,
	BLOCK_THEME_WITH_TEMPLATES_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import TemplatesPage from './templates.page';

const test = base.extend< { pageObject: TemplatesPage } >( {
	pageObject: async ( { admin, editor }, use ) => {
		const pageObject = new TemplatesPage( {
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Template priority', () => {
	// Templates might come from different sources, and they should have this order of priority:
	// 1. Template from the database with the theme slug.
	// 2. Template from the database with the WooCommerce slug.
	// 3. Fallback template from the database with the theme slug.
	// 4. Fallback template from the database with the WooCommerce slug.
	// 5. Template from the theme.
	// 6. Fallback template from the theme.
	// 7. Template from WooCommerce.

	// We test a regular template and a taxonomy template with fallback, as they follow slightly different flow.
	const templatesToTest = [
		{
			path: '/product/hoodie',
			templateName: 'Single Product',
			templatePath: 'single-product',
			identifiableText: 'Related products',
		},
		{
			path: '/product-category/clothing',
			templateName: 'Products by Category',
			templatePath: 'taxonomy-product_cat',
			fallbackTemplate: {
				templateName: 'Product Catalog',
				templatePath: 'archive-product',
			},
			identifiableText: 'Showing all 9 results',
		},
	];

	templatesToTest.forEach( ( testData ) => {
		test( `priorities are applied correctly in the ${ testData.templateName } template`, async ( {
			admin,
			editor,
			page,
			requestUtils,
			pageObject,
		} ) => {
			await test.step( 'WooCommerce template', async () => {
				await page.goto( testData.path );

				// Verify it loaded correctly but has no custom text.
				await expect(
					page.getByText( testData.identifiableText )
				).toBeVisible();
				await expect(
					page.getByText( 'Custom template' )
				).toBeHidden();
			} );

			await test.step( 'theme template', async () => {
				await requestUtils.activateTheme(
					BLOCK_THEME_WITH_TEMPLATES_SLUG
				);

				await page.goto( testData.path );

				await expect(
					page.getByText( testData.identifiableText )
				).toBeVisible();
				await expect(
					page.getByText( 'Custom template' )
				).toBeHidden();
				await requestUtils.activateTheme( BLOCK_THEME_SLUG );
			} );

			if ( testData.fallbackTemplate ) {
				await test.step( 'custom fallback template with WooCommerce slug', async () => {
					await pageObject.addParagraphToTemplate(
						`woocommerce/woocommerce//${ testData.fallbackTemplate.templatePath }`,
						'Custom fallback template with WooCommerce slug'
					);

					await page.goto( testData.path );

					await expect(
						page.getByText( testData.identifiableText )
					).toBeVisible();
					await expect(
						page.getByText(
							'Custom fallback template with WooCommerce slug'
						)
					).toBeVisible();
				} );

				await test.step( 'custom fallback template with theme slug', async () => {
					await pageObject.addParagraphToTemplate(
						`${ BLOCK_THEME_SLUG }//${ testData.fallbackTemplate.templatePath }`,
						'Custom fallback template with theme slug'
					);

					await page.goto( testData.path );

					await expect(
						page.getByText( testData.identifiableText )
					).toBeVisible();
					await expect(
						page.getByText(
							'Custom fallback template with theme slug'
						)
					).toBeVisible();
					await expect(
						page.getByText(
							'Custom fallback template with WooCommerce slug'
						)
					).toBeHidden();
				} );
			}

			await test.step( 'custom template with WooCommerce slug', async () => {
				await pageObject.addParagraphToTemplate(
					`woocommerce/woocommerce//${ testData.templatePath }`,
					'Custom template with WooCommerce slug'
				);

				await page.goto( testData.path );

				await expect(
					page.getByText( testData.identifiableText )
				).toBeVisible();
				await expect(
					page.getByText( 'Custom fallback template with theme slug' )
				).toBeHidden();
				await expect(
					page.getByText( 'Custom template with WooCommerce slug' )
				).toBeVisible();
			} );

			await test.step( 'custom template with theme slug', async () => {
				await admin.visitSiteEditor( {
					postType: 'wp_template',
				} );
				await editor.revertTemplate( {
					templateName: testData.templateName,
				} );

				if ( testData.fallbackTemplate ) {
					await editor.createTemplate( {
						templateName: testData.templateName,
					} );

					// Verify we are editing the correct template.
					await page
						.getByRole( 'heading', {
							name: `${ testData.templateName } · Template`,
							level: 1,
						} )
						.waitFor();

					await editor.insertBlock( {
						name: 'core/paragraph',
						attributes: {
							content: 'Custom template with theme slug',
						},
					} );

					await editor.saveSiteEditorEntities( {
						isOnlyCurrentEntityDirty: true,
					} );
				} else {
					await pageObject.addParagraphToTemplate(
						`${ BLOCK_THEME_SLUG }//${ testData.templatePath }`,
						'Custom template with theme slug'
					);
				}

				await page.goto( testData.path );

				await expect(
					page.getByText( testData.identifiableText )
				).toBeVisible();
				await expect(
					page.getByText( 'Custom template with theme slug' )
				).toBeVisible();
				await expect(
					page.getByText( 'Custom template with WooCommerce slug' )
				).toBeHidden();
			} );
		} );
	} );
} );
