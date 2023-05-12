const {
	test,
	expect
} = require('@playwright/test');

test.describe('Store owner can finish initial store setup', () => {
	test.use({
		storageState: process.env.ADMINSTATE
	});
	test('can enable tax rates and calculations', async ({
		page
	}) => {
		await page.goto('http://localhost:8086/wp-admin/options-general.php');

		await page.getByRole('combobox', {
			name: 'Site Language'
		}).selectOption('es_ES');
		await page.getByRole('button', {
			name: 'Save Changes'
		}).click();


		//await page.getByRole('link', { name: '5 actualizaciones disponibles' }).click();
		await page.getByRole('link', {
			name: 'actualizaciones disponibles'
		}).click();

		//check if translations up to date
		const translationsUpToDate = await test.step(
			'check if translations up to date',
			async () => {
				return await page.getByText('Tus traducciones están actualizadas.')
					.isVisible();
			}
		);

		if (!translationsUpToDate) {
			console.log('translations not up to date')
			await page.getByRole('button', {
				name: 'Actualizar las traducciones'
			}).click();
			await page.getByRole('link', {
				name: 'Ir a la página de actualizaciones de WordPress'
			}).click();

		}


		// Verify changes have been saved
		//		await expect( page.locator( '#woocommerce_calc_taxes' ) ).toBeChecked();
	});

});
