/**
 * External dependencies
 */
import { ElementHandle } from 'puppeteer';
import {
	canvas,
	ensureSidebarOpened,
	findSidebarPanelWithTitle,
} from '@wordpress/e2e-test-utils';
import {
	selectBlockByName,
	visitBlockPage,
	saveOrPublish,
} from '@woocommerce/blocks-test-utils';

/**
 * Internal dependencies
 */
import {
	block,
	SELECTORS,
	resetProductQueryBlockPage,
	setupProductQueryShortcodeComparison,
	setupEditorFrontendComparison,
} from './common';

const getPopularFilterPanel = async () => {
	await ensureSidebarOpened();
	await selectBlockByName( block.slug );
	return await findSidebarPanelWithTitle( 'Popular Filters' );
};

const getCurrentPopularFilter = async ( $panel: ElementHandle< Node > ) => {
	const $selectedFilter = await $panel.$(
		SELECTORS.customSelectControl.button
	);
	if ( ! $selectedFilter ) {
		throw new Error( 'Can not find selected filter.' );
	}
	return await page.evaluate( ( el ) => el.textContent, $selectedFilter );
};

const selectPopularFilter = async ( filter: string ) => {
	let $panel = await getPopularFilterPanel();
	const $toggleButton = await $panel.$(
		SELECTORS.customSelectControl.button
	);
	await $toggleButton.click();
	await $panel.waitForSelector(
		SELECTORS.customSelectControl.menu( { hidden: false } )
	);
	const [ $filter ] = await $panel.$x(
		`//li[contains(text(), "${ filter }")]`
	);
	if ( ! $filter ) {
		throw new Error(
			`Filter "${ filter }" not found among Popular Filters options`
		);
	}
	await $filter.click();
	/**
	 * We use try with empty catch block here to avoid the race condition
	 * between the block loading and the test execution. After user actions,
	 * the products may or may not finish loading at the time we try to wait for
	 * the loading class.
	 */
	try {
		await canvas().waitForSelector( SELECTORS.productsGridLoading );
	} catch ( ok ) {}
	await canvas().waitForSelector( SELECTORS.productsGrid );
	await saveOrPublish();

	// Verify if filter is selected or try again.
	await visitBlockPage( `${ block.name } Block` );
	$panel = await getPopularFilterPanel();
	const currentSelectedFilter = await getCurrentPopularFilter( $panel );
	if ( currentSelectedFilter !== filter ) {
		await selectPopularFilter( filter );
	}
};

// These tests are skipped and previously relied on GUTENBERG_EDITOR_CONTEXT.
describe.skip( 'Product Query > Popular Filters', () => {
	let $popularFiltersPanel: ElementHandle< Node >;
	beforeEach( async () => {
		/**
		 * Reset the block page before each test to ensure the block is
		 * inserted in a known state. This is also needed to ensure each
		 * test can be run individually.
		 */
		await resetProductQueryBlockPage();
		$popularFiltersPanel = await getPopularFilterPanel();
	} );

	/**
	 * Reset the content of Product Query Block page after this test suite
	 * to avoid breaking other tests.
	 */
	afterAll( async () => {
		await resetProductQueryBlockPage();
	} );

	it( 'Popular Filters is expanded by default', async () => {
		await expect( $popularFiltersPanel ).toMatch(
			'Arrange products by popular pre-sets.'
		);
	} );

	it( 'Sorted by title is the default preset', async () => {
		const currentFilter = await getCurrentPopularFilter(
			$popularFiltersPanel
		);
		expect( currentFilter ).toEqual( 'Sorted by title' );
	} );

	describe.each( [
		{
			filter: 'Sorted by title',
			shortcode: '[products orderby="title" order="ASC" limit="9"]',
		},
		{
			filter: 'Newest',
			shortcode: '[products orderby="date" order="DESC" limit="9"]',
		},
		/**
		 * The following tests are commented out because they are flaky
		 * due to the lack of orders and reviews in the test environment.
		 *
		 * @see https://github.com/woocommerce/woocommerce-blocks/issues/8116
		 */
		// {
		// 	filter: 'Best Selling',
		// 	shortcode: '[products best_selling="true" limit="9"]',
		// },
		// {
		// 	filter: 'Top Rated',
		// 	shortcode: '[products top_rated="true" limit="9"]',
		// },
	] )( '$filter', ( { filter, shortcode } ) => {
		beforeEach( async () => {
			await selectPopularFilter( filter );
		} );
		it( 'Editor preview and block frontend display the same products', async () => {
			const { previewProducts, frontEndProducts } =
				await setupEditorFrontendComparison();
			expect( frontEndProducts ).toEqual( previewProducts );
		} );

		it( 'Products are displayed in the correct order', async () => {
			const { productQueryProducts, shortcodeProducts } =
				await setupProductQueryShortcodeComparison( shortcode );
			expect( productQueryProducts ).toEqual( shortcodeProducts );
		} );
	} );
} );
