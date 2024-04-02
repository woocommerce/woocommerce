/* eslint-disable no-console */

/**
 * External dependencies
 */
import { chromium, request } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { BASE_URL, adminFile, cli, customerFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { customer, admin } from './test-data/data/data';

const prepareAttributes = async () => {
	const browser = await chromium.launch();
	const context = await browser.newContext( {
		baseURL: BASE_URL,
		storageState: adminFile,
	} );

	const page = await context.newPage();

	// Intercept the dialog event. This is needed because when the regenerate
	// button is clicked, a dialog is shown.
	page.on( 'dialog', async ( dialog ) => {
		await dialog.accept();
	} );

	await page.goto( '/wp-admin/admin.php?page=wc-status&tab=tools' );

	// Attributes regeneration should be doable via a CLI command, e.g.:
	// "wp wc tool run regenerate_product_attributes_lookup_table --user=1"
	// It doesn't seem to be working correctly ATM so we need to do it via
	// browser actions.
	// See: https://github.com/woocommerce/woocommerce/issues/32831
	await page
		.getByRole( 'row', {
			name: /Regenerate the product attributes lookup table/,
		} )
		.getByRole( 'button' )
		.click();

	await page.goto( BASE_URL + '/wp-admin/post-new.php' );

	await page.waitForFunction( () => {
		return window.wp.data !== undefined;
	} );

	// Disable the welcome guide for the site editor.
	await page.evaluate( () => {
		return Promise.all( [
			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-site', 'welcomeGuide', false ),
			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-site', 'welcomeGuideStyles', false ),
			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-site', 'welcomeGuidePage', false ),
			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-site', 'welcomeGuideTemplate', false ),
			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-post', 'welcomeGuide', false ),
			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-post', 'welcomeGuideStyles', false ),
			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-post', 'welcomeGuidePage', false ),

			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-post', 'welcomeGuideTemplate', false ),
		] );
	} );

	await page.context().storageState( { path: adminFile } );

	await context.close();
	await browser.close();

	// Note that the two commands below are intentionally duplicated as we need
	// to run the cron task twice as we need to process more than 1 batch of
	// items.
	const cronTask = `npm run wp-env run tests-cli -- wp action-scheduler run --hooks="woocommerce_run_product_attribute_lookup_regeneration_callback"`;
	await cli( cronTask );
	await cli( cronTask );
};

async function globalSetup() {
	const timers = {
		total: '└ Total time',
		authentication: '├ Authentication time',
		attributes: '├ Attributes preparation time',
	};

	console.log( 'Running global setup...' );
	console.time( timers.total );

	const requestContext = await request.newContext( {
		baseURL: BASE_URL,
	} );

	console.time( timers.authentication );
	await new RequestUtils( requestContext, {
		user: admin,
		storageStatePath: adminFile,
	} ).setupRest();
	await new RequestUtils( requestContext, {
		user: customer,
		storageStatePath: customerFile,
	} ).setupRest();
	console.timeEnd( timers.authentication );

	console.time( timers.attributes );
	await prepareAttributes();
	console.timeEnd( timers.attributes );

	await requestContext.dispose();
	console.timeEnd( timers.total );
}

export default globalSetup;
