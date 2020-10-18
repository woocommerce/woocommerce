const { e2eBabelConfig } = require( '@woocommerce/e2e-environment' );

module.exports = function( api ) {
	api.cache( true );

	return {
		...e2eBabelConfig,
	};
};
