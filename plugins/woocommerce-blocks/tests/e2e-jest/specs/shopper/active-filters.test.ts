/**
 * External dependencies
 */
import {
	insertBlock,
	deleteAllTemplates,
	canvas,
	createNewPost,
	switchUserToAdmin,
	publishPost,
} from '@wordpress/e2e-test-utils';
import { SHOP_PAGE } from '@woocommerce/e2e-utils';
import { Frame, Page } from 'puppeteer';

/**
 * Internal dependencies
 */
import {
	goToTemplateEditor,
	insertAllProductsBlock,
	useTheme,
	saveTemplate,
	waitForAllProductsBlockLoaded,
} from '../../utils';
import {
	clickLink,
	shopper,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
} from '../../../utils';

const block = {
	name: 'Active Filters',
	slug: 'woocommerce/active-filters',
	class: '.wp-block-woocommerce-active-filters',
	selectors: {
		editor: {
			firstAttributeInTheList:
				'.woocommerce-search-list__list > li > label > input.woocommerce-search-list__item-input',
			filterButtonToggle: "//label[text()='Filter button']",
			doneButton: '.wc-block-attribute-filter__selection > button',
		},
		frontend: {
			activeFilterType: '.wc-block-active-filters__list-item-type',
			activeFilterName: '.wc-block-active-filters__list-item-name',
			removeFilterButton: '.wc-block-active-filters__list-item-remove',
			removeAllFiltersButton: '.wc-block-active-filters__clear-all',
			stockFilterBlock: '.wc-block-stock-filter-list',
			attributeFilterBlock: '.wc-block-attribute-filter-list',
			productsList: '.wc-block-grid__products > li',
			productsBlockProducts: '.wp-block-post-template > li',
			classicProductsList: '.products.columns-3 > li',
		},
	},
};

const FILTER_STOCK_STATUS_TITLE = 'Stock Status';
const FILTER_STOCK_STATUS_PROPERTY = 'In stock';
const FILTER_CAPACITY_TITLE = 'Capacity:';
const FILTER_CAPACITY_PROPERTY = '128gb';

const { selectors } = block;

const insertBlocks = async () => {
	await insertBlock( 'Filter by Price' );
	await insertBlock( 'Filter by Stock' );
	await insertBlock( 'Filter by Attribute' );
	await insertBlock( block.name );
};

const configureAttributeFilterBlock = async ( pageOrCanvas: Page | Frame ) => {
	await pageOrCanvas.$eval(
		selectors.editor.firstAttributeInTheList,
		( el ) => ( el as HTMLElement ).click()
	);
	await pageOrCanvas.click( selectors.editor.doneButton );
};

const getActiveFilterTypeText = () =>
	page.$eval(
		selectors.frontend.activeFilterType,
		( el ) => ( el as HTMLElement ).innerText
	);

const getActiveFilterNameText = () =>
	page.$eval(
		selectors.frontend.activeFilterName,
		( el ) => ( el as HTMLElement ).childNodes[ 1 ].textContent
	);

