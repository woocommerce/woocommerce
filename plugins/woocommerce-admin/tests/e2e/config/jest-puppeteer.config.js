const { useE2EJestPuppeteerConfig } = require( '@woocommerce/e2e-environment' );

const puppeteerConfig = useE2EJestPuppeteerConfig( {
	launch: {
		browserContext: 'incognito',
		args: [ '--incognito' ],
	},
} );

module.exports = puppeteerConfig;
