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
	enableApplyFiltersButton,
	goToTemplateEditor,
	insertAllProductsBlock,
	saveTemplate,
	useTheme,
	waitForAllProductsBlockLoaded,
	waitForCanvas,
} from '../../utils';
import { saveOrPublish } from '../../../utils';

const block = {
	name: 'Filter by Attribute',
	slug: 'woocommerce/attribute-filter',
	class: '.wc-block-attribute-filter',
	selectors: {
		editor: {
			firstAttributeInTheList:
				'.woocommerce-search-list__list > li > label > input.woocommerce-search-list__item-input',
			doneButton: '.wc-block-attribute-filter__selection > button',
		},
		frontend: {
			firstAttributeInTheList:
				'.wc-block-attribute-filter-list > li:not([class^="is-loading"])',
			productsList: '.wc-block-grid__products > li',
			queryProductsList: '.wp-block-post-template > li',
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
	const insertFilterByAttributeBlock = async () => {
		await insertBlock( block.name );
		const canvasEl = canvas();

		// It seems that .click doesn't work well with radio input element.
		await canvasEl.$eval(
			block.selectors.editor.firstAttributeInTheList,
			( el ) => ( el as HTMLInputElement ).click()
		);
		await canvasEl.click( selectors.editor.doneButton );
	};

	describe( 'with All Products Block', () => {
		beforeAll( async () => {
			await switchUserToAdmin();
			await createNewPost( {
				postType: 'post',
				title: block.name,
			} );

			await insertAllProductsBlock();
			await insertFilterByAttributeBlock();
			await publishPost();

			const link = await page.evaluate( () =>
				wp.data.select( 'core/editor' ).getPermalink()
			);

			await page.goto( link );
		} );

		it.skip( 'should render products', async () => {
			await waitForAllProductsBlockLoaded();
			const products = await page.$$( selectors.frontend.productsList );

			expect( products ).toHaveLength( 5 );
		} );

		it.skip( 'should show only products that match the filter', async () => {
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
			await insertFilterByAttributeBlock();
			await saveTemplate();
		} );

		afterAll( async () => {
			await deleteAllTemplates( 'wp_template' );
			await deleteAllTemplates( 'wp_template_part' );
		} );

		beforeEach( async () => {
			await goToShopPage();
		} );

		it( 'should render products', async () => {
			const products = await page.$$(
				selectors.frontend.classicProductsList
			);

			const productsBlockProductsList = await page.$$(
				selectors.frontend.queryProductsList
			);

			expect( productsBlockProductsList ).toHaveLength( 5 );
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
				page.click( selectors.frontend.filter ),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			] );

			const products = await page.$$(
				selectors.frontend.classicProductsList
			);

			const pageURL = page.url();
			const parsedURL = new URL( pageURL );

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

		it.skip( 'should refresh the page only if the user clicks on button', async () => {
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
			await insertFilterByAttributeBlock();
			await publishPost();

			editorPageUrl = page.url();
			frontedPageUrl = await page.evaluate( () =>
				wp.data.select( 'core/editor' ).getPermalink()
			);
			await page.goto( frontedPageUrl, { waitUntil: 'networkidle2' } );
		} );

		it.skip( 'should render products', async () => {
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

			expect( isRefreshed ).not.toBeCalled();

			await page.waitForSelector( selectors.frontend.filter );

			await Promise.all( [
				page.click( selectors.frontend.filter ),
				page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			] );

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

		it( 'should refresh the page only if the user clicks on button', async () => {
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
