/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage from './product-collection.page';

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
 * for developers to register their own product collections.
 */
test.describe( 'Testing registerProductCollection', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin(
			'register-product-collection-tester'
		);
	} );

	test.describe( 'My Custom Collection', () => {
		test( '"My Custom Collection" should be available in Collection chooser', async ( {
			pageObject,
			admin,
			editor,
		} ) => {
			await admin.createNewPost();
			await editor.insertBlockUsingGlobalInserter(
				pageObject.BLOCK_NAME
			);

			// Get text of all buttons
			const collectionChooserButtons = editor.page.locator(
				'.wc-blocks-product-collection__collection-button-title'
			);

			let found = false;
			const allButtonTextContents =
				await collectionChooserButtons.allTextContents();
			for ( const buttonText of allButtonTextContents ) {
				if ( buttonText === 'My Custom Collection' ) {
					found = true;
					break;
				}
			}

			expect( found ).toBeTruthy();
		} );

		test( 'Clicking "My Custom Collection" should insert block and show 9 products', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock(
				'myCustomCollection'
			);
			expect( pageObject.productTemplate ).not.toBeNull();
			await expect( pageObject.products ).toHaveCount( 9 );
			await expect( pageObject.productImages ).toHaveCount( 9 );
			await expect( pageObject.productTitles ).toHaveCount( 9 );
			await expect( pageObject.productPrices ).toHaveCount( 9 );
			await expect( pageObject.addToCartButtons ).toHaveCount( 9 );
		} );
	} );

	test.describe( 'My Custom Collection with Preview', () => {
		test( '"My Custom Collection with Preview" collection', async ( {
			pageObject,
			admin,
			editor,
		} ) => {
			await admin.createNewPost();
			await editor.insertBlockUsingGlobalInserter(
				pageObject.BLOCK_NAME
			);

			// Get text of all buttons
			const collectionChooserButtons = editor.page.locator(
				'.wc-blocks-product-collection__collection-button-title'
			);

			let found = false;
			const allButtonTextContents =
				await collectionChooserButtons.allTextContents();
			for ( const buttonText of allButtonTextContents ) {
				if ( buttonText === 'My Custom Collection with Preview' ) {
					found = true;
					break;
				}
			}

			expect( found ).toBeTruthy();
		} );

		test( 'Clicking "My Custom Collection with Preview" should insert block and show 9 products', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock(
				'myCustomCollectionWithPreview'
			);
			expect( pageObject.productTemplate ).not.toBeNull();
			await expect( pageObject.products ).toHaveCount( 9 );
			await expect( pageObject.productImages ).toHaveCount( 9 );
			await expect( pageObject.productTitles ).toHaveCount( 9 );
			await expect( pageObject.productPrices ).toHaveCount( 9 );
			await expect( pageObject.addToCartButtons ).toHaveCount( 9 );
		} );

		test( 'Clicking "My Custom Collection with Preview" should show preview', async ( {
			pageObject,
			editor,
		} ) => {
			await pageObject.createNewPostAndInsertBlock(
				'myCustomCollectionWithPreview'
			);
			const previewButtonLocator = editor.page.locator(
				'button[data-test-id="product-collection-preview-button"]'
			);

			// The preview button should be visible
			await expect( previewButtonLocator ).toBeVisible();
		} );
	} );

	// My Custom Collection with Advanced Preview
	test.describe( 'My Custom Collection with Advanced Preview', () => {
		test( '"My Custom Collection with Advanced Preview" collection', async ( {
			pageObject,
			admin,
			editor,
		} ) => {
			await admin.createNewPost();
			await editor.insertBlockUsingGlobalInserter(
				pageObject.BLOCK_NAME
			);

			// Get text of all buttons
			const collectionChooserButtons = editor.page.locator(
				'.wc-blocks-product-collection__collection-button-title'
			);

			let found = false;
			const allButtonTextContents =
				await collectionChooserButtons.allTextContents();
			for ( const buttonText of allButtonTextContents ) {
				if (
					buttonText === 'My Custom Collection with Advanced Preview'
				) {
					found = true;
					break;
				}
			}

			expect( found ).toBeTruthy();
		} );

		test( 'Clicking "My Custom Collection with Advanced Preview" should insert block and show 9 products', async ( {
			pageObject,
		} ) => {
			await pageObject.createNewPostAndInsertBlock(
				'myCustomCollectionWithAdvancedPreview'
			);
			expect( pageObject.productTemplate ).not.toBeNull();
			await expect( pageObject.products ).toHaveCount( 9 );
			await expect( pageObject.productImages ).toHaveCount( 9 );
			await expect( pageObject.productTitles ).toHaveCount( 9 );
			await expect( pageObject.productPrices ).toHaveCount( 9 );
			await expect( pageObject.addToCartButtons ).toHaveCount( 9 );
		} );

		test( 'Clicking "My Custom Collection with Advanced Preview" should show preview for 5 seconds', async ( {
			pageObject,
			editor,
			page,
		} ) => {
			await pageObject.createNewPostAndInsertBlock(
				'myCustomCollectionWithAdvancedPreview'
			);
			const previewButtonLocator = editor.page.locator(
				'button[data-test-id="product-collection-preview-button"]'
			);

			// The preview button should be visible
			await expect( previewButtonLocator ).toBeVisible();

			// Disabling eslint rule because we need to wait for the preview to disappear
			// eslint-disable-next-line playwright/no-wait-for-timeout, no-restricted-syntax
			await page.waitForTimeout( 5000 );

			// The preview button should be hidden
			await expect( previewButtonLocator ).toBeHidden();
		} );
	} );
} );
