/**
 * External dependencies
 */
import { ensureSidebarOpened, canvas } from '@wordpress/e2e-test-utils';
import {
	saveOrPublish,
	selectBlockByName,
	findToolsPanelWithTitle,
	getFixtureProductsData,
	shopper,
	getToggleIdByLabel,
} from '@woocommerce/blocks-test-utils';
import { ElementHandle } from 'puppeteer';
import { setCheckbox } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	block,
	SELECTORS,
	resetProductQueryBlockPage,
	toggleAdvancedFilter,
	getPreviewProducts,
	getFrontEndProducts,
	clearSelectedTokens,
	selectToken,
} from './common';

// These tests are skipped and previously relied on GUTENBERG_EDITOR_CONTEXT.
describe.skip( `${ block.name } > Advanced Filters`, () => {
	let $productFiltersPanel: ElementHandle< Node >;
	const defaultCount = getFixtureProductsData().length;
	const saleCount = getFixtureProductsData( 'sale_price' ).length;
	const outOfStockCount = getFixtureProductsData( 'stock_status' ).filter(
		( status: string ) => status === 'outofstock'
	).length;

	beforeEach( async () => {
		/**
		 * Reset the block page before each test to ensure the block is
		 * inserted in a known state. This is also needed to ensure each
		 * test can be run individually.
		 */
		await resetProductQueryBlockPage();
		await ensureSidebarOpened();
		await selectBlockByName( block.slug );
		$productFiltersPanel = await findToolsPanelWithTitle(
			'Advanced Filters'
		);
	} );

	/**
	 * Reset the content of Product Query Block page after this test suite
	 * to avoid breaking other tests.
	 */
	afterAll( async () => {
		await resetProductQueryBlockPage();
	} );

	it( 'Editor preview shows all products by default', async () => {
		expect( await getPreviewProducts() ).toHaveLength( defaultCount );
	} );

	it( 'On the front end, blocks shows all products by default', async () => {
		expect( await getPreviewProducts() ).toHaveLength( defaultCount );
	} );

	describe( 'Sale Status', () => {
		it( 'Sale status is disabled by default', async () => {
			await expect( $productFiltersPanel ).not.toMatch(
				'Show only products on sale'
			);
		} );

		it( 'Can add and remove Sale Status filter', async () => {
			await toggleAdvancedFilter( 'Sale status' );
			await expect( $productFiltersPanel ).toMatch(
				'Show only products on sale'
			);
			await toggleAdvancedFilter( 'Sale status' );
			await expect( $productFiltersPanel ).not.toMatch(
				'Show only products on sale'
			);
		} );

		it( 'Enable Sale Status > Editor preview shows only on sale products', async () => {
			await toggleAdvancedFilter( 'Sale status' );
			await setCheckbox(
				await getToggleIdByLabel( 'Show only products on sale' )
			);
			expect( await getPreviewProducts() ).toHaveLength( saleCount );
		} );

		it( 'Enable Sale Status > On the front end, block shows only on sale products', async () => {
			await toggleAdvancedFilter( 'Sale status' );
			await setCheckbox(
				await getToggleIdByLabel( 'Show only products on sale' )
			);
			await canvas().waitForSelector( SELECTORS.productsGrid );
			await saveOrPublish();
			await shopper.block.goToBlockPage( block.name );
			expect( await getFrontEndProducts() ).toHaveLength( saleCount );
		} );
	} );

	describe( 'Stock Status', () => {
		it( 'Stock status is enabled by default', async () => {
			await expect( $productFiltersPanel ).toMatchElement(
				SELECTORS.formTokenField.label,
				{ text: 'Stock status' }
			);
		} );

		it( 'Can add and remove Stock Status filter', async () => {
			await toggleAdvancedFilter( 'Stock status' );
			await expect( $productFiltersPanel ).not.toMatchElement(
				SELECTORS.formTokenField.label,
				{ text: 'Stock status' }
			);
			await toggleAdvancedFilter( 'Stock status' );
			await expect( $productFiltersPanel ).toMatchElement(
				SELECTORS.formTokenField.label,
				{ text: 'Stock status' }
			);
		} );

		it( 'All statuses are enabled by default', async () => {
			await expect( $productFiltersPanel ).toMatch( 'In stock' );
			await expect( $productFiltersPanel ).toMatch( 'Out of stock' );
			await expect( $productFiltersPanel ).toMatch( 'On backorder' );
		} );

		it( 'Set Stock status to Out of stock > Editor preview shows only out-of-stock products', async () => {
			await clearSelectedTokens( $productFiltersPanel );
			await selectToken( 'Stock status', 'Out of stock' );
			expect( await getPreviewProducts() ).toHaveLength(
				outOfStockCount
			);
		} );

		it( 'Set Stock status to Out of stock > On the front end, block shows only out-of-stock products', async () => {
			await clearSelectedTokens( $productFiltersPanel );
			await selectToken( 'Stock status', 'Out of stock' );
			await canvas().waitForSelector( SELECTORS.productsGrid );
			await saveOrPublish();
			await shopper.block.goToBlockPage( block.name );
			expect( await getFrontEndProducts() ).toHaveLength(
				outOfStockCount
			);
		} );
	} );
} );
