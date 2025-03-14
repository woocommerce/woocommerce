/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

test.describe( 'Product Gallery Thumbnails block', () => {
	test.beforeEach( async ( { admin, editor, requestUtils } ) => {
		const template = await requestUtils.createTemplate( 'wp_template', {
			slug: 'single-product',
			title: 'Custom Single Product',
			content: 'placeholder',
		} );

		await admin.visitSiteEditor( {
			postId: template.id,
			postType: 'wp_template',
			canvas: 'edit',
		} );

		await expect( editor.canvas.getByText( 'placeholder' ) ).toBeVisible();

		await editor.insertBlock( {
			name: 'woocommerce/product-gallery',
		} );
	} );

	test( 'renders as expected', async ( { page, editor } ) => {
		await test.step( 'in editor', async () => {
			const productGalleryBlock = editor.canvas.locator(
				'[data-type="woocommerce/product-gallery"]'
			);

			await expect(
				productGalleryBlock.locator(
					'[data-type="woocommerce/product-gallery-thumbnails"]'
				)
			).toBeVisible();

			await expect(
				productGalleryBlock.locator(
					`[data-type="woocommerce/product-gallery-thumbnails"]:left-of(
						[data-type="woocommerce/product-gallery-large-image"]
					)`
				)
			).toBeVisible();

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
		} );

		await test.step( 'in frontend', async () => {
			await page.goto( '/product/v-neck-t-shirt/' );
			const productGalleryBlock = page.locator(
				'[data-block-name="woocommerce/product-gallery"]'
			);

			await expect(
				productGalleryBlock.locator(
					'[data-block-name="woocommerce/product-gallery-thumbnails"]'
				)
			).toBeVisible();

			await expect(
				productGalleryBlock.locator(
					`[data-block-name="woocommerce/product-gallery-thumbnails"]:left-of(
						[data-block-name="woocommerce/product-gallery-large-image"]
					)`
				)
			).toBeVisible();
		} );
	} );
} );
