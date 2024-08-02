/**
 * External dependencies
 */
import {
	test,
	expect,
	BLOCK_THEME_WITH_TEMPLATES_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CUSTOMIZABLE_WC_TEMPLATES } from './constants';

test.describe( 'Template customization', () => {
	CUSTOMIZABLE_WC_TEMPLATES.forEach( ( testData ) => {
		const userText = `Hello World in the ${ testData.templateName } template`;
		const fallbackTemplateUserText = `Hello World in the fallback ${ testData.templateName } template`;
		const templateTypeName =
			testData.templateType === 'wp_template'
				? 'template'
				: 'template part';

		test.describe( `${ testData.templateName } template`, () => {
			test( 'can be modified and reverted', async ( {
				admin,
				frontendUtils,
				editor,
				page,
			} ) => {
				await admin.visitSiteEditor( {
					postId: `woocommerce/woocommerce//${ testData.templatePath }`,
					postType: testData.templateType,
					canvas: 'edit',
				} );

				await editor.insertBlock( {
					name: 'core/paragraph',
					attributes: { content: userText },
				} );
				await editor.saveSiteEditorEntities( {
					isOnlyCurrentEntityDirty: true,
				} );
				// Verify template name didn't change.
				// See: https://github.com/woocommerce/woocommerce/issues/42221
				await expect(
					page.getByRole( 'heading', {
						name: `Editing ${ templateTypeName }: ${ testData.templateName }`,
					} )
				).toBeVisible();

				await testData.visitPage( { frontendUtils, page } );
				await expect(
					page.getByText( userText ).first()
				).toBeVisible();

				// Verify the edition can be reverted.
				await admin.visitSiteEditor( {
					postType: testData.templateType,
				} );
				await editor.revertTemplate( {
					templateName: testData.templateName,
				} );
				await testData.visitPage( { frontendUtils, page } );
				await expect( page.getByText( userText ) ).toBeHidden();
			} );

			if ( testData.fallbackTemplate ) {
				test( `defaults to the ${ testData.fallbackTemplate.templateName } template`, async ( {
					admin,
					frontendUtils,
					editor,
					page,
				} ) => {
					// Edit fallback template and verify changes are visible.
					await admin.visitSiteEditor( {
						postId: `woocommerce/woocommerce//${ testData.fallbackTemplate?.templatePath }`,
						postType: testData.templateType,
						canvas: 'edit',
					} );

					await editor.insertBlock( {
						name: 'core/paragraph',
						attributes: {
							content: fallbackTemplateUserText,
						},
					} );
					await editor.saveSiteEditorEntities( {
						isOnlyCurrentEntityDirty: true,
					} );
					await testData.visitPage( { frontendUtils, page } );
					await expect(
						page.getByText( fallbackTemplateUserText ).first()
					).toBeVisible();

					// Verify the edition can be reverted.
					await admin.visitSiteEditor( {
						postType: testData.templateType,
					} );
					await editor.revertTemplate( {
						templateName:
							testData.fallbackTemplate?.templateName || '',
					} );
					await testData.visitPage( { frontendUtils, page } );
					await expect(
						page.getByText( fallbackTemplateUserText )
					).toBeHidden();
				} );
			}
		} );
	} );

	const testToRun = CUSTOMIZABLE_WC_TEMPLATES.filter(
		( data ) => data.canBeOverriddenByThemes
	);

	for ( const testData of testToRun ) {
		const userText = `Hello World in the ${ testData.templateName } template`;
		const woocommerceTemplateUserText = `Hello World in the WooCommerce ${ testData.templateName } template`;

		test.describe( `${ testData.templateName } template`, () => {
			test( `user-modified ${ testData.templateName } template based on the theme template has priority over the user-modified template based on the default WooCommerce template`, async ( {
				page,
				admin,
				editor,
				requestUtils,
				frontendUtils,
			} ) => {
				// Edit the WooCommerce default template
				await admin.visitSiteEditor( {
					postId: `woocommerce/woocommerce//${ testData.templatePath }`,
					postType: testData.templateType,
					canvas: 'edit',
				} );

				await editor.insertBlock( {
					name: 'core/paragraph',
					attributes: { content: woocommerceTemplateUserText },
				} );
				await editor.saveSiteEditorEntities( {
					isOnlyCurrentEntityDirty: true,
				} );

				await requestUtils.activateTheme(
					BLOCK_THEME_WITH_TEMPLATES_SLUG
				);

				// Edit the theme template. The theme template is not
				// directly available from the UI, because the customized
				// one takes priority, so we go directly to its URL.
				await admin.visitSiteEditor( {
					postId: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${ testData.templatePath }`,
					postType: testData.templateType,
					canvas: 'edit',
				} );

				await editor.insertBlock( {
					name: 'core/paragraph',
					attributes: { content: userText },
				} );
				await editor.saveSiteEditorEntities( {
					isOnlyCurrentEntityDirty: true,
				} );

				// Verify the template is the one modified by the user based on the theme.
				await testData.visitPage( { frontendUtils, page } );
				await expect(
					page.getByText( userText ).first()
				).toBeVisible();
				await expect(
					page.getByText( woocommerceTemplateUserText )
				).toBeHidden();

				// Revert edition and verify the user-modified WC template is used.
				// Note: we need to revert it from the admin (instead of calling
				// `deleteAllTemplates()`). This way, we verify there are no
				// duplicate templates with the same name.
				// See: https://github.com/woocommerce/woocommerce/issues/42220
				await admin.visitSiteEditor( {
					postType: testData.templateType,
				} );

				await editor.revertTemplate( {
					templateName: testData.templateName,
				} );

				await testData.visitPage( { frontendUtils, page } );

				await expect(
					page.getByText( woocommerceTemplateUserText ).first()
				).toBeVisible();
				await expect( page.getByText( userText ) ).toBeHidden();
			} );
		} );
	}
} );
