/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';
import {
	installPluginFromPHPFile,
	uninstallPluginFromPHPFile,
} from '@woocommerce/e2e-mocks/custom-plugins';

/**
 * Internal dependencies
 */

const dummyBlockPluginFileName = 'dummy-block-plugin.php';

test.describe( 'useGetLocation hook', () => {
	test.beforeAll( async () => {
		await installPluginFromPHPFile(
			`${ __dirname }/${ dummyBlockPluginFileName }`
		);
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await uninstallPluginFromPHPFile(
			`${ __dirname }/${ dummyBlockPluginFileName }`
		);
		await requestUtils.deleteAllTemplates( 'wp_template' );
	} );

	test.describe( 'Location is recognised correctly', () => {
		test( 'in specific Single Product template', async ( {
			page,
			editorUtils,
		} ) => {
			const productName = 'Cap';
			const productSlug = 'cap';

			await editorUtils.openSpecificProductTemplate(
				productName,
				productSlug
			);

			await editorUtils.insertBlockUsingGlobalInserter(
				'Test useGetLocation hook'
			);

			const locationType = page.getByText( 'Location type: product' );

			expect( locationType ).toBeVisible();
		} );
		test( 'in Single Product block in specific Product template', async ( {
			editorUtils,
		} ) => {
			const productName = 'Belt';
			const productSlug = 'belt';

			await editorUtils.openSpecificProductTemplate(
				productName,
				productSlug
			);
		} );
		test( 'in Single Product template', async ( {} ) => {} );
		test( 'in specific Category template', async ( {} ) => {} );
		test( 'in specific Tag template', async ( {} ) => {} );
		test( 'in Products by Category template', async ( {} ) => {} );
		test( 'in Products by Tag template', async ( {} ) => {} );
		test( 'in Products by Attribute template', async ( {} ) => {} );
		test( 'in Cart template', async ( {} ) => {} );
		test( 'in Checkout template', async ( {} ) => {} );
		test( 'in Order Confirmation template', async ( {} ) => {} );
		test( 'as product in Single Product block in specific Category template', async ( {} ) => {} );
		test( 'as product in Single Product block in specific Tag template', async ( {} ) => {} );
		test( 'as product in Single Product block in Products by Attribute template', async ( {} ) => {} );
		test( 'as generic in post', async ( {} ) => {} );
		test( 'as generic in Product Catalog', async ( {} ) => {} );
	} );
} );
