import config from '../../eslint.config.mjs';

export default [
	...config,
	{
		rules: {
			'@wordpress/i18n-text-domain': [
				'error',
				{ allowedTextDomain: 'purple' },
			],
		},
	},
];
