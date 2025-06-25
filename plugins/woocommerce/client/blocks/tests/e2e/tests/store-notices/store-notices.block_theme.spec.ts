/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';
import { PHPRequestHandler, PHP } from '@php-wasm/universal';
import { runCLI } from '@wp-playground/cli';
import { login } from '@wp-playground/blueprints';
import { readFileSync } from 'fs';
import { resolve } from 'path';

/**
 * Internal dependencies
 */

const blockData = {
	name: 'Store Notices',
	slug: 'woocommerce/store-notices',
};

test.describe( `${ blockData.slug } Block`, () => {
	let cliServer: any;
	let handler: PHPRequestHandler;
	let php: PHP;

	test.beforeEach( async () => {
		const blueprint = JSON.parse(
			readFileSync(
				resolve( __dirname, '../../../playground/blueprint.json' ),
				'utf8'
			)
		);

		cliServer = await runCLI( {
			command: 'server',
			mount: [
				{
					hostPath: resolve( __dirname, '../../../../../..' ),
					vfsPath: '/woocommerce',
				},
			],
			blueprint,
			quiet: true,
		} );

		handler = cliServer.requestHandler;
		php = await handler.getPrimaryPhp();
		await login( php, {
			username: 'admin',
		} );
	} );

	test.afterEach( async () => {
		if ( cliServer ) {
			await cliServer.server.close();
		}
	} );

	test( 'should be visible on the Product Catalog template', async ( {
		page,
		editor,
	} ) => {
		const wpAdminUrl = new URL( handler.absoluteUrl );
		wpAdminUrl.pathname = '/wp-admin/site-editor.php';
		wpAdminUrl.searchParams.set(
			'p',
			'/wp_template/woocommerce/woocommerce//archive-product'
		);
		wpAdminUrl.searchParams.set( 'canvas', 'edit' );
		await page.goto( wpAdminUrl.toString() );

		await editor.setPreferences( 'core/edit-site', {
			welcomeGuide: false,
			welcomeGuideStyles: false,
			welcomeGuidePage: false,
			welcomeGuideTemplate: false,
		} );

		const block = await editor.getBlockByName( blockData.slug );
		await expect( block ).toBeVisible();
		await expect( block ).toHaveText(
			'Notices added by WooCommerce or extensions will show up here.'
		);
	} );
} );
