/**
 * External dependencies
 */
import {
	createNewPost,
	deleteAllTemplates,
	insertBlock,
	switchBlockInspectorTab,
	switchUserToAdmin,
	publishPost,
	ensureSidebarOpened,
} from '@wordpress/e2e-test-utils';
import {
	selectBlockByName,
	getToggleIdByLabel,
	saveOrPublish,
} from '@woocommerce/blocks-test-utils';
import { setCheckbox } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	BASE_URL,
	enableApplyFiltersButton,
	goToTemplateEditor,
	insertAllProductsBlock,
	saveTemplate,
	useTheme,
	waitForAllProductsBlockLoaded,
	waitForCanvas,
} from '../../utils';

const block = {
	name: 'Filter by Stock',
	slug: 'woocommerce/stock-filter',
	class: '.wc-block-stock-filter',
	selectors: {
		frontend: {
			productsList: '.wc-block-grid__products > li',
			classicProductsList: '.products.columns-3 > li',
			filter: 'input[id=outofstock]',
			submitButton: '.wc-block-components-filter-submit-button',
			queryProductsList: '.wp-block-post-template > li',
		},
	},
	urlSearchParamWhenFilterIsApplied: '?filter_stock_status=outofstock',
	foundProduct: 'Woo Single #3',
};

const { selectors } = block;

const goToShopPage = () =>
	page.goto( BASE_URL + '/shop', {
		waitUntil: 'networkidle0',
	} );

