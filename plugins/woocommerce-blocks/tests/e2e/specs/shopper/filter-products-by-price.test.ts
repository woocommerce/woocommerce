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
	goToTemplateEditor,
	openBlockEditorSettings,
	saveTemplate,
	useTheme,
	waitForAllProductsBlockLoaded,
} from '../../utils';
import { clickLink } from '../../../utils';

const block = {
	name: 'Filter Products by Price',
	slug: 'woocommerce/price-filter',
	class: '.wc-block-price-filter',
	selectors: {
		editor: {
			filterButtonToggle: "//label[text()='Filter button']",
		},
		frontend: {
			priceMaxAmount: '.wc-block-price-filter__amount--max',
			productsList: '.wc-block-grid__products > li',
			classicProductsList: '.products.columns-3 > li',
			submitButton: '.wc-block-components-filter-submit-button',
		},
	},
	urlSearchParamWhenFilterIsApplied: '?max_price=1.99',
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
	await page.$eval(
		selectors.frontend.priceMaxAmount,
		( el ) => ( ( el as HTMLInputElement ).value = '' )
	);
	await page.keyboard.type( '1.99' );
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
			await insertBlock( 'All Products' );
			await insertBlock( 'Active Product Filters' );
			await publishPost();

			const link = await page.evaluate( () =>
				wp.data.select( 'core/editor' ).getPermalink()
			);
			await page.goto( link );
		} );

		it( 'should render', async () => {
			await waitForAllProductsBlockLoaded();
			const products = await page.$$( selectors.frontend.productsList );

			expect( products ).toHaveLength( 5 );
		} );

		it( 'should show only products that match the filter', async () => {
			const isRefreshed = jest.fn( () => void 0 );
			page.on( 'load', isRefreshed );
			await setMaxPrice();
			await expect( page ).toMatchElement(
				'.wc-block-active-filters__title',
				{
					text: 'Active filters',
				}
			);
			await waitForAllProductsBlockLoaded();

			const products = await page.$$( selectors.frontend.productsList );

			expect( isRefreshed ).not.toBeCalled();

			expect( products ).toHaveLength( 1 );

			await expect( page ).toMatch( block.foundProduct );
		} );
	} );

	describe( 'with PHP classic template ', () => {
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

		it( 'should refresh the page only if the user click on button', async () => {
			await goToTemplateEditor( {
				postId: productCatalogTemplateId,
			} );

			await selectBlockByName( block.slug );
			await openBlockEditorSettings( { isFSEEditor: true } );
			await page.waitForXPath(
				block.selectors.editor.filterButtonToggle
			);
			const [ filterButtonToggle ] = await page.$x(
				block.selectors.editor.filterButtonToggle
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

			await setMaxPrice();

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
} );
