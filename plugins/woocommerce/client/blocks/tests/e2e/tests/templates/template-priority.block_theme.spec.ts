/**
 * External dependencies
 */
import {
	test,
	expect,
	BLOCK_THEME_SLUG,
	BLOCK_THEME_WITH_TEMPLATES_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CUSTOMIZABLE_WC_TEMPLATES } from './constants';

test.describe( 'Template priority', () => {
	// Templates might come from different sources, and they should have this order of priority:
	// 1. Template from the database with the theme slug.
	// 2. Template from the database with the WooCommerce slug.
	// 3. Fallback template from the database with the theme BLOCK_THEME_SLUG.
	// 4. Fallback template from the database with the WooCommerce BLOCK_THEME_SLUG.
	// 5. Template from the theme.
	// 6. Fallback template from the theme.
	// 7. Template from WooCommerce.

	// We test a regular template and a taxonomy template with fallback, as they follow slightly different flow.
	const templatesToTest = [
		{
			path: '/product/hoodie',
			templateName: 'Single Product',
			templatePath: 'single-product',
			identificableText: 'Related products',
		},
		{
			path: '/product-category/clothing',
			templateName: 'Products by Category',
			templatePath: 'taxonomy-product_cat',
			fallbackTemplate: {
				templateName: 'Product Catalog',
				templatePath: 'archive-product',
			},
			identificableText: 'Showing all 9 results',
		},
	];

	templatesToTest.forEach( ( testData ) => {
		test( `priorities are applied correctly in the ${ testData.templateName } template`, async ( {
			admin,
			frontendUtils,
			editor,
			page,
			requestUtils,
		} ) => {
			const addParagraphToTemplate = async (
				templateSlug: string,
				content: string
			) => {
				await admin.visitSiteEditor( {
					postId: templateSlug,
					postType: 'wp_template',
					canvas: 'edit',
				} );

				await editor.insertBlock( {
					name: 'core/paragraph',
					attributes: {
						content,
					},
				} );

				await editor.saveSiteEditorEntities( {
					isOnlyCurrentEntityDirty: true,
				} );
			};

			await test.step( 'WooCommerce template', async () => {
				await page.goto( testData.path );

				// Verify it loaded correctly but has no custom text.
				await expect(
					page.getByText( testData.identificableText )
				).toBeVisible();
				await expect(
					page.getByText( 'Custom template' )
				).toBeHidden();
			} );

			await test.step( 'theme template', async () => {
				await requestUtils.activateTheme(
					BLOCK_THEME_WITH_TEMPLATES_SLUG
				);

				await expect(
					page.getByText( testData.identificableText )
				).toBeVisible();
				await expect(
					page.getByText( 'Custom template' )
				).toBeHidden();
				await requestUtils.activateTheme( BLOCK_THEME_SLUG );
			} );

			if ( testData.fallbackTemplate ) {
				await test.step( 'custom fallback template with WooCommerce slug', async () => {
					await addParagraphToTemplate(
						`woocommerce/woocommerce//${ testData.fallbackTemplate.templatePath }`,
						'Custom fallback template with WooCommerce slug'
					);

					await page.goto( testData.path );

					await expect(
						page.getByText( testData.identificableText )
					).toBeVisible();
					await expect(
						page.getByText(
							'Custom fallback template with WooCommerce slug'
						)
					).toBeVisible();
				} );

				await test.step( 'custom fallback template with theme slug', async () => {
					await addParagraphToTemplate(
						`${ BLOCK_THEME_SLUG }//${ testData.fallbackTemplate.templatePath }`,
						'Custom fallback template with theme slug'
					);

					await page.goto( testData.path );

					await expect(
						page.getByText( testData.identificableText )
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
				await addParagraphToTemplate(
					`woocommerce/woocommerce//${ testData.templatePath }`,
					'Custom template with WooCommerce slug'
				);

				await page.goto( testData.path );

				await expect(
					page.getByText( testData.identificableText )
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
					await page.getByLabel( 'Add Template' ).click();

					const dialog = page.getByRole( 'dialog' );
					await dialog
						.getByRole( 'button', { name: testData.templateName } )
						.click();
					// There is the chance that the Add template dialog is opened before
					// product taxonomies could load. In that case, the screen to select
					// whether to create a template for a specific taxonomy or for all of
					// them won't be shown. That's why we click the 'All Categories' /
					// 'All Tags' button only if visible.
					const allButton = dialog.getByRole( 'button', {
						name: 'All',
					} );
					if ( await allButton.isVisible() ) {
						await allButton.click();
					}
					await page.getByLabel( 'Fallback content' ).click();

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
					await addParagraphToTemplate(
						`${ BLOCK_THEME_SLUG }//${ testData.templatePath }`,
						'Custom template with theme slug'
					);
				}

				await page.goto( testData.path );

				await expect(
					page.getByText( testData.identificableText )
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
