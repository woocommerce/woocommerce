const { test: baseTest, expect } = require( '../../../../fixtures' );
const {
	toggleBlockProductEditor,
} = require( '../../../../utils/simple-products' );

async function addImagesFromLibrary( page, imagesNames ) {
	const dataIds = [];

	await page.getByRole( 'tab', { name: 'Media Library' } ).click();

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
			const dataIds = await addImagesFromLibrary( page, images );

			for ( const dataId of dataIds ) {
				await expect(
					page
						.getByLabel( 'Block: Product images' )
						.locator( `img[id="${ dataId }"]` )
				).toBeVisible();
			}

			await page.getByRole( 'button', { name: 'Update' } ).click();
		} );

		await test.step( 'Verify product image was set', async () => {
			// Verify product was updated
			await expect(
				page.getByLabel( 'Dismiss this notice' )
			).toContainText( 'Product updated' );

			// Verify image in store frontend
			await page.goto( product.permalink );
			await expect( page.getByTitle( `image-01` ) ).toBeVisible();
		} );
	} );
} );
