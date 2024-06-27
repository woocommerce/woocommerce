/**
 * External dependencies
 */
import {
	test,
	expect,
	CLASSIC_CHILD_THEME_WITH_BLOCK_TEMPLATE_PARTS_SLUG,
	FrontendUtils,
} from '@woocommerce/e2e-utils';

import type { Page } from '@playwright/test';

test.describe( 'Template part customization', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activateTheme(
			CLASSIC_CHILD_THEME_WITH_BLOCK_TEMPLATE_PARTS_SLUG
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

	test.describe( `${ testData.templateName } template`, () => {
		test( "theme template has priority over WooCommerce's and can be modified", async ( {
			admin,
			editor,
			frontendUtils,
			page,
		} ) => {
			// Edit the theme template.
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
			// Verify template name didn't change.
			// See: https://github.com/woocommerce/woocommerce/issues/42221
			await expect(
				page.getByRole( 'heading', {
					name: `Editing template part: ${ testData.templateName }`,
				} )
			).toBeVisible();

			// Verify the template is the one modified by the user.
			await testData.visitPage( { frontendUtils, page } );
			await expect( page.getByText( userText ).first() ).toBeVisible();

			// Revert edition and verify the template from the theme is used.
			await admin.visitSiteEditor( {
				postType: 'wp_template_part',
			} );
			await editor.revertTemplateCustomizations( {
				templateName: testData.templateName,
				templateType: 'wp_template_part',
			} );
			await testData.visitPage( { frontendUtils, page } );

			await expect(
				page
					.getByText(
						`${ testData.templateName } template loaded from classic theme with template part support`
					)
					.first()
			).toBeVisible();
			await expect( page.getByText( userText ) ).toHaveCount( 0 );
		} );
	} );
} );
