/**
 * External dependencies
 */
import { test, expect } from '@playwright/test';
import { PHPRequestHandler, PHP } from '@php-wasm/universal';
import { runCLI } from '@wp-playground/cli';
import { login } from '@wp-playground/blueprints';
import { readFileSync } from 'fs';
import { resolve } from 'path';

test.describe( 'WooCommerce Playground Tests', () => {
	let cliServer: any;
	let handler: PHPRequestHandler;
	let php: PHP;

	test.beforeEach( async () => {
		const blueprint = JSON.parse(
			readFileSync( resolve( __dirname, './blueprint.json' ), 'utf8' )
		);

		cliServer = await runCLI( {
			command: 'server',
			mount: [
				{
					hostPath: resolve( __dirname, '../../../..' ),
					vfsPath: '/wordpress/wp-content/plugins/woocommerce',
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

	test( 'Post Editor', async ( { page } ) => {
		// I can't find where we redirect to this page :sob:
		const wcAdminUrl = new URL( handler.absoluteUrl );
		wcAdminUrl.pathname = '/wp-admin/admin.php';
		wcAdminUrl.searchParams.set( 'page', 'wc-admin' );
		await page.goto( wcAdminUrl.toString() );

		const newPageUrl = new URL( handler.absoluteUrl );
		newPageUrl.pathname = '/wp-admin/post-new.php';
		await page.goto( newPageUrl.toString() );

		await page
			.getByRole( 'button', {
				name: 'Close',
			} )
			.click();

		await page
			.getByRole( 'button', {
				name: 'Block Inserter',
			} )
			.click();

		await page.getByPlaceholder( 'Search' ).fill( 'Product Collection' );
		await expect(
			page.getByRole( 'option', {
				name: 'Product Collection',
				exact: true,
			} )
		).toHaveCount( 1 );
	} );
} );
