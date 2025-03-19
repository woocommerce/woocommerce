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

			const thumbnailsCount = await thumbnailsContainer
				.locator( '.wc-block-product-gallery-thumbnails__thumbnail' )
				.count();

			expect( thumbnailsCount ).toBe( 4 );
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

		// Verify 10% size class is applied
		await expect( thumbnailsBlock ).toHaveClass(
			/wc-block-product-gallery-thumbnails--thumbnails-size-10/
		);

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie/' );

		const thumbnailsContainer = page.locator(
			'[data-block-name="woocommerce/product-gallery-thumbnails"]'
		);

		// Verify the 10% size class is present
		await expect( thumbnailsContainer ).toHaveClass(
			/wc-block-product-gallery-thumbnails--thumbnails-size-10/
		);

		// The width should be approximately 9% of its parent.
		// 100% is x + x/10 where x is large image width.
		const containerWidth = await thumbnailsContainer.evaluate( ( el ) => {
			return el.clientWidth || 0;
		} );

		const parentWidth = await thumbnailsContainer.evaluate( ( el ) => {
			return el.parentElement?.clientWidth || 0;
		} );

		const ratio = containerWidth / parentWidth;

		// Allow for some small rounding differences
		expect( ratio ).toBeGreaterThan( 0.08 );
		expect( ratio ).toBeLessThan( 0.1 );
	} );
} );
