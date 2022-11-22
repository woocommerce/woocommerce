/**
 * External dependencies
 */
import { canvas, setPostContent, insertBlock } from '@wordpress/e2e-test-utils';
import {
	visitBlockPage,
	saveOrPublish,
	selectBlockByName,
	findToolsPanelWithTitle,
	getFixtureProductsData,
	getFormElementIdByLabel,
	shopper,
	getToggleIdByLabel,
} from '@woocommerce/blocks-test-utils';
import { ElementHandle } from 'puppeteer';
import { setCheckbox, unsetCheckbox } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	GUTENBERG_EDITOR_CONTEXT,
	describeOrSkip,
	waitForCanvas,
	openBlockEditorSettings,
} from '../../../utils';

const block = {
	name: 'Product Query',
	slug: 'core/query',
	class: '.wp-block-query',
};

/**
 * Selectors used for interacting with the block in the editor. These selectors
 * can be changed upstream in Gutenberg, so we scope them here for
 * maintainability.
 *
 * There are also some labels that are used repeatedly, but we don't scope them
 * in favor of readability. Unlike selectors, those label are visible to end
 * users, so it's easier to understand what's going on if we don't scope them.
 * Those labels can get upated in the future, but the tests will fail and we'll
 * know to update them.
 */
const SELECTORS = {
	productFiltersDropdownButton: (
		{ expanded }: { expanded: boolean } = { expanded: false }
	) =>
		`.components-tools-panel-header .components-dropdown-menu button[aria-expanded="${ expanded }"]`,
	productFiltersDropdown:
		'.components-dropdown-menu__menu[aria-label="Advanced Filters options"]',
	productFiltersDropdownItem: '.components-menu-item__button',
	editorPreview: {
		productsGrid: 'ul.wp-block-post-template',
		productsGridItem:
			'ul.wp-block-post-template > li.block-editor-block-preview__live-content',
	},
	productsGrid: `${ block.class } ul.wp-block-post-template`,
	productsGridItem: `${ block.class } ul.wp-block-post-template > li.product`,
	formTokenFieldLabel: '.components-form-token-field__label',
	tokenRemoveButton: '.components-form-token-field__remove-token',
};

const toggleProductFilter = async ( filterName: string ) => {
	const $productFiltersPanel = await findToolsPanelWithTitle(
		'Advanced Filters'
	);
	await expect( $productFiltersPanel ).toClick(
		SELECTORS.productFiltersDropdownButton()
	);
	await canvas().waitForSelector( SELECTORS.productFiltersDropdown );
	await expect( canvas() ).toClick( SELECTORS.productFiltersDropdownItem, {
		text: filterName,
	} );
	await expect( $productFiltersPanel ).toClick(
		SELECTORS.productFiltersDropdownButton( { expanded: true } )
	);
};

const resetProductQueryBlockPage = async () => {
	await visitBlockPage( `${ block.name } Block` );
	await waitForCanvas();
	await setPostContent( '' );
	await insertBlock( block.name );
	await saveOrPublish();
};

const getPreviewProducts = async (): Promise< ElementHandle[] > => {
	await canvas().waitForSelector( SELECTORS.editorPreview.productsGrid );
	return await canvas().$$( SELECTORS.editorPreview.productsGridItem );
};

const getFrontEndProducts = async (): Promise< ElementHandle[] > => {
	await canvas().waitForSelector( SELECTORS.productsGrid );
	return await canvas().$$( SELECTORS.productsGridItem );
};

