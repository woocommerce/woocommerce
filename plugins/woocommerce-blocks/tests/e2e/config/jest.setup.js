/**
 * External dependencies
 */
import {
	enablePageDialogAccept,
	isOfflineMode,
	setBrowserViewport,
	switchUserToAdmin,
	switchUserToTest,
	visitAdminPage,
} from '@wordpress/e2e-test-utils';

// Set the default test timeout.
jest.setTimeout( 60000 );

/**
 * Array of page event tuples of [ eventName, handler ].
 *
 * @type {Array}
 */
const pageEvents = [];
/**
 * Set of console logging types observed to protect against unexpected yet
 * handled (i.e. not catastrophic) errors or warnings. Each key corresponds
 * to the Puppeteer ConsoleMessage type, its value the corresponding function
 * on the console global object.
 *
 * @type {Object<string,string>}
 */
const OBSERVED_CONSOLE_MESSAGE_TYPES = {
	error: 'error',
};
async function setupBrowser() {
	await setBrowserViewport( 'large' );
}

/**
 * Navigates to WooCommerce's import page and imports sample products.
 *
 * @return {Promise} Promise resolving once products have been imported.
 */
async function importSampleProducts() {
	await switchUserToAdmin();
	// Visit `/wp-admin/edit.php?post_type=product` so we can see a list of products and decide if we should import them or not.
	await visitAdminPage( 'edit.php', 'post_type=product' );
	const emptyState = await page.evaluate( () =>
		window.find( 'Ready to start selling something awesome' )
	);
	const noProduct = await page.evaluate( () =>
		window.find( 'No products found' )
	);
	if ( emptyState || noProduct ) {
		// Visit Import Products page.
		await visitAdminPage(
			'edit.php',
			'post_type=product&page=product_importer'
		);
		await page.click( 'a.woocommerce-importer-toggle-advanced-options' );
		await page.focus( '#woocommerce-importer-file-url' );
		// local path for sample data that is included with woo.
		await page.keyboard.type(
			'wp-content/plugins/woocommerce/sample-data/sample_products.csv'
		);
		await page.click( '.wc-actions .button-next' );
		await page.waitForSelector( '.wc-importer-mapping-table' );
		await page.select(
			'.wc-importer-mapping-table tr:nth-child(29) select',
			''
		);
		await page.click( '.wc-actions .button-next' );
		await page.waitForXPath(
			"//*[@class='woocommerce-importer-done' and contains(., 'Import complete! ')]"
		);
		await switchUserToTest();
	}
}

/**
 * Adds an event listener to the page to handle additions of page event
 * handlers, to assure that they are removed at test teardown.
 */
function capturePageEventsForTearDown() {
	page.on( 'newListener', ( eventName, listener ) => {
		pageEvents.push( [ eventName, listener ] );
	} );
}

/**
 * Removes all bound page event handlers.
 */
function removePageEvents() {
	pageEvents.forEach( ( [ eventName, handler ] ) => {
		page.removeListener( eventName, handler );
	} );
}

/**
 * Adds a page event handler to emit uncaught exception to process if one of
 * the observed console logging types is encountered.
 */
function observeConsoleLogging() {
	page.on( 'console', ( message ) => {
		const type = message.type();
		if ( ! OBSERVED_CONSOLE_MESSAGE_TYPES.hasOwnProperty( type ) ) {
			return;
		}
		const text = message.text();

		// Viewing posts on the front end can result in this error, which
		// has nothing to do with Gutenberg.
		if ( text.includes( 'net::ERR_UNKNOWN_URL_SCHEME' ) ) {
			return;
		}

		// Network errors are ignored only if we are intentionally testing
		// offline mode.
		if (
			text.includes( 'net::ERR_INTERNET_DISCONNECTED' ) &&
			isOfflineMode()
		) {
			return;
		}

		const logFunction = OBSERVED_CONSOLE_MESSAGE_TYPES[ type ];

		// Disable reason: We intentionally bubble up console error messages
		// for debugging reasons. If you need to test explicitly the logging,
		// use  @wordpress/jest-console
		// eslint-disable-next-line no-console
		console[ logFunction ]( text );
	} );
}

// Before every test suite run, delete all content created by the test. This ensures
// other posts/comments/etc. aren't dirtying tests and tests don't depend on
// each other's side-effects.
beforeAll( async () => {
	capturePageEventsForTearDown();
	enablePageDialogAccept();
	observeConsoleLogging();
	await setupBrowser();
	await importSampleProducts();
} );

afterEach( async () => {
	await setupBrowser();
} );

afterAll( () => {
	removePageEvents();
} );