describe( `${ block.name } Block`, () => {
	describe.skip( 'with All Products Block', () => {
		let link = '';
		beforeAll( async () => {
			await switchUserToAdmin();
			await createNewPost( {
				postType: 'post',
				title: block.name,
			} );

			await insertBlock( block.name );
			await insertAllProductsBlock();
			await publishPost();

			link = await page.evaluate( () =>
				wp.data.select( 'core/editor' ).getPermalink()
			);
		} );

		it( 'should render', async () => {
			await page.goto( link );
			await waitForAllProductsBlockLoaded();
			const products = await page.$$( selectors.frontend.productsList );

			expect( products ).toHaveLength( 5 );
		} );

		it( 'should show only products that match the filter', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );
			await page.click( selectors.frontend.filter );
			await waitForAllProductsBlockLoaded();
			const products = await page.$$( selectors.frontend.productsList );
			expect( isRefreshed ).not.toBeCalled();
			expect( products ).toHaveLength( 1 );
			await expect( page ).toMatch( block.foundProduct );
		} );
	} );

	describe.skip( 'with PHP classic template (Products Block and Classic Template Block)', () => {
		const productCatalogTemplateId =
			'woocommerce/woocommerce//archive-product';

		useTheme( 'emptytheme' );
		beforeAll( async () => {
			await deleteAllTemplates( 'wp_template' );
			await deleteAllTemplates( 'wp_template_part' );
			await goToTemplateEditor( {
				postId: productCatalogTemplateId,
			} );
			await insertBlock( 'WooCommerce Product Grid Block' );
			await insertBlock( block.name );
			await saveTemplate();
			await goToShopPage();
		} );

		beforeEach( async () => {
			await goToShopPage();
		} );

		afterAll( async () => {
			await deleteAllTemplates( 'wp_template' );
			await deleteAllTemplates( 'wp_template_part' );
		} );

		it( 'should render', async () => {
			const products = await page.$$(
				selectors.frontend.classicProductsList
			);

			const productsBlockProductsList = await page.$$(
				selectors.frontend.queryProductsList
			);

			expect( products ).toHaveLength( 5 );
			expect( productsBlockProductsList ).toHaveLength( 5 );
		} );

		it( 'should show only products that match the filter', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );

			await page.waitForSelector( block.class + '.is-loading', {
				hidden: true,
			} );

			expect( isRefreshed ).not.toBeCalled();

			await page.waitForSelector( selectors.frontend.filter );

			await Promise.all( [
				page.waitForNavigation(),
				page.click( selectors.frontend.filter ),
			] );

			const products = await page.$$(
				selectors.frontend.classicProductsList
			);

			const productsBlockProductsList = await page.$$(
				selectors.frontend.queryProductsList
			);

			const pageURL = page.url();
			const parsedURL = new URL( pageURL );

			expect( isRefreshed ).toBeCalledTimes( 1 );
			expect( products ).toHaveLength( 1 );
			expect( productsBlockProductsList ).toHaveLength( 1 );

			await expect( page ).toMatch( block.foundProduct );
			expect( parsedURL.search ).toEqual(
				block.urlSearchParamWhenFilterIsApplied
			);
		} );

		it( 'should refresh the page only if the user click on button', async () => {
			await goToTemplateEditor( {
				postId: productCatalogTemplateId,
			} );

			await waitForCanvas();
			await selectBlockByName( block.slug );
			await enableApplyFiltersButton();
			await saveTemplate();
			await goToShopPage();

			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );

			await page.waitForSelector( block.class + '.is-loading', {
				hidden: true,
			} );

			expect( isRefreshed ).not.toBeCalled();

			await page.waitForSelector( selectors.frontend.filter );
			await page.click( selectors.frontend.filter );
			await Promise.all( [
				page.waitForNavigation( {
					waitUntil: 'networkidle0',
				} ),
				page.click( selectors.frontend.submitButton ),
			] );

			const pageURL = page.url();
			const parsedURL = new URL( pageURL );

			await page.waitForSelector(
				selectors.frontend.classicProductsList
			);
			const products = await page.$$(
				selectors.frontend.classicProductsList
			);

			const productsBlockProductsList = await page.$$(
				selectors.frontend.queryProductsList
			);

			expect( isRefreshed ).toBeCalledTimes( 1 );
			expect( products ).toHaveLength( 1 );
			expect( productsBlockProductsList ).toHaveLength( 1 );
			await expect( page ).toMatch( block.foundProduct );
			expect( parsedURL.search ).toEqual(
				block.urlSearchParamWhenFilterIsApplied
			);
		} );
	} );

	describe( 'with Product Query Block', () => {
		let editorPageUrl = '';
		let frontedPageUrl = '';

		useTheme( 'emptytheme' );
		beforeAll( async () => {
			await switchUserToAdmin();
			await createNewPost( {
				postType: 'post',
				title: block.name,
			} );

			await insertBlock( 'Products (Beta)' );
			await insertBlock( block.name );
			await publishPost();

			editorPageUrl = page.url();
			frontedPageUrl = await page.evaluate( () =>
				wp.data.select( 'core/editor' ).getPermalink()
			);
			await page.goto( frontedPageUrl );
		} );

		it( 'should show only products that match the filter', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );

			await page.waitForSelector( block.class + '.is-loading', {
				hidden: true,
			} );

			expect( isRefreshed ).not.toBeCalled();

			await page.waitForSelector( selectors.frontend.filter );

			await Promise.all( [
				page.waitForNavigation(),
				page.click( selectors.frontend.filter ),
			] );

			const products = await page.$$(
				selectors.frontend.queryProductsList
			);
			const pageURL = page.url();
			const parsedURL = new URL( pageURL );

			expect( isRefreshed ).toBeCalledTimes( 1 );
			expect( products ).toHaveLength( 1 );
			expect( parsedURL.search ).toEqual(
				block.urlSearchParamWhenFilterIsApplied
			);
		} );

		it( 'should refresh the page only if the user clicks on button', async () => {
			await page.goto( editorPageUrl );
			await waitForCanvas();
			await ensureSidebarOpened();
			await selectBlockByName( block.slug );
			await switchBlockInspectorTab( 'Settings' );
			await setCheckbox(
				await getToggleIdByLabel( "Show 'Apply filters' button" )
			);

			await saveOrPublish();
			await page.goto( frontedPageUrl );

			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );

			await page.waitForSelector( block.class + '.is-loading', {
				hidden: true,
			} );

			expect( isRefreshed ).not.toBeCalled();

			await page.waitForSelector( selectors.frontend.filter );
			await page.click( selectors.frontend.filter );
			await Promise.all( [
				page.waitForNavigation( {
					waitUntil: 'networkidle0',
				} ),
				page.click( selectors.frontend.submitButton ),
			] );

			const pageURL = page.url();
			const parsedURL = new URL( pageURL );

			await page.waitForSelector( selectors.frontend.queryProductsList );
			const products = await page.$$(
				selectors.frontend.queryProductsList
			);

			expect( isRefreshed ).toBeCalledTimes( 1 );
			expect( products ).toHaveLength( 1 );
			expect( parsedURL.search ).toEqual(
				block.urlSearchParamWhenFilterIsApplied
			);
		} );
	} );
} );
