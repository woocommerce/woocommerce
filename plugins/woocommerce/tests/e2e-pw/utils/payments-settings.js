const { request } = require( '@playwright/test' );
const { setOption, deleteOption } = require( './options' );

const setNewPaymentsSettingsPage = async ( { baseURL, enabled } ) => {
	try {
		await setOption(
			request,
			baseURL,
			'woocommerce_feature_reactify-classic-payments-settings_enabled',
			enabled
		);
	} catch ( error ) {
		console.log( error );
	}
};

const resetGatewayOrder = async ( baseURL ) => {
	try {
		await deleteOption( request, baseURL, 'woocommerce_gateway_order' );
	} catch ( error ) {
		console.log( error );
	}
};

module.exports = {
	setNewPaymentsSettingsPage,
	resetGatewayOrder,
};
