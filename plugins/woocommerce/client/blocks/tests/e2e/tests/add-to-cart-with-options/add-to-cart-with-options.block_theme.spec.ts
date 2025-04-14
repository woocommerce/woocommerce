/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

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

test.describe( 'Add to Cart with Options Block', () => {
	test( 'allows modifying the template parts', async ( {
		page,
		pageObject,
		editor,
		admin,
	} ) => {
		await pageObject.setFeatureFlags();

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//single-product',
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( { name: pageObject.BLOCK_SLUG } );

		await pageObject.insertParagraphInTemplatePart(
			'This is a test paragraph added to the Add to Cart with Options template part.'
		);

		await editor.saveSiteEditorEntities();

		await page.goto( '/product/cap' );

		await expect(
			page.getByText(
				'This is a test paragraph added to the Add to Cart with Options template part.'
			)
		).toBeVisible();
	} );

	test( 'allows switching to 3rd-party product types', async ( {
		pageObject,
		editor,
		admin,
		requestUtils,
	} ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-custom-product-type'
		);

		await pageObject.setFeatureFlags();

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//single-product',
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await editor.insertBlock( { name: pageObject.BLOCK_SLUG } );

		await pageObject.switchProductType( 'Custom Product Type' );

		const block = editor.canvas.getByLabel(
			`Block: ${ pageObject.BLOCK_NAME }`
		);
		const skeleton = block.locator( '.wc-block-components-skeleton' );
		await expect( skeleton ).toBeVisible();
	} );
} );
