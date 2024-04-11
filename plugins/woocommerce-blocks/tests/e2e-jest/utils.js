/**
 * External dependencies
 */
import config from 'config';
import {
	canvas,
	disableSiteEditorWelcomeGuide,
	ensureSidebarOpened,
	openGlobalBlockInserter,
	switchBlockInspectorTab,
	switchUserToAdmin,
	visitAdminPage,
	pressKeyWithModifier,
	searchForBlock as searchForFSEBlock,
	enterEditMode,
	getAllBlocks,
} from '@wordpress/e2e-test-utils';
import { addQueryArgs } from '@wordpress/url';
import { WP_ADMIN_DASHBOARD } from '@woocommerce/e2e-utils';
import fs from 'fs';

/**
 * Internal dependencies
 */
import { elementExists, getTextContent } from './page-utils';
import { PERFORMANCE_REPORT_FILENAME } from '../utils/constants';
import { cli } from '../utils';

/**
 * @typedef {import('@types/puppeteer').ElementHandle} ElementHandle
 * @typedef {import('@wordpress/blocks').Block} WPBlock
 */

/**
 * @typedef {{ addedBy: string, hasActions: boolean, templateTitle: string }} TemplateTableItem
 */

export const BASE_URL = config.get( 'url' );
export const adminUsername = config.get( 'users.admin.username' );
export const adminPassword = config.get( 'users.admin.password' );
export const client = HTTPClientFactory.build( BASE_URL )
	.withBasicAuth( adminUsername, adminPassword )
	.create();
export const DEFAULT_TIMEOUT = 30000;
export const SHOP_CHECKOUT_BLOCK_PAGE = BASE_URL + 'checkout-block/';

const SELECTORS = {
	canvas: 'iframe[name="editor-canvas"],.edit-post-visual-editor',
	inserter: {
		search: '.components-search-control__input,.block-editor-inserter__search input,.block-editor-inserter__search-input,input.block-editor-inserter__search',
	},
	templatesListTable: {
		actionsContainer: '.edit-site-list-table__actions',
		cells: '.edit-site-list-table-column',
		headings: 'thead th.edit-site-list-table-column',
		root: '.edit-site-list-table',
		rows: '.edit-site-list-table-row',
		templateActions: '.edit-site-list-table button[aria-label="Actions"]',
		templateTitle: '[data-wp-component="Heading"]',
	},
	themesPage: {
		currentTheme: '.theme.active',
	},
	toolbar: {
		confirmSave: '.editor-entities-saved-states__save-button',
		saveButton: '.edit-site-save-button__button',
		savePrompt: '.entities-saved-states__text-prompt',
	},
	templateEditor: {
		editButton:
			'.edit-site-site-hub__edit-button[aria-label="Open the editor"]',
	},
	editor: {
		filterButtonToggle: '//label[text()="Show \'Apply filters\' button"]',
	},
	frontend: {
		XPathSubmitButton:
			"//*[contains(@class,'wc-block-components-filter-submit-button')]",
	},
};

/**
 * Search for block in the global inserter.
 *
 * @see https://github.com/WordPress/gutenberg/blob/2356b2d3165acd0af980d52bc93fb1e42748bb25/packages/e2e-test-utils/src/inserter.js#L95
 *
 * @param {string} searchTerm The text to search the inserter for.
 */
export async function searchForBlock( searchTerm ) {
	await openGlobalBlockInserter();
	await page.waitForSelector( SELECTORS.inserter.search );
	await page.focus( SELECTORS.inserter.search );
	await pressKeyWithModifier( 'primary', 'a' );
	await page.keyboard.type( searchTerm );
}

/**
 * Opens the inserter, searches for the given term, then selects the first
 * result that appears.
 *
 * @param {string} searchTerm The text to search the inserter for.
 */
export async function insertBlockDontWaitForInsertClose( searchTerm ) {
	await searchForBlock( searchTerm );
	await page.waitForXPath( `//button//span[text()='${ searchTerm }']` );
	const insertButton = (
		await page.$x( `//button//span[text()='${ searchTerm }']` )
	 )[ 0 ];
	await insertButton.click();
}

