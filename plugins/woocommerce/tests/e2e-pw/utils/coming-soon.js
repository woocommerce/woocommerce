const { request } = require( '@playwright/test' );
const { setOption } = require( './options' );

const setComingSoon = async ( { baseURL, enabled } ) => {
	try {
		await setOption( request, baseURL, 'woocommerce_coming_soon', enabled );
	} catch ( error ) {
		console.log( error );
	}
};

module.exports = {
	setComingSoon,
};
