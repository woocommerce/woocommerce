/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import type { PageContext, EditorCanvas } from './types';

// Re-export types for consumers
export type { PageContext, EditorCanvas } from './types';

/**
 * WordPress data store interface for type checking.
 */
interface WpData {
	select: ( store: string ) => {
		isFeatureActive: ( feature: string ) => boolean;
	};
	dispatch: ( store: string ) => {
		toggleFeature: ( feature: string ) => void;
	};
}

type WindowWithWp = Window & { wp?: { data?: WpData } };

/**
 * Closes the "Choose a pattern" modal if present.
 *
 * @param context      - Object containing the Playwright page
 * @param context.page - The Playwright page object
 */
export const closeChoosePatternModal = async ( {
	page,
}: PageContext ): Promise< void > => {
	const closeModal = page
		.locator( 'div' )
		.filter( { hasText: 'Choose a pattern' } )
		.getByLabel( 'Close' );
	await page.addLocatorHandler( closeModal, async () => {
		await closeModal.click();
	} );
};

/**
 * Disables the Gutenberg welcome modal.
 *
 * @param context      - Object containing the Playwright page
 * @param context.page - The Playwright page object
 */
export const disableWelcomeModal = async ( {
	page,
}: PageContext ): Promise< void > => {
	// Further info: https://github.com/woocommerce/woocommerce/pull/45856/
	await page.waitForLoadState( 'domcontentloaded' );

	const isWelcomeGuideActive = await page.evaluate( () =>
		( window as unknown as WindowWithWp ).wp?.data
			?.select( 'core/edit-post' )
			?.isFeatureActive( 'welcomeGuide' )
	);

	if ( isWelcomeGuideActive ) {
		await page.evaluate( () =>
			( window as unknown as WindowWithWp ).wp?.data
				?.dispatch( 'core/edit-post' )
				?.toggleFeature( 'welcomeGuide' )
		);
	}
};

/**
 * Opens the editor settings sidebar if closed.
 *
 * @param context      - Object containing the Playwright page
 * @param context.page - The Playwright page object
 */
export const openEditorSettings = async ( {
	page,
}: PageContext ): Promise< void > => {
	// Open Settings sidebar if closed
	if ( await page.getByLabel( 'Editor Settings' ).isVisible() ) {
		console.log( 'Editor Settings is open, skipping action.' );
	} else {
		await page.getByLabel( 'Settings', { exact: true } ).click();
	}
};

/**
 * Returns the editor canvas frame for Gutenberg interactions.
 *
 * The Gutenberg editor content can be contained within an iframe in some contexts.
 * This helper function returns the content frame of the editor canvas iframe if it exists,
 * or falls back to the main page if the iframe isn't present.
 *
 * @param page - The Playwright page object
 * @return The editor canvas frame or the original page
 */
export const getCanvas = async ( page: Page ): Promise< EditorCanvas > => {
	const iframeLocator = page.locator( 'iframe[name="editor-canvas"]' );
	if ( ( await iframeLocator.count() ) > 0 ) {
		return iframeLocator.contentFrame();
	}
	return page;
};

/**
 * Navigates to the WordPress page editor.
 *
 * @param context      - Object containing the Playwright page
 * @param context.page - The Playwright page object
 */
export const goToPageEditor = async ( {
	page,
}: PageContext ): Promise< void > => {
	await page.goto( 'wp-admin/post-new.php?post_type=page' );
	await disableWelcomeModal( { page } );
	await closeChoosePatternModal( { page } );
};

/**
 * Navigates to the WordPress post editor.
 *
 * @param context      - Object containing the Playwright page
 * @param context.page - The Playwright page object
 */
export const goToPostEditor = async ( {
	page,
}: PageContext ): Promise< void > => {
	await page.goto( 'wp-admin/post-new.php' );
	await disableWelcomeModal( { page } );
};

/**
 * Inserts a block using the block inserter.
 *
 * @param page      - The Playwright page object
 * @param blockName - The name of the block to insert
 */
export const insertBlock = async (
	page: Page,
	blockName: string
): Promise< void > => {
	// Focus on "Empty block" element before inserting a new block.
	// Otherwise, Gutenberg nightly (v19.9-nightly) would display "{Block name} can't be inserted."
	const canvas = await getCanvas( page );
	const emptyBlock = canvas.getByLabel( 'Empty block' );
	if ( await emptyBlock.isVisible() ) {
		await emptyBlock.click();
	}

	// With Gutenberg active we have Block Inserter name
	await page
		.getByRole( 'button', {
			name: /Toggle block inserter|Block Inserter/,
			expanded: false,
		} )
		.click();

	await page.getByPlaceholder( 'Search', { exact: true } ).fill( blockName );
	await page.getByRole( 'option', { name: blockName, exact: true } ).click();

	await page
		.getByRole( 'button', {
			name: 'Close block inserter',
		} )
		.click();
};

/**
 * Inserts a block using the slash command shortcut.
 *
 * @param page      - The Playwright page object
 * @param blockName - The name of the block to insert
 */
export const insertBlockByShortcut = async (
	page: Page,
	blockName: string
): Promise< void > => {
	const canvas = await getCanvas( page );
	const emptyBlockField = canvas.getByText( 'Type / to choose a block' ).or(
		canvas.getByRole( 'document', {
			name: 'Empty block; start writing or type forward slash to choose a block',
		} )
	);
	await emptyBlockField.click();
	await emptyBlockField.pressSequentially( `/${ blockName }` );
	await page.getByRole( 'option', { name: blockName, exact: true } ).click();
};

/**
 * Transforms classic content into blocks.
 *
 * @param page - The Playwright page object
 */
export const transformIntoBlocks = async ( page: Page ): Promise< void > => {
	const canvas = await getCanvas( page );

	await canvas
		.getByRole( 'button' )
		.filter( { hasText: 'Transform into blocks' } )
		.click();
};

/**
 * Response JSON structure for published pages/posts.
 */
interface PublishResponse {
	title: {
		rendered: string;
	};
	status: string;
}

/**
 * Publishes a page or post.
 *
 * @param page      - The Playwright page object
 * @param pageTitle - The title of the page/post being published
 * @param isPost    - Whether this is a post (true) or page (false)
 */
export const publishPage = async (
	page: Page,
	pageTitle: string,
	isPost = false
): Promise< void > => {
	await page
		.getByRole( 'button', { name: 'Publish', exact: true } )
		.dispatchEvent( 'click' );

	const createPageResponse = page.waitForResponse( ( response ) => {
		return (
			response.url().includes( isPost ? '/posts/' : '/pages/' ) &&
			response.ok() &&
			response.request().method() === 'POST' &&
			response
				.json()
				.then(
					( json: PublishResponse ) =>
						json.title.rendered === pageTitle &&
						json.status === 'publish'
				)
		);
	} );

	await page
		.getByRole( 'region', { name: 'Editor publish' } )
		.getByRole( 'button', { name: 'Publish', exact: true } )
		.click();

	// Validating that page was published via UI elements is not reliable,
	// installed plugins (e.g. WooCommerce PayPal Payments) can interfere and add flakiness to the flow.
	// In WC context, checking the API response is possibly the most reliable way to ensure the page was published.
	await createPageResponse;
};