describe.skip( 'Shopper → Active Filters Block', () => {
	describe( 'With All Products block', () => {
		beforeAll( async () => {
			await switchUserToAdmin();
			await createNewPost( {
				postType: 'post',
				title: block.name,
			} );

			await insertBlocks();
			await insertAllProductsBlock();
			await configureAttributeFilterBlock( page );
			await publishPost();

			const link = await page.evaluate( () =>
				wp.data.select( 'core/editor' ).getPermalink()
			);
			await page.goto( link );
		} );

		beforeEach( async () => {
			await page.reload();
		} );

		it( 'Active Filters is hidden if there is no filter selected', async () => {
			expect( page ).not.toMatch( 'Active Filters' );
		} );

		it.skip( 'Shows selected filters', async () => {
			const isRefreshed = jest.fn( () => void 0 );

			await page.waitForSelector( block.class );
			await page.waitForSelector(
				selectors.frontend.attributeFilterBlock + '.is-loading',
				{ hidden: true }
			);

			await page.waitForSelector( selectors.frontend.stockFilterBlock );

			await expect( page ).toClick( 'label', {
				text: FILTER_CAPACITY_PROPERTY,
			} );

			const activeFilterType = await getActiveFilterTypeText();

			expect( activeFilterType ).toBe(
				FILTER_CAPACITY_TITLE.toUpperCase()
			);

			await waitForAllProductsBlockLoaded();

			await expect( page ).toClick( 'label', {
				text: FILTER_STOCK_STATUS_PROPERTY,
			} );

			await expect( page ).toMatch( FILTER_STOCK_STATUS_TITLE );

			const activeFilterNameText = await getActiveFilterNameText();

			expect( activeFilterNameText ).toBe( FILTER_STOCK_STATUS_PROPERTY );

			await waitForAllProductsBlockLoaded();

			const products = await page.$$( selectors.frontend.productsList );
			expect( products ).toHaveLength( 1 );
			expect( isRefreshed ).not.toHaveBeenCalled();
			await expect( page ).toMatch( SIMPLE_PHYSICAL_PRODUCT_NAME );
		} );

		it.skip( 'When clicking the X on a filter it removes a filter', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			await page.waitForSelector( block.class );
			await page.waitForSelector(
				selectors.frontend.attributeFilterBlock + '.is-loading',
				{
					hidden: true,
				}
			);

			await expect( page ).toClick( 'label', {
				text: FILTER_CAPACITY_PROPERTY,
			} );

			await expect( page ).toClick(
				selectors.frontend.removeFilterButton
			);

			expect( page ).not.toMatch( 'Active Filters' );

			await waitForAllProductsBlockLoaded();

			const products = await page.$$( selectors.frontend.productsList );
			expect( products ).toHaveLength( 5 );
			expect( isRefreshed ).not.toHaveBeenCalled();
		} );

		it.skip( 'Clicking "Clear All" button removes all active filters', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			await page.waitForSelector( block.class );
			await page.waitForSelector(
				selectors.frontend.attributeFilterBlock + '.is-loading',
				{ hidden: true }
			);
			await page.waitForSelector( selectors.frontend.stockFilterBlock );

			await expect( page ).toClick( 'label', {
				text: FILTER_STOCK_STATUS_PROPERTY,
			} );

			await expect( page ).toClick( 'label', {
				text: FILTER_CAPACITY_PROPERTY,
			} );

			await page.click( selectors.frontend.removeAllFiltersButton );

			await waitForAllProductsBlockLoaded();

			const products = await page.$$( selectors.frontend.productsList );

			expect( products ).toHaveLength( 5 );
			expect( isRefreshed ).not.toHaveBeenCalled();
		} );
	} );
	describe.skip( 'With PHP Templates (Products Block and Classic Template Block)', () => {
		useTheme( 'emptytheme' );
		beforeAll( async () => {
			await deleteAllTemplates( 'wp_template_part' );
			await deleteAllTemplates( 'wp_template' );
			await goToTemplateEditor( {
				postId: 'woocommerce/woocommerce//archive-product',
			} );

			await insertBlock( 'WooCommerce Product Grid Block' );
			await insertBlocks();

			const canvasEl = canvas();
			await configureAttributeFilterBlock( canvasEl );
			await saveTemplate();
		} );

		beforeEach( async () => {
			await shopper.goToShop();
		} );

		afterAll( async () => {
			await deleteAllTemplates( 'wp_template' );
			await deleteAllTemplates( 'wp_template_part' );
		} );

		it( 'Active Filters is hidden if there is no filter selected', async () => {
			expect( page ).not.toMatch( 'Active Filters' );
		} );

		it( 'Shows selected filters', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );
			await page.waitForSelector( block.class );
			await page.waitForSelector(
				selectors.frontend.attributeFilterBlock + '.is-loading',
				{ hidden: true }
			);

			await expect( page ).toClick( 'label', {
				text: FILTER_CAPACITY_PROPERTY,
			} );

			await page.waitForNavigation( { waitUntil: 'networkidle0' } );

			await page.waitForSelector( block.class );

			const activeFilterType = await getActiveFilterTypeText();

			expect( activeFilterType ).toBe(
				FILTER_CAPACITY_TITLE.toUpperCase()
			);
			await page.waitForSelector( selectors.frontend.stockFilterBlock );

			await expect( page ).toClick( 'label', {
				text: FILTER_STOCK_STATUS_PROPERTY,
			} );

			await page.waitForNavigation( { waitUntil: 'networkidle0' } );

			await page.waitForSelector( block.class );

			const activeFilterNameText = await getActiveFilterNameText();
			expect( activeFilterNameText ).toBe( FILTER_STOCK_STATUS_PROPERTY );

			const classicProductsList = await page.$$(
				selectors.frontend.classicProductsList
			);

			const products = await page.$$(
				selectors.frontend.productsBlockProducts
			);

			expect( isRefreshed ).toHaveBeenCalledTimes( 2 );
			expect( products ).toHaveLength( 1 );
			expect( classicProductsList ).toHaveLength( 1 );
			await expect( page ).toMatch( SIMPLE_PHYSICAL_PRODUCT_NAME );
		} );

		it( 'When clicking the X on a filter it removes a filter and triggers a page refresh', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );
			await page.waitForSelector( selectors.frontend.stockFilterBlock );

			expect( isRefreshed ).not.toHaveBeenCalled();
			await expect( page ).toClick( 'label', {
				text: FILTER_STOCK_STATUS_PROPERTY,
			} );

			await page.waitForNavigation( { waitUntil: 'networkidle0' } );

			await page.waitForSelector( block.class );

			await expect( page ).toClick( 'label', {
				text: FILTER_CAPACITY_PROPERTY,
			} );

			await page.waitForNavigation( { waitUntil: 'networkidle0' } );

			await page.waitForSelector( block.class );

			await clickLink( selectors.frontend.removeFilterButton );

			const classicProductsList = await page.$$(
				selectors.frontend.classicProductsList
			);

			const products = await page.$$(
				selectors.frontend.productsBlockProducts
			);

			expect( page.url() ).not.toMatch( 'instock' );
			expect( page.url() ).toMatch( FILTER_CAPACITY_PROPERTY );
			expect( isRefreshed ).toHaveBeenCalledTimes( 3 );
			expect( products ).toHaveLength( 1 );
			expect( classicProductsList ).toHaveLength( 1 );
			await expect( page ).toMatch( SIMPLE_PHYSICAL_PRODUCT_NAME );
		} );

		it( 'Clicking "Clear All" button removes all active filters and the page redirects to the base URL', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );
			await page.waitForSelector( selectors.frontend.stockFilterBlock );

			await expect( page ).toClick( 'label', {
				text: FILTER_STOCK_STATUS_PROPERTY,
			} );

			await page.waitForNavigation( { waitUntil: 'networkidle0' } );

			await page.waitForSelector( block.class );

			await clickLink( selectors.frontend.removeAllFiltersButton );

			const classicProductsList = await page.$$(
				selectors.frontend.classicProductsList
			);

			const products = await page.$$(
				selectors.frontend.productsBlockProducts
			);

			expect( page.url() ).not.toMatch( 'instock' );
			expect( page.url() ).toMatch( SHOP_PAGE );
			expect( isRefreshed ).toHaveBeenCalledTimes( 2 );
			expect( classicProductsList ).toHaveLength( 5 );
			expect( products ).toHaveLength( 5 );
		} );
	} );
} );
