let config = require( '../../playwright.config.js' );

process.env.USE_WP_ENV = 'true';

config = {
	...config,
	projects: [
		{
			name: 'Gutenberg',
			testIgnore:
				/.*smoke-tests\/*|.*js-file-monitor\/*|.*admin-tasks\/*|.*activate-and-setup\/*|.*admin-analytics\/*|.*admin-marketing\/*|.*basic\/*|.*account-\/*|.*settings-\/*|.*users-\/*|.*order\/*|.*page-loads\/*/,
		},
	],
};

module.exports = config;
