const { webpackAlias: coreE2EAlias } = require( '@woocommerce/e2e-environment' );

module.exports = {
	resolve: {
		alias: {
			...coreE2EAlias,
		},
	},
};
