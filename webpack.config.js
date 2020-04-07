const { webpackAlias: coreE2EAlias } = require( '@woocommerce/e2e-env' );

module.exports = {
	resolve: {
		alias: {
			...coreE2EAlias,
		},
	},
};
