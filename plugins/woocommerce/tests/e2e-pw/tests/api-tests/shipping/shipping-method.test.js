/* eslint-disable */
const { test, expect } = require( '../../../fixtures/api-tests-fixtures' );

const {
	getShippingMethodExample
} = require('../../../data');

/**
 * Shipping zone id for "Locations not covered by your other zones".
 */
const shippingZoneId = 0;

/**
 * Data table for shipping methods.
 */
const shippingMethods = [
	['Flat rate', 'flat_rate', '10'],
	['Free shipping', 'free_shipping', undefined],
	['Local pickup', 'local_pickup', '30'],
];
//indexes for referencing the data in the table above
const methodTitleIndex = 0;
const methodIdIndex = 1;
const methodCostIndex = 2;

/**
 * Tests for the WooCommerce Shipping methods API.
 *
 * @group api
 * @group shipping
 *
 */
test.describe('Shipping methods API tests', () => {

	test('cannot create a shipping method', async ({
		request,
	}) => {
		/**
		 * call API to attempt to create a shipping method
		 * This call will not work as we have no ability to create new shipping methods,
		 * only retrieve the existing shipping methods
		 * i.e. Flat rate, Free shipping and Local pickup
		 */
		const response = await request.post(
			'/wp-json/wc/v3/shipping_methods', {
				data: {
					title: "flat_rate",
					description: "Lets you charge a fixed rate for shipping.",
				},
			}
		);
		const responseJSON = await response.json();
		expect(response.status()).toEqual(404);
		expect(responseJSON.code).toEqual('rest_no_route');
		expect(responseJSON.message).toEqual('No route was found matching the URL and request method.');
	});

	test('can retrieve all shipping methods', async ({
		request
	}) => {
		// call API to retrieve all shipping methods
		const response = await request.get('/wp-json/wc/v3/shipping_methods');
		const responseJSON = await response.json();
		expect(response.status()).toEqual(200);
		expect(Array.isArray(responseJSON)).toBe(true);
		expect(responseJSON.length).toBeGreaterThanOrEqual(3);
		expect(responseJSON[0].id).toEqual("flat_rate");
		expect(responseJSON[1].id).toEqual("free_shipping");
		expect(responseJSON[2].id).toEqual("local_pickup");
	});

	test('can retrieve a shipping method', async ({
		request
	}) => {
		// call API to retrieve a shipping method
		const response = await request.get(
			`/wp-json/wc/v3/shipping_methods/local_pickup`
		);
		const responseJSON = await response.json();
		expect(response.status()).toEqual(200);
		expect(Array.isArray(responseJSON)).toBe(false);
		expect(typeof responseJSON.id).toEqual('string');
	});

	test(`cannot update a shipping method`, async ({
		request,
	}) => {
		/**
		 * call API to attempt to update a shipping method
		 * This call will not work as we have no ability to update new shipping methods,
		 * only retrieve the existing shipping methods
		 * i.e. Flat rate, Free shipping and Local pickup
		 */
		const response = await request.put(
			'/wp-json/wc/v3/shipping_methods/local_pickup', {
				data: {
					description: "update local pickup description"
				}
			}
		);
		const responseJSON = await response.json();
		expect(response.status()).toEqual(404);
		expect(responseJSON.code).toEqual('rest_no_route');
		expect(responseJSON.message).toEqual('No route was found matching the URL and request method.');
	});


	test('cannot delete a shipping method', async ({
		request
	}) => {
		/**
		 * call API to attempt to delete a shipping method
		 * This call will not work as we have no ability to delete shipping methods,
		 * only retrieve the existing shipping methods
		 * i.e. Flat rate, Free shipping and Local pickup
		 */
		const response = await request.delete('/wp-json/wc/v3/shipping_methods', {
			data: {
				force: true
			}
		});
		const responseJSON = await response.json();
		expect(response.status()).toEqual(404);
		expect(responseJSON.code).toEqual('rest_no_route');
		expect(responseJSON.message).toEqual('No route was found matching the URL and request method.');
	});


	//loop through each row from the shippingMethods test data table above
	for (const shippingMethodRow of shippingMethods) {

		test(`can add a ${shippingMethodRow[methodTitleIndex]} shipping method`,
			async ({
				request
			}) => {

				//create the shipping method
				const shippingMethod = getShippingMethodExample(shippingMethodRow[methodIdIndex], shippingMethodRow[methodCostIndex]);

				//call the API to create the shipping method on the shipping zone
				const response = await request.post(`/wp-json/wc/v3/shipping/zones/${ shippingZoneId }/methods`, {
					data: shippingMethod
				});
				const responseJSON = await response.json();

				//validate the response
				expect(response.status()).toEqual(200);
				expect(typeof responseJSON.id).toEqual('number');
				expect(responseJSON.method_id).toEqual(shippingMethodRow[methodIdIndex]);
				expect(responseJSON.method_title).toEqual(shippingMethodRow[methodTitleIndex]);
				expect(responseJSON.enabled).toEqual(true);

				const shippingMethodInstanceId = responseJSON.id;

				// if the shipping method is flat_rate OR local_pickup then based on the data, it should have a cost value
				if (['flat_rate', 'local_pickup'].includes(shippingMethodRow[methodIdIndex])) {
					expect(responseJSON.settings.cost.value).toEqual(shippingMethodRow[methodCostIndex]);
				}

				// Cleanup: Remove the shipping method from the shipping zone
				const deleteResponse = await request.delete(`/wp-json/wc/v3/shipping/zones/${ shippingZoneId }/methods/${ shippingMethodInstanceId }`, {
					data: {
						force: true
					}
				});
			}
		);
	}
});
