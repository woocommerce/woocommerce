const e2eBabelConfig = require( '@woocommerce/e2e-environment' ).babelConfig;

module.exports = function( api ) {
	api.cache( true );

	return {
		...e2eBabelConfig,
	};
};
