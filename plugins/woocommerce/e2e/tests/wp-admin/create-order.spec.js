const { test, expect } = require('@playwright/test');
const wcApi = require('@woocommerce/woocommerce-rest-api').default;

const taxClasses = [
	{
		name: 'Tax Class Simple',
	},
	{
		name: 'Tax Class Variable',
	},
	{
		name: 'Tax Class External',
	},
];
const taxRates = [
	{
		name: 'Tax Rate Simple',
		rate: '10.0000',
		class: 'tax-class-simple',
	},
	{
		name: 'Tax Rate Variable',
		rate: '20.0000',
		class: 'tax-class-variable',
	},
	{
		name: 'Tax Rate External',
		rate: '30.0000',
		class: 'tax-class-external',
	},
];
const taxTotals = ['$10.00', '$40.00', '$240.00'];

test.describe.only('WooCommerce Orders > Add new order', () => {
	test.use({ storageState: 'e2e/storage/adminState.json' });

	test.beforeAll(async () => {
		const api = new wcApi({
			url: 'http://localhost:8084',
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		});
		// enable taxes on the account
		await api.put('settings/general/woocommerce_calc_taxes', { value: 'yes' });
		// add tax classes
		for (const key in taxClasses) {
			api.post('taxes/classes', taxClasses[key]).then(() => {
				// add tax rates
				for (const key in taxRates) {
					api.post('taxes', taxRates[key]).catch((error) => {
						console.log(error.response.status);
						console.log(error.response.data);
						console.log(error.response.headers);
					});
				}
			});
		}
		// create simple product
		await api.post('products', {
			name: 'Simple Product 273722',
			type: 'simple',
			regular_price: '100',
			taxClass: 'Tax Class Simple'
		}).then((response) => {
			simpleProductId = response.data.id;
		});
		// create variable product
		const variations = [{
			regularPrice: '200',
			attributes: [{
				name: 'Size',
				option: 'Small'
			}, {
				name: 'Colour',
				option: 'Yellow'
			}],
			taxClass: 'Tax Class Variable'
		}, {
			regularPrice: '300',
			attributes: [{
				name: 'Size',
				option: 'Medium'
			}, {
				name: 'Colour',
				option: 'Magenta'
			}],
			taxClass: 'Tax Class Variable'
		}];
		await api.post('products', {
			name: 'Variable Product 024611',
			type: 'variable',
			taxClass: 'Tax Class Variable'
		}).then((response) => {
			variableProductId = response.data.id;
			for (const key in variations) {
				api.post(`products/${variableProductId}/variations`, variations[key]);
			}
		});
	});

	test.afterAll(async () => {
		// cleans up all products after run
		const api = new wcApi({
			url: 'http://localhost:8084',
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		});
		await api.delete(`products/${simpleProductId}`, { force: true });
	});

	test('can create new order', async ({ page }) => { });

	test('can create new complex order with multiple product types & tax classes', async ({
		page,
	}) => { });
});
