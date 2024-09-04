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

	test.describe( 'settings', () => {
		test( 'hides thumbnails', async ( { page, editor } ) => {
			await test.step( 'in editor', async () => {
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
			test( `positions thumbnails to the ${ position }`, async ( {
				page,
				editor,
			} ) => {
				const layoutClass = {
					left: 'left-of',
					bottom: 'below',
					right: 'right-of',
				}[ position ];

				await test.step( 'in editor', async () => {
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
							`[data-type="woocommerce/product-gallery-thumbnails"]:${ layoutClass }(
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
							`[data-block-name="woocommerce/product-gallery-thumbnails"]:${ layoutClass }(
								[data-block-name="woocommerce/product-gallery-large-image"]
							)`
						)
					).toBeVisible();
				} );
			} );
		}

		test( 'rounds the number of thumbnails to integer', async ( {
			page,
			editor,
		} ) => {
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
