const e2eBabelConfig = require( '@woocommerce/e2e-env' ).babelConfig;

module.exports = function( api ) {
	api.cache( true );

	return {
		...e2eBabelConfig,
	};
};
