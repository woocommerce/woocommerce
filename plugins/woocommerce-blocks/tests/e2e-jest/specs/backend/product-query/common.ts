/**
 * We choose to name this file as common instead of splitting it to multiple
 * files like constants.ts and utils.ts because we want to keep the file
 * structure as simple as possible. We also want to distinguish the local
 * utilities like resetProductQueryBlockPage with our e2e utilites in
 * `@woocommerce/blocks-test-utils`. We think exporting local utilites from
 * a different file name is an simple but effective way to achieve that goal.
 */

/**
 * External dependencies
 */
import { canvas, setPostContent } from '@wordpress/e2e-test-utils';
import {
	shopper,
	visitBlockPage,
	saveOrPublish,
	findToolsPanelWithTitle,
	getFormElementIdByLabel,
	insertShortcodeBlock,
} from '@woocommerce/blocks-test-utils';
import { ElementHandle } from 'puppeteer';

/**
 * Internal dependencies
 */
import { waitForCanvas } from '../../../utils';
import fixture from '../__fixtures__/products-beta.fixture.json';

export const block = {
	name: 'Products (Beta)',
	slug: 'core/query',
	class: '.wp-block-query',
};

/**
 * Selectors used for interacting with the blocks. These selectors can be
 * changed upstream in Gutenberg, so we scope them here for maintainability.
 *
 * There are also some labels that are used repeatedly, but we don't scope them
 * in favor of readability. Unlike selectors, those label are visible to end
 * users, so it's easier to understand what's going on if we don't scope them.
 * Those labels can get upated in the future, but the tests will fail and we'll
 * know to update them, again the update process is easier than selector as the
 * label is visible to end users.
 */
export const SELECTORS = {
	advancedFiltersDropdownButton: (
		{ expanded }: { expanded: boolean } = { expanded: false }
	) =>
		`.components-tools-panel-header .components-dropdown-menu button[aria-expanded="${ expanded }"]`,
	advancedFiltersDropdown:
		'.components-dropdown-menu__menu[aria-label="Advanced Filters options"]',
	advancedFiltersDropdownItem: '.components-menu-item__button',
	productsGrid: `${ block.class } ul.wp-block-post-template`,
	productsGridLoading: `${ block.class } p.wp-block-post-template`,
	productsGridItem: `${ block.class } ul.wp-block-post-template > li`,
	formTokenField: {
		label: '.components-form-token-field__label',
		removeToken: '.components-form-token-field__remove-token',
		suggestionsList: '.components-form-token-field__suggestions-list',
		firstSuggestion:
			'.components-form-token-field__suggestions-list > li:first-child',
	},
	productButton: '.wc-block-components-product-button',
	productPrice: '.wc-block-components-product-price',
	productRating: '.wc-block-components-product-rating',
	productImage: '.wc-block-components-product-image',
	cartItemRow: '.wc-block-cart-items__row',
	shortcodeProductsGrid: `${ block.class } ul.wp-block-post-template`,
	shortcodeProductsGridItem: `${ block.class } ul.wp-block-post-template > li`,
	customSelectControl: {
		button: '.components-custom-select-control__button',
		menu: ( { hidden }: { hidden: boolean } = { hidden: true } ) =>
			`.components-custom-select-control__menu[aria-hidden="${ hidden }"]`,
	},
	visuallyHiddenComponents: '.components-visually-hidden',
};

export const goToProductQueryBlockPage = async () => {
	await shopper.block.goToBlockPage( block.name );
};

export const resetProductQueryBlockPage = async () => {
	await visitBlockPage( `${ block.name } Block` );
	await waitForCanvas();
	await setPostContent( fixture.pageContent );
	await canvas().waitForSelector( SELECTORS.productsGrid );
	await saveOrPublish();
};

export const getPreviewProducts = async (): Promise< ElementHandle[] > => {
	await canvas().waitForSelector( SELECTORS.productsGrid );
	return await canvas().$$(
		`${ SELECTORS.productsGridItem }.block-editor-block-preview__live-content`
	);
};

export const getFrontEndProducts = async (): Promise< ElementHandle[] > => {
	await canvas().waitForSelector( SELECTORS.productsGrid );
	return await canvas().$$( SELECTORS.productsGridItem );
};

export const getShortcodeProducts = async (): Promise< ElementHandle[] > => {
	await canvas().waitForSelector( SELECTORS.shortcodeProductsGrid );
	return await canvas().$$( SELECTORS.shortcodeProductsGridItem );
};

export const toggleAdvancedFilter = async ( filterName: string ) => {
	const $advancedFiltersPanel = await findToolsPanelWithTitle(
		'Advanced Filters'
	);
	await expect( $advancedFiltersPanel ).toClick(
		SELECTORS.advancedFiltersDropdownButton()
	);
	await canvas().waitForSelector( SELECTORS.advancedFiltersDropdown );
	await expect( canvas() ).toClick( SELECTORS.advancedFiltersDropdownItem, {
		text: filterName,
	} );
	await expect( $advancedFiltersPanel ).toClick(
		SELECTORS.advancedFiltersDropdownButton( { expanded: true } )
	);
};

export const clearSelectedTokens = async ( $panel: ElementHandle< Node > ) => {
	const tokenRemoveButtons = await $panel.$$(
		SELECTORS.formTokenField.removeToken
	);
	for ( const el of tokenRemoveButtons ) {
		await el.click();
	}
};

export const selectToken = async ( formLabel: string, optionLabel: string ) => {
	const $stockStatusInput = await canvas().$(
		await getFormElementIdByLabel(
			formLabel,
			SELECTORS.formTokenField.label
		)
	);
	await $stockStatusInput.focus();
	await canvas().keyboard.type( optionLabel );
	const firstSuggestion = await canvas().waitForSelector(
		SELECTORS.formTokenField.firstSuggestion
	);
	await firstSuggestion.click();
	await canvas().waitForSelector( SELECTORS.productsGrid );
};

export const getProductElementNodesCount = async ( selector: string ) => {
	return await page.$$eval( selector, ( elements ) => elements.length );
};

export const getEditorProductElementNodesCount = async ( selector: string ) => {
	return await getProductElementNodesCount(
		`li.block-editor-block-list__layout ${ selector }`
	);
};

export const getProductTitle = async (
	product: ElementHandle
): Promise< string > => {
	return (
		( await product.$eval(
			'.wp-block-post-title',
			( el ) => el.textContent
		) ) || ''
	);
};

export const setupEditorFrontendComparison = async () => {
	const previewProducts = await Promise.all(
		(
			await getPreviewProducts()
		 ).map( async ( product ) => await getProductTitle( product ) )
	);
	await goToProductQueryBlockPage();
	await canvas().waitForSelector( SELECTORS.productsGrid );
	const frontEndProducts = await Promise.all(
		(
			await getFrontEndProducts()
		 ).map( async ( product ) => await getProductTitle( product ) )
	);
	return { previewProducts, frontEndProducts };
};

export const setupProductQueryShortcodeComparison = async (
	shortcode: string
) => {
	await insertShortcodeBlock( shortcode );
	await saveOrPublish();
	await goToProductQueryBlockPage();
	const productQueryProducts = await Promise.all(
		(
			await getFrontEndProducts()
		 ).map( async ( product ) => await getProductTitle( product ) )
	);
	const shortcodeProducts = await Promise.all(
		(
			await getShortcodeProducts()
		 ).map( async ( product ) => await getProductTitle( product ) )
	);
	return { productQueryProducts, shortcodeProducts };
};