export const closeInserter = async () => {
	if (
		await page.evaluate( () => {
			return !! document.querySelector(
				'.edit-post-header [aria-label="Add block"]'
			);
		} )
	) {
		await page.click( '.edit-post-header [aria-label="Add block"]' );
		return;
	}
	await page.click(
		'.edit-post-header [aria-label="Toggle block inserter"]'
	);
};

const WP_ADMIN_WIDGETS_EDITOR = WP_ADMIN_DASHBOARD + 'widgets.php';

export const openWidgetEditor = async () => {
	await page.goto( WP_ADMIN_WIDGETS_EDITOR, {
		waitUntil: 'networkidle0',
	} );
};

export const closeModalIfExists = async () => {
	// The modal close button can have different aria-labels, depending on the version of Gutenberg/WP.
	// Newer versions (WP >=6.2) use `Close`, while older versions (WP <6.1) use `Close dialog`.
	const closeButton = await page.$(
		'.components-modal__header [aria-label="Close"], .components-modal__header [aria-label="Close dialog"]'
	);
	if ( closeButton ) {
		await closeButton.click();
	}
};

export const isBlockInsertedInWidgetsArea = async ( blockName ) => {
	const widgetAreaSelector = '.wp-block-widget-area';

	const widgetsArea = await page.$$( widgetAreaSelector );

	return widgetsArea.some(
		async ( widgetArea ) =>
			( await widgetArea.$$( `[data-block-title="${ blockName }"]` )
				.length ) > 0
	);
};

/**
 * Visits site editor depending on used WordPress version and how Gutenberg is installed.
 *
 * @param {Object}                             params                          Query parameters to add to the URL.
 * @param {string}                             [params.postId]                 ID of the template if we want to access template editor.
 * @param {'wp_template' | 'wp_template_part'} [params.postType='wp_template'] Type of template.
 * @param {string}                             [params.path]                   Navigation path.
 */
export async function goToSiteEditor( params = {} ) {
	await visitAdminPage( 'site-editor.php', addQueryArgs( '', params ) );

	if ( params?.postId || Object.keys( params ).length === 0 ) {
		await enterEditMode();
	}
}

/**
 * Waits for the Gutenberg canvas to be available
 *
 * @param {number} [timeout=DEFAULT_TIMEOUT] The amount of ms to wait for the element
 *
 * @return {Promise<?ElementHandle>} The canvas element handle
 */
export function waitForCanvas( timeout = DEFAULT_TIMEOUT ) {
	return page.waitForSelector( SELECTORS.canvas, { timeout } );
}

/**
 * Visits the Site Editor template edit view.
 *
 * @param {Object}                             params
 * @param {string}                             [params.postId]                 ID of the template if we want to access template editor.
 * @param {'wp_template' | 'wp_template_part'} [params.postType='wp_template'] Type of template.
 */
export async function goToTemplateEditor( {
	postId,
	postType = 'wp_template',
} = {} ) {
	await goToSiteEditor( {
		postType,
		postId,
	} );

	await disableSiteEditorWelcomeGuide();
	await waitForCanvas();
}

/**
 * Visits the Site Editor templates list view.
 *
 * @param {Object}                             params
 * @param {'wp_template' | 'wp_template_part'} [params.postType='wp_template'] Type of template.
 * @param {'list' | 'actions'}                 [params.waitFor='false']        Wait for list or for actions to be present - tempalte actions can take a moment to load, we can wait for them to be present if needed.
 */
export async function goToTemplatesList( {
	postType = 'wp_template',
	waitFor = 'list',
} = {} ) {
	await goToSiteEditor( {
		postType,
		// In WP 6.2, if postId is not defined, the route expects `path` instead
		// of `postType`.
		path: `/${ postType }/all`,
	} );

	if ( waitFor === 'actions' ) {
		await page.waitForSelector(
			SELECTORS.templatesListTable.templateActions
		);
	} else {
		await page.waitForSelector( SELECTORS.templatesListTable.root );
	}
}

