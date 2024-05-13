/* eslint-disable rulesdir/no-raw-playwright-test-import */
/**
 * External dependencies
 */
import { test as base, expect, request as baseRequest } from '@playwright/test';
import type { ConsoleMessage } from '@playwright/test';
import {
	Admin,
	Editor,
	PageUtils,
	RequestUtils,
} from '@wordpress/e2e-test-utils-playwright';
import {
	TemplateApiUtils,
	STORAGE_STATE_PATH,
	DB_EXPORT_FILE,
	EditorUtils,
	FrontendUtils,
	StoreApiUtils,
	PerformanceUtils,
	ShippingUtils,
	LocalPickupUtils,
	MiniCartUtils,
	WPCLIUtils,
	cli,
} from '@woocommerce/e2e-utils';
import { Post } from '@wordpress/e2e-test-utils-playwright/build-types/request-utils/posts';

/**
 * Internal dependencies
 */
import {
	type PostPayload,
	createPostFromTemplate,
	updateTemplateContents,
	deletePost,
} from '../utils/create-dynamic-content';
import type { ExtendedTemplate } from '../types/e2e-test-utils-playwright';

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
		templateApiUtils: TemplateApiUtils;
		editorUtils: EditorUtils;
		frontendUtils: FrontendUtils;
		storeApiUtils: StoreApiUtils;
		performanceUtils: PerformanceUtils;
		snapshotConfig: void;
		shippingUtils: ShippingUtils;
		localPickupUtils: LocalPickupUtils;
		miniCartUtils: MiniCartUtils;
		wpCliUtils: WPCLIUtils;
	},
	{
		requestUtils: RequestUtils & {
			createPostFromTemplate: (
				post: PostPayload,
				templatePath: string,
				data: unknown
			) => Promise< Post >;
			deletePost: ( id: number ) => Promise< void >;
			updateTemplateContents: (
				templateId: string,
				templatePath: string,
				data: unknown
			) => Promise< ExtendedTemplate >;
		};
	}
>( {
	admin: async ( { page, pageUtils, editor }, use ) => {
		await use( new Admin( { page, pageUtils, editor } ) );
	},
	editor: async ( { page }, use ) => {
		await use( new Editor( { page } ) );
	},
	page: async ( { page }, use ) => {
		page.on( 'console', observeConsoleLogging );

		await use( page );

		// Clear local storage after each test.
		await page.evaluate( () => {
			window.localStorage.clear();
		} );

		const cliOutput = await cli(
			`npm run wp-env run tests-cli wp db import ${ DB_EXPORT_FILE }`
		);
		if ( ! cliOutput.stdout.includes( 'Success: Imported ' ) ) {
			throw new Error( `Failed to import ${ DB_EXPORT_FILE }` );
		}
	},
	pageUtils: async ( { page }, use ) => {
		await use( new PageUtils( { page } ) );
	},
	templateApiUtils: async ( {}, use ) =>
		await use( new TemplateApiUtils( baseRequest ) ),
	editorUtils: async ( { editor, page, admin }, use ) => {
		await use( new EditorUtils( editor, page, admin ) );
	},
	frontendUtils: async ( { page, requestUtils }, use ) => {
		await use( new FrontendUtils( page, requestUtils ) );
	},
	performanceUtils: async ( { page }, use ) => {
		await use( new PerformanceUtils( page ) );
	},
	storeApiUtils: async ( { requestUtils }, use ) => {
		await use( new StoreApiUtils( requestUtils ) );
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
	wpCliUtils: async ( {}, use ) => {
		await use( new WPCLIUtils() );
	},
	requestUtils: [
		async ( {}, use, workerInfo ) => {
			const requestUtils = await RequestUtils.setup( {
				baseURL: workerInfo.project.use.baseURL,
				storageStatePath: STORAGE_STATE_PATH,
			} );

			const utilCreatePostFromTemplate = (
				post: Partial< PostPayload >,
				templatePath: string,
				data: unknown
			) =>
				createPostFromTemplate(
					requestUtils,
					post,
					templatePath,
					data
				);

			const utilDeletePost = ( id: number ) =>
				deletePost( requestUtils, id );

			const utilUpdateTemplateContents = (
				templateId: string,
				templatePath: string,
				data: unknown
			) =>
				updateTemplateContents(
					requestUtils,
					templateId,
					templatePath,
					data
				);

			await use( {
				...requestUtils,
				createPostFromTemplate: utilCreatePostFromTemplate,
				updateTemplateContents: utilUpdateTemplateContents,
				deletePost: utilDeletePost,
			} );
		},
		{ scope: 'worker', auto: true },
	],
} );

export { test, expect };
