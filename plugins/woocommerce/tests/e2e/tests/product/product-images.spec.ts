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
import { getMediaBySlug } from '../../utils/media';

async function addImageFromLibrary(
	page: Page,
	imageName: string,
	actionButtonName: string
) {
	// The WordPress media modal can be slow to open under parallel load; wait
	// for its "Media Library" tab with extra headroom beyond the default action
	// timeout before clicking, so a slow-opening modal doesn't fail the test.
	const mediaLibraryTab = page.getByRole( 'tab', { name: 'Media Library' } );
	await mediaLibraryTab.waitFor( { state: 'visible', timeout: 30_000 } );
	await mediaLibraryTab.click();
	await page.getByRole( 'searchbox', { name: 'Search' } ).fill( imageName );
	const imageLocator = page.getByLabel( imageName ).nth( 0 );
	await imageLocator.click();
	const dataId = await imageLocator.getAttribute( 'data-id' );
	await expect( imageLocator ).toBeChecked();
	await page.getByRole( 'button', { name: actionButtonName } ).click();
	await expect( page.locator( '.media-modal' ) ).toBeHidden();
	if ( ! dataId ) {
		throw new Error( `Media library item ${ imageName } has no data-id.` );
	}
	return dataId;
}

async function expectProductImageHelpTipNextTo(
	page: Page,
	actionName: 'Remove product image' | 'Set product image'
) {
	const productImageBox = page.locator( '#postimagediv' );
	const action = productImageBox
		.getByRole( 'link', { name: actionName } )
		.or( productImageBox.getByRole( 'button', { name: actionName } ) );
	const actionId =
		actionName === 'Remove product image'
			? 'remove-post-thumbnail'
			: 'set-post-thumbnail';
	// The fix inserts the tip immediately after the active action, so assert
	// that DOM adjacency directly instead of comparing rendered coordinates,
	// which vary with the platform font stack.
	const helpTip = productImageBox.locator(
		`#${ actionId } + .woocommerce-product-image-help-tip`
	);

	await expect( action ).toBeVisible();
	await expect( helpTip ).toBeVisible();
	await expect( helpTip ).toHaveAttribute( 'tabindex', '0' );
	await expect( helpTip ).toHaveAttribute(
		'aria-label',
		/For best results, upload JPEG or PNG files/
	);
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
		const { id: imageId } = await getMediaBySlug( 'image-01' );
		let productWithImage;
		await restApi
			.put( `${ WC_API_PATH }/products/${ product.id }`, {
				images: [ { id: imageId } ],
			} )
			.then( ( response ) => {
				productWithImage = response.data;
			} );

		await use( productWithImage );
	},
} );

