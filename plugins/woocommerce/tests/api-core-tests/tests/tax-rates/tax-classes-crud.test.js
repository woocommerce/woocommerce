const {
	test,
	expect
} = require('@playwright/test');

const {
	allUSTaxesExample
} = require('../../data');

/**
 * Tests for the WooCommerce Customers API.
 *
 * @group api
 * @group taxRates
 *
 */
test.describe('Tax Classes API tests: CRUD', () => {
	let taxRateId;

	test.describe('Create a tax class', () => {

		test('can enable tax calculations', async ({
			request,
		}) => {
			// call API to enable tax rates and calculations
			const response = await request.put(
				'/wp-json/wc/v3/settings/general/woocommerce_calc_taxes', {
					data: {
						value: 'yes',
					},
				}
			);
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(typeof responseJSON.id).toEqual('string');
			expect(responseJSON.id).toEqual('woocommerce_calc_taxes');
			expect(responseJSON.label).toEqual("Enable taxes");
			expect(responseJSON.type).toEqual("checkbox");
			expect(responseJSON.value).toEqual("yes");
			expect(responseJSON.group_id).toEqual("general");
		});

		test('can create a tax class', async ({
			request,
		}) => {
			// call API to create a customer
			const response = await request.post('/wp-json/wc/v3/taxes', {
				data: {
					country: "US",
					state: "AL",
					cities: ["Alpine", "Brookside", "Cardiff"],
					postcodes: ["35014", "35036", "35041"],
					rate: "4",
					name: "State Tax",
					shipping: false
				}
			});
			const responseJSON = await response.json();

			// Save the tax rate ID. It will be used by the retrieve, update, and delete tests.
			taxRateId = responseJSON.id;
			console.log('taxRateId=', taxRateId);

			expect(response.status()).toEqual(201);
			expect(typeof responseJSON.id).toEqual('number');
			// Verify that the tax rate class is 'standard'
			expect(responseJSON.country).toEqual("US");
			expect(responseJSON.state).toEqual("AL");
			expect(responseJSON.city).toEqual(expect.stringMatching(/ALPINE|BROOKSIDE|CARDIFF/));
			expect(responseJSON.postcode).toEqual(expect.stringMatching(/35014|35036|35041/));
			expect(responseJSON.name).toEqual("State Tax");
			expect(responseJSON.priority).toEqual(1);
			expect(responseJSON.compound).toEqual(false);
			expect(responseJSON.shipping).toEqual(false);
			expect(responseJSON.order).toEqual(0);
			expect(responseJSON.rate).toEqual('4.0000');


		});
	});

	test.describe('Retrieve after create', () => {
		test('can retrieve a tax rate', async ({
			request
		}) => {
			// call API to retrieve the previously saved customer
			const response = await request.get(
				`/wp-json/wc/v3/taxes/${taxRateId}`
			);
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(responseJSON.id).toEqual(taxRateId);
			expect(typeof responseJSON.id).toEqual('number');
			// Verify that the tax rate class is 'standard'
			expect(responseJSON.country).toEqual("US");
			expect(responseJSON.state).toEqual("AL");
			expect(responseJSON.city).toEqual(expect.stringMatching(/ALPINE|BROOKSIDE|CARDIFF/));
			expect(responseJSON.postcode).toEqual(expect.stringMatching(/35014|35036|35041/));
			expect(responseJSON.name).toEqual("State Tax");
			expect(responseJSON.priority).toEqual(1);
			expect(responseJSON.compound).toEqual(false);
			expect(responseJSON.shipping).toEqual(false);
			expect(responseJSON.order).toEqual(0);
			expect(responseJSON.rate).toEqual('4.0000');

		});

		test('can retrieve all tax rates', async ({
			request
		}) => {
			// call API to retrieve all customers
			const response = await request.get('/wp-json/wc/v3/taxes');
			const responseJSON = await response.json();
			console.log('response=', responseJSON);
			expect(response.status()).toEqual(200);
			expect(Array.isArray(responseJSON));
			expect(responseJSON.length).toBeGreaterThan(0);
		});
	});

	test.describe('Update a tax rate', () => {

		test(`can update a tax rate`, async ({
			request,
		}) => {
			// update customer names (regular, billing and shipping) from John to Jack
			const response = await request.put(
				`/wp-json/wc/v3/taxes/${ taxRateId }`, {
					data: {
						name: "Not State Tax"
					}
				}
			);
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(responseJSON.id).toEqual(taxRateId);
			expect(responseJSON.name).toEqual("Not State Tax");
			expect(responseJSON.priority).toEqual(1);
			expect(responseJSON.compound).toEqual(false);
			expect(responseJSON.shipping).toEqual(false);
			expect(responseJSON.order).toEqual(0);
			expect(responseJSON.rate).toEqual('4.0000')

		});

		test('retrieve after update customer', async ({
			request
		}) => {
			// call API to retrieve all customers
			const response = await request.get(`/wp-json/wc/v3/taxes/${ taxRateId }`);
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);
			expect(responseJSON.id).toEqual(taxRateId);
			expect(responseJSON.name).toEqual('Not State Tax');

		});
	});

	test.describe('Delete a tax rate', () => {
		test('can permanently delete a tax rate', async ({
			request
		}) => {
			// Delete the customer.
			const response = await request.delete(
				`/wp-json/wc/v3/taxes/${ taxRateId }`, {
					data: {
						force: true,
					},
				}
			);
			expect(response.status()).toEqual(200);

			// Verify that the customer can no longer be retrieved.
			const getDeletedTaxRateResponse = await request.get(
				`/wp-json/wc/v3/taxes/${ taxRateId }`
			);
			expect(getDeletedTaxRateResponse.status()).toEqual(404);
		});
	});


	test.describe('Batch tax rate operations', () => {
		/**
		 * 48 tax rates to be created in one batch.
		 */

		// set payload to use batch create: action
		const batchCreate48TaxRatesPayload = {
			create: allUSTaxesExample
		};

		console.log('before test allUSTaxesExample=', allUSTaxesExample);
		console.log('batchCreate48TaxRatesPayload=', batchCreate48TaxRatesPayload);

		test('can batch create tax rates', async ({
			request
		}) => {
			// Batch create tax rates.
			// call API to batch create tax rates
			const response = await request.post('wp-json/wc/v3/taxes/batch', {
				data: batchCreate48TaxRatesPayload,
			});
			const responseJSON = await response.json();
			expect(response.status()).toEqual(200);

			// Verify that the 48 new tax rates were created
			const actualTaxRates = responseJSON.create;
			console.log('actualTaxRates=', actualTaxRates);
			expect(actualTaxRates).toHaveLength(allUSTaxesExample.length);

			for (let i = 0; i < actualTaxRates.length; i++) {
				const {
					id,
					rate
				} = actualTaxRates[i];

				expect(id).toBeDefined();
				expect(rate).toEqual(allUSTaxesExample[i].rate);

				// Save the tax rate id
				allUSTaxesExample[i].id = id;
			}

			console.log('after test allUSTaxesExample=', allUSTaxesExample);
		});

		test('can batch update tax rates', async ({
			request
		}) => {
			// set payload to use batch update: action
			const batchUpdatePayload = {
				update: [{
						id: allUSTaxesExample[0].id,
						rate: '4.1111',
					},
					{
						id: allUSTaxesExample[1].id,
						order: 49,
						rate: '5.6111'
					},
				],
			};

			// Call API to update the customers
			const response = await request.post(
				'wp-json/wc/v3/taxes/batch', {
					data: batchUpdatePayload,
				}
			);
			const responseJSON = await response.json();

			// Verify the response code and the number of customers that were updated.
			const updatedTaxRates = responseJSON.update;
			console.log('updatedTaxRates=', updatedTaxRates);
			expect(response.status()).toEqual(200);
			expect(updatedTaxRates).toHaveLength(2);

			// Verify that the 1st customer was updated to have a new email address.
			expect(updatedTaxRates[0].id).toEqual(allUSTaxesExample[0].id);
			expect(updatedTaxRates[0].rate).toEqual('4.1111');

			// Verify that the amount of the 2nd customer was updated to have a new billing address.
			expect(updatedTaxRates[1].id).toEqual(allUSTaxesExample[1].id);
			expect(updatedTaxRates[1].order).toEqual(49);
			expect(updatedTaxRates[1].rate).toEqual('5.6111');

			console.log('after batch update allUSTaxesExample=', allUSTaxesExample);
			console.log('allUSTaxesExample[0].id=', allUSTaxesExample[0].id);
			console.log('allUSTaxesExample[1].id=', allUSTaxesExample[1].id);

		});

		test('can batch delete customers', async ({
			request
		}) => {
			// Batch delete the 48 tax rates
			const taxRateIdsToDelete = allUSTaxesExample.map(({
				id
			}) => id);
			const batchDeletePayload = {
				delete: taxRateIdsToDelete,
			};

			//Call API to batch delete the customers
			const response = await request.post(
				'wp-json/wc/v3/taxes/batch', {
					data: batchDeletePayload,
				}
			);
			const responseJSON = await response.json();

			// Verify that the response shows the 48 tax rates.
			const deletedCustomerIds = responseJSON.delete.map(
				({
					id
				}) => id);
			expect(response.status()).toEqual(200);
			expect(deletedCustomerIds).toEqual(taxRateIdsToDelete);

			// Verify that the deleted tax rates cannot be retrieved.
			for (const taxRateId of taxRateIdsToDelete) {
				//Call the API to attempte to retrieve the tax rates
				const response = await request.get(
					`wp-json/wc/v3/customers/${ taxRateId }`
				);
				expect(response.status()).toEqual(404);
			}
		});
	});
});
