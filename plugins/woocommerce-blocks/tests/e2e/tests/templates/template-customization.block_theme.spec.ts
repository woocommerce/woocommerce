/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { CUSTOMIZABLE_WC_TEMPLATES } from './constants';

CUSTOMIZABLE_WC_TEMPLATES.forEach( ( testData ) => {
	const userText = `Hello World in the ${ testData.templateName } template`;
	const fallbackTemplateUserText = `Hello World in the fallback ${ testData.templateName } template`;
	const templateTypeName =
		testData.templateType === 'wp_template' ? 'template' : 'template part';

	test.describe( `${ testData.templateName } template`, () => {
		test( 'can be modified and reverted', async ( {
			admin,
			frontendUtils,
			editor,
			editorUtils,
			page,
		} ) => {
			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( testData.gutenbergTemplateName ) {
				// Create a new template.
				await editorUtils.createTemplate(
					testData.gutenbergTemplateName,
					testData.templateType
				);
			} else {
				// Edit the WooCommerce default template.
				await editorUtils.visitTemplateEditor(
					testData.templateName,
					testData.templateType
				);
			}
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

			await testData.visitPage( { frontendUtils, page } );
			await expect( page.getByText( userText ).first() ).toBeVisible();

			// Verify the edition can be reverted.
			await admin.visitSiteEditor( {
				path: `/${ testData.templateType }/all`,
			} );
			// eslint-disable-next-line playwright/no-conditional-in-test
			if ( testData.fallbackTemplate ) {
				await editorUtils.revertTemplateCreation(
					testData.templateName
				);
			} else {
				await editorUtils.revertTemplateCustomizations(
					testData.templateName
				);
			}
			await testData.visitPage( { frontendUtils, page } );
			await expect( page.getByText( userText ) ).toHaveCount( 0 );
		} );

		if ( testData.fallbackTemplate ) {
			test( `defaults to the ${ testData.fallbackTemplate.templateName } template`, async ( {
				admin,
				frontendUtils,
				editor,
				editorUtils,
				page,
			} ) => {
				// Edit fallback template and verify changes are visible.
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
					page.getByText( fallbackTemplateUserText ).first()
				).toBeVisible();

				// Verify the edition can be reverted.
				await admin.visitSiteEditor( {
					path: `/${ testData.templateType }/all`,
				} );
				await editorUtils.revertTemplateCustomizations(
					testData.fallbackTemplate?.templateName || ''
				);
				await testData.visitPage( { frontendUtils, page } );
				await expect(
					page.getByText( fallbackTemplateUserText )
				).toHaveCount( 0 );
			} );
		}
	} );
} );
