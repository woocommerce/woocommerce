/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage, {
	BLOCK_LABELS,
	Collections,
	SELECTORS,
} from './product-collection.page';

declare global {
	interface Window {
		__removePreview: () => void;
	}
}

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

/**
 * These E2E tests are for `registerProductCollection` which we are exposing
 * for 3PDs to register new product collections.
 */
test.describe( 'Product Collection: Register Product Collection', () => {
	const MY_REGISTERED_COLLECTIONS = {
		myCustomCollection: {
			name: 'My Custom Collection',
			label: 'Block: My Custom Collection',
		},
		myCustomCollectionWithPreview: {
			name: 'My Custom Collection with Preview',
			label: 'Block: My Custom Collection with Preview',
		},
		myCustomCollectionWithAdvancedPreview: {
			name: 'My Custom Collection with Advanced Preview',
			label: 'Block: My Custom Collection with Advanced Preview',
		},
		myCustomCollectionWithProductContext: {
			name: 'My Custom Collection - Product Context',
			label: 'Block: My Custom Collection - Product Context',
		},
	};

	// Activate plugin which registers custom product collections
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-register-product-collection'
		);
	} );

	test( `Registered collections should be available in Collection chooser`, async ( {
		pageObject,
		editor,
		admin,
		page,
	} ) => {
		await admin.createNewPost();
		await editor.insertBlockUsingGlobalInserter( pageObject.BLOCK_NAME );
		await editor.canvas
			.getByRole( 'button', {
				name: 'Choose collection',
			} )
			.click();

		// This viewport size is required to ensure that the selectors are visible.
		// For smaller viewports, a different DOM structure is rendered, which may cause the selectors to be hidden or not interactable.
		await page.setViewportSize( {
			width: 1920,
			height: 1080,
		} );

		for ( const myCollection of Object.values(
			MY_REGISTERED_COLLECTIONS
		) ) {
			await expect(
				editor.canvas
					.locator(
						`.wc-blocks-product-collection__collection-button-title`
					)
					.getByText( myCollection.name, { exact: true } )
			).toBeVisible();
		}
	} );

	test.describe( 'My Custom Collection', () => {
		test( 'Clicking "My Custom Collection" should insert block and show 5 products', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock(
				'myCustomCollection'
			);

			await expect( pageObject.products ).toHaveCount( 5 );
			await expect( pageObject.productImages ).toHaveCount( 5 );
			await expect( pageObject.productTitles ).toHaveCount( 5 );
			await expect( pageObject.productPrices ).toHaveCount( 5 );
			await expect( pageObject.addToCartButtons ).toHaveCount( 5 );

			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.products ).toHaveCount( 5 );
		} );

		test( 'Should display properly in Product Catalog template', async ( {
			pageObject,
			editor,
		} ) => {
			await pageObject.goToProductCatalogAndInsertCollection(
				'myCustomCollection'
			);

			const block = editor.canvas.getByLabel(
				MY_REGISTERED_COLLECTIONS.myCustomCollection.label
			);

			const products = block
				.getByLabel( BLOCK_LABELS.productImage )
				.locator( 'visible=true' );
			await expect( products ).toHaveCount( 5 );
		} );

		test( 'hideControls allows to hide filters', async ( {
			pageObject,
			page,
		} ) => {
			await pageObject.goToProductCatalogAndInsertCollection(
				'myCustomCollection'
			);

			const sidebarSettings = pageObject.locateSidebarSettings();
			const onsaleControl = sidebarSettings.getByLabel(
				SELECTORS.onSaleControlLabel
			);
			await expect( onsaleControl ).toBeHidden();

			await page
				.getByRole( 'button', { name: 'Filters options' } )
				.click();
			const keywordControl = page.getByRole( 'menuitemcheckbox', {
				name: 'Keyword',
			} );

			await expect( keywordControl ).toBeHidden();
		} );
	} );

	test.describe( 'My Custom Collection with Preview', () => {
		test( 'Clicking "My Custom Collection with Preview" should insert block and show 9 products', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock(
				'myCustomCollectionWithPreview'
			);

			await expect( pageObject.products ).toHaveCount( 9 );
			await expect( pageObject.productImages ).toHaveCount( 9 );
			await expect( pageObject.productTitles ).toHaveCount( 9 );
			await expect( pageObject.productPrices ).toHaveCount( 9 );
			await expect( pageObject.addToCartButtons ).toHaveCount( 9 );

			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.products ).toHaveCount( 9 );
		} );

		test( 'Clicking "My Custom Collection with Preview" should show preview', async ( {
			pageObject,
			editor,
		} ) => {
			await pageObject.createNewPostAndInsertBlock(
				'myCustomCollectionWithPreview'
			);
			const previewButtonLocator = editor.canvas.getByTestId(
				SELECTORS.previewButtonTestID
			);

			// The preview button should be visible
			await expect( previewButtonLocator ).toBeVisible();
		} );

		test( 'Should display properly in Product Catalog template', async ( {
			pageObject,
			editor,
		} ) => {
			await pageObject.goToProductCatalogAndInsertCollection(
				'myCustomCollectionWithPreview'
			);

			const block = editor.canvas.getByLabel(
				MY_REGISTERED_COLLECTIONS.myCustomCollectionWithPreview.label
			);

			// Check if products are visible
			const products = block
				.getByLabel( BLOCK_LABELS.productImage )
				.locator( 'visible=true' );
			await expect( products ).toHaveCount( 9 );

			// Check if the preview button is visible
			const previewButtonLocator = block.getByTestId(
				SELECTORS.previewButtonTestID
			);
			await expect( previewButtonLocator ).toBeVisible();
		} );
	} );

	test.describe( 'My Custom Collection with Advanced Preview', () => {
		test( 'Clicking "My Custom Collection with Advanced Preview" should insert block and show 9 products', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock(
				'myCustomCollectionWithAdvancedPreview'
			);

			await expect( pageObject.products ).toHaveCount( 9 );
			await expect( pageObject.productImages ).toHaveCount( 9 );
			await expect( pageObject.productTitles ).toHaveCount( 9 );
			await expect( pageObject.productPrices ).toHaveCount( 9 );
			await expect( pageObject.addToCartButtons ).toHaveCount( 9 );

			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.products ).toHaveCount( 9 );
		} );

		test( 'Clicking "My Custom Collection with Advanced Preview" should show preview and then replace it by the actual content', async ( {
			pageObject,
			editor,
			page,
		} ) => {
			await pageObject.createNewPostAndInsertBlock(
				'myCustomCollectionWithAdvancedPreview'
			);
			const previewButtonLocator = editor.canvas.getByTestId(
				SELECTORS.previewButtonTestID
			);

			// The preview button should be visible
			await expect( previewButtonLocator ).toBeVisible();

			await page.evaluate( () => {
				window.__removePreview();
			} );

			// The preview button should be hidden
			await expect( previewButtonLocator ).toBeHidden();
		} );

		test( 'Should display properly in Product Catalog template', async ( {
			pageObject,
			editor,
			page,
		} ) => {
			await pageObject.goToProductCatalogAndInsertCollection(
				'myCustomCollectionWithAdvancedPreview'
			);

			const block = editor.canvas.getByLabel(
				MY_REGISTERED_COLLECTIONS.myCustomCollectionWithAdvancedPreview
					.label
			);

			// Check if the preview button is visible
			const previewButtonLocator = block.getByTestId(
				SELECTORS.previewButtonTestID
			);
			await expect( previewButtonLocator ).toBeVisible();

			// Check if products are visible
			const products = block
				.getByLabel( BLOCK_LABELS.productImage )
				.locator( 'visible=true' );
			await expect( products ).toHaveCount( 9 );

			await page.evaluate( () => {
				window.__removePreview();
			} );

			await expect( previewButtonLocator ).toBeHidden();
		} );
	} );

	[
		{
			id: 'myCustomCollectionWithProductContext',
			name: 'My Custom Collection - Product Context',
			label: 'Block: My Custom Collection - Product Context',
			previewLabelTemplate: [ 'woocommerce/woocommerce//single-product' ],
		},
		{
			id: 'myCustomCollectionWithCartContext',
			name: 'My Custom Collection - Cart Context',
			label: 'Block: My Custom Collection - Cart Context',
			previewLabelTemplate: [ 'woocommerce/woocommerce//page-cart' ],
		},
		{
			id: 'myCustomCollectionWithOrderContext',
			name: 'My Custom Collection - Order Context',
			label: 'Block: My Custom Collection - Order Context',
			previewLabelTemplate: [
				'woocommerce/woocommerce//order-confirmation',
			],
		},
		{
			id: 'myCustomCollectionWithArchiveContext',
			name: 'My Custom Collection - Archive Context',
			label: 'Block: My Custom Collection - Archive Context',
			previewLabelTemplate: [
				'woocommerce/woocommerce//taxonomy-product_cat',
			],
		},
		{
			id: 'myCustomCollectionMultipleContexts',
			name: 'My Custom Collection - Multiple Contexts',
			label: 'Block: My Custom Collection - Multiple Contexts',
			previewLabelTemplate: [
				'woocommerce/woocommerce//single-product',
				'woocommerce/woocommerce//order-confirmation',
			],
		},
	].forEach( ( collection ) => {
		collection.previewLabelTemplate.forEach( ( template ) => {
			test( `Collection "${ collection.name }" should show preview label in "${ template }"`, async ( {
				pageObject,
				editor,
			} ) => {
				await pageObject.goToEditorTemplate( template );
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInTemplate(
					collection.id as Collections
				);

				const block = editor.canvas.getByLabel( collection.label );
				const previewButtonLocator = block.getByTestId(
					SELECTORS.previewButtonTestID
				);

				await expect( previewButtonLocator ).toBeVisible();
			} );
		} );

		test( `Collection "${ collection.name }" should not show preview label in a post`, async ( {
			pageObject,
			editor,
		} ) => {
			await pageObject.createNewPostAndInsertBlock(
				collection.id as Collections
			);

			const block = editor.canvas.getByLabel( collection.label );
			const previewButtonLocator = block.getByTestId(
				SELECTORS.previewButtonTestID
			);

			await expect( previewButtonLocator ).toBeHidden();
		} );

		test( `Collection "${ collection.name }" should not show preview label in Product Catalog template`, async ( {
			pageObject,
			editor,
		} ) => {
			await pageObject.goToProductCatalogAndInsertCollection(
				collection.id as Collections
			);

			const block = editor.canvas.getByLabel( collection.label );
			const previewButtonLocator = block.getByTestId(
				SELECTORS.previewButtonTestID
			);

			await expect( previewButtonLocator ).toBeHidden();
		} );
	} );

	test( 'Product picker should be shown when selected product is deleted', async ( {
		pageObject,
		admin,
		editor,
		requestUtils,
		page,
	} ) => {
		// Add a new test product to the database
		let testProductId: number | null = null;
		const newProduct = await requestUtils.rest( {
			method: 'POST',
			path: 'wc/v3/products',
			data: {
				name: 'A Test Product',
				price: 10,
			},
		} );
		testProductId = newProduct.id;

		await admin.createNewPost();
		await pageObject.insertProductCollection();
		await pageObject.chooseCollectionInPost(
			'myCustomCollectionWithProductContext'
		);
		const block = editor.canvas.getByLabel(
			MY_REGISTERED_COLLECTIONS.myCustomCollectionWithProductContext.label
		);

		// Verify that product picker is shown in Editor
		const editorProductPicker = editor.canvas.locator(
			SELECTORS.productPicker
		);
		await expect( editorProductPicker ).toBeVisible();

		// Once a product is selected, the product picker should be hidden
		await pageObject.chooseProductInEditorProductPickerIfAvailable(
			editor.canvas,
			'A Test Product'
		);
		await expect(
			block.getByLabel( BLOCK_LABELS.productImage ).first()
		).toBeVisible();
		await expect( editorProductPicker ).toBeHidden();

		await editor.saveDraft();

		// Delete the product
		await requestUtils.rest( {
			method: 'DELETE',
			path: `wc/v3/products/${ testProductId }`,
		} );

		// Product picker should be shown in Editor
		await page.reload();
		const deletedProductPicker = editor.canvas.getByText(
			'Previously selected product'
		);
		await expect( deletedProductPicker ).toBeVisible();

		// Change status from "trash" to "publish"
		await requestUtils.rest( {
			method: 'PUT',
			path: `wc/v3/products/${ testProductId }`,
			data: {
				status: 'publish',
			},
		} );

		// Product Picker shouldn't be shown as product is available now
		await page.reload();
		await expect(
			block.getByLabel( BLOCK_LABELS.productImage ).first()
		).toBeVisible();
		await expect( editorProductPicker ).toBeHidden();

		// Delete the product from database, instead of trashing it
		await requestUtils.rest( {
			method: 'DELETE',
			path: `wc/v3/products/${ testProductId }`,
			params: {
				// Bypass trash and permanently delete the product
				force: true,
			},
		} );

		// Product picker should be shown in Editor
		await page.reload();
		await expect( deletedProductPicker ).toBeVisible();
	} );

	test.describe( 'with "usesReference" argument', () => {
		[
			{
				id: 'myCustomCollectionWithProductContext',
				name: 'My Custom Collection - Product Context',
				label: 'Block: My Custom Collection - Product Context',
				previewLabelTemplate: [
					'woocommerce/woocommerce//single-product',
				],
				shouldShowProductPicker: true,
			},
			{
				id: 'myCustomCollectionWithCartContext',
				name: 'My Custom Collection - Cart Context',
				label: 'Block: My Custom Collection - Cart Context',
				previewLabelTemplate: [ 'woocommerce/woocommerce//page-cart' ],
				shouldShowProductPicker: false,
			},
			{
				id: 'myCustomCollectionWithOrderContext',
				name: 'My Custom Collection - Order Context',
				label: 'Block: My Custom Collection - Order Context',
				previewLabelTemplate: [
					'woocommerce/woocommerce//order-confirmation',
				],
				shouldShowProductPicker: false,
			},
			{
				id: 'myCustomCollectionWithArchiveContext',
				name: 'My Custom Collection - Archive Context',
				label: 'Block: My Custom Collection - Archive Context',
				previewLabelTemplate: [
					'woocommerce/woocommerce//taxonomy-product_cat',
				],
				shouldShowProductPicker: false,
			},
			{
				id: 'myCustomCollectionMultipleContexts',
				name: 'My Custom Collection - Multiple Contexts',
				label: 'Block: My Custom Collection - Multiple Contexts',
				previewLabelTemplate: [
					'woocommerce/woocommerce//single-product',
					'woocommerce/woocommerce//order-confirmation',
				],
				shouldShowProductPicker: true,
			},
		].forEach( ( collection ) => {
			collection.previewLabelTemplate.forEach( ( template ) => {
				test( `Collection "${ collection.name }" should show preview label in "${ template }"`, async ( {
					pageObject,
					editor,
				} ) => {
					await pageObject.goToEditorTemplate( template );
					await pageObject.insertProductCollection();
					await pageObject.chooseCollectionInTemplate(
						collection.id as Collections
					);

					const block = editor.canvas.getByLabel( collection.label );
					const previewButtonLocator = block.getByTestId(
						SELECTORS.previewButtonTestID
					);

					await expect( previewButtonLocator ).toBeVisible();
				} );
			} );

			test( `Collection "${ collection.name }" should not show preview label in a post`, async ( {
				pageObject,
				editor,
				admin,
			} ) => {
				await admin.createNewPost();
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInPost(
					collection.id as Collections
				);

				// Check visibility of product picker
				const editorProductPicker = editor.canvas.locator(
					SELECTORS.productPicker
				);
				const expectedVisibility = collection.shouldShowProductPicker
					? 'toBeVisible'
					: 'toBeHidden';
				await expect( editorProductPicker )[ expectedVisibility ]();

				if ( collection.shouldShowProductPicker ) {
					await pageObject.chooseProductInEditorProductPickerIfAvailable(
						editor.canvas
					);
				}

				// At this point, the product picker should be hidden
				await expect( editorProductPicker ).toBeHidden();

				// Check visibility of preview label
				const block = editor.canvas.getByLabel( collection.label );
				const previewButtonLocator = block.getByTestId(
					SELECTORS.previewButtonTestID
				);

				await expect( previewButtonLocator ).toBeHidden();
			} );

			test( `Collection "${ collection.name }" should not show preview label in Product Catalog template`, async ( {
				pageObject,
				editor,
			} ) => {
				await pageObject.goToProductCatalogAndInsertCollection(
					collection.id as Collections
				);

				const block = editor.canvas.getByLabel( collection.label );
				const previewButtonLocator = block.getByTestId(
					SELECTORS.previewButtonTestID
				);

				await expect( previewButtonLocator ).toBeHidden();
			} );
		} );
	} );

	test.describe( 'with "scope" argument', () => {
		test( 'Collection with only `inserter` scope should not be displayed in Collection Chooser', async ( {
			pageObject,
			editor,
			admin,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();

			const placeholderSelector = editor.canvas.locator(
				SELECTORS.collectionPlaceholder
			);

			const collectionButton = placeholderSelector.getByRole( 'button', {
				name: 'My Custom Collection - With Inserter Scope',
				exact: true,
			} );

			await expect( collectionButton ).toBeHidden();
		} );

		test( 'Collection with only `block` scope should be displayed in Collection Chooser', async ( {
			pageObject,
			editor,
			admin,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();

			const placeholderSelector = editor.canvas.locator(
				SELECTORS.collectionPlaceholder
			);

			const collectionButton = placeholderSelector.getByRole( 'button', {
				name: 'My Custom Collection - With Block Scope',
				exact: true,
			} );

			await expect( collectionButton ).toBeVisible();
		} );

		test( 'Choose collection button visibility for different scopes', async ( {
			pageObject,
			editor,
			admin,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost(
				'myCustomCollectionWithBlockScope'
			);
			const chooseCollectionButton = admin.page
				.getByRole( 'toolbar', { name: 'Block Tools' } )
				.getByRole( 'button', { name: 'Choose collection' } );
			await expect( chooseCollectionButton ).toBeVisible();

			// Test inserter scope collection
			await editor.setContent( '' );
			await editor.insertBlockUsingGlobalInserter(
				'My Custom Collection - With Inserter Scope'
			);
			await expect( chooseCollectionButton ).toBeHidden();
		} );
	} );
} );
