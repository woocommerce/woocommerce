let config = require( '../../playwright.config.js' );
const { tags } = require( '../../fixtures/fixtures' );

process.env.USE_WP_ENV = 'true';
process.env.DISABLE_HPOS = '1';

config = {
	...config.default,
	projects: [
		...config.setupProjects,
		{
			name: 'e2e-hpos-disabled',
			grep: new RegExp( tags.HPOS ),
			dependencies: [ 'site setup' ],
		},
		{
			name: 'api-hpos-disabled',
			testMatch: [ '**/api-tests/**' ],
			dependencies: [ 'site setup' ],
		},
	],
};

module.exports = config;
