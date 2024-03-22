/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import {
	BLOCK_THEME_SLUG,
	BLOCK_THEME_WITH_TEMPLATES_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CUSTOMIZABLE_WC_TEMPLATES } from './constants';

const testToRun = CUSTOMIZABLE_WC_TEMPLATES.filter(
	( data ) => data.canBeOverriddenByThemes
);

for ( const testData of testToRun ) {
	const userText = `Hello World in the ${ testData.templateName } template`;
	const woocommerceTemplateUserText = `Hello World in the WooCommerce ${ testData.templateName } template`;

	test.describe( `${ testData.templateName } template`, async () => {
		test.afterAll( async ( { requestUtils } ) => {
			await requestUtils.deleteAllTemplates( testData.templateType );
		} );

		test.beforeEach( async ( { requestUtils } ) => {
			await requestUtils.deleteAllTemplates( testData.templateType );
		} );

		test( `user-modified ${ testData.templateName } template based on the theme template has priority over the user-modified template based on the default WooCommerce template`, async ( {
			page,
			admin,
			editor,
			requestUtils,
			editorUtils,
			frontendUtils,
		} ) => {
			// Edit the WooCommerce default template
			await editorUtils.visitTemplateEditor(
				testData.templateName,
				testData.templateType
			);
			await editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: woocommerceTemplateUserText },
			} );
			await editor.saveSiteEditorEntities();

			await requestUtils.activateTheme( BLOCK_THEME_WITH_TEMPLATES_SLUG );

			// Edit the theme template. The theme template is not
			// directly available from the UI, because the customized
			// one takes priority, so we go directly to its URL.
			await admin.visitSiteEditor( {
				postId: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${ testData.templatePath }`,
				postType: testData.templateType,
			} );
			await editorUtils.enterEditMode();
			await editorUtils.editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: userText },
			} );
			await editor.saveSiteEditorEntities();

			// Verify the template is the one modified by the user based on the theme.
			await testData.visitPage( { frontendUtils, page } );
			await expect( page.getByText( userText ).first() ).toBeVisible();
			await expect(
				page.getByText( woocommerceTemplateUserText )
			).toHaveCount( 0 );

			// Revert edition and verify the user-modified WC template is used.
			// Note: we need to revert it from the admin (instead of calling
			// `deleteAllTemplates()`). This way, we verify there are no
			// duplicate templates with the same name.
			// See: https://github.com/woocommerce/woocommerce/issues/42220
			await admin.visitAdminPage(
				'site-editor.php',
				`path=/${ testData.templateType }/all`
			);
			await editorUtils.revertTemplateCustomizations(
				testData.templateName
			);

			await page.waitForTimeout( 1000 );
			await testData.visitPage( { frontendUtils, page } );

			await expect(
				page.getByText( woocommerceTemplateUserText ).first()
			).toBeVisible();
			await expect( page.getByText( userText ) ).toHaveCount( 0 );

			await requestUtils.activateTheme( BLOCK_THEME_SLUG );
		} );
	} );
}
