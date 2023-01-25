/**
 * External dependencies
 */
import {
	createNewPost,
	deleteAllTemplates,
	insertBlock,
	switchUserToAdmin,
	publishPost,
	ensureSidebarOpened,
} from '@wordpress/e2e-test-utils';
import {
	selectBlockByName,
	insertBlockUsingSlash,
	saveOrPublish,
	getToggleIdByLabel,
	switchBlockInspectorTabWhenGutenbergIsInstalled,
} from '@woocommerce/blocks-test-utils';
import { setCheckbox } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	BASE_URL,
	goToTemplateEditor,
	saveTemplate,
	useTheme,
	waitForAllProductsBlockLoaded,
	waitForCanvas,
} from '../../utils';

const block = {
	name: 'Filter by Rating',
	slug: 'woocommerce/rating-filter',
	class: '.wc-block-rating-filter',
	selectors: {
		editor: {
			filterButtonToggle:
				'//label[text()="Show \'Apply filters\' button"]',
		},
		frontend: {
			productsList: '.wc-block-grid__products > li',
			queryProductsList: '.wp-block-post-template > li',
			classicProductsList: '.products.columns-3 > li',
			fiveStarInput: ".wc-block-rating-filter label[for='5'] input",
			submitButton: '.wc-block-components-filter-submit-button',
		},
	},
	urlSearchParamWhenFilterIsApplied: '?rating_filter=5',
};

const FIVE_STAR_PRODUCTS_AMOUNT = 5;

const { selectors } = block;

const goToShopPage = () =>
	page.goto( BASE_URL + '/shop', {
		waitUntil: 'networkidle0',
	} );

describe( `${ block.name } Block`, () => {
	describe( 'with All Products Block', () => {
		let link = '';
		beforeAll( async () => {
			await switchUserToAdmin();
			await createNewPost( {
				postType: 'post',
				title: block.name,
			} );

			await insertBlock( block.name );
			await insertBlockUsingSlash( 'All Products' );
			await publishPost();

			link = await page.evaluate( () =>
				wp.data.select( 'core/editor' ).getPermalink()
			);
			await page.goto( link );
		} );

		it( 'should render', async () => {
			await expect( page ).toMatchElement( block.class );
		} );

		it( 'should show only products that match the filter', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );
			await waitForAllProductsBlockLoaded();
			await page.waitForSelector( selectors.frontend.fiveStarInput );
			await page.click( selectors.frontend.fiveStarInput );
			await waitForAllProductsBlockLoaded();
			const products = await page.$$( selectors.frontend.productsList );
			expect( isRefreshed ).not.toBeCalled();
			expect( products ).toHaveLength( FIVE_STAR_PRODUCTS_AMOUNT );
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

		it( 'should render', async () => {
			await expect( page ).toMatchElement( block.class );
		} );

		it( 'should show only products that match the filter', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );

			await page.waitForSelector( block.class + '.is-loading', {
				hidden: true,
			} );

			expect( isRefreshed ).not.toBeCalled();

			await page.waitForSelector( selectors.frontend.fiveStarInput );

			await Promise.all( [
				page.waitForNavigation(),
				page.click( selectors.frontend.fiveStarInput ),
			] );

			const products = await page.$$(
				selectors.frontend.classicProductsList
			);
			const pageURL = page.url();
			const parsedURL = new URL( pageURL );

			expect( isRefreshed ).toBeCalledTimes( 1 );
			expect( products ).toHaveLength( FIVE_STAR_PRODUCTS_AMOUNT );
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
			await ensureSidebarOpened();
			await switchBlockInspectorTabWhenGutenbergIsInstalled( 'Settings' );

			await page.waitForXPath(
				block.selectors.editor.filterButtonToggle
			);

			const [ filterButtonToggle ] = await page.$x(
				selectors.editor.filterButtonToggle
			);
			await filterButtonToggle.click();
			await saveTemplate();
			await goToShopPage();

			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );

			await page.waitForSelector( block.class + '.is-loading', {
				hidden: true,
			} );

			expect( isRefreshed ).not.toBeCalled();

			await page.waitForSelector( selectors.frontend.fiveStarInput );
			await page.click( selectors.frontend.fiveStarInput );
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

			expect( isRefreshed ).toBeCalledTimes( 1 );
			expect( products ).toHaveLength( FIVE_STAR_PRODUCTS_AMOUNT );
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
			await page.waitForNetworkIdle();
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

			await page.waitForSelector( selectors.frontend.fiveStarInput );

			await Promise.all( [
				page.waitForNavigation(),
				page.click( selectors.frontend.fiveStarInput ),
			] );

			const products = await page.$$(
				selectors.frontend.queryProductsList
			);
			const pageURL = page.url();
			const parsedURL = new URL( pageURL );

			expect( isRefreshed ).toBeCalledTimes( 1 );
			expect( products ).toHaveLength( FIVE_STAR_PRODUCTS_AMOUNT );
			expect( parsedURL.search ).toEqual(
				block.urlSearchParamWhenFilterIsApplied
			);
		} );

		it( 'should refresh the page only if the user click on button', async () => {
			await page.goto( editorPageUrl );
			await waitForCanvas();
			await ensureSidebarOpened();
			await selectBlockByName( block.slug );
			await switchBlockInspectorTabWhenGutenbergIsInstalled( 'Settings' );
			await setCheckbox(
				await getToggleIdByLabel( "Show 'Apply filters' button", 1 )
			);

			await saveOrPublish();
			await page.goto( frontedPageUrl );

			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );

			await page.waitForSelector( block.class + '.is-loading', {
				hidden: true,
			} );

			expect( isRefreshed ).not.toBeCalled();

			await page.waitForSelector( selectors.frontend.fiveStarInput );
			await page.click( selectors.frontend.fiveStarInput );
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
			expect( products ).toHaveLength( FIVE_STAR_PRODUCTS_AMOUNT );
			expect( parsedURL.search ).toEqual(
				block.urlSearchParamWhenFilterIsApplied
			);
		} );
	} );
} );