describeOrSkip( GUTENBERG_EDITOR_CONTEXT === 'gutenberg' )(
	'Product Query > Advanced Filters',
	() => {
		let $productFiltersPanel: ElementHandle< Node >;
		beforeEach( async () => {
			/**
			 * Reset the block page before each test to ensure the block is
			 * inserted in a known state. This is also needed to ensure each
			 * test can be run individually.
			 */
			await resetProductQueryBlockPage();
			await openBlockEditorSettings();
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

		describe( 'Sale Status', () => {
			it( 'Sale status is disabled by default', async () => {
				await expect( $productFiltersPanel ).not.toMatch(
					'Show only products on sale'
				);
			} );

			it( 'Can add and remove Sale Status filter', async () => {
				await toggleProductFilter( 'Sale status' );
				await expect( $productFiltersPanel ).toMatch(
					'Show only products on sale'
				);
				await toggleProductFilter( 'Sale status' );
				await expect( $productFiltersPanel ).not.toMatch(
					'Show only products on sale'
				);
			} );

			it( 'Editor preview shows correct products corresponding to the value `Show only products on sale`', async () => {
				const defaultCount = getFixtureProductsData().length;
				const saleCount = getFixtureProductsData( 'sale_price' ).length;
				expect( await getPreviewProducts() ).toHaveLength(
					defaultCount
				);
				await toggleProductFilter( 'Sale status' );
				await setCheckbox(
					await getToggleIdByLabel( 'Show only products on sale' )
				);
				expect( await getPreviewProducts() ).toHaveLength( saleCount );
				await unsetCheckbox(
					await getToggleIdByLabel( 'Show only products on sale' )
				);
				expect( await getPreviewProducts() ).toHaveLength(
					defaultCount
				);
			} );

			it( 'Works on the front end', async () => {
				await toggleProductFilter( 'Sale status' );
				await setCheckbox(
					await getToggleIdByLabel( 'Show only products on sale' )
				);
				await canvas().waitForSelector(
					SELECTORS.editorPreview.productsGrid
				);
				await saveOrPublish();
				await shopper.block.goToBlockPage( block.name );
				const saleCount = getFixtureProductsData( 'sale_price' ).length;
				expect( await getFrontEndProducts() ).toHaveLength( saleCount );
			} );
		} );

		describe( 'Stock Status', () => {
			it( 'Stock status is enabled by default', async () => {
				await expect( $productFiltersPanel ).toMatchElement(
					SELECTORS.formTokenFieldLabel,
					{ text: 'Stock status' }
				);
			} );

			it( 'Can add and remove Stock Status filter', async () => {
				await toggleProductFilter( 'Stock status' );
				await expect( $productFiltersPanel ).not.toMatchElement(
					SELECTORS.formTokenFieldLabel,
					{ text: 'Stock status' }
				);
				await toggleProductFilter( 'Stock status' );
				await expect( $productFiltersPanel ).toMatchElement(
					SELECTORS.formTokenFieldLabel,
					{ text: 'Stock status' }
				);
			} );

			it( 'All statuses are enabled by default', async () => {
				await expect( $productFiltersPanel ).toMatch( 'In stock' );
				await expect( $productFiltersPanel ).toMatch( 'Out of stock' );
				await expect( $productFiltersPanel ).toMatch( 'On backorder' );
			} );

			it( 'Editor preview shows all products by default', async () => {
				const defaultCount = getFixtureProductsData().length;

				expect( await getPreviewProducts() ).toHaveLength(
					defaultCount
				);
			} );

			/**
			 * Skipping this test for now as Product Query doesn't show correct set of products based on stock status.
			 *
			 * @see https://github.com/woocommerce/woocommerce-blocks/pull/7682
			 */
			it.skip( 'Editor preview shows correct products that has enabled stock statuses', async () => {
				const $$tokenRemoveButtons = await $productFiltersPanel.$$(
					SELECTORS.tokenRemoveButton
				);
				for ( const $el of $$tokenRemoveButtons ) {
					await $el.click();
				}

				const $stockStatusInput = await canvas().$(
					await getFormElementIdByLabel(
						'Stock status',
						SELECTORS.formTokenFieldLabel.replace( '.', '' )
					)
				);
				await $stockStatusInput.click();
				await canvas().keyboard.type( 'Out of Stock' );
				await canvas().keyboard.press( 'Enter' );
				const outOfStockCount = getFixtureProductsData(
					'stock_status'
				).filter( ( status ) => status === 'outofstock' ).length;
				expect( await getPreviewProducts() ).toHaveLength(
					outOfStockCount
				);
			} );

			it( 'Works on the front end', async () => {
				const tokenRemoveButtons = await $productFiltersPanel.$$(
					SELECTORS.tokenRemoveButton
				);
				for ( const el of tokenRemoveButtons ) {
					await el.click();
				}
				const $stockStatusInput = await canvas().$(
					await getFormElementIdByLabel(
						'Stock status',
						SELECTORS.formTokenFieldLabel.replace( '.', '' )
					)
				);
				await $stockStatusInput.click();
				await canvas().keyboard.type( 'Out of stock' );
				await canvas().keyboard.press( 'Enter' );
				await canvas().waitForSelector(
					SELECTORS.editorPreview.productsGrid
				);
				await saveOrPublish();
				await shopper.block.goToBlockPage( block.name );
				const outOfStockCount = getFixtureProductsData(
					'stock_status'
				).filter( ( status ) => status === 'outofstock' ).length;
				expect( await getFrontEndProducts() ).toHaveLength(
					outOfStockCount
				);
			} );
		} );
	}
);
