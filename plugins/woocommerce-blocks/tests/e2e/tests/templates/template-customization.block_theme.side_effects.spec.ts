/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import { deleteAllTemplates } from '@wordpress/e2e-test-utils';
import {
	BLOCK_THEME_SLUG,
	BLOCK_THEME_WITH_TEMPLATES_SLUG,
	cli,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CUSTOMIZABLE_WC_TEMPLATES, WC_TEMPLATES_SLUG } from './constants';

CUSTOMIZABLE_WC_TEMPLATES.forEach( ( testData ) => {
	if ( ! testData.canBeOverridenByThemes ) {
		return;
	}
	const userText = `Hello World in the ${ testData.templateName } template`;
	const woocommerceTemplateUserText = `Hello World in the WooCommerce ${ testData.templateName } template`;

	test.describe( `${ testData.templateName } template`, async () => {
		test.afterAll( async () => {
			await deleteAllTemplates( 'wp_template' );
		} );

		test( `user-modified ${ testData.templateName } template based on the theme template has priority over the user-modified template based on the default WooCommerce template`, async ( {
			admin,
			frontendUtils,
			editorUtils,
			page,
		} ) => {
			// Edit the WooCommerce default template
			await admin.visitSiteEditor( {
				postId: `${ WC_TEMPLATES_SLUG }//${ testData.templatePath }`,
				postType: testData.templateType,
			} );
			await editorUtils.enterEditMode();
			await editorUtils.closeWelcomeGuideModal();
			await editorUtils.editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: woocommerceTemplateUserText },
			} );
			await editorUtils.saveTemplate();

			await cli(
				`npm run wp-env run tests-cli -- wp theme activate ${ BLOCK_THEME_WITH_TEMPLATES_SLUG }`
			);

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
			await testData.visitPage( { frontendUtils, page } );

			await expect(
				page.getByText( woocommerceTemplateUserText ).first()
			).toBeVisible();
			await expect( page.getByText( userText ) ).toHaveCount( 0 );

			await cli(
				`npm run wp-env run tests-cli -- wp theme activate ${ BLOCK_THEME_SLUG }`
			);
		} );
	} );
} );
