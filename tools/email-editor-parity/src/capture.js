import { chromium } from 'playwright';

import { config } from '../config.js';
import { extractGeometryInPage } from './geometry.js';

export async function launchBrowser() {
	const browser = await chromium.launch( { headless: config.headless } );
	const context = await browser.newContext( {
		viewport: { width: 1600, height: 1200 },
		reducedMotion: 'reduce',
		deviceScaleFactor: 1,
	} );
	return { browser, context };
}

export async function login( context ) {
	const page = await context.newPage();
	await page.goto( `${ config.baseUrl }/wp-login.php` );
	await page.fill( '#user_login', config.username );
	await page.fill( '#user_pass', config.password );
	await Promise.all( [
		page.waitForURL( '**/wp-admin/**', { timeout: 30000 } ),
		page.click( '#wp-submit' ),
	] );
	// Loading the permalinks screen flushes rewrite rules, so the woo_email
	// preview permalink resolves even right after the feature flag flip.
	await page.goto( `${ config.baseUrl }/wp-admin/options-permalink.php` );
	await page.close();
}

async function dismissModals( page ) {
	const closeButton = page.locator(
		'.components-modal__frame button[aria-label="Close"], .components-modal__frame button[aria-label="Close dialog"]'
	);
	try {
		await closeButton.first().click( { timeout: 3000 } );
	} catch {
		// No modal — fine.
	}
}

export async function captureEditor( context, postId ) {
	const page = await context.newPage();
	await page.goto(
		`${ config.baseUrl }/wp-admin/post.php?post=${ postId }&action=edit`
	);
	await page
		.locator( '#woocommerce-email-editor' )
		.waitFor( { timeout: 60000 } );

	const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
	const rootLocator = canvas.locator( '.is-root-container' );
	await rootLocator.waitFor( { timeout: 60000 } );
	await dismissModals( page );

	const frame = page.frames().find( ( f ) => f.name() === 'editor-canvas' );
	if ( ! frame ) {
		throw new Error( 'editor-canvas frame not found' );
	}
	await frame.evaluate( () => document.fonts.ready );
	// Give the style pipeline (useEmailCss + stylesheet filtering) a moment.
	await page.waitForTimeout( 1500 );

	const warnings = [];
	const warningCount = await canvas
		.locator( '.block-editor-warning' )
		.count();
	if ( warningCount > 0 ) {
		warnings.push(
			`${ warningCount } block validation warning(s) in the canvas — fixture markup does not match the block's expected output. Editor capture may be unreliable.`
		);
	}

	const geometry = await frame.evaluate( extractGeometryInPage, {
		rootSelector: '.is-root-container',
		overflowSelectors: [ '.is-root-container' ],
	} );
	const html = await frame.evaluate(
		() => document.querySelector( '.is-root-container' )?.outerHTML ?? ''
	);
	const png = await rootLocator.screenshot();

	await page.close();
	return { png, geometry, warnings, html };
}

export async function capturePreview( context, previewUrl ) {
	const page = await context.newPage();
	await page.goto( previewUrl );
	const rootLocator = page.locator( '.email_content_wrapper' );
	try {
		await rootLocator.waitFor( { timeout: 30000 } );
	} catch {
		// The wp-env site occasionally serves a transient error page —
		// one reload usually recovers it.
		await page.reload();
		await rootLocator.waitFor( { timeout: 30000 } );
	}
	await page.evaluate( () => document.fonts.ready );

	const geometry = await page.evaluate( extractGeometryInPage, {
		rootSelector: '.email_content_wrapper',
		overflowSelectors: [
			'.email_content_wrapper',
			'.email_layout_wrapper',
		],
	} );
	const html = await page.content();
	const png = await rootLocator.screenshot();

	await page.close();
	return { png, geometry, html };
}
