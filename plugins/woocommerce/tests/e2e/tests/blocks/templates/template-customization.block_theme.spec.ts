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

test.describe( 'Template customization', () => {
	CUSTOMIZABLE_WC_TEMPLATES.forEach( ( testData ) => {
		const userText = `Hello World in the ${ testData.templateName } template`;
		const fallbackTemplateUserText = `Hello World in the fallback ${ testData.templateName } template`;
		const templateTypeName =
			testData.templateType === 'wp_template'
				? 'template'
				: 'template part';
		const templateOrigin =
			testData.templateType === 'wp_template'
				? BLOCK_THEME_SLUG
				: 'woocommerce/woocommerce';
		const templateId = `${ templateOrigin }//${ testData.templatePath }`;

		test( `"${ testData.templateName }" template can be modified and reverted`, async ( {
			admin,
			frontendUtils,
			editor,
			page,
			requestUtils,
		} ) => {
			if (
				'isTaxonomyTemplate' in testData &&
				testData.isTaxonomyTemplate
			) {
				await admin.visitSiteEditor( {
					postType: 'wp_template',
				} );

				await editor.createTemplate( {
					templateName: testData.templateName,
				} );
			} else {
				await admin.visitSiteEditor( {
					postId: templateId,
					postType: testData.templateType,
					canvas: 'edit',
				} );
			}

			await editor.canvas.locator( 'body' ).waitFor( { timeout: 20000 } );

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

			await testData.visitPage( {
				admin,
				editor,
				frontendUtils,
				requestUtils,
				page,
			} );
			await expect( page.getByText( userText ).first() ).toBeVisible();

			// Verify the edition can be reverted.
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
			await expect( page.getByText( userText ) ).toBeHidden();
		} );

		if ( testData.fallbackTemplate ) {
			const fallbackTemplate = testData.fallbackTemplate;

			test( `"${ testData.templateName }" template defaults to the "${ fallbackTemplate.templateName }" template`, async ( {
				admin,
				frontendUtils,
				requestUtils,
				editor,
				page,
			} ) => {
				// Customize the fallback template and verify changes are visible.
				const fallbackTemplateId = `${ templateOrigin }//${ fallbackTemplate.templatePath }`;
				await requestUtils.updateTemplateContent(
					testData.templateType,
					fallbackTemplateId,
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
					page.getByText( fallbackTemplateUserText ).first()
				).toBeVisible();

				// Verify the edition can be reverted.
				await requestUtils.revertTemplate(
					testData.templateType,
					fallbackTemplateId
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
				).toBeHidden();
			} );
		}
	} );

	// Note: `wp_template` hierarchy is tested in `template-priority.block_theme.spec.ts`.
	const testToRun = CUSTOMIZABLE_WC_TEMPLATES.filter(
		( data ) =>
			data.templateType === 'wp_template_part' &&
			data.canBeOverriddenByThemes
	);

	for ( const testData of testToRun ) {
		const userText = `Hello World in the ${ testData.templateName } template`;
		const woocommerceTemplateUserText = `Hello World in the WooCommerce ${ testData.templateName } template`;

		test( `user-modified "${ testData.templateName }" template based on the theme template has priority over the user-modified template based on the default WooCommerce template`, async ( {
			page,
			admin,
			editor,
			requestUtils,
			frontendUtils,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ testData.templatePath }`,
				postType: testData.templateType,
				canvas: 'edit',
			} );

			await editor.canvas.locator( 'body' ).waitFor( { timeout: 20000 } );

			await editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: woocommerceTemplateUserText },
			} );
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			await requestUtils.activateTheme( BLOCK_THEME_WITH_TEMPLATES_SLUG );

			// Edit the theme template. The theme template is not
			// directly available from the UI, because the customized
			// one takes priority, so we go directly to its URL.
			await admin.visitSiteEditor( {
				postId: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${ testData.templatePath }`,
				postType: testData.templateType,
				canvas: 'edit',
			} );
			await editor.canvas.locator( 'body' ).waitFor( { timeout: 20000 } );

			await editor.insertBlock( {
				name: 'core/paragraph',
				attributes: { content: userText },
			} );
			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );

			// Verify only the customized theme template is returned for this slug.
			// See: https://github.com/woocommerce/woocommerce/issues/42220
			const templates = await requestUtils.getTemplates(
				testData.templateType
			);
			expect(
				templates.filter(
					( template ) => template.slug === testData.templatePath
				)
			).toHaveLength( 1 );

			// Verify the template is the one modified by the user based on the theme.
			await testData.visitPage( {
				admin,
				editor,
				frontendUtils,
				requestUtils,
				page,
			} );
			await expect( page.getByText( userText ).first() ).toBeVisible();
			await expect(
				page.getByText( woocommerceTemplateUserText )
			).toBeHidden();

			// Revert edition and verify the user-modified WC template is used.
			// Revert the exact template rather than selecting it by its display
			// name, which can be shared by templates from different origins.
			// See: https://github.com/woocommerce/woocommerce/issues/42220
			await requestUtils.revertTemplate(
				testData.templateType,
				`${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${ testData.templatePath }`
			);

			await testData.visitPage( {
				admin,
				editor,
				frontendUtils,
				requestUtils,
				page,
			} );

			await expect(
				page.getByText( woocommerceTemplateUserText ).first()
			).toBeVisible();
			await expect( page.getByText( userText ) ).toBeHidden();
		} );
	}
} );
