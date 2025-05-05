/**
 * External dependencies
 */
import {
	test as base,
	expect,
	BLOCK_THEME_WITH_TEMPLATES_SLUG,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from './add-to-cart-with-options.page';

const test = base.extend< { pageObject: AddToCartWithOptionsPage } >( {
	pageObject: async ( { page, admin, editor, requestUtils }, use ) => {
		const pageObject = new AddToCartWithOptionsPage( {
			page,
			admin,
			editor,
			requestUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( `Add to Cart with Options Block (block theme with templates)`, () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activateTheme( BLOCK_THEME_WITH_TEMPLATES_SLUG );
	} );

	test( 'allows modifying the template parts', async ( {
		page,
		pageObject,
		editor,
		admin,
		requestUtils,
	} ) => {
		await requestUtils.setFeatureFlag( 'experimental-blocks', true );
		await requestUtils.setFeatureFlag( 'blockified-add-to-cart', true );

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//single-product',
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( { name: pageObject.BLOCK_SLUG } );

		await pageObject.switchProductType( 'External/Affiliate product' );

		await pageObject.insertParagraphInTemplatePart(
			'This is a test paragraph added to the Add to Cart with Options template part.'
		);

		await editor.saveSiteEditorEntities();

		await page.goto( '/product/wordpress-pennant/' );

		await expect(
			page.getByText(
				'This is a test paragraph added to the Add to Cart with Options template part.'
			)
		).toBeVisible();

		await expect(
			page.getByText(
				'External Product Add to cart with Options template loaded from theme'
			)
		).toBeVisible();
	} );
} );
