/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { CUSTOMIZABLE_WC_TEMPLATES, WC_TEMPLATES_SLUG } from './constants';

CUSTOMIZABLE_WC_TEMPLATES.forEach( ( testData ) => {
	const userText = `Hello World in the ${ testData.templateName } template`;
	const fallbackTemplateUserText = `Hello World in the fallback ${ testData.templateName } template`;

	test.describe( `${ testData.templateName } template`, async () => {
		test( 'can be modified and reverted', async ( {
			admin,
			frontendUtils,
			editorUtils,
			page,
		} ) => {
			// Verify the template can be edited.
			await admin.visitSiteEditor( {
				postId: `${ WC_TEMPLATES_SLUG }//${ testData.templatePath }`,
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

		if ( testData.fallbackTemplate ) {
			test( `defaults to the ${ testData.fallbackTemplate.templateName } template`, async ( {
				admin,
				frontendUtils,
				editorUtils,
				page,
			} ) => {
				// Edit default template and verify changes are visible.
				await admin.visitSiteEditor( {
					postId: `${ WC_TEMPLATES_SLUG }//${
						testData.fallbackTemplate?.templatePath || ''
					}`,
					postType: testData.templateType,
				} );
				await editorUtils.enterEditMode();
				await editorUtils.closeWelcomeGuideModal();
				await editorUtils.editor.insertBlock( {
					name: 'core/paragraph',
					attributes: {
						content: fallbackTemplateUserText,
					},
				} );
				await editorUtils.saveTemplate();
				await testData.visitPage( { frontendUtils, page } );
				await expect(
					page.getByText( fallbackTemplateUserText ).first()
				).toBeVisible();

				// Verify the edition can be reverted.
				await admin.visitAdminPage(
					'site-editor.php',
					`path=/${ testData.templateType }/all`
				);
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
