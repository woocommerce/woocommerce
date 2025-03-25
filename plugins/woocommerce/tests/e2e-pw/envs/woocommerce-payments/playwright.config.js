let config = require( '../../playwright.config.js' );
const { tags } = require( '../../fixtures/fixtures' );

process.env.USE_WP_ENV = 'true';

config = {
	...config.default,
	projects: [
		...config.setupProjects,
		{
			name: 'WooPayments',
			grep: new RegExp( tags.PAYMENTS ),
			dependencies: [ 'site setup' ],
		},
	],
};

module.exports = config;
