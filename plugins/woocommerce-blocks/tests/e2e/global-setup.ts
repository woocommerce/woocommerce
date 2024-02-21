/* eslint-disable no-console */
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

const prepareAttributes = async ( config: FullConfig ) => {
	const { baseURL, userAgent } = config.projects[ 0 ].use;

	// Specify user agent when running against an external test site to avoid getting HTTP 406 NOT ACCEPTABLE errors.
	const contextOptions = { baseURL, userAgent };

	// Create browser, browserContext, and page for customer and admin users
	const browser = await chromium.launch();
	const context = await browser.newContext( contextOptions );
	const page = await context.newPage();

	await page.goto( `/wp-admin`, { waitUntil: 'commit' } );
	await page.fill( 'input[name="log"]', admin.username );
	await page.fill( 'input[name="pwd"]', admin.password );
	await page.click( 'text=Log In' );

	/*
	 * Intercept the dialog event.
	 * This is needed because when the regenerate
	 * button is clicked, a dialog is shown.
	 */
	page.on( 'dialog', async ( dialog ) => {
		await dialog.accept();
	} );

	await page.goto( '/wp-admin/admin.php?page=wc-status&tab=tools', {
		waitUntil: 'commit',
	} );

	await page.click( '.regenerate_product_attributes_lookup_table input' );

	await context.close();
	await browser.close();

	/*
	 * Note that the two commands below are intentionally
	 * duplicated as we need to run the cron task twice as
	 * we need to process more than 1 batch of items.
	 */
	await cli(
		`npm run wp-env run tests-cli -- wp action-scheduler run --hooks="woocommerce_run_product_attribute_lookup_regeneration_callback"`
	);

	await cli(
		`npm run wp-env run tests-cli -- wp action-scheduler run --hooks="woocommerce_run_product_attribute_lookup_regeneration_callback"`
	);
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

	await requestContext.dispose();

	await prepareAttributes( config );
}

export default globalSetup;
