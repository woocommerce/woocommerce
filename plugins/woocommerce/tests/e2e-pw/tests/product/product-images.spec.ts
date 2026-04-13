/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { test as baseTest, expect } from '../../fixtures/fixtures';
import { ADMIN_STATE_PATH } from '../../playwright.config';

async function addImageFromLibrary(
	page: Page,
	imageName: string,
	actionButtonName = 'Add to product'
) {
	await page.getByRole( 'tab', { name: 'Media Library' } ).click();
	await page.getByRole( 'searchbox', { name: 'Search' } ).fill( imageName );
	const imageLocator = page.getByLabel( imageName ).nth( 0 );
	await imageLocator.click();
	const dataId = await imageLocator.getAttribute( 'data-id' );
	await expect( imageLocator ).toBeChecked();
	await page.getByRole( 'button', { name: actionButtonName } ).click();
	return dataId;
}

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	product: async ( { restApi }, use ) => {
		let product = {
			id: 0,
			name: `Product ${ Date.now() }`,
			type: 'simple',
			regular_price: '12.99',
			sale_price: '11.59',
		};

		await restApi
			.post( `${ WC_API_PATH }/products`, product )
			.then( ( response ) => {
				product = response.data;
			} );

		await use( product );

		// Cleanup
		await restApi.delete( `${ WC_API_PATH }/products/${ product.id }`, {
			force: true,
		} );
	},
	productWithImage: async ( { restApi, product }, use ) => {
		let productWithImage;
		await restApi
			.put( `${ WC_API_PATH }/products/${ product.id }`, {
				images: [
					{
						src: 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_2_front.jpg',
					},
				],
			} )
			.then( ( response ) => {
				productWithImage = response.data;
			} );

		await use( productWithImage );
	},
	productWithGallery: async ( { restApi, product }, use ) => {
		let productWithGallery;
		await restApi
			.put( `${ WC_API_PATH }/products/${ product.id }`, {
				images: [
					{
						src: 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_2_front.jpg',
					},
					{
						src: 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_2_back.jpg',
					},
					{
						src: 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_3_front.jpg',
					},
				],
			} )
			.then( ( response ) => {
				productWithGallery = response.data;
			} );

		await use( productWithGallery );
	},
} );

