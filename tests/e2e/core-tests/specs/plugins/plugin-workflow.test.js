/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/no-standalone-expect */
/**
 * Internal dependencies
 */
const { merchant } = require( '@woocommerce/e2e-utils' );

const path = require( 'path' );
const pluginFilePath = path.resolve( './deps/e2e-plugin.zip' );

const pluginName = 'WooCommerce e2e plugin';

const runPluginWorkflowTest = () => {
	describe('Plugins workflow', () => {
		beforeAll(async () => {
			await merchant.login();
			await merchant.uploadAndActivatePlugin( pluginFilePath, pluginName );
		});

		afterAll( async () => {
			await merchant.logout();
		});

		it('can deactivate a plugin', async () => {
			await merchant.deactivatePlugin( pluginName );

			await expect(page).toMatchElement('td.plugin-title.column-primary', { text: pluginName });
			await expect(page).toMatchElement('div.updated.notice.is-dismissible', { text: 'Plugin deactivated.' });
		});

		it('can activate a plugin', async () => {
			await merchant.activatePlugin( pluginName );

			await expect(page).toMatchElement('td.plugin-title.column-primary', { text: pluginName });
			await expect(page).toMatchElement('div.updated.notice.is-dismissible', { text: 'Plugin activated.' });
		});

		it('can deativate and delete a plugin', async () => {
			await merchant.deactivatePlugin( pluginName );

			await merchant.deletePlugin( pluginName );

			await expect(page).not.toMatchElement('td.plugin-title.column-primary', { text: pluginName });
			await expect(page).toMatchElement('td.plugin-update.colspanchange', { text: `${ pluginName } was successfully deleted.` });
        });
	});
}

module.exports = runPluginWorkflowTest;
