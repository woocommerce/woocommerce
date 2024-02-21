/**
 * External dependencies
 */
import { FullConfig, chromium, request } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { cli, BASE_URL, adminFile, customerFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { customer, admin } from './test-data/data/data';

const prepareAttributes = async ( requestUtils: RequestUtils ) => {
	const browser = await chromium.launch();
	const context = await browser.newContext( {
		baseURL: requestUtils.baseURL as string,
		storageState: requestUtils.storageStatePath as string,
	} );

	const page = await context.newPage();

	// Intercept the dialog event. This is needed because when the regenerate
	// button is clicked, a dialog is shown.
	page.on( 'dialog', async ( dialog ) => {
		await dialog.accept();
	} );

	await page.goto( '/wp-admin/admin.php?page=wc-status&tab=tools', {
		waitUntil: 'commit',
	} );

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
	const { storageState, baseURL } = config.projects[ 0 ].use;

	const requestContext = await request.newContext( {
		baseURL: baseURL || BASE_URL,
	} );

	const adminRequestUtils = new RequestUtils( requestContext, {
		user: admin,
		storageStatePath:
			typeof storageState === 'string' ? storageState : adminFile,
	} );
	const customerRequestUtils = new RequestUtils( requestContext, {
		user: customer,
		storageStatePath: customerFile,
	} );

	await Promise.all( [
		adminRequestUtils.setupRest(),
		customerRequestUtils.setupRest(),
	] );

	await prepareAttributes( adminRequestUtils );

	await requestContext.dispose();
}

export default globalSetup;
