/**
 * External dependencies
 */
import {
	test,
	expect,
	CLASSIC_CHILD_THEME_WITH_BLOCK_TEMPLATE_PARTS_SUPPORT_SLUG,
	CLASSIC_CHILD_THEME_WITH_BLOCK_TEMPLATE_PARTS_SLUG,
	FrontendUtils,
} from '@woocommerce/e2e-utils';
import type { Page } from '@playwright/test';

test.describe( 'Template part customization', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activateTheme(
			CLASSIC_CHILD_THEME_WITH_BLOCK_TEMPLATE_PARTS_SUPPORT_SLUG
		);
	} );

	const testData = {
		visitPage: async ( {
			frontendUtils,
			page,
		}: {
			frontendUtils: FrontendUtils;
			page: Page;
		} ) => {
			await frontendUtils.goToShop();
			await frontendUtils.addToCart();
			await page.goto( '/mini-cart' );
			const block = await frontendUtils.getBlockByName(
				'woocommerce/mini-cart'
			);
			await block.click();
		},
		templateName: 'Mini-Cart',
		templatePath: 'mini-cart',
	};
	const userText = `Hello World in the ${ testData.templateName } template`;
	const woocommerceTemplateUserText = `Hello World in the WooCommerce ${ testData.templateName } template`;

	test.describe( `${ testData.templateName } template`, () => {
		test( 'can be modified and reverted', async ( {
			admin,
			frontendUtils,
			editor,
			page,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ testData.templatePath }`,
				postType: 'wp_template_part',
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
					name: `Editing template part: ${ testData.templateName }`,
				} )
			).toBeVisible();

			await testData.visitPage( { frontendUtils, page } );
			await expect( page.getByText( userText ).first() ).toBeVisible();

			// Verify the edition can be reverted.
			await admin.visitSiteEditor( {
				postType: 'wp_template_part',
			} );
			await editor.revertTemplateCustomizations( {
				templateName: testData.templateName,
				templateType: 'wp_template_part',
			} );
			await testData.visitPage( { frontendUtils, page } );
			await expect( page.getByText( userText ) ).toHaveCount( 0 );
		} );
	} );

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
				postType: 'wp_template_part',
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
				CLASSIC_CHILD_THEME_WITH_BLOCK_TEMPLATE_PARTS_SLUG
			);

			// Edit the theme template. The theme template is not
			// directly available from the UI, because the customized
			// one takes priority, so we go directly to its URL.
			await admin.visitSiteEditor( {
				postId: `${ CLASSIC_CHILD_THEME_WITH_BLOCK_TEMPLATE_PARTS_SLUG }//${ testData.templatePath }`,
				postType: 'wp_template_part',
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
			await expect( page.getByText( userText ).first() ).toBeVisible();
			await expect(
				page.getByText( woocommerceTemplateUserText )
			).toHaveCount( 0 );

			// Revert edition and verify the user-modified WC template is used.
			// Note: we need to revert it from the admin (instead of calling
			// `deleteAllTemplates()`). This way, we verify there are no
			// duplicate templates with the same name.
			// See: https://github.com/woocommerce/woocommerce/issues/42220
			await admin.visitSiteEditor( {
				postType: 'wp_template_part',
			} );

			await editor.revertTemplateCustomizations( {
				templateName: testData.templateName,
				templateType: 'wp_template_part',
			} );

			await testData.visitPage( { frontendUtils, page } );

			await expect(
				page.getByText( woocommerceTemplateUserText ).first()
			).toBeVisible();
			await expect( page.getByText( userText ) ).toHaveCount( 0 );
		} );
	} );
} );
