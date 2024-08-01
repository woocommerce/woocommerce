/**
 * External dependencies
 */
import {
	test as base,
	expect,
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
			CLASSIC_CHILD_THEME_WITH_BLOCK_TEMPLATE_PARTS_SLUG
		);
	} );

	const templateName = 'Mini-Cart';
	const templatePath = 'mini-cart';

	const userText = `Hello World in the ${ templateName } template`;

	test.describe( `${ templateName } template`, () => {
		test( "theme template has priority over WooCommerce's and can be modified", async ( {
			admin,
			editor,
			page,
			testUtils,
		} ) => {
			// Edit the theme template.
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
			// Verify template name didn't change.
			// See: https://github.com/woocommerce/woocommerce/issues/42221
			await expect(
				page.getByRole( 'heading', {
					name: `Editing template part: ${ templateName }`,
				} )
			).toBeVisible();

			// Verify the template is the one modified by the user.
			await testUtils.openMiniCart();
			await expect( page.getByText( userText ).first() ).toBeVisible();

			// Revert edition and verify the template from the theme is used.
			await admin.visitSiteEditor( {
				postType: 'wp_template_part',
			} );
			await editor.revertTemplate( {
				templateName,
			} );
			await testUtils.openMiniCart();

			await expect(
				page
					.getByText(
						`${ templateName } template loaded from classic theme with template part support`
					)
					.first()
			).toBeVisible();
			await expect( page.getByText( userText ) ).toBeHidden();
		} );
	} );
} );