test.describe( 'Products > Product Images', () => {
	test( 'can set product image', async ( { page, product } ) => {
		await test.step( 'Navigate to product edit page', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
		} );

		await test.step( 'Set product image', async () => {
			await page.locator( '#wc-product-images__add-slot' ).click();
			await addImageFromLibrary( page, 'image-01' );

			await expect(
				page.locator(
					'.wc-product-images__image--featured img[src*="image-01"]'
				)
			).toBeVisible();

			await page
				.locator( '#publishing-action' )
				.getByRole( 'button', { name: 'Update' } )
				.click();
		} );

		await test.step( 'Verify product image was set', async () => {
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();

			await page.goto( product.permalink );
			await expect(
				page.locator( `img.wp-post-image[src*="image-01"]` )
			).toBeVisible();
		} );
	} );

	test( 'can update the product image', async ( {
		page,
		productWithImage,
	} ) => {
		await test.step( 'Navigate to product edit page', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ productWithImage.id }&action=edit`
			);
		} );

		await test.step( 'Add a new image and remove the old one', async () => {
			await page.locator( '#wc-product-images__add-slot' ).click();
			await addImageFromLibrary( page, 'image-02' );

			// Remove the original featured image.
			await page.locator( '.wc-product-images__image--featured' ).hover();
			await page
				.locator(
					'.wc-product-images__image--featured .wc-product-images__remove'
				)
				.click();

			// The new image should now be featured.
			await expect(
				page.locator(
					'.wc-product-images__image--featured img[src*="image-02"]'
				)
			).toBeVisible();

			await page
				.locator( '#publishing-action' )
				.getByRole( 'button', { name: 'Update' } )
				.click();
		} );

		await test.step( 'Verify product image was updated', async () => {
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();

			await page.goto( productWithImage.permalink );
			await expect(
				page.locator( `img.wp-post-image[src*="image-02"]` )
			).toBeVisible();
		} );
	} );

	test( 'can delete the product image', async ( {
		page,
		productWithImage,
	} ) => {
		await test.step( 'Navigate to product edit page', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ productWithImage.id }&action=edit`
			);
		} );

		await test.step( 'Remove product image', async () => {
			await page.locator( '.wc-product-images__image--featured' ).hover();
			await page
				.locator(
					'.wc-product-images__image--featured .wc-product-images__remove'
				)
				.click();

			await expect(
				page.locator( '.wc-product-images__add-slot--featured' )
			).toBeVisible();

			await page
				.locator( '#publishing-action' )
				.getByRole( 'button', { name: 'Update' } )
				.click();
		} );

		await test.step( 'Verify product image was removed', async () => {
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();

			await page.goto( productWithImage.permalink );
			await expect(
				page.getByAltText( 'Awaiting product image' )
			).toBeVisible();
		} );
	} );

	test( 'can create a product gallery', async ( {
		page,
		productWithImage,
	} ) => {
		const images = [ 'image-02', 'image-03' ];

		await test.step( 'Navigate to product edit page', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ productWithImage.id }&action=edit`
			);
		} );

		await test.step( 'Add product gallery images', async () => {
			const imageSelector = '.wc-product-images__image';
			let initialImagesCount = await page
				.locator( imageSelector )
				.count();

			for ( const image of images ) {
				await page.locator( '#wc-product-images__add-slot' ).click();
				await addImageFromLibrary( page, image );

				const currentImagesCount = await page
					.locator( imageSelector )
					.count();
				await expect(
					currentImagesCount,
					'number of images should increase'
				).toEqual( initialImagesCount + 1 );
				initialImagesCount = currentImagesCount;
			}

			await page
				.locator( '#publishing-action' )
				.getByRole( 'button', { name: 'Update' } )
				.click();
		} );

		await test.step( 'Verify product gallery', async () => {
			await page.goto( productWithImage.permalink );
			await page
				.locator( '.flex-viewport' )
				.waitFor( { state: 'attached' } );
			const galleryImages = page.locator(
				`.woocommerce-product-gallery__image:not(.clone) img:not(.zoomImg)`
			);
			await expect(
				galleryImages,
				'all gallery images should be visible'
			).toHaveCount( images.length + 1 );
			await expect( galleryImages.first() ).toBeVisible();
		} );
	} );

	test( 'can update a product gallery', async ( {
		page,
		productWithGallery,
	} ) => {
		let imagesCount;

		await test.step( 'Navigate to product edit page', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ productWithGallery.id }&action=edit`
			);
		} );

		await test.step( 'Remove an image from product gallery', async () => {
			const imageSelector = '.wc-product-images__image';
			imagesCount = await page.locator( imageSelector ).count();

			// Remove the first gallery image (second image overall).
			const galleryImage = page
				.locator( '.wc-product-images__image--gallery' )
				.first();
			await galleryImage.hover();
			await galleryImage.locator( '.wc-product-images__remove' ).click();

			await expect(
				await page.locator( imageSelector ).count(),
				'number of images should decrease'
			).toEqual( imagesCount - 1 );

			await page
				.locator( '#publishing-action' )
				.getByRole( 'button', { name: 'Update' } )
				.click();
		} );

		await test.step( 'Verify product gallery', async () => {
			await page.goto( productWithGallery.permalink );
			await page
				.locator( '.flex-viewport' )
				.waitFor( { state: 'attached' } );
			const galleryImages = page.locator(
				`.woocommerce-product-gallery__image:not(.clone) img:not(.zoomImg)`
			);
			await expect(
				galleryImages,
				'gallery should show remaining images after removing one'
			).toHaveCount( imagesCount - 1 );
			await expect( galleryImages.first() ).toBeVisible();
		} );
	} );
} );
