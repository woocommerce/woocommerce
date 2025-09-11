const { request } = require( '@playwright/test' );
const { deleteOption } = require( './options' );

const resetGatewayOrder = async ( baseURL ) => {
	try {
		await deleteOption( request, baseURL, 'woocommerce_gateway_order' );
	} catch ( error ) {
		console.log( error );
	}
};

module.exports = {
	resetGatewayOrder,
};
