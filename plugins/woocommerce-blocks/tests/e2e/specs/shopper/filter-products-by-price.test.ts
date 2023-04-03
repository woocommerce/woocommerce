/**
 * External dependencies
 */
import {
	createNewPost,
	deleteAllTemplates,
	insertBlock,
	switchUserToAdmin,
	publishPost,
} from '@wordpress/e2e-test-utils';
import { selectBlockByName } from '@woocommerce/blocks-test-utils';

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
	waitForCanvas,
	waitForAllProductsBlockLoaded,
} from '../../utils';
import { clickLink, saveOrPublish } from '../../../utils';

const block = {
	name: 'Filter by Price',
	slug: 'woocommerce/price-filter',
	class: '.wc-block-price-filter',
	selectors: {
		frontend: {
			priceMaxAmount: '.wc-block-price-filter__amount--max',
			productsList: '.wc-block-grid__products > li',
			queryProductsList: '.wp-block-post-template > li',
			classicProductsList: '.products.columns-3 > li',
			submitButton: '.wc-block-components-filter-submit-button',
		},
	},
	urlSearchParamWhenFilterIsApplied: '?max_price=2',
	foundProduct: '32GB USB Stick',
};

const { selectors } = block;

const goToShopPage = () =>
	page.goto( BASE_URL + '/shop', {
		waitUntil: 'networkidle0',
	} );

const setMaxPrice = async () => {
	await page.waitForSelector( selectors.frontend.priceMaxAmount );
	await page.focus( selectors.frontend.priceMaxAmount );
	await page.keyboard.down( 'Shift' );
	await page.keyboard.press( 'Home' );
	await page.keyboard.up( 'Shift' );
	await page.keyboard.type( '2' );
	await page.keyboard.press( 'Tab' );
};

describe( `${ block.name } Block`, () => {
	describe( 'with All Products Block', () => {
		beforeAll( async () => {
			await switchUserToAdmin();
			await createNewPost( {
				postType: 'post',
				title: block.name,
			} );

			await insertBlock( block.name );
			await insertAllProductsBlock();
			await insertBlock( 'Active Filters' );
			await publishPost();

			const link = await page.evaluate( () =>
				wp.data.select( 'core/editor' ).getPermalink()
			);
			await page.goto( link );
		} );

		it( 'should render products', async () => {
			await waitForAllProductsBlockLoaded();
			const products = await page.$$( selectors.frontend.productsList );

			expect( products ).toHaveLength( 5 );
		} );

		it( 'should show only products that match the filter', async () => {
			const isRefreshed = jest.fn( () => void 0 );

			page.on( 'load', isRefreshed );

			await setMaxPrice();

			await waitForAllProductsBlockLoaded();

			await expect( page ).toMatchElement( '.wc-blocks-filter-wrapper', {
				text: 'Active filters',
			} );

			const products = await page.$$( selectors.frontend.productsList );

			expect( isRefreshed ).not.toBeCalled();

			expect( products ).toHaveLength( 1 );

			await expect( page ).toMatch( block.foundProduct );
		} );
	} );

	describe( 'with PHP classic template', () => {
		const productCatalogTemplateId =
			'woocommerce/woocommerce//archive-product';

		useTheme( 'emptytheme' );
		beforeAll( async () => {
			await deleteAllTemplates( 'wp_template' );
			await deleteAllTemplates( 'wp_template_part' );

			await goToTemplateEditor( {
				postId: productCatalogTemplateId,
			} );
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

		it( 'should render products', async () => {
			const products = await page.$$(
				selectors.frontend.classicProductsList
			);

			expect( products ).toHaveLength( 5 );
		} );

		it( 'should show only products that match the filter', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );

			await page.waitForSelector( block.class + '.is-loading', {
				hidden: true,
			} );

			await expect( page ).toMatch( block.foundProduct );
			expect( isRefreshed ).not.toBeCalled();

			await Promise.all( [ page.waitForNavigation(), setMaxPrice() ] );

			await page.waitForSelector(
				selectors.frontend.classicProductsList
			);
			const products = await page.$$(
				selectors.frontend.classicProductsList
			);

			const pageURL = page.url();
			const parsedURL = new URL( pageURL );

			expect( isRefreshed ).toBeCalledTimes( 1 );
			expect( products ).toHaveLength( 1 );

			expect( parsedURL.search ).toEqual(
				block.urlSearchParamWhenFilterIsApplied
			);
			await expect( page ).toMatch( block.foundProduct );
		} );

		it( 'should refresh the page only if the user clicks on button', async () => {
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

			await setMaxPrice();

			expect( isRefreshed ).not.toBeCalled();

			await clickLink( selectors.frontend.submitButton );

			await page.waitForSelector(
				selectors.frontend.classicProductsList
			);

			const products = await page.$$(
				selectors.frontend.classicProductsList
			);

			const pageURL = page.url();
			const parsedURL = new URL( pageURL );

			expect( isRefreshed ).toBeCalledTimes( 1 );
			expect( products ).toHaveLength( 1 );
			await expect( page ).toMatch( block.foundProduct );
			expect( parsedURL.search ).toEqual(
				block.urlSearchParamWhenFilterIsApplied
			);
		} );
	} );

	describe( 'with Product Query Block', () => {
		let editorPageUrl = '';
		let frontedPageUrl = '';
		beforeAll( async () => {
			await switchUserToAdmin();
			await createNewPost( {
				postType: 'post',
				title: block.name,
			} );

			await insertBlock( 'Products (Beta)' );
			await insertBlock( block.name );
			await insertBlock( 'Active Filters' );
			await page.waitForNetworkIdle();
			await publishPost();

			editorPageUrl = page.url();
			frontedPageUrl = await page.evaluate( () =>
				wp.data.select( 'core/editor' ).getPermalink()
			);
			await page.goto( frontedPageUrl );
		} );

		it( 'should render products', async () => {
			const products = await page.$$(
				selectors.frontend.queryProductsList
			);

			expect( products ).toHaveLength( 5 );
		} );

		it( 'should show only products that match the filter', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );

			await page.waitForSelector( block.class + '.is-loading', {
				hidden: true,
			} );

			await expect( page ).toMatch( block.foundProduct );
			expect( isRefreshed ).not.toBeCalled();

			await Promise.all( [ setMaxPrice(), page.waitForNavigation() ] );

			await page.waitForSelector( selectors.frontend.queryProductsList );
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
			await expect( page ).toMatch( block.foundProduct );
		} );

		it( 'should refresh the page only if the user click on button', async () => {
			await page.goto( editorPageUrl );

			await waitForCanvas();
			await selectBlockByName( block.slug );
			await enableApplyFiltersButton();
			await saveOrPublish();
			await page.goto( frontedPageUrl );

			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );
			await page.waitForSelector( block.class + '.is-loading', {
				hidden: true,
			} );

			expect( isRefreshed ).not.toBeCalled();

			await setMaxPrice();

			expect( isRefreshed ).not.toBeCalled();

			await clickLink( selectors.frontend.submitButton );

			await page.waitForSelector( selectors.frontend.queryProductsList );

			const products = await page.$$(
				selectors.frontend.queryProductsList
			);

			const pageURL = page.url();
			const parsedURL = new URL( pageURL );

			expect( isRefreshed ).toBeCalledTimes( 1 );
			expect( products ).toHaveLength( 1 );
			await expect( page ).toMatch( block.foundProduct );
			expect( parsedURL.search ).toEqual(
				block.urlSearchParamWhenFilterIsApplied
			);
		} );
	} );
} );
