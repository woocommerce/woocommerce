/* eslint-disable rulesdir/no-raw-playwright-test-import */
/**
 * External dependencies
 */
import { test as base, expect, ConsoleMessage } from '@playwright/test';
import {
	STORAGE_STATE_PATH,
	DB_EXPORT_FILE,
	wpCLI,
	Admin,
	Editor,
	FrontendUtils,
	LocalPickupUtils,
	MiniCartUtils,
	PageUtils,
	PerformanceUtils,
	RequestUtils,
	ShippingUtils,
} from '@woocommerce/e2e-utils';

/**
 * Set of console logging types observed to protect against unexpected yet
 * handled (i.e. not catastrophic) errors or warnings. Each key corresponds
 * to the Playwright ConsoleMessage type, its value the corresponding function
 * on the console global object.
 */
const OBSERVED_CONSOLE_MESSAGE_TYPES = [ 'warn', 'error' ] as const;

/**
 * Adds a page event handler to emit uncaught exception to process if one of
 * the observed console logging types is encountered.
 *
 * @param message The console message.
 */
function observeConsoleLogging( message: ConsoleMessage ) {
	const type = message.type();
	if (
		! OBSERVED_CONSOLE_MESSAGE_TYPES.includes(
			type as ( typeof OBSERVED_CONSOLE_MESSAGE_TYPES )[ number ]
		)
	) {
		return;
	}

	const text = message.text();

	// An exception is made for _blanket_ deprecation warnings: Those
	// which log regardless of whether a deprecated feature is in use.
	if ( text.includes( 'This is a global warning' ) ) {
		return;
	}

	// A chrome advisory warning about SameSite cookies is informational
	// about future changes, tracked separately for improvement in core.
	//
	// See: https://core.trac.wordpress.org/ticket/37000
	// See: https://www.chromestatus.com/feature/5088147346030592
	// See: https://www.chromestatus.com/feature/5633521622188032
	if ( text.includes( 'A cookie associated with a cross-site resource' ) ) {
		return;
	}

	// Viewing posts on the front end can result in this error, which
	// has nothing to do with Gutenberg.
	if ( text.includes( 'net::ERR_UNKNOWN_URL_SCHEME' ) ) {
		return;
	}

	// Not implemented yet.
	// Network errors are ignored only if we are intentionally testing
	// offline mode.
	// if (
	// 	text.includes( 'net::ERR_INTERNET_DISCONNECTED' ) &&
	// 	isOfflineMode()
	// ) {
	// 	return;
	// }

	// As of WordPress 5.3.2 in Chrome 79, navigating to the block editor
	// (Posts > Add New) will display a console warning about
	// non - unique IDs.
	// See: https://core.trac.wordpress.org/ticket/23165
	if ( text.includes( 'elements with non-unique id #_wpnonce' ) ) {
		return;
	}

	// Ignore all JQMIGRATE (jQuery migrate) deprecation warnings.
	if ( text.includes( 'JQMIGRATE' ) ) {
		return;
	}

	const logFunction =
		type as ( typeof OBSERVED_CONSOLE_MESSAGE_TYPES )[ number ];

	// Disable reason: We intentionally bubble up the console message
	// which, unless the test explicitly anticipates the logging via
	// @wordpress/jest-console matchers, will cause the intended test
	// failure.

	// eslint-disable-next-line no-console
	console[ logFunction ]( text );
}

const test = base.extend<
	{
		admin: Admin;
		editor: Editor;
		pageUtils: PageUtils;
		frontendUtils: FrontendUtils;
		performanceUtils: PerformanceUtils;
		snapshotConfig: void;
		shippingUtils: ShippingUtils;
		localPickupUtils: LocalPickupUtils;
		miniCartUtils: MiniCartUtils;
	},
	{
		requestUtils: RequestUtils;
		wpCoreVersion: number;
	}
>( {
	admin: async ( { page, pageUtils, editor, wpCoreVersion }, use ) => {
		await use( new Admin( { page, pageUtils, editor, wpCoreVersion } ) );
	},
	editor: async ( { page, wpCoreVersion }, use ) => {
		await use( new Editor( { page, wpCoreVersion } ) );
	},
	page: async ( { page }, use ) => {
		page.on( 'console', observeConsoleLogging );

		await use( page );

		// Clear local storage after each test.
		try {
			await page.evaluate( () => {
				window.localStorage.clear();
			} );
		} catch ( error ) {
			// Ignore errors if page is already closed/navigated away
			// eslint-disable-next-line no-console
			console.log( 'Failed to clear localStorage:', error.message );
		}

		// Dispose the current APIRequestContext to free up resources.
		await page.request.dispose();

		await wpCLI( `db reset --yes` );
		// Reset the database to the initial state via snapshot import.
		await wpCLI( `db import ${ DB_EXPORT_FILE }` );
	},
	pageUtils: async ( { page }, use ) => {
		await use( new PageUtils( { page } ) );
	},
	frontendUtils: async ( { page, requestUtils }, use ) => {
		await use( new FrontendUtils( page, requestUtils ) );
	},
	performanceUtils: async ( { page }, use ) => {
		await use( new PerformanceUtils( page ) );
	},
	shippingUtils: async ( { page, admin }, use ) => {
		await use( new ShippingUtils( page, admin ) );
	},
	localPickupUtils: async ( { page, admin }, use ) => {
		await use( new LocalPickupUtils( page, admin ) );
	},
	miniCartUtils: async ( { page, frontendUtils }, use ) => {
		await use( new MiniCartUtils( page, frontendUtils ) );
	},
	requestUtils: [
		async ( {}, use, workerInfo ) => {
			const requestUtils = await RequestUtils.setup( {
				baseURL: workerInfo.project.use.baseURL as string,
				storageStatePath: STORAGE_STATE_PATH,
			} );

			await use( requestUtils );
		},
		{ scope: 'worker', auto: true },
	],
	wpCoreVersion: [
		async ( {}, use ) => {
			const output = await wpCLI( 'core version' );
			const version = output.stdout.trim().split( '\n' ).at( -1 ) ?? '';

			// We can parse this as a float because WP never updates the minor
			// version over x.9.x. E.g., after 6.9.x, it will be 7.0.x.
			const parsedVersion = Number.parseFloat( version );

			if ( Number.isNaN( parsedVersion ) ) {
				throw new Error(
					`Failed to parse WordPress version: ${ version }`
				);
			}

			await use( parsedVersion );
		},
		{ scope: 'worker' },
	],
} );

export { test, expect };
