const { test, expect } = require('@playwright/test');
const request = require('request');
const fs = require('fs');

test.describe('WooCommerce plugin can be uploaded and activated', () => {
	test.use({ storageState: 'e2e/storage/adminState.json' });

	test('can upload and activate the WooCommerce plugin', async ({
		page,
	}) => {
		request.get(
			'https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip'
		)
			.pipe(fs.createWriteStream('/tmp/woo-latest.zip'));

		await page.goto('wp-admin/plugins.php');
		// Deactivate and delete the plugin if it exists
		if ((await page.$('#deactivate-woocommerce')) !== null) {
			await page.locator('#deactivate-woocommerce').click();
			if ((await page.$('#delete-woocommerce')) !== null) {
				page.on('dialog', dialog => dialog.accept());
				await page.locator('#delete-woocommerce').click();
			}
		}

		// Open the plugin install page
		await page.goto('wp-admin/plugin-install.php');

		// Upload the plugin zip file
		await page.locator('a.upload-view-toggle').click();
		await expect(page.locator('p.install-help')).toHaveText(
			'If you have a plugin in a .zip format, you may install or update it by uploading it here.'
		);
		await page.setInputFiles('#pluginzip', '/tmp/woo-latest.zip');

		// Manually update the button to `enabled` so we can submit the file
		await page.$eval('#install-plugin-submit', (el) =>
			el.removeAttribute('disabled')
		);
		await page.locator('#install-plugin-submit').click();

		// Activate the plugin
		await page.locator('.button-primary').click();
	});

	test('can run the database update', async ({ page }) => {
		await page.goto('wp-admin/');
		if (
			(await page.$('.updated.woocommerce-message.wc-connect')) !==
			null
		) {
			await expect(
				page
					.locator('a.wc-update-now')
					.toHaveText('Update WooCommerce Database')
			);
			await page.locator('a.wc-update-now').click();
			do {
				await expect(
					page.locator('a.components-button.is-primary')
				).toHaveText('Thanks!');
				await page.locator('a.components-button.is-primary').click();
			} while (
				(await page.$('a.components-button.is-primary')) !== null
			);
		}
	});


	// copying this logic, but this isn't a test. It's a cleanup task -- and there's no assertions.
	test.skip('can remove downloaded plugin zip', async ({ page }) => {
		const pluginSavePath = '/tmp/woo-latest.zip';

		fs.readdir(pluginSavePath, (err, contents) => {
			if (err) throw err;

			for (const content of contents) {
				const contentPath = path.join(pluginSavePath, content);
				const stats = fs.lstatSync(contentPath);

				if (stats.isDirectory()) {
					fs.rmSync(contentPath, { recursive: true, force: true });
				} else {
					fs.unlink(contentPath, (error) => {
						if (error) throw error;
					});
				}
			}
		});
	});
});
