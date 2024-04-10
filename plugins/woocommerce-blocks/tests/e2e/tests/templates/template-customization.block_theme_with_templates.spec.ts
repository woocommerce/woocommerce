/**
 * External dependencies
 */
import { BLOCK_THEME_WITH_TEMPLATES_SLUG } from '@woocommerce/e2e-utils';
import { test, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { CUSTOMIZABLE_WC_TEMPLATES } from './constants';

test.describe( 'Template customization', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.activateTheme( BLOCK_THEME_WITH_TEMPLATES_SLUG );
	} );

	CUSTOMIZABLE_WC_TEMPLATES.forEach( ( testData ) => {
		if ( ! testData.canBeOverriddenByThemes ) {
			return;
		}
		const userText = `Hello World in the ${ testData.templateName } template`;
		const fallbackTemplateUserText = `Hello World in the fallback ${ testData.templateName } template`;
		const templateTypeName =
			testData.templateType === 'wp_template'
				? 'template'
				: 'template part';

		test.describe( `${ testData.templateName } template`, async () => {
			test.afterAll( async ( { requestUtils } ) => {
				await requestUtils.deleteAllTemplates( testData.templateType );
			} );

			test( "theme template has priority over WooCommerce's and can be modified", async ( {
				admin,
				editor,
				editorUtils,
				frontendUtils,
				page,
			} ) => {
				// Edit the theme template.
				await editorUtils.visitTemplateEditor(
					testData.templateName,
					testData.templateType
				);
				await editor.insertBlock( {
					name: 'core/paragraph',
					attributes: { content: userText },
				} );
				await editor.saveSiteEditorEntities();
				// Verify template name didn't change.
				// See: https://github.com/woocommerce/woocommerce/issues/42221
				await expect(
					page.getByRole( 'heading', {
						name: `Editing ${ templateTypeName }: ${ testData.templateName }`,
					} )
				).toBeVisible();

				// Verify the template is the one modified by the user.
				await testData.visitPage( { frontendUtils, page } );
				await expect(
					page.getByText( userText ).first()
				).toBeVisible();

				// Revert edition and verify the template from the theme is used.
				await admin.visitSiteEditor( {
					path: `/${ testData.templateType }/all`,
				} );
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

			if ( testData.fallbackTemplate ) {
				test( `theme template has priority over user-modified ${ testData.fallbackTemplate.templateName } template`, async ( {
					admin,
					frontendUtils,
					editor,
					editorUtils,
					page,
				} ) => {
					// Edit default template and verify changes are not visible, as the theme template has priority.
					await editorUtils.visitTemplateEditor(
						testData.fallbackTemplate?.templateName || '',
						testData.templateType
					);
					await editor.insertBlock( {
						name: 'core/paragraph',
						attributes: {
							content: fallbackTemplateUserText,
						},
					} );
					await editor.saveSiteEditorEntities();
					await testData.visitPage( { frontendUtils, page } );
					await expect(
						page.getByText( fallbackTemplateUserText )
					).toHaveCount( 0 );

					// Revert the edit.
					await admin.visitSiteEditor( {
						path: `/${ testData.templateType }/all`,
					} );
					await editorUtils.revertTemplateCustomizations(
						testData.fallbackTemplate?.templateName || ''
					);
				} );
			}
		} );
	} );
} );