/**
 * Saves a template
 */
export async function saveTemplate() {
	const { confirmSave, saveButton, savePrompt } = SELECTORS.toolbar;

	await page.click( saveButton );
	await page.waitForSelector( savePrompt );
	await page.click( confirmSave );
	await page.waitForSelector( `${ saveButton }[aria-disabled="true"]` );
}

/**
 * Gets all available templates from the template list table UI
 *
 * @return {Promise<TemplateTableItem[]>} A promise of an array of informations about the templates extracted from the UI
 */
export async function getAllTemplates() {
	const { templatesListTable } = SELECTORS;

	const table = await page.waitForSelector( templatesListTable.root );

	if ( ! table ) throw new Error( 'Templates table not found' );

	const rows = await table.$$( templatesListTable.rows );

	return Promise.all(
		rows.map( async ( row ) => ( {
			addedBy: (
				await getTextContent( templatesListTable.cells, row )
			 )[ 1 ],
			hasActions: await elementExists(
				templatesListTable.actionsContainer,
				row
			),
			templateTitle: (
				await getTextContent( templatesListTable.templateTitle, row )
			 )[ 0 ],
		} ) )
	);
}

/**
 * Gets all the blocks that fulfill a given predicate
 *
 * @param {( block: WPBlock ) => boolean} predicate The function invoked per iteration
 * @return {Promise< Partial< WPBlock >[] >} The blocks which have been found
 */
export async function filterCurrentBlocks( predicate ) {
	/**
	 * @type {WPBlock[]}
	 */
	const blocks = await page.evaluate( () => {
		/**
		 * Gets all serializeable data from a block
		 *
		 * @param {WPBlock} block A Gutenberg Block
		 * @return {Partial<WPBlock>} A block with unserializeable values turned to `null`
		 */
		function getSerializeableBlockData( block ) {
			return JSON.parse( JSON.stringify( block ) );
		}

		const blockEditorStore = window.wp.data.select( 'core/block-editor' );
		/**
		 * @type {string[]}
		 */
		const allClientIds = blockEditorStore.getClientIdsWithDescendants();

		return allClientIds.map( ( id ) =>
			getSerializeableBlockData( blockEditorStore.getBlock( id ) )
		);
	} );

	return blocks.filter( predicate );
}

/**
 * Sets up a suite to use a theme without side-effects
 *
 * *Note:* this “hook” is supposed to be used within `describe` calls to
 * make it easier to set theme dependencies for tests without messing
 * the environment of any other test and to explicitly declare a
 * dependency.
 *
 * @param {string} themeSlug The theme the test suite should use
 */
export function useTheme( themeSlug ) {
	beforeAll( async () => {
		await cli(
			`npm run wp-env run tests-cli wp theme activate ${ themeSlug }`
		);
		await switchUserToAdmin();
	} );

	afterAll( async () => {
		await cli(
			`npm run wp-env run tests-cli wp theme activate storefront`
		);
		await switchUserToAdmin();
	} );
}

/**
 * Takes an average value of all items in an array.
 *
 * @param {Array} array An array of numbers to take an average from.
 * @return {number} The average value of all members of the array.
 */
const average = ( array ) => array.reduce( ( a, b ) => a + b ) / array.length;

/**
 * Writes a line to the e2e performance result for the current test containing longest, shortest, and average run times.
 *
 * @param {string} description Message to describe what you're logging the performance of.
 * @param {Array}  times       array of times to record.
 */
export const logPerformanceResult = ( description, times ) => {
	const roundedTimes = times.map(
		( time ) => Math.round( time + Number.EPSILON * 100 ) / 100
	);
	fs.appendFileSync(
		PERFORMANCE_REPORT_FILENAME,
		JSON.stringify( {
			description,
			longest: Math.max( ...roundedTimes ),
			shortest: Math.min( ...roundedTimes ),
			average: average( roundedTimes ),
		} ) + '\n'
	);
};

/* Add a block to Full Site Editing.
 *
 * *Note:* insertBlock function gets focused on the canvas, this could prevent some dialogs from being displayed. e.g. compatibility notice.
 *
 * @param {string} blockName Block name.
 */
