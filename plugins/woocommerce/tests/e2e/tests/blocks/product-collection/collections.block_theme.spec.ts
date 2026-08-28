/**
 * External dependencies
 */
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

test.describe( 'Product Collection: Collections', () => {
	test( 'Top Rated Products collection can be added and displays proper products', async ( {
		pageObject,
	} ) => {
		const topRatedProducts = [
			'V-Neck T-Shirt',
			'Hoodie',
			'Hoodie with Logo',
			'T-Shirt',
			'Beanie',
		];

		await pageObject.createNewPostAndInsertBlock( 'topRated' );
		await expect( pageObject.productTitles ).toHaveText( topRatedProducts );

		await pageObject.publishAndGoToFrontend();
		await expect( pageObject.productTitles ).toHaveText( topRatedProducts );
	} );

	test( 'Product Catalog Collection can be added in product archive and syncs query with template', async ( {
		pageObject,
		editor,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postId: `${ BLOCK_THEME_SLUG }//archive-product`,
			postType: 'wp_template',
			canvas: 'edit',
		} );
		await editor.setContent( '' );
		await pageObject.insertProductCollection();
		await pageObject.chooseCollectionInTemplate();
		await editor.openDocumentSettingsSidebar();

		const queryType = pageObject
			.locateSidebarSettings()
			.getByLabel( SELECTORS.usePageContextControl );
		await expect( queryType.getByLabel( 'Default' ) ).toBeChecked();
		await expect( queryType.getByLabel( 'Custom' ) ).not.toBeChecked();

		await pageObject.refreshLocators( 'editor' );
		await expect( pageObject.products.first() ).toBeVisible();
		await expect(
			pageObject.productTitles.getByRole( 'link' ).first()
		).toBeVisible();
		const editorProductPaths = await pageObject.getProductPermalinkPaths();
		expect( editorProductPaths ).not.toHaveLength( 0 );

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );
		await pageObject.goToProductCatalogFrontend();
		await expect( pageObject.products.first() ).toBeVisible();
		await expect(
			pageObject.productTitles.getByRole( 'link' ).first()
		).toBeVisible();
		await expect
			.poll( async () => pageObject.getProductPermalinkPaths() )
			.toEqual( editorProductPaths );
	} );

	test.describe( 'Related Products collection', () => {
		test( 'Can configure related products criteria using "Related by" settings', async ( {
			pageObject,
			editor,
		} ) => {
			await pageObject.createNewPostAndInsertBlock( 'bestSellers' );
			const sidebarSettings = pageObject.locateSidebarSettings();
			const relatedByControl = sidebarSettings.locator(
				'.wc-block-editor-product-collection-inspector-controls__relate-by'
			);
			await expect( relatedByControl ).toBeHidden();

			await pageObject.changeCollectionUsingToolbar( 'relatedProducts' );
			await pageObject.chooseProductInEditorProductPickerIfAvailable(
				editor.canvas
			);
			await expect( relatedByControl ).toBeVisible();

			const categoriesCheckbox =
				sidebarSettings.getByLabel( 'Categories' );
			const tagsCheckbox = sidebarSettings.getByLabel( 'Tags' );
			await expect( categoriesCheckbox ).toBeChecked();
			await expect( tagsCheckbox ).toBeChecked();
			await expect( pageObject.productTitles ).toHaveText( [ 'Single' ] );

			await categoriesCheckbox.uncheck();
			await expect(
				editor.canvas.getByText(
					'No products to display. Try adjusting the filters in the block settings panel.'
				)
			).toBeVisible();

			await categoriesCheckbox.check();
			await expect( pageObject.productTitles ).toHaveText( [ 'Single' ] );
			await pageObject.publishAndGoToFrontend();
			await expect( pageObject.productTitles ).toHaveText( [ 'Single' ] );
		} );
	} );
} );
