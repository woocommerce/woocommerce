/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';
import { PHPRequestHandler, PHP } from '@php-wasm/universal';
import { runCLI } from '@wp-playground/cli';
import { login } from '@wp-playground/blueprints';
import { resolve } from 'path';

const blockData = {
	name: 'Store Notices',
	slug: 'woocommerce/store-notices',
};

test.describe( `${ blockData.slug } Block`, () => {
	let cliServer: any;
	// let handler: PHPRequestHandler;
	// let php: PHP;

	test.beforeEach( async () => {
		cliServer = await runCLI( {
			command: 'server',
			mountBeforeInstall: [
				{
					hostPath: resolve(
						__dirname,
						'../../playground/tmp/wordpress'
					),
					vfsPath: '/wordpress/',
				},
			],
			mount: [
				{
					hostPath: resolve( __dirname, '../../../../../../' ),
					vfsPath: '/wordpress/wp-content/plugins/woocommerce',
				},
			],
			skipWordPressSetup: true,
			followSymlinks: true,
			login: true,
			quiet: true,
		} );

		// handler = cliServer.requestHandler;
		// php = await handler.getPrimaryPhp();
		// await login( php, {
		// 	username: 'admin',
		// } );
	} );

	test.afterEach( async () => {
		if ( cliServer ) {
			await cliServer.server.close();
		}
	} );

	test( 'should be visible on the Product Catalog template', async ( {
		editor,
		admin,
	} ) => {
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
			canvas: 'edit',
		} );
		const block = await editor.getBlockByName( blockData.slug );
		await expect( block ).toBeVisible();
		await expect( block ).toHaveText(
			'Notices added by WooCommerce or extensions will show up here.'
		);
	} );
} );
