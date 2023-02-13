/**
 * External dependencies
 */
import {
	deleteAllTemplates,
	ensureSidebarOpened,
} from '@wordpress/e2e-test-utils';

import { switchBlockInspectorTabWhenGutenbergIsInstalled } from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import {
	BASE_URL,
	goToTemplateEditor,
	saveTemplate,
	useTheme,
} from '../../../utils';
import {
	addProductQueryBlock,
	block,
	configureProductQueryBlock,
	getProductsNameFromClassicTemplate,
	getProductsNameFromProductQuery,
	toggleInheritQueryFromTemplateSetting,
} from './utils';

describe( `${ block.name } Block`, () => {
	useTheme( 'emptytheme' );

	describe( 'with All Templates', () => {
		beforeEach( async () => {
			const productCatalogTemplateId =
				'woocommerce/woocommerce//archive-product';

			await goToTemplateEditor( {
				postId: productCatalogTemplateId,
			} );
			await ensureSidebarOpened();
			await addProductQueryBlock();
			await switchBlockInspectorTabWhenGutenbergIsInstalled( 'Settings' );
		} );

		it( 'when Inherit Query from template is disabled all the settings that customize the query should be hidden', async () => {
			await toggleInheritQueryFromTemplateSetting();

			await expect( page ).toMatchElement(
				block.selectors.editor.popularFilter
			);

			await expect( page ).toMatchElement(
				block.selectors.editor.advanceFilterOptionsButton
			);
		} );

		it( 'when Inherit Query from template is enabled all the settings that customize the query should be hidden', async () => {
			await configureProductQueryBlock();

			const popularFilterEl = await page.$(
				block.selectors.editor.popularFilter
			);
			const advanceFilterOptionsButton = await page.$(
				block.selectors.editor.advanceFilterOptionsButton
			);

			expect( popularFilterEl ).toBeNull();
			expect( advanceFilterOptionsButton ).toBeNull();
		} );
	} );

	describe( 'with Product Catalog Template', () => {
		beforeAll( async () => {
			const productCatalogTemplateId =
				'woocommerce/woocommerce//archive-product';

			await goToTemplateEditor( {
				postId: productCatalogTemplateId,
			} );
			await addProductQueryBlock();
			await configureProductQueryBlock();
			await page.waitForNetworkIdle();
			await saveTemplate();
			await page.waitForNetworkIdle();
			await page.goto( BASE_URL + '/shop' );
			await page.waitForNetworkIdle();
		} );

		afterAll( async () => {
			await deleteAllTemplates( 'wp_template' );
		} );

		it( 'should render the same products in the same position of the classic template', async () => {
			const classicProducts = await getProductsNameFromClassicTemplate();
			const products = await getProductsNameFromProductQuery();

			expect( classicProducts ).toEqual( products );
		} );
	} );

	describe( 'with Products By Category Template', () => {
		beforeAll( async () => {
			const taxonomyProductCategory =
				'woocommerce/woocommerce//taxonomy-product_cat';

			await goToTemplateEditor( {
				postId: taxonomyProductCategory,
			} );
			await addProductQueryBlock();
			await configureProductQueryBlock();
			await page.waitForNetworkIdle();
			await saveTemplate();
			await page.waitForNetworkIdle();
			await Promise.all( [
				page.goto( BASE_URL + '/product-category/uncategorized/' ),
				page.waitForNavigation( {
					waitUntil: 'networkidle0',
				} ),
			] );
		} );

		afterAll( async () => {
			await deleteAllTemplates( 'wp_template' );
		} );

		it( 'should render the same products in the same position of the classic template', async () => {
			const classicProducts = await getProductsNameFromClassicTemplate();
			const products = await getProductsNameFromProductQuery();

			expect( classicProducts ).toEqual( products );
		} );
	} );

	describe( 'with Products By Tag Template', () => {
		beforeAll( async () => {
			const tagProductCategory =
				'woocommerce/woocommerce//taxonomy-product_tag';
			await goToTemplateEditor( {
				postId: tagProductCategory,
			} );
			await addProductQueryBlock();
			await configureProductQueryBlock();
			await page.waitForNetworkIdle();
			await saveTemplate();
			await page.waitForNetworkIdle();
			await Promise.all( [
				page.goto( BASE_URL + '/product-tag/newest/' ),
				page.waitForNavigation( {
					waitUntil: 'networkidle0',
				} ),
			] );
		} );

		afterAll( async () => {
			await deleteAllTemplates( 'wp_template' );
		} );

		it( 'should render the same products in the same position of the classic template', async () => {
			const classicProducts = await getProductsNameFromClassicTemplate();
			const products = await getProductsNameFromProductQuery();

			expect( classicProducts ).toEqual( products );
		} );
	} );

	describe( 'with Products Search Results Template', () => {
		beforeAll( async () => {
			const productSearchResults =
				'woocommerce/woocommerce//product-search-results';
			await goToTemplateEditor( {
				postId: productSearchResults,
			} );
			await addProductQueryBlock();
			await configureProductQueryBlock();
			await page.waitForNetworkIdle();
			await saveTemplate();
			await page.waitForNetworkIdle();
			await Promise.all( [
				page.goto( BASE_URL + '/?s=usb&post_type=product' ),
				page.waitForNavigation( {
					waitUntil: 'networkidle0',
				} ),
			] );
		} );

		afterAll( async () => {
			await deleteAllTemplates( 'wp_template' );
		} );

		it( 'should render the same products in the same position of the classic template', async () => {
			const classicProducts = await getProductsNameFromClassicTemplate();
			const products = await getProductsNameFromProductQuery();
			expect( classicProducts ).toEqual( products );
		} );
	} );
} );
