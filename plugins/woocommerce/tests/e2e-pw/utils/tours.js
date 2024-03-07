const { admin } = require( '../test-data/data' );

const base64String = Buffer.from(
	`${ admin.username }:${ admin.password }`
).toString( 'base64' );

const headers = {
	Authorization: `Basic ${ base64String }`,
};

/**
 * Enables or disables the product editor tour.
 *
 * @param {import('@playwright/test').APIRequestContext} request Request context from calling function.
 * @param {boolean} enable Set to `true` if you want to enable the block product tour. `false` if otherwise.
 */
const toggleBlockProductTour = async ( request, enable ) => {
	const url = '/wp-json/wc-admin/options';
	const params = { _locale: 'user' };
	const toggleValue = enable ? 'no' : 'yes';
	const data = { woocommerce_block_product_tour_shown: toggleValue };

	await request.post( url, {
		data,
		params,
		headers,
	} );
};

module.exports = { toggleBlockProductTour };
