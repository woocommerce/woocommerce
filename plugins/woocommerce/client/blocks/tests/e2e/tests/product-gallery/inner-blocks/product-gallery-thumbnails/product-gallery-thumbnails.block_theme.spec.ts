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

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
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
		} );

		await test.step( 'in frontend', async () => {
			await page.goto( '/product/hoodie/' );

			const productGalleryBlock = page.locator(
				'[data-block-name="woocommerce/product-gallery"]'
			);

			const thumbnailsContainer = productGalleryBlock.locator(
				'[data-block-name="woocommerce/product-gallery-thumbnails"]'
			);

			await expect( thumbnailsContainer ).toBeVisible();

			await expect(
				productGalleryBlock.locator(
					`[data-block-name="woocommerce/product-gallery-thumbnails"]:left-of(
						[data-block-name="woocommerce/product-gallery-large-image"]
					)`
				)
			).toBeVisible();

			const thumbnailsCount = thumbnailsContainer.locator(
				'.wc-block-product-gallery-thumbnails__thumbnail'
			);

			await expect( thumbnailsCount ).toHaveCount( 4 );
		} );
	} );

	test( 'thumbnail size settings work correctly', async ( {
		page,
		editor,
	} ) => {
		const thumbnailsBlock = editor.canvas.locator(
			'[data-type="woocommerce/product-gallery-thumbnails"]'
		);

		// Open block settings
		await thumbnailsBlock.click();
		await editor.openDocumentSettingsSidebar();

		// Set size to 10%
		await page.getByLabel( 'Thumbnail Size' ).fill( '10' );

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie/' );

		const thumbnailsContainer = page.locator(
			'[data-block-name="woocommerce/product-gallery-thumbnails"]'
		);

		await expect( async () => {
			// The width should be approximately 9% of its parent.
			// 100% is x + x/10 where x is large image width.
			const containerWidth = await thumbnailsContainer.evaluate(
				( el ) => {
					return el.clientWidth || 0;
				}
			);

			const parentWidth = await thumbnailsContainer.evaluate( ( el ) => {
				return el.parentElement?.clientWidth || 0;
			} );

			const ratio = containerWidth / parentWidth;

			// Allow for some small rounding differences
			expect( ratio ).toBeGreaterThan( 0.08 );
			expect( ratio ).toBeLessThan( 0.1 );
		} ).toPass( { timeout: 5_000 } );
	} );

	test( 'thumbnails are scrollable and last thumbnail is reachable', async ( {
		page,
		editor,
	} ) => {
		const thumbnailsBlock = editor.canvas.locator(
			'[data-type="woocommerce/product-gallery-thumbnails"]'
		);

		// Open block settings and set size to 50% to make thumbnails scrollable
		await thumbnailsBlock.click();
		await editor.openDocumentSettingsSidebar();
		await page.getByLabel( 'Thumbnail Size' ).fill( '50' );

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie/' );

		const thumbnailsContainer = page.locator(
			'[data-block-name="woocommerce/product-gallery-thumbnails"]'
		);

		const scrollableContainer = page.locator(
			'.wc-block-product-gallery-thumbnails__scrollable'
		);

		// Get all thumbnails
		const thumbnails = scrollableContainer.locator(
			'.wc-block-product-gallery-thumbnails__thumbnail'
		);

		// Get the last thumbnail
		const lastThumbnail = thumbnails.last();

		// Check if overflow classes are present initially
		await expect( thumbnailsContainer ).toHaveClass(
			/wc-block-product-gallery-thumbnails--overflow-bottom/
		);

		// Scroll to the last thumbnail
		await lastThumbnail.scrollIntoViewIfNeeded();

		// Verify the last thumbnail is visible
		await expect( lastThumbnail ).toBeVisible();

		// After scrolling to the end, the bottom overflow should be gone
		await expect( thumbnailsContainer ).not.toHaveClass(
			/wc-block-product-gallery-thumbnails--overflow-bottom/
		);
	} );
} );