export const addBlockToFSEArea = async ( blockName ) => {
	await searchForFSEBlock( blockName );
	const insertButton = await page.waitForXPath(
		`//button//span[contains(text(), '${ blockName }')]`
	);
	await insertButton.click();
};

/**
 *  Wait for all Products Block is loaded completely: when the skeleton disappears, and the products are visible
 */
export const waitForAllProductsBlockLoaded = async () => {
	/**
	 * We use try with empty catch block here to avoid the race condition
	 * between the block loading and the test execution. After user actions,
	 * the products may or may not finish loading at the time we try to wait for
	 * the loading class.
	 *
	 * We need to wait for the loading class to be added then removed because
	 * only waiting for the loading class to be removed could result in a false
	 * positive pass.
	 */
	try {
		await page.waitForSelector(
			'.wc-block-grid__products.is-loading-products'
		);
	} catch ( ok ) {}

	await page.waitForSelector(
		'.wc-block-grid__products:not(.is-loading-products)'
	);
};

/**
 * Get all blocks in the document that match a certain slug.
 *
 * @param {string} slug Slug of the blocks to get.
 *
 * @return {Promise<{}>} Promise resolving with an array containing all blocks in
 * the document that match a certain slug.
 */
export const getBlocksBySlug = async ( slug ) => {
	const blocks = await getAllBlocks();
	return blocks.filter( ( { name } ) => name === slug );
};

/**
 * Insert the All Products block using the global inserter. This util is needed
 * because inserting the All Products block using the `insertBlock()` util
 * causes time outs.
 */
export const insertAllProductsBlock = async () => {
	const searchTerm = 'All Products';

	await searchForBlock( searchTerm );

	// Wait for the default block list to disappear to prevent its items from
	// being considered as search results. This is needed since we're debouncing
	// search request.
	await page.waitForSelector( '.block-editor-inserter__block-list', {
		hidden: true,
	} );

	const insertButton = await page.waitForXPath(
		`//*[@role='option' and contains(., '${ searchTerm }')]`
	);
	if ( ! insertButton ) {
		throw new Error( `Could not find the "${ searchTerm }" block` );
	}
	insertButton?.click();
};

/**
 * Clicks on the button in the header which opens Document Settings sidebar when it is closed.
 * Based on https://github.com/WordPress/gutenberg/blob/trunk/packages/e2e-test-utils/src/open-document-settings-sidebar.js,
 * but updates the selector so it works in WP 6.2 without GB.
 */
export async function openSettingsSidebar() {
	const toggleButton = await page.waitForSelector(
		'.edit-post-header__settings button[aria-label="Settings"]'
	);

	const isClosed = await page.evaluate(
		( element ) => element.getAttribute( 'aria-expanded' ) === 'false',
		toggleButton
	);

	if ( isClosed ) {
		await toggleButton.click();
		await page.waitForSelector( '.edit-post-sidebar' );
	}
}

/**
 * Enables the `Show 'Apply filters' button` toggle.
 */
export const enableApplyFiltersButton = async () => {
	await ensureSidebarOpened();
	await switchBlockInspectorTab( 'Settings' );

	await page.waitForXPath( SELECTORS.editor.filterButtonToggle );

	const [ filterButtonToggle ] = await page.$x(
		SELECTORS.editor.filterButtonToggle
	);
	if ( ! filterButtonToggle ) {
		throw new Error( "'Apply filters' toggle not found via XPath." );
	}
	await filterButtonToggle.click();

	// If for some reason click didn't work (it seems to happen intermittently),
	// click on the toggle via JS.
	await page.evaluate( () => {
		const toggle = document.querySelector(
			'.components-toggle-control:last-child .components-form-toggle__input'
		);
		if ( ! toggle ) {
			throw new Error( "'Apply filters' toggle not found via CSS." );
		}
		if ( ! toggle.checked ) {
			toggle.click();
		}
	} );

	await canvas().waitForXPath( SELECTORS.frontend.XPathSubmitButton );
};
