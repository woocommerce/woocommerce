/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/no-standalone-expect */
/**
 * Internal dependencies
 */
const { merchant } = require( '@woocommerce/e2e-utils' );

const path = require( 'path' );
const pluginFilePath = path.resolve( './deps/e2e-plugin.zip' );

const pluginName = 'WooCommerce e2e plugin';

const runUploadPluginTest = () => {
	describe('Plugin > Upload plugin', () => {
		beforeAll(async () => {
			await merchant.login();
			await merchant.openPlugins();
		});

		afterAll( async () => {
			await merchant.deactivatePlugin(pluginName, true);
			await merchant.logout();
		});

		it('can upload and install a plugin', async () => {
			await merchant.goToPluginInstall();

			await expect(page).toClick('a.upload-view-toggle');

			await expect(page).toMatchElement(
				'p.install-help',
				{
					text: 'If you have a plugin in a .zip format, you may install or update it by uploading it here.'
				}
			);

			// Upload the plugin zip
			const uploader = await page.$('input[type=file]');

			await uploader.uploadFile(pluginFilePath);

			// Manually update the button to be enabled so we can submit the file
			await page.evaluate(() => {
				document.getElementById('install-plugin-submit').disabled = false;
			 });

			// Click to upload the file
			await expect(page).toClick('#install-plugin-submit');

			await page.waitForNavigation( { waitUntil: 'networkidle0' } );

			// Verify we have the Active Plugin button and click to activate it
			await expect(page).toMatchElement('.button-primary', { text: 'Activate Plugin' });
			await expect(page).toClick('.button-primary');

			await page.waitForNavigation( { waitUntil: 'networkidle0' } );

			await expect(page).toMatchElement('div.updated.notice.is-dismissible', { text: 'Plugin activated.' });
		});
	});
}

module.exports = runUploadPluginTest;
