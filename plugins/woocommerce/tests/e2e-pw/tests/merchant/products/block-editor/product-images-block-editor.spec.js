const { test: baseTest, expect } = require( '../../../../fixtures' );
const {
	toggleBlockProductEditor,
} = require( '../../../../utils/simple-products' );

async function selectImagesInLibrary( page, imagesNames ) {
	const dataIds = [];

	await page.getByRole( 'tab', { name: 'Media Library' } ).click();

	// Deselect all images
	// await page.waitForTimeout( 2000 );
	// for ( const checkbox of await page
	// 	.getByRole( 'checkbox', { checked: true } )
	// 	.all() ) {
	// 	await checkbox.getByRole( 'button' ).click();
	//
	// 	expect(
	// 		await page.getByRole( 'checkbox', { checked: true } ).count()
	// 	).toBe( 0 );
	// }

	// Select the given images
	for ( const imageName of imagesNames ) {
		await page
			.getByRole( 'searchbox', { name: 'Search' } )
			.fill( imageName );
		const imageLocator = page.getByLabel( imageName ).nth( 0 );
		await imageLocator.click();
		await expect( imageLocator ).toBeChecked();
		const dataId = await imageLocator.getAttribute( 'data-id' );
		dataIds.push( dataId );
	}

	await page.getByRole( 'button', { name: 'Select', exact: true } ).click();

	return dataIds;
}

baseTest.describe( 'Products > Edit Product', () => {
	baseTest.use( { storageState: process.env.ADMINSTATE } );

	const test = baseTest.extend( {
		product: async ( { page, api }, use ) => {
			let product;

			await api
				.post( 'products', {
					name: `Product ${ Date.now() }`,
					type: 'simple',
					description: `This is a description of the awesome product ${ Date.now() }`,
					short_description: `This product is pretty awesome ${ Date.now() }`,
					regular_price: '12.99',
				} )
				.then( ( response ) => {
					product = response.data;
				} );

			await test.step( 'ensure block product editor is enabled', async () => {
				await toggleBlockProductEditor( 'enable', page );
			} );

			await use( product );

			// Cleanup
			await api.delete( `products/${ product.id }`, { force: true } );
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

	test( 'can add images', async ( { page, product } ) => {
		const images = [ 'image-01', 'image-02' ];

		await test.step( 'navigate to product edit page', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ product.id }&action=edit`
			);
		} );

		await test.step( 'add images', async () => {
			await page.getByText( 'Choose an image' ).click();
			const dataIds = await selectImagesInLibrary( page, images );

			await expect(
				page.getByLabel( 'Block: Product images' ).locator( 'img' )
			).toHaveCount( images.length );

			for ( const dataId of dataIds ) {
				await expect(
					page
						.getByLabel( 'Block: Product images' )
						.locator( `img[id="${ dataId }"]` )
				).toBeVisible();
			}
		} );

		await test.step( 'update the product', async () => {
			await page.getByRole( 'button', { name: 'Update' } ).click();
			// Verify product was updated
			await expect(
				page.getByLabel( 'Dismiss this notice' )
			).toContainText( 'Product updated' );
		} );

		await test.step( 'verify product image was set', async () => {
			// Verify image in store frontend
			await page.goto( product.permalink );

			for ( const image of images ) {
				await expect( page.getByTitle( image ) ).toBeVisible();
			}
		} );
	} );

	test( 'can replace an image', async ( { page, productWithGallery } ) => {
		const newImageName = 'image-01';
		const replacedImgLocator = page
			.getByLabel( 'Block: Product images' )
			.locator( 'img' )
			.nth( 1 );
		let dataIds = [];

		await test.step( 'navigate to product edit page', async () => {
			await page.goto(
				`wp-admin/post.php?post=${ productWithGallery.id }&action=edit`
			);
		} );

		await test.step( 'replace an image', async () => {
			await replacedImgLocator.click();
			await page
				.getByRole( 'toolbar', { name: 'Options' } )
				.getByLabel( 'Options' )
				.click();
			await page.getByRole( 'menuitem', { name: 'Replace' } ).click();
			dataIds = await selectImagesInLibrary( page, [ newImageName ] );

			expect( await replacedImgLocator.getAttribute( 'src' ) ).toContain(
				newImageName
			);
		} );

		await test.step( 'update the product', async () => {
			await page.getByRole( 'button', { name: 'Update' } ).click();
			// Verify product was updated
			await expect(
				page.getByLabel( 'Dismiss this notice' )
			).toContainText( 'Product updated' );
		} );

		await test.step( 'verify product image was set', async () => {
			await expect( replacedImgLocator ).toHaveId( dataIds[ 0 ] );

			// Verify image in store frontend
			await page.goto( productWithGallery.permalink );
			await expect( page.getByTitle( newImageName ) ).toBeVisible();
		} );
	} );

	// test( 'can update the gallery', async ( {
	// 	page,
	// 	productWithGallery,
	// } ) => {
	// 	const images = [ 'image-01', 'image-02' ];
	//
	// 	await test.step( 'navigate to product edit page', async () => {
	// 		await page.goto(
	// 			`wp-admin/post.php?post=${ productWithGallery.id }&action=edit`
	// 		);
	// 	} );
	//
	// 	await test.step( 'update the images', async () => {
	// 		await page.getByText( 'Choose an image' ).click();
	// 		const dataIds = await selectImagesInLibrary( page, images );
	//
	// 		for ( const dataId of dataIds ) {
	// 			await expect(
	// 				page
	// 					.getByLabel( 'Block: Product images' )
	// 					.locator( `img[id="${ dataId }"]` )
	// 			).toBeVisible();
	// 		}
	//
	// 		await page.getByRole( 'button', { name: 'Update' } ).click();
	// 	} );
	//
	// 	await test.step( 'Verify product image was set', async () => {
	// 		// Verify product was updated
	// 		await expect(
	// 			page.getByLabel( 'Dismiss this notice' )
	// 		).toContainText( 'Product updated' );
	//
	// 		// Verify image in store frontend
	// 		await page.goto( productWithGallery.permalink );
	// 		await expect( page.getByTitle( `image-01` ) ).toBeVisible();
	// 	} );
	// } );
} );
