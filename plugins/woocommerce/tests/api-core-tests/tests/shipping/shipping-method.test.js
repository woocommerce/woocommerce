/* eslint-disable */
const {	test, expect } = require('@playwright/test');

const {	getShippingMethodExample } = require('../../data');

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

	//loop through each row from the shippingMethods test data table above
	for (const shippingMethodRow of shippingMethods) {

		test(`can add a ${shippingMethodRow[methodTitleIndex]} shipping method`,
			async ({ request }) => {

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

				//if the shipping method is flat_rate OR local_pickup then based on the data, it should have a cost value
				if (['flat_rate', 'local_pickup'].includes(shippingMethodRow[methodIdIndex])) {
					expect(responseJSON.settings.cost.value).toEqual(shippingMethodRow[methodCostIndex]);
				}

				// Cleanup: Delete the shipping method
				const deleteResponse = await request.delete(`/wp-json/wc/v3/shipping/zones/${ shippingZoneId }/methods/${ shippingMethodInstanceId }`, {
					data: {
						force: true
					}
				});
			}
		);
	}
});
