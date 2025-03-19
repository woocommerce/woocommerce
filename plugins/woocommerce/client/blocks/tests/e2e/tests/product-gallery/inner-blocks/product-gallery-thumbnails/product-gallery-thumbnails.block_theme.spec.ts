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

	test( 'all thumbnails are rendered correctly in frontend', async ( {
		page,
	} ) => {
		// Navigate to the product page
		await page.goto( '/product/v-neck-t-shirt/' );

		const productGalleryBlock = page.locator(
			'[data-block-name="woocommerce/product-gallery"]'
		);

		// Get the thumbnails container
		const thumbnailsContainer = productGalleryBlock.locator(
			'[data-block-name="woocommerce/product-gallery-thumbnails"]'
		);

		// Get all thumbnail elements
		const thumbnails = thumbnailsContainer.locator(
			'.wc-block-product-gallery-thumbnails__thumbnail'
		);

		// Verify thumbnails container is visible
		await expect( thumbnailsContainer ).toBeVisible();

		// Get the count of thumbnails
		const thumbnailCount = await thumbnails.count();

		// Ensure we have at least one thumbnail
		expect( thumbnailCount ).toBeGreaterThan( 0 );

		// Verify each thumbnail has an image and it's visible
		for ( let i = 0; i < thumbnailCount; i++ ) {
			const thumbnail = thumbnails.nth( i );
			const thumbnailImage = thumbnail.locator( 'img' );

			await expect( thumbnail ).toBeVisible();
			await expect( thumbnailImage ).toBeVisible();

			// Verify the image has required attributes
			await expect( thumbnailImage ).toHaveAttribute( 'src' );
			await expect( thumbnailImage ).toHaveAttribute( 'data-image-id' );
		}

		// Verify thumbnails are within a scrollable container
		const scrollableContainer = thumbnailsContainer.locator(
			'.wc-block-product-gallery-thumbnails__scrollable'
		);
		await expect( scrollableContainer ).toBeVisible();
	} );
} );
