/**
 * External dependencies
 */
import {
	test as base,
	expect,
	CLASSIC_CHILD_THEME_WITH_BLOCK_TEMPLATE_PARTS_SUPPORT_SLUG,
	CLASSIC_CHILD_THEME_WITH_BLOCK_TEMPLATE_PARTS_SLUG,
	FrontendUtils,
} from '@woocommerce/e2e-utils';
import type { Page } from '@playwright/test';

class TestUtils {
	page: Page;
	frontendUtils: FrontendUtils;

	constructor( {
		page,
		frontendUtils,
	}: {
		page: Page;
		frontendUtils: FrontendUtils;
	} ) {
		this.page = page;
		this.frontendUtils = frontendUtils;
	}

	async openMiniCart() {
		await this.frontendUtils.goToShop();
		await this.frontendUtils.addToCart();
		await this.page.goto( '/mini-cart' );
		const miniCart = await this.frontendUtils.getBlockByName(
			'woocommerce/mini-cart'
		);
		await miniCart.click();
	}
}

const test = base.extend< { testUtils: TestUtils } >( {
	testUtils: async ( { page, frontendUtils }, use ) => {
		await use( new TestUtils( { page, frontendUtils } ) );
	},
} );

test.describe( 'Template part customization', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activateTheme(
			CLASSIC_CHILD_THEME_WITH_BLOCK_TEMPLATE_PARTS_SUPPORT_SLUG
		);
	} );

	const templateName = 'Mini-Cart';
	const templatePath = 'mini-cart';
	const userText = `Hello World in the ${ templateName } template`;
	const woocommerceTemplateUserText = `Hello World in the WooCommerce ${ templateName } template`;

	test.describe( `${ templateName } template`, () => {
		test( 'can be modified and reverted', async ( {
			admin,
			editor,
			page,
			testUtils,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ templatePath }`,
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
					name: `Editing template part: ${ templateName }`,
				} )
			).toBeVisible();

			await testUtils.openMiniCart();
			await expect( page.getByText( userText ).first() ).toBeVisible();

			// Verify the edition can be reverted.
			await admin.visitSiteEditor( {
				postType: 'wp_template_part',
			} );
			await editor.revertTemplate( {
				templateName,
			} );
			await testUtils.openMiniCart();
			await expect( page.getByText( userText ) ).toBeHidden();
		} );
	} );

	test.describe( `${ templateName } template`, () => {
		test( `user-modified ${ templateName } template based on the theme template has priority over the user-modified template based on the default WooCommerce template`, async ( {
			page,
			admin,
			editor,
			requestUtils,
			testUtils,
		} ) => {
			// Edit the WooCommerce default template
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//${ templatePath }`,
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
				postId: `${ CLASSIC_CHILD_THEME_WITH_BLOCK_TEMPLATE_PARTS_SLUG }//${ templatePath }`,
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
			await testUtils.openMiniCart();
			await expect( page.getByText( userText ).first() ).toBeVisible();
			await expect(
				page.getByText( woocommerceTemplateUserText )
			).toBeHidden();

			// Revert edition and verify the user-modified WC template is used.
			// Note: we need to revert it from the admin (instead of calling
			// `deleteAllTemplates()`). This way, we verify there are no
			// duplicate templates with the same name.
			// See: https://github.com/woocommerce/woocommerce/issues/42220
			await admin.visitSiteEditor( {
				postType: 'wp_template_part',
			} );

			await editor.revertTemplate( {
				templateName,
			} );

			await testUtils.openMiniCart();

			await expect(
				page.getByText( woocommerceTemplateUserText ).first()
			).toBeVisible();
			await expect( page.getByText( userText ) ).toBeHidden();
		} );
	} );
} );
