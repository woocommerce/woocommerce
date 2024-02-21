/* eslint-disable no-console */

/**
 * External dependencies
 */
import {
	BrowserContextOptions,
	FullConfig,
	chromium,
	request,
} from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { cli, customerFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { customer, admin } from './test-data/data/data';

const prepareAttributes = async ( contextOptions: BrowserContextOptions ) => {
	const browser = await chromium.launch();
	const context = await browser.newContext( contextOptions );

	const page = await context.newPage();

	// Intercept the dialog event. This is needed because when the regenerate
	// button is clicked, a dialog is shown.
	page.on( 'dialog', async ( dialog ) => {
		await dialog.accept();
	} );

	await page.goto( '/wp-admin/admin.php?page=wc-status&tab=tools' );

	// TODO: This sometimes does not work - the button is disabled and the job
	// is pending. We need to investigate why this happens.
	await page
		.getByRole( 'row', {
			name: /Regenerate the product attributes lookup table/,
		} )
		.getByRole( 'button' )
		.click();

	await context.close();
	await browser.close();

	// Note that the two commands below are intentionally duplicated as we need
	// to run the cron task twice as we need to process more than 1 batch of
	// items.
	const cronTask = `npm run wp-env run tests-cli -- wp action-scheduler run --hooks="woocommerce_run_product_attribute_lookup_regeneration_callback"`;
	await cli( cronTask );
	await cli( cronTask );
};

async function globalSetup( config: FullConfig ) {
	const timers = {
		total: '└ Total time',
		authentication: '├ Authentication time',
		attributes: '├ Attributes preparation time',
	};

	console.log( 'Running global setup...' );
	console.time( timers.total );

	const { storageState, baseURL } = config.projects[ 0 ].use;

	if ( ! storageState || typeof storageState !== 'string' ) {
		throw new Error( 'Storage state path is required.' );
	}

	if ( ! baseURL ) {
		throw new Error( 'Base URL is required.' );
	}

	const requestContext = await request.newContext( {
		baseURL,
	} );

	const adminRequestUtils = new RequestUtils( requestContext, {
		user: admin,
		storageStatePath: storageState,
	} );
	const customerRequestUtils = new RequestUtils( requestContext, {
		user: customer,
		storageStatePath: customerFile,
	} );

	console.time( timers.authentication );
	await adminRequestUtils.setupRest();
	await customerRequestUtils.setupRest();
	console.timeEnd( timers.authentication );

	console.time( timers.attributes );
	await prepareAttributes( { baseURL, storageState } );
	console.timeEnd( timers.attributes );

	await requestContext.dispose();
	console.timeEnd( timers.total );
}

export default globalSetup;
