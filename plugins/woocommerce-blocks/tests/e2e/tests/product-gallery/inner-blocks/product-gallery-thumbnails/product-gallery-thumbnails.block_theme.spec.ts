/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

test.describe( 'woocommerce/product-gallery-thumbnails', () => {
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
	} );

	test( 'Default render', async ( { page, editor } ) => {
		await test.step( 'editor', async () => {
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );
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
					'[data-type="woocommerce/product-gallery-thumbnails"]:left-of([data-type="woocommerce/product-gallery-large-image"])'
				)
			).toBeVisible();

			await editor.saveSiteEditorEntities();
		} );

		await test.step( 'frontend', async () => {
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
					'[data-block-name="woocommerce/product-gallery-thumbnails"]:left-of([data-block-name="woocommerce/product-gallery-large-image"])'
				)
			).toBeVisible();
		} );
	} );

	test.describe( 'Settings', () => {
		test( 'Hide thumbnails', async ( { page, editor } ) => {
			await test.step( 'editor', async () => {
				await editor.insertBlock( {
					name: 'woocommerce/product-gallery',
				} );

				const productGalleryBlock = editor.canvas.locator(
					'[data-type="woocommerce/product-gallery"]'
				);
				const thumbailsBlock = productGalleryBlock.locator(
					'[data-type="woocommerce/product-gallery-thumbnails"]'
				);
				await expect( thumbailsBlock ).toBeVisible();

				await editor.selectBlocks( thumbailsBlock );

				await editor.openDocumentSettingsSidebar();
				await page
					.getByLabel( 'Editor settings' )
					.locator( 'button[data-value="off"]' )
					.click();

				await expect( thumbailsBlock ).toBeHidden();

				await editor.saveSiteEditorEntities();
			} );

			await test.step( 'frontend', async () => {
				await page.goto( '/product/v-neck-t-shirt/' );

				const productGalleryBlock = page.locator(
					'[data-block-name="woocommerce/product-gallery"]'
				);

				await expect(
					productGalleryBlock.locator(
						'[data-block-name="woocommerce/product-gallery-large-image"]:visible'
					)
				).toBeVisible();

				await expect(
					productGalleryBlock.locator(
						'[data-block-name="woocommerce/product-gallery-thumbnails"]'
					)
				).toBeHidden();
			} );
		} );

		for ( const position of [ 'left', 'bottom', 'right' ] ) {
			test( `Position thumbnails to the ${ position }`, async ( {
				page,
				editor,
			} ) => {
				const layoutClass = {
					left: 'left-of',
					bottom: 'below',
					right: 'right-of',
				}[ position ];

				await test.step( 'editor', async () => {
					await editor.insertBlock( {
						name: 'woocommerce/product-gallery',
					} );

					const productGalleryBlock = editor.canvas.locator(
						'[data-type="woocommerce/product-gallery"]'
					);

					await editor.selectBlocks( productGalleryBlock );
					await editor.openDocumentSettingsSidebar();
					await page
						.getByLabel( 'Editor settings' )
						.locator( `button[data-value="${ position }"]` )
						.click();

					await expect(
						productGalleryBlock.locator(
							`[data-type="woocommerce/product-gallery-thumbnails"]:${ layoutClass }([data-type="woocommerce/product-gallery-large-image"])`
						)
					).toBeVisible();

					await editor.saveSiteEditorEntities();
				} );

				await test.step( 'frontend', async () => {
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
							`[data-block-name="woocommerce/product-gallery-thumbnails"]:${ layoutClass }([data-block-name="woocommerce/product-gallery-large-image"])`
						)
					).toBeVisible();
				} );
			} );
		}

		test( 'Number of thumbnails rounds to integer', async ( {
			page,
			editor,
		} ) => {
			await editor.insertBlock( {
				name: 'woocommerce/product-gallery',
			} );

			const productGalleryBlock = editor.canvas.locator(
				'[data-type="woocommerce/product-gallery"]'
			);

			await editor.selectBlocks( productGalleryBlock );

			await editor.openDocumentSettingsSidebar();
			const numberOfThumbnailInput = page
				.getByLabel( 'Editor settings' )
				.getByRole( 'spinbutton', {
					name: 'Number of Thumbnails',
				} );

			await numberOfThumbnailInput.fill( '4.2' );
			await page.keyboard.press( 'Enter' );

			const numberOfThumbnailsOnScreen = productGalleryBlock.locator(
				'.wc-block-product-gallery-thumbnails__thumbnail'
			);

			await expect( numberOfThumbnailsOnScreen ).toHaveCount( 4 );

			await numberOfThumbnailInput.fill( '4.7' );
			await page.keyboard.press( 'Enter' );

			await expect( numberOfThumbnailsOnScreen ).toHaveCount( 5 );
		} );
	} );
} );
