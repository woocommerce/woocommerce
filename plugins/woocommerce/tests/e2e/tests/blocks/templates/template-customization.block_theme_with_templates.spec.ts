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
		const templateTypeName =
			testData.templateType === 'wp_template'
				? 'template'
				: 'template part';

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
					postId: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${ testData.templatePath }`,
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
				await admin.visitSiteEditor( {
					postType: testData.templateType,
				} );
				await editor.revertTemplate( {
					templateName: testData.templateName,
				} );

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
		} );
	} );

	const fallbackPriorityTemplates = CUSTOMIZABLE_WC_TEMPLATES.filter(
		( testData ) =>
			testData.canBeOverriddenByThemes && testData.fallbackTemplate
	);
	const expectedTemplateNames = [
		'Products by Attribute',
		'Products by Category',
		'Products by Tag',
	];
	const hasExpectedFallbackPriorityTemplates =
		fallbackPriorityTemplates.length === expectedTemplateNames.length &&
		fallbackPriorityTemplates.every(
			( testData, index ) =>
				testData.templateName === expectedTemplateNames[ index ] &&
				testData.templateType === 'wp_template' &&
				testData.fallbackTemplate?.templateName === 'Product Catalog' &&
				testData.fallbackTemplate.templatePath === 'archive-product'
		);

	if ( ! hasExpectedFallbackPriorityTemplates ) {
		throw new Error(
			'Expected Attribute, Category, and Tag to share the Product Catalog fallback.'
		);
	}

	test( 'theme taxonomy templates have priority over user-modified Product Catalog template', async ( {
		admin,
		frontendUtils,
		requestUtils,
		editor,
		page,
	} ) => {
		const fallbackTemplate =
			fallbackPriorityTemplates[ 0 ].fallbackTemplate;

		// Edit the shared fallback and verify its changes are not visible,
		// as the more specific theme templates have priority.
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_WITH_TEMPLATES_SLUG }//${ fallbackTemplate?.templatePath }`,
			postType: fallbackPriorityTemplates[ 0 ].templateType,
			canvas: 'edit',
		} );

		await editor.canvas.locator( 'body' ).waitFor( { timeout: 20000 } );

		for ( const testData of fallbackPriorityTemplates ) {
			await editor.insertBlock( {
				name: 'core/paragraph',
				attributes: {
					content: `Hello World in the fallback ${ testData.templateName } template`,
				},
			} );
		}

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		for ( const testData of fallbackPriorityTemplates ) {
			await test.step( `${ testData.templateName } template: theme template has priority over user-modified ${ testData.fallbackTemplate?.templateName } template`, async () => {
				await testData.visitPage( {
					admin,
					editor,
					frontendUtils,
					requestUtils,
					page,
				} );
				await expect(
					page.getByText(
						`Hello World in the fallback ${ testData.templateName } template`
					)
				).toHaveCount( 0 );
			} );
		}
	} );
} );
