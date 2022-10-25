/**
 * External dependencies
 */
import {
	canvas,
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

const block = {
	name: 'Filter by Attribute',
	slug: 'woocommerce/attribute-filter',
	class: '.wc-block-attribute-filter',
	selectors: {
		editor: {
			firstAttributeInTheList:
				'.woocommerce-search-list__list > li > label > input.woocommerce-search-list__item-input',
			filterButtonToggle:
				'//label[text()="Show \'Apply filters\' button"]',
			doneButton: '.wc-block-attribute-filter__selection > button',
		},
		frontend: {
			firstAttributeInTheList:
				'.wc-block-attribute-filter-list > li:not([class^="is-loading"])',
			productsList: '.wc-block-grid__products > li',
			classicProductsList: '.products.columns-3 > li',
			filter: "input[id='128gb']",
			submitButton: '.wc-block-components-filter-submit-button',
		},
	},
	urlSearchParamWhenFilterIsApplied:
		'?filter_capacity=128gb&query_type_capacity=or',
	foundProduct: '128GB USB Stick',
};

const { selectors } = block;

const goToShopPage = () =>
	page.goto( BASE_URL + '/shop', {
		waitUntil: 'networkidle0',
	} );

describe( `${ block.name } Block`, () => {
	describe( 'with All Products Block', () => {
		beforeAll( async () => {
			await switchUserToAdmin();
			await createNewPost( {
				postType: 'post',
				title: block.name,
			} );

			await insertBlock( 'All Products' );
			await insertBlock( block.name );
			const canvasEl = canvas();

			// It seems that .click doesn't work well with radio input element.
			await canvasEl.$eval(
				block.selectors.editor.firstAttributeInTheList,
				( el ) => ( el as HTMLInputElement ).click()
			);
			await canvasEl.click( selectors.editor.doneButton );
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

			await page.waitForSelector( selectors.frontend.filter );
			await page.click( selectors.frontend.filter );
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
			const canvasEl = canvas();

			// It seems that .click doesn't work well with radio input element.
			await canvasEl.$eval(
				block.selectors.editor.firstAttributeInTheList,
				( el ) => ( el as HTMLInputElement ).click()
			);
			await canvasEl.click( selectors.editor.doneButton );
			await saveTemplate();
		} );

		afterAll( async () => {
			await deleteAllTemplates( 'wp_template' );
			await deleteAllTemplates( 'wp_template_part' );
		} );

		beforeEach( async () => {
			await goToShopPage();
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

			expect( isRefreshed ).not.toBeCalled();

			await page.waitForSelector( selectors.frontend.filter );

			await Promise.all( [
				page.waitForNavigation(),
				page.click( selectors.frontend.filter ),
			] );

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

		it( 'should refresh the page only if the user click on button', async () => {
			await goToTemplateEditor( {
				postId: productCatalogTemplateId,
			} );

			await selectBlockByName( block.slug );
			await openBlockEditorSettings( { isFSEEditor: true } );
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
			await page.waitForSelector( selectors.frontend.filter );
			await page.click( selectors.frontend.filter );

			expect( isRefreshed ).not.toBeCalled();

			await Promise.all( [
				page.waitForNavigation( {
					waitUntil: 'networkidle0',
				} ),
				page.click( selectors.frontend.submitButton ),
			] );

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
