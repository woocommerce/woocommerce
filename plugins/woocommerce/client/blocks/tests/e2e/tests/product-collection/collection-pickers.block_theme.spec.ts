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

/**
 * Taxonomy-based collections configuration for parameterized tests.
 */
const taxonomyCollections: {
	slug: Collections;
	name: string;
	termName: string;
	termLabel: string;
	expectedProductCount: number;
}[] = [
	{
		slug: 'productsByCategory',
		name: 'Products by Category',
		termName: 'categories',
		termLabel: 'Accessories',
		expectedProductCount: 5,
	},
	{
		slug: 'productsByTag',
		name: 'Products by Tag',
		termName: 'tags',
		termLabel: 'Recommended',
		expectedProductCount: 2,
	},
];

test.describe( 'Product Collection: Collection Pickers', () => {
	test.describe( 'Hand-Picked Products', () => {
		test( 'Can select multiple products and Done button becomes enabled', async ( {
			pageObject,
			admin,
			editor,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'handPicked' );

			const productPicker = editor.canvas.locator(
				SELECTORS.productPicker
			);
			const doneButton = productPicker.locator(
				SELECTORS.pickerDoneButton
			);

			// Initially disabled
			await expect( doneButton ).toBeDisabled();

			// Select first product
			await productPicker.getByText( 'Album (woo-album)' ).click();

			// Done button should now be enabled
			await expect( doneButton ).toBeEnabled();

			// Select second product
			await productPicker.getByText( 'Beanie (woo-beanie)' ).click();

			// Click Done
			await doneButton.click();

			// Picker should be hidden and products should be displayed
			await expect( productPicker ).toBeHidden();
			await pageObject.refreshLocators( 'editor' );
			await expect( pageObject.products ).toHaveCount( 2 );
		} );

		test( 'Picker is not shown after save and refresh', async ( {
			pageObject,
			admin,
			editor,
			page,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'handPicked' );

			// Select a product and click Done
			const productPicker = editor.canvas.locator(
				SELECTORS.productPicker
			);
			await productPicker.getByText( 'Album (woo-album)' ).click();
			await productPicker.locator( SELECTORS.pickerDoneButton ).click();

			// Save and refresh
			await editor.saveDraft();
			await page.reload();
			await editor.canvas.locator( 'body' ).waitFor();

			// Click on the block to select it
			await editor.canvas
				.locator( '[data-type="woocommerce/product-collection"]' )
				.first()
				.click();

			// Picker should not be shown
			const pickerAfterRefresh = editor.canvas.locator(
				SELECTORS.productPicker
			);
			await expect( pickerAfterRefresh ).toBeHidden();

			// Products should be visible
			await pageObject.refreshLocators( 'editor' );
			await expect( pageObject.products ).toHaveCount( 1 );
		} );

		test( 'Products are displayed on frontend', async ( {
			pageObject,
			admin,
			editor,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'handPicked' );

			// Select products and click Done
			const productPicker = editor.canvas.locator(
				SELECTORS.productPicker
			);
			await productPicker.getByText( 'Album (woo-album)' ).click();
			await productPicker.getByText( 'Beanie (woo-beanie)' ).click();
			await productPicker.locator( SELECTORS.pickerDoneButton ).click();

			await pageObject.refreshLocators( 'editor' );
			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.products ).toHaveCount( 2 );
		} );
	} );

	for ( const collection of taxonomyCollections ) {
		test.describe( `${ collection.name }`, () => {
			test( `Can select ${ collection.termName } and Done button becomes enabled`, async ( {
				pageObject,
				admin,
				editor,
			} ) => {
				await admin.createNewPost();
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInPost( collection.slug );

				const taxonomyPicker = editor.canvas.locator(
					SELECTORS.taxonomyPicker
				);
				const doneButton = taxonomyPicker.locator(
					SELECTORS.pickerDoneButton
				);

				// Initially disabled
				await expect( doneButton ).toBeDisabled();

				// Select a term
				await taxonomyPicker
					.getByRole( 'checkbox', { name: collection.termLabel } )
					.click();

				// Done button should now be enabled
				await expect( doneButton ).toBeEnabled();

				// Click Done
				await doneButton.click();

				// Picker should be hidden and products should be displayed
				await expect( taxonomyPicker ).toBeHidden();
				await pageObject.refreshLocators( 'editor' );
				await expect( pageObject.products ).toHaveCount(
					collection.expectedProductCount
				);
			} );

			test( `Products from selected ${ collection.termName } are displayed on frontend`, async ( {
				pageObject,
				admin,
				editor,
			} ) => {
				await admin.createNewPost();
				await pageObject.insertProductCollection();
				await pageObject.chooseCollectionInPost( collection.slug );

				// Select term and click Done
				const taxonomyPicker = editor.canvas.locator(
					SELECTORS.taxonomyPicker
				);
				await taxonomyPicker
					.getByRole( 'checkbox', { name: collection.termLabel } )
					.click();
				await taxonomyPicker
					.locator( SELECTORS.pickerDoneButton )
					.click();

				await pageObject.refreshLocators( 'editor' );
				await pageObject.publishAndGoToFrontend();
				await expect( pageObject.products ).toHaveCount(
					collection.expectedProductCount
				);
			} );
		} );
	}

	test.describe( 'Collection switching', () => {
		test( 'Switching from Hand-Picked to Products by Category shows taxonomy picker', async ( {
			pageObject,
			admin,
			editor,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'handPicked' );

			// Select a product and click Done
			const productPicker = editor.canvas.locator(
				SELECTORS.productPicker
			);
			await productPicker.getByText( 'Album (woo-album)' ).click();
			await productPicker.locator( SELECTORS.pickerDoneButton ).click();

			// Switch to Products by Category using toolbar
			await pageObject.changeCollectionUsingToolbar(
				'productsByCategory'
			);

			// Taxonomy picker should now be shown
			const taxonomyPicker = editor.canvas.locator(
				SELECTORS.taxonomyPicker
			);
			await expect( taxonomyPicker ).toBeVisible();
		} );

		test( 'Switching to a non-picker collection displays products immediately', async ( {
			pageObject,
			admin,
			editor,
		} ) => {
			await admin.createNewPost();
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInPost( 'handPicked' );

			// Select a product and click Done
			const productPicker = editor.canvas.locator(
				SELECTORS.productPicker
			);
			await productPicker.getByText( 'Album (woo-album)' ).click();
			await productPicker.locator( SELECTORS.pickerDoneButton ).click();

			// Switch to Featured Products (no picker needed)
			await pageObject.changeCollectionUsingToolbar( 'featured' );

			// No picker should be shown
			await expect( productPicker ).toBeHidden();
			const taxonomyPicker = editor.canvas.locator(
				SELECTORS.taxonomyPicker
			);
			await expect( taxonomyPicker ).toBeHidden();

			// Products should be displayed
			await pageObject.refreshLocators( 'editor' );
			await expect( pageObject.products ).toHaveCount( 4 );
		} );
	} );
} );