test.describe( 'Products > Product Images', () => {
	test( 'keeps the product image help tip aligned when the image changes', async ( {
		page,
		productWithImage,
	} ) => {
		await page.goto(
			`wp-admin/post.php?post=${ productWithImage.id }&action=edit`
		);

		await expectProductImageHelpTipNextTo( page, 'Remove product image' );

		await page
			.getByRole( 'link', { name: 'Remove product image' } )
			.or(
				page.getByRole( 'button', {
					name: 'Remove product image',
				} )
			)
			.click();
		await expectProductImageHelpTipNextTo( page, 'Set product image' );

		await page
			.getByRole( 'link', { name: 'Set product image' } )
			.or( page.getByRole( 'button', { name: 'Set product image' } ) )
			.click();
		await addImageFromLibrary( page, 'image-02', 'Set product image' );
		await expectProductImageHelpTipNextTo( page, 'Remove product image' );
		await expect(
			page.locator( '.woocommerce-product-image-help-tip' )
		).toHaveCount( 1 );
	} );

	test( 'can manage the product image through the classic editor', async ( {
		page,
		product,
	} ) => {
		await test.step( 'Set the featured image', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
			await page
				.getByRole( 'link', { name: 'Set product image' } )
				.or( page.getByRole( 'button', { name: 'Set product image' } ) )
				.click();
			await addImageFromLibrary( page, 'image-01', 'Set product image' );
			await expect(
				page.locator( '#set-post-thumbnail img[src*="image-01"]' )
			).toBeVisible();
			await page
				.locator( '#publishing-action' )
				.getByRole( 'button', { name: 'Update' } )
				.click();
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();

			await page.goto( product.permalink );
			await expect(
				page.locator( 'img.wp-post-image[src*="image-01"]' )
			).toBeVisible();
		} );

		await test.step( 'Replace the featured image', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
			await page.locator( '#set-post-thumbnail' ).click();
			await addImageFromLibrary( page, 'image-02', 'Set product image' );
			await expect(
				page.locator( '#set-post-thumbnail img[src*="image-02"]' )
			).toBeVisible();
			await page
				.locator( '#publishing-action' )
				.getByRole( 'button', { name: 'Update' } )
				.click();
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();

			await page.goto( product.permalink );
			await expect(
				page.locator( 'img.wp-post-image[src*="image-02"]' )
			).toBeVisible();
			await expect(
				page.locator(
					'.woocommerce-product-gallery__wrapper img[src*="image-01"]'
				)
			).toHaveCount( 0 );
		} );

		await test.step( 'Clear the featured image', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
			const setProductImage = page
				.locator( '#postimagediv' )
				.getByRole( 'link', { name: 'Set product image' } )
				.or(
					page
						.locator( '#postimagediv' )
						.getByRole( 'button', { name: 'Set product image' } )
				);
			await page
				.getByRole( 'link', { name: 'Remove product image' } )
				.or(
					page.getByRole( 'button', {
						name: 'Remove product image',
					} )
				)
				.click();
			await expect( setProductImage ).toBeVisible();
			await page
				.locator( '#publishing-action' )
				.getByRole( 'button', { name: 'Update' } )
				.click();
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();

			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
			await expect( setProductImage ).toBeVisible();

			await page.goto( product.permalink );
			await expect(
				page.locator(
					'.woocommerce-product-gallery__wrapper img[src*="image-"]'
				)
			).toHaveCount( 0 );
		} );
	} );

	test( 'can manage the product gallery through the classic editor', async ( {
		page,
		productWithImage,
	} ) => {
		let image02Id = '';
		let image03Id = '';

		await test.step( 'Create the ordered product gallery', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ productWithImage.id }&action=edit`
			);
			await page
				.getByRole( 'link', {
					name: 'Add product gallery images',
				} )
				.click();
			image02Id = await addImageFromLibrary(
				page,
				'image-02',
				'Add to gallery'
			);
			await page
				.getByRole( 'link', {
					name: 'Add product gallery images',
				} )
				.click();
			image03Id = await addImageFromLibrary(
				page,
				'image-03',
				'Add to gallery'
			);

			const galleryItems = page.locator(
				'#product_images_container li[data-attachment_id]'
			);
			await expect( galleryItems ).toHaveCount( 2 );
			await expect( galleryItems.nth( 0 ) ).toHaveAttribute(
				'data-attachment_id',
				image02Id
			);
			await expect( galleryItems.nth( 1 ) ).toHaveAttribute(
				'data-attachment_id',
				image03Id
			);

			await page
				.locator( '#publishing-action' )
				.getByRole( 'button', { name: 'Update' } )
				.click();
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();

			await page.goto( productWithImage.permalink );
			const frontendImages = page.locator(
				'.woocommerce-product-gallery__wrapper a[href*="/uploads/"] > img'
			);
			await expect( frontendImages ).toHaveCount( 3 );
			await expect( frontendImages.nth( 0 ) ).toHaveAttribute(
				'src',
				/image-01/
			);
			await expect( frontendImages.nth( 1 ) ).toHaveAttribute(
				'src',
				/image-02/
			);
			await expect( frontendImages.nth( 2 ) ).toHaveAttribute(
				'src',
				/image-03/
			);
		} );

		await test.step( 'Remove one gallery image and preserve order', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ productWithImage.id }&action=edit`
			);
			const image02Row = page.locator(
				`#product_images_container li[data-attachment_id="${ image02Id }"]`
			);
			await image02Row.hover();
			await image02Row.getByRole( 'link', { name: /Delete/ } ).click();
			await expect( image02Row ).toHaveCount( 0 );
			await expect(
				page.locator(
					`#product_images_container li[data-attachment_id="${ image03Id }"]`
				)
			).toBeVisible();
			await page
				.locator( '#publishing-action' )
				.getByRole( 'button', { name: 'Update' } )
				.click();
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();

			await page.goto( productWithImage.permalink );
			const frontendImages = page.locator(
				'.woocommerce-product-gallery__wrapper a[href*="/uploads/"] > img'
			);
			await expect( frontendImages ).toHaveCount( 2 );
			await expect( frontendImages.nth( 0 ) ).toHaveAttribute(
				'src',
				/image-01/
			);
			await expect( frontendImages.nth( 1 ) ).toHaveAttribute(
				'src',
				/image-03/
			);
		} );

		await test.step( 'Clear the remaining gallery image', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ productWithImage.id }&action=edit`
			);
			const image03Row = page.locator(
				`#product_images_container li[data-attachment_id="${ image03Id }"]`
			);
			await image03Row.hover();
			await image03Row.getByRole( 'link', { name: /Delete/ } ).click();
			await expect( image03Row ).toHaveCount( 0 );
			await page
				.locator( '#publishing-action' )
				.getByRole( 'button', { name: 'Update' } )
				.click();
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();

			await page.goto( productWithImage.permalink );
			const frontendImages = page.locator(
				'.woocommerce-product-gallery__wrapper a[href*="/uploads/"] > img'
			);
			await expect( frontendImages ).toHaveCount( 1 );
			await expect( frontendImages ).toHaveAttribute( 'src', /image-01/ );
		} );
	} );
} );
