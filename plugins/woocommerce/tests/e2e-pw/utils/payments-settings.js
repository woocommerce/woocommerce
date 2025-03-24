const { request } = require( '@playwright/test' );
const { setOption } = require( './options' );

const setNewPaymentsSettingsPage = async ( { baseURL, enabled } ) => {
	try {
		await setOption(
			request,
			baseURL,
			'woocommerce_feature_reactify-classic-payments-settings',
			enabled
		);
	} catch ( error ) {
		console.log( error );
	}
};

const resetGatewayOrder = async ( baseURL ) => {
	try {
		await setOption(
			request,
			baseURL,
			'woocommerce_feature_reactify-classic-payments-settings',
			''
		);
	} catch ( error ) {
		console.log( error );
	}
};

module.exports = {
	setNewPaymentsSettingsPage,
	resetGatewayOrder,
};
