/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage, {
	Collections,
	SELECTORS,
} from './product-collection.page';

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

test.describe( 'Product Collection: Product Picker', () => {
	const CUSTOM_COLLECTIONS = [
		{
			id: 'myCustomCollectionWithProductContext',
			name: 'My Custom Collection - Product Context',
			label: 'Block: My Custom Collection - Product Context',
			collection:
				'woocommerce/product-collection/my-custom-collection-product-context',
		},
		{
			id: 'myCustomCollectionMultipleContexts',
			name: 'My Custom Collection - Multiple Contexts',
			label: 'Block: My Custom Collection - Multiple Contexts',
			collection:
				'woocommerce/product-collection/my-custom-collection-multiple-contexts',
		},
	];

	// Activate plugin which registers custom product collections
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-register-product-collection'
		);
	} );

	CUSTOM_COLLECTIONS.forEach( ( collection ) => {
		test( `For collection "${ collection.name }" - manually selected product reference should be available on Frontend in a post`, async ( {
			pageObject,
			admin,
			page,
			editor,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost(
				collection.id as Collections
			);

			// Verify that product picker is shown in Editor
			const editorProductPicker = editor.canvas.locator(
				SELECTORS.productPicker
			);
			await expect( editorProductPicker ).toBeVisible();

			// Once a product is selected, the product picker should be hidden
			await pageObject.chooseProductInEditorProductPickerIfAvailable(
				editor.canvas
			);
			await expect( editorProductPicker ).toBeHidden();

			// On Frontend, verify that product reference is a number
			await pageObject.publishAndGoToFrontend();
			const collectionWithProductContext = page.locator(
				`[data-collection="${ collection.collection }"]`
			);
			const queryAttribute = JSON.parse(
				( await collectionWithProductContext.getAttribute(
					'data-query'
				) ) || '{}'
			);
			expect( typeof queryAttribute?.productReference ).toBe( 'number' );
		} );

		test( `For collection "${ collection.name }" - changing product using inspector control`, async ( {
			pageObject,
			admin,
			page,
			editor,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost(
				collection.id as Collections
			);

			// Verify that product picker is shown in Editor
			const editorProductPicker = editor.canvas.locator(
				SELECTORS.productPicker
			);
			await expect( editorProductPicker ).toBeVisible();

			// Once a product is selected, the product picker should be hidden
			await pageObject.chooseProductInEditorProductPickerIfAvailable(
				editor.canvas
			);
			await expect( editorProductPicker ).toBeHidden();

			// Verify that Album is selected
			await expect(
				admin.page.locator( SELECTORS.linkedProductControl.button )
			).toContainText( 'Album' );

			// Change product using inspector control to Beanie
			await admin.page
				.locator( SELECTORS.linkedProductControl.button )
				.click();
			await admin.page
				.locator( SELECTORS.linkedProductControl.popoverContent )
				.getByLabel( 'Beanie', { exact: true } )
				.click();
			await expect(
				admin.page.locator( SELECTORS.linkedProductControl.button )
			).toContainText( 'Beanie' );

			// On Frontend, verify that product reference is a number
			await pageObject.publishAndGoToFrontend();
			const collectionWithProductContext = page.locator(
				`[data-collection="${ collection.collection }"]`
			);
			const queryAttribute = JSON.parse(
				( await collectionWithProductContext.getAttribute(
					'data-query'
				) ) || '{}'
			);
			expect( typeof queryAttribute?.productReference ).toBe( 'number' );
		} );

		test( `For collection "${ collection.name }" - "From current product" is chosen by default`, async ( {
			pageObject,
			admin,
			editor,
		} ) => {
			await admin.visitSiteEditor( {
				postId: `woocommerce/woocommerce//single-product`,
				postType: 'wp_template',
				canvas: 'edit',
			} );
			await editor.canvas.locator( 'body' ).click();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInTemplate(
				collection.id as Collections
			);

			const productToShowControl = admin.page.getByText(
				'From the current product'
			);
			await expect( productToShowControl ).toBeChecked();
		} );
	} );

	test( 'Product picker should work as expected while changing collection using "Choose collection" button from Toolbar', async ( {
		pageObject,
		admin,
		editor,
	} ) => {
		await admin.createNewPost();
		await pageObject.insertProductCollection();
		await pageObject.chooseCollectionInPost(
			'myCustomCollectionWithProductContext'
		);

		// Verify that product picker is shown in Editor
		const editorProductPicker = editor.canvas.locator(
			SELECTORS.productPicker
		);
		await expect( editorProductPicker ).toBeVisible();

		// Once a product is selected, the product picker should be hidden
		await pageObject.chooseProductInEditorProductPickerIfAvailable(
			editor.canvas
		);
		await expect( editorProductPicker ).toBeHidden();

		// Change collection using Toolbar
		await pageObject.changeCollectionUsingToolbar(
			'myCustomCollectionMultipleContexts'
		);
		await expect( editorProductPicker ).toBeVisible();

		// Once a product is selected, the product picker should be hidden
		await pageObject.chooseProductInEditorProductPickerIfAvailable(
			editor.canvas
		);
		await expect( editorProductPicker ).toBeHidden();

		// Product picker should be hidden for collections that don't need product
		await pageObject.changeCollectionUsingToolbar( 'featured' );
		await expect( editorProductPicker ).toBeHidden();
	} );
} );
