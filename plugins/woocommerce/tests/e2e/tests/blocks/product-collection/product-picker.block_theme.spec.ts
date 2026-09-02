/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { test as base, expect, BLOCK_THEME_SLUG } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage, { SELECTORS } from './product-collection.page';

const test = base.extend< { pageObject: ProductCollectionPage } >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

const getProductReference = async ( page: Page ) =>
	page.evaluate( () => {
		const block = window.wp.data
			.select( 'core/block-editor' )
			.getBlocks()
			.find(
				( candidate: { name: string } ) =>
					candidate.name === 'woocommerce/product-collection'
			);

		return block?.attributes.query?.productReference;
	} );

test.describe( 'Product Collection: Product Picker', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-register-product-collection'
		);
	} );

	test( 'For collection "My Custom Collection - Product Context" - manually selected product reference should be available on Frontend in a post', async ( {
		pageObject,
		admin,
		page,
		editor,
		requestUtils,
	} ) => {
		const matchingProducts = await requestUtils.rest<
			Array< { id: number; name: string } >
		>( {
			method: 'GET',
			path: 'wc/v3/products',
			params: { search: 'Album' },
		} );
		const exactAlbums = matchingProducts.filter(
			( product ) => product.name === 'Album'
		);
		expect( exactAlbums ).toHaveLength( 1 );
		const albumId = exactAlbums[ 0 ].id;

		await admin.createNewPost();
		await pageObject.insertProductCollection();
		await pageObject.chooseCollectionInPost(
			'myCustomCollectionWithProductContext'
		);

		const productPicker = editor.canvas.locator( SELECTORS.productPicker );
		await expect( productPicker ).toBeVisible();
		await pageObject.chooseProductInEditorProductPickerIfAvailable(
			editor.canvas,
			'Album'
		);
		await expect( productPicker ).toBeHidden();

		const selectedProductId = await getProductReference( page );
		expect( selectedProductId ).toBe( albumId );

		await pageObject.publishAndGoToFrontend();
		const collection = page.locator(
			'[data-collection="woocommerce/product-collection/my-custom-collection-product-context"]'
		);
		const query = JSON.parse(
			( await collection.getAttribute( 'data-query' ) ) || '{}'
		);
		expect( query.productReference ).toBe( selectedProductId );
	} );

	test( 'For collection "Block: My Custom Collection - Order Context" - "From products in the order" is chosen by default in Order Confirmation Template', async ( {
		pageObject,
		admin,
		editor,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//order-confirmation`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.canvas.locator( 'body' ).click();
		await pageObject.insertProductCollection();
		await pageObject.chooseCollectionInTemplate(
			'myCustomCollectionWithOrderContext'
		);

		await expect(
			admin.page.getByText( 'From products in the order' )
		).toBeChecked();
	} );

	test( 'Product picker should work as expected while changing collection using "Choose collection" button from Toolbar', async ( {
		pageObject,
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		const matchingProducts = await requestUtils.rest<
			Array< { id: number; name: string } >
		>( {
			method: 'GET',
			path: 'wc/v3/products',
			params: { search: 'Album' },
		} );
		const exactAlbums = matchingProducts.filter(
			( product ) => product.name === 'Album'
		);
		expect( exactAlbums ).toHaveLength( 1 );
		const albumId = exactAlbums[ 0 ].id;

		await admin.createNewPost();
		await pageObject.insertProductCollection();
		await pageObject.chooseCollectionInPost(
			'myCustomCollectionWithProductContext'
		);

		const productPicker = editor.canvas.locator( SELECTORS.productPicker );
		await expect( productPicker ).toBeVisible();
		await pageObject.chooseProductInEditorProductPickerIfAvailable(
			editor.canvas,
			'Album'
		);
		await expect( productPicker ).toBeHidden();
		const firstProductId = await getProductReference( page );
		expect( firstProductId ).toBe( albumId );

		await pageObject.changeCollectionUsingToolbar(
			'myCustomCollectionMultipleContexts'
		);
		await expect( productPicker ).toBeVisible();
		expect( await getProductReference( page ) ).toBeUndefined();

		await pageObject.chooseProductInEditorProductPickerIfAvailable(
			editor.canvas
		);
		await expect( productPicker ).toBeHidden();
		const secondProductId = await getProductReference( page );
		expect( secondProductId ).toBe( albumId );

		await pageObject.changeCollectionUsingToolbar( 'featured' );
		await expect( productPicker ).toBeHidden();
		expect( await getProductReference( page ) ).toBeUndefined();
		await pageObject.refreshLocators( 'editor' );
		await expect( pageObject.productTitles ).toHaveText( [
			'Cap',
			'Hoodie with Zipper',
			'Sunglasses',
			'V-Neck T-Shirt',
		] );
	} );
} );
