/**
 * External dependencies
 */
const fetch = require( 'node-fetch' );

async function getLatestWooVersion() {
	const api =
		'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=woocommerce';

	const response = await fetch( api );
	const json = await response.json();

	return json.version;
}

module.exports = ( async function () {
	const latestWooVersion = await getLatestWooVersion();

	return {
		templatesPath: __dirname,
		defaultValues: {
			npmDependencies: [
                '@wordpress/hooks',
                '@wordpress/i18n',
                '@woocommerce/components',
            ],
			npmDevDependencies: [
				'@woocommerce/dependency-extraction-webpack-plugin',
				'@woocommerce/eslint-plugin',
				'@wordpress/prettier-config',
				'@wordpress/scripts@24.6.0',
			],
			namespace: 'extension',
			license: 'GPL-3.0+',
			attributes: {
				latestWooVersion,
			},
		},
		customScripts: {
			postinstall: 'composer install',
		},
	};
} )();