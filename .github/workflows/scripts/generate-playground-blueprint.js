exports.generateWordpressPlaygroundBlueprint = ( artifactUrl ) => {
	const defaultSchema = {
		$schema: 'https://playground.wordpress.net/blueprint-schema.json',

		landingPage: '/wp-admin/',

		preferredVersions: {
			php: '8.0',
			wp: 'latest',
		},

		phpExtensionBundles: [ 'kitchen-sink' ],

		steps: [
			{
				step: 'installPlugin',
				pluginZipFile: {
					resource: 'url',
					url: artifactUrl,
				},
				options: {
					activate: true,
				},
			},

			{
				step: 'installPlugin',
				pluginZipFile: {
					resource: 'url',
					url: 'https://github.com/woocommerce/wc-smooth-generator/releases/download/1.1.0/wc-smooth-generator.zip',
				},
				options: {
					activate: true,
				},
			},

			{
				step: 'login',
				username: 'admin',
				password: 'password',
			},
		],
		plugins: [],
	};

	return defaultSchema;
};
