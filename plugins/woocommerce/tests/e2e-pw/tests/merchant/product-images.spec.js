const { test: baseTest, expect } = require( '../../fixtures/fixtures' );

async function addImageFromLibrary( page, imageName, actionButtonName ) {
	await page.getByRole( 'tab', { name: 'Media Library' } ).click();
	await page.getByRole( 'searchbox', { name: 'Search' } ).fill( imageName );
	const imageLocator = page.getByLabel( imageName ).nth( 0 );
	await imageLocator.click();
	const dataId = await imageLocator.getAttribute( 'data-id' );
	await expect( imageLocator ).toBeChecked();
	await page.getByRole( 'button', { name: actionButtonName } ).click();
	return dataId;
}

baseTest.describe( 'Products > Product Images', () => {
	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
		product: async ( { api }, use ) => {
			let product = {
				id: 0,
				name: `Product ${ Date.now() }`,
				type: 'simple',
				regular_price: '12.99',
				sale_price: '11.59',
			};

			await api.post( 'products', product ).then( ( response ) => {
				product = response.data;
			} );

			await use( product );

			// Cleanup
			await api.delete( `products/${ product.id }`, { force: true } );
		},
		productWithImage: async ( { api, product }, use ) => {
			let productWithImage;
			await api
				.put( `products/${ product.id }`, {
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
		productWithGallery: async ( { api, product }, use ) => {
			let productWithGallery;
			await api
				.put( `products/${ product.id }`, {
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

	test( 'can set product image', async ( { page, product } ) => {
		await test.step( 'Navigate to product edit page', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
		} );

		await test.step( 'Set product image', async () => {
			await page
				.getByRole( 'link', { name: 'Set product image' } )
				.click();
			await addImageFromLibrary( page, 'image-01', 'Set product image' );

			// Wait for the product image thumbnail to be updated.
			// Clicking the "Update" button before this happens will not update the image.
			// Use src* (contains) instead of src$ (ends with) to match duplicated images, like image-01-1.png
			await expect(
				page.locator( '#set-post-thumbnail img[src*="image-01"]' )
			).toBeVisible();

			await page.getByRole( 'button', { name: 'Update' } ).click();
		} );

		await test.step( 'Verify product image was set', async () => {
			// Verify product was updated
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();

			// Verify image in store frontend
			await page.goto( product.permalink );
			await expect( page.getByTitle( `image-01` ) ).toBeVisible();
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

		expect( 1 ).toBeGreaterThanOrEqual( 5 );

		await test.step( 'Update product image', async () => {
			await page.locator( '#set-post-thumbnail' ).click();
			await addImageFromLibrary( page, 'image-02', 'Set product image' );

			// Wait for the product image thumbnail to be updated.
			// Clicking the "Update" button before this happens will not update the image.
			// Use src* (contains) instead of src$ (ends with) to match duplicated images, like image-01-1.png
			await expect(
				page.locator( '#set-post-thumbnail img[src*="image-02"]' )
			).toBeVisible();

			await page.getByRole( 'button', { name: 'Update' } ).click();
		} );

		await test.step( 'Verify product image was set', async () => {
			// Verify product was updated
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();

			// Verify image in store frontend
			await page.goto( productWithImage.permalink );
			await expect( page.getByTitle( `image-02` ) ).toBeVisible();
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
			await page
				.getByRole( 'link', { name: 'Remove product image' } )
				.click();
			await expect(
				page.getByRole( 'link', { name: 'Set product image' } )
			).toBeVisible();

			await page.getByRole( 'button', { name: 'Update' } ).click();
		} );

		await test.step( 'Verify product image was removed', async () => {
			// Verify product was updated
			await expect( page.getByText( 'Product updated.' ) ).toBeVisible();

			// Verify image in store frontend
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
			const imageSelector = '#product_images_container img';
			let initialImagesCount = await page
				.locator( imageSelector )
				.count();

			for ( const image of images ) {
				await page
					.getByRole( 'link', { name: 'Add product gallery images' } )
					.click();
				const dataId = await addImageFromLibrary(
					page,
					image,
					'Add to gallery'
				);

				await expect(
					page.locator( `li[data-attachment_id="${ dataId }"]` ),
					'thumbnail should be visible'
				).toBeVisible();
				const currentImagesCount = await page
					.locator( imageSelector )
					.count();
				await expect(
					currentImagesCount,
					'number of images should increase'
				).toEqual( initialImagesCount + 1 );
				initialImagesCount = currentImagesCount;
			}

			await page.getByRole( 'button', { name: 'Update' } ).click();
		} );

		await test.step( 'Verify product gallery', async () => {
			// Verify gallery in store frontend
			await page.goto( productWithImage.permalink );
			await expect(
				page
					.locator( `.woocommerce-product-gallery ol img` )
					.nth( images.length ),
				'all gallery images should be visible'
			).toBeVisible(); // +1 for the featured image
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

		await test.step( 'Remove images from product gallery', async () => {
			const imageSelector = '#product_images_container img';
			imagesCount = await page.locator( imageSelector ).count();

			await page.locator( imageSelector ).first().hover();
			await page.getByRole( 'link', { name: ' Delete' } ).click();

			await expect(
				await page.locator( imageSelector ).count(),
				'number of images should decrease'
			).toEqual( imagesCount - 1 );

			await page.getByRole( 'button', { name: 'Update' } ).click();
		} );

		await test.step( 'Verify product gallery', async () => {
			// Verify gallery in store frontend
			await page.goto( productWithGallery.permalink );
			const selector = `.woocommerce-product-gallery ol img`;
			await expect(
				page.locator( selector ).nth( imagesCount - 1 ),
				'gallery images should be visible'
			).toBeVisible();
			await expect(
				page.locator( selector ).nth( imagesCount ),
				'one gallery image should not be visible'
			).toBeHidden();
		} );
	} );
} );
