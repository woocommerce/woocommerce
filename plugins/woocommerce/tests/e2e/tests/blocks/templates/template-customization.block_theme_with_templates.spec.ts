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
	test.beforeEach( async ( { requestUtils } ) => {
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
		const templateId = `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${ testData.templatePath }`;

		test.describe( `${ testData.templateName } template`, () => {
			test( "theme template has priority over WooCommerce's and can be modified", async ( {
				admin,
				editor,
				frontendUtils,
				requestUtils,
				page,
			} ) => {
				// Edit the theme template.
				await admin.visitSiteEditor( {
					postId: templateId,
					postType: testData.templateType,
					canvas: 'edit',
				} );

				await editor.canvas
					.locator( 'body' )
					.waitFor( { timeout: 20000 } );

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
						name: templateTypeName,
					} )
				).toBeVisible();

				// Verify the template is the one modified by the user.
				await testData.visitPage( {
					admin,
					editor,
					frontendUtils,
					requestUtils,
					page,
				} );
				await expect(
					page.getByText( userText ).first()
				).toBeVisible();

				// Revert edition and verify the template from the theme is used.
				await requestUtils.revertTemplate(
					testData.templateType,
					templateId
				);

				await testData.visitPage( {
					admin,
					editor,
					frontendUtils,
					requestUtils,
					page,
				} );

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
				const fallbackTemplate = testData.fallbackTemplate;

				test( `theme template has priority over user-modified ${ fallbackTemplate.templateName } template`, async ( {
					admin,
					frontendUtils,
					requestUtils,
					editor,
					page,
				} ) => {
					// Customize the fallback template and verify changes are not
					// visible, as the theme template has priority.
					await requestUtils.updateTemplateContent(
						testData.templateType,
						`${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${ fallbackTemplate.templatePath }`,
						`<!-- wp:paragraph --><p>${ fallbackTemplateUserText }</p><!-- /wp:paragraph -->`
					);
					await testData.visitPage( {
						admin,
						editor,
						frontendUtils,
						requestUtils,
						page,
					} );
					await expect(
						page.getByText( fallbackTemplateUserText )
					).toHaveCount( 0 );
				} );
			}
		} );
	} );
} );
