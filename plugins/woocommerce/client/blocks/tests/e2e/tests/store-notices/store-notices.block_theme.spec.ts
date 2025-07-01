/**
 * External dependencies
 */
import { expect, test } from '@woocommerce/e2e-utils';
import { runCLI } from '@wp-playground/cli';
import { resolve } from 'path';
import { addQueryArgs } from '@wordpress/url';

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
			port: 9401,
			login: true,
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
		page,
		editor,
		admin,
	} ) => {
		const query = addQueryArgs( '', {
			postId: 'woocommerce/woocommerce//archive-product',
			postType: 'wp_template',
			canvas: 'edit',
		} ).slice( 1 );

		await admin.visitAdminPage( 'site-editor.php', query );

		await page.evaluate( () => {
			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-site', 'welcomeGuide', false );

			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-site', 'welcomeGuideStyles', false );

			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-site', 'welcomeGuidePage', false );

			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-site', 'welcomeGuideTemplate', false );
		} );

		const block = await editor.getBlockByName( blockData.slug );
		await expect( block ).toBeVisible();
		await expect( block ).toHaveText(
			'Notices added by WooCommerce or extensions will show up here.'
		);
	} );
} );
