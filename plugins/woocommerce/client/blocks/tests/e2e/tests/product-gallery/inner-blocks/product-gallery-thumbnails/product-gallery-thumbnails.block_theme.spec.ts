/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

test.describe( 'Product Gallery Thumbnails block', () => {
	test.beforeEach(
		async ( { admin, editor, requestUtils, wpCoreVersion } ) => {
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

			// TODO: WP 7.0 compat - Custom HTML block content is inside an iframe
			// since WP 7.0. Simplify when WP 7.0 is the minimum supported version.
			const placeholderLocator =
				wpCoreVersion >= 7
					? editor.canvas
							.frameLocator( 'iframe' )
							.getByText( 'placeholder' )
					: editor.canvas.getByText( 'placeholder' );

			await expect( placeholderLocator ).toBeVisible();

			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
		}
	);

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
		await test.step( 'in editor', async () => {
			const viewerBlock = editor.canvas.locator(
				'[data-type="woocommerce/product-gallery-large-image"]'
			);
			const thumbnailsBlock = editor.canvas.locator(
				'[data-type="woocommerce/product-gallery-thumbnails"]'
			);
			const thumbnailsSizeInput = page.getByLabel( 'Thumbnail Size' );

			// Open block settings
			await thumbnailsBlock.click();
			await editor.openDocumentSettingsSidebar();

			await expect( thumbnailsSizeInput ).toHaveValue( '25' );
			await expect( async () => {
				const viewerBox = await viewerBlock.boundingBox();
				const thumbnailsBox = await thumbnailsBlock.boundingBox();
				const viewerWidth = viewerBox?.width ?? 0;
				const thumbnailsWidth = thumbnailsBox?.width ?? 0;

				expect( thumbnailsWidth ).toBeCloseTo( viewerWidth * 0.25, 0 );
			} ).toPass( { timeout: 3_000 } );

			await expect( async () => {
				// Set size to 10%
				await thumbnailsSizeInput.fill( '10' );

				const viewerBox = await viewerBlock.boundingBox();
				const thumbnailsBox = await thumbnailsBlock.boundingBox();
				const viewerWidth = viewerBox?.width ?? 0;
				const thumbnailsWidth = thumbnailsBox?.width ?? 0;

				expect( thumbnailsWidth ).toBeCloseTo( viewerWidth * 0.1, 0 );
			} ).toPass( { timeout: 3_000 } );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
		} );

		await test.step( 'in frontend', async () => {
			await page.goto( '/product/hoodie/' );

			const thumbnailsBlock = page.locator(
				'[data-block-name="woocommerce/product-gallery-thumbnails"]'
			);
			const viewerBlock = page.locator(
				'[data-block-name="woocommerce/product-gallery-large-image"]'
			);

			await expect( async () => {
				await page.reload();

				const viewerBox = await viewerBlock.boundingBox();
				const thumbnailsBox = await thumbnailsBlock.boundingBox();
				const viewerWidth = viewerBox?.width ?? 0;
				const thumbnailsWidth = thumbnailsBox?.width ?? 0;

				expect( thumbnailsWidth ).toBeCloseTo( viewerWidth * 0.1, 0 );
			} ).toPass( { timeout: 3_000 } );
		} );
	} );

	test( 'thumbnails are scrollable and last thumbnail is reachable', async ( {
		page,
		editor,
	} ) => {
		await test.step( 'in editor', async () => {
			const viewerBlock = editor.canvas.locator(
				'[data-type="woocommerce/product-gallery-large-image"]'
			);
			const thumbnailsBlock = editor.canvas.locator(
				'[data-type="woocommerce/product-gallery-thumbnails"]'
			);
			const thumbnailsSizeInput = page.getByLabel( 'Thumbnail Size' );

			// Open block settings
			await thumbnailsBlock.click();
			await editor.openDocumentSettingsSidebar();

			await expect( thumbnailsSizeInput ).toHaveValue( '25' );
			await expect( async () => {
				// Set size to 10%
				await thumbnailsSizeInput.fill( '50' );

				const viewerBox = await viewerBlock.boundingBox();
				const thumbnailsBox = await thumbnailsBlock.boundingBox();
				const viewerWidth = viewerBox?.width ?? 0;
				const thumbnailsWidth = thumbnailsBox?.width ?? 0;

				expect( thumbnailsWidth ).toBeCloseTo( viewerWidth * 0.5, 0 );
			} ).toPass( { timeout: 3_000 } );

			await editor.saveSiteEditorEntities( {
				isOnlyCurrentEntityDirty: true,
			} );
		} );

		// TODO: Frontend overflow assertion requires a variation with a
		// multi-image gallery now that variable products show only the
		// parent's featured image until a variation is selected. Re-enable
		// when a fixture sets `gallery_image_ids` on a Hoodie variation.
	} );
} );
