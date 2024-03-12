/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage, { SELECTORS } from './product-collection.page';

const test = base.extend< { pageObject: ProductCollectionPage } >( {
	pageObject: async (
		{ page, admin, editor, templateApiUtils, editorUtils },
		use
	) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
			templateApiUtils,
			editorUtils,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Product Collection', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		requestUtils.deleteAllTemplates( 'wp_template' );
	} );
	test.describe( 'Sync with current template (former "Inherit query from template")', () => {
		test( 'should work as expected in Product Catalog template', async ( {
			pageObject,
		} ) => {
			await pageObject.goToProductCatalogAndInsertCollection();

			const sidebarSettings = await pageObject.locateSidebarSettings();

			// Inherit query from template should be visible & enabled by default
			await expect(
				sidebarSettings.locator(
					SELECTORS.inheritQueryFromTemplateControl
				)
			).toBeVisible();
			await expect(
				sidebarSettings.locator(
					`${ SELECTORS.inheritQueryFromTemplateControl } input`
				)
			).toBeChecked();

			// "On sale control" should be hidden when inherit query from template is enabled
			await expect(
				sidebarSettings.getByLabel( SELECTORS.onSaleControlLabel )
			).toBeHidden();

			// "On sale control" should be visible when inherit query from template is disabled
			await pageObject.setInheritQueryFromTemplate( false );
			await expect(
				sidebarSettings.getByLabel( SELECTORS.onSaleControlLabel )
			).toBeVisible();

			// "On sale control" should retain its state when inherit query from template is enabled again
			pageObject.setShowOnlyProductsOnSale( {
				onSale: true,
				isLocatorsRefreshNeeded: false,
			} );
			await expect(
				sidebarSettings.getByLabel( SELECTORS.onSaleControlLabel )
			).toBeChecked();
			await pageObject.setInheritQueryFromTemplate( true );
			await expect(
				sidebarSettings.getByLabel( SELECTORS.onSaleControlLabel )
			).toBeHidden();
			await pageObject.setInheritQueryFromTemplate( false );
			await expect(
				sidebarSettings.getByLabel( SELECTORS.onSaleControlLabel )
			).toBeVisible();
			await expect(
				sidebarSettings.getByLabel( SELECTORS.onSaleControlLabel )
			).toBeChecked();
		} );

		test( 'is enabled by default in 1st Product Collection and disabled in 2nd+', async ( {
			pageObject,
		} ) => {
			// First Product Catalog
			// Option should be visible & ENABLED by default
			await pageObject.goToProductCatalogAndInsertCollection();

			const sidebarSettings = await pageObject.locateSidebarSettings();

			await expect(
				sidebarSettings.locator(
					SELECTORS.inheritQueryFromTemplateControl
				)
			).toBeVisible();
			await expect(
				sidebarSettings.locator(
					`${ SELECTORS.inheritQueryFromTemplateControl } input`
				)
			).toBeChecked();

			// Second Product Catalog
			// Option should be visible & DISABLED by default
			await pageObject.insertProductCollection();
			await pageObject.chooseCollectionInTemplate( 'productCatalog' );

			await expect(
				sidebarSettings.locator(
					SELECTORS.inheritQueryFromTemplateControl
				)
			).toBeVisible();
			await expect(
				sidebarSettings.locator(
					`${ SELECTORS.inheritQueryFromTemplateControl } input`
				)
			).not.toBeChecked();
		} );
	} );
} );
