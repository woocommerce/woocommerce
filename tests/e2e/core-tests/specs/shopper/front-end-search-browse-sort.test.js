/* eslint-disable jest/no-export, jest/no-disabled-tests, jest/expect-expect */
/**
 * Internal dependencies
 */
const {
	shopper,
	merchant
} = require( '@woocommerce/e2e-utils' );

/**
 * External dependencies
 */
const {
	it,
	describe,
	beforeAll,
} = require( '@jest/globals' );

const runSearchBrowseSortTest = () => {
	describe('Search, browse by categories and sort items in the shop', () => {
		beforeAll(async () => {
			// test
		});

		it('should let user search the store', async () => {
			// test
		});

		it('should let user browse products by categories', async () => {
			// test
		});

		it('should let user sort the products in the shop', async () => {
			// test
		});
	});
};

module.exports = runSearchBrowseSortTest;
