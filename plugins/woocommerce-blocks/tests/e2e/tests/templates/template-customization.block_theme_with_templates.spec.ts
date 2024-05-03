/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { BLOCK_THEME_WITH_TEMPLATES_SLUG } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CUSTOMIZABLE_WC_TEMPLATES } from './constants';

CUSTOMIZABLE_WC_TEMPLATES.forEach( ( testData ) => {
	if ( ! testData.canBeOverridenByThemes ) {
		return;
	}
	const userText = `Hello World in the ${ testData.templateName } template`;
	const fallbackTemplateUserText = `Hello World in the fallback ${ testData.templateName } template`;

	test.describe( `${ testData.templateName } template`, async () => {
		test( "theme template has priority over WooCommerce's and can be modified", async ( {
			admin,
			editorUtils,
			frontendUtils,
			page,
		} ) => {
			// Edit the theme template.
			await admin.visitSiteEditor( {
				postId: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${ testData.templatePath }`,
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

		if ( testData.fallbackTemplate ) {
			test( `theme template has priority over user-modified ${ testData.fallbackTemplate.templateName } template`, async ( {
				admin,
				frontendUtils,
				editorUtils,
				page,
			} ) => {
				// Edit default template and verify changes are not visible, as the theme template has priority.
				await admin.visitSiteEditor( {
					postId: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${
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
					page.getByText( fallbackTemplateUserText )
				).toHaveCount( 0 );

				// Revert the edit.
				await admin.visitAdminPage(
					'site-editor.php',
					`path=/${ testData.templateType }/all`
				);
				await editorUtils.revertTemplateCustomizations(
					testData.fallbackTemplate?.templateName || ''
				);
			} );
		}
	} );
} );
