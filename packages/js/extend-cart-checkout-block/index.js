module.exports = {
	templatesPath: __dirname,
	defaultValues: {
		customScripts: {
			postinstall:
				'npm i --D "prettier@npm:wp-prettier@latest" && npm i --D eslint-plugin-prettier@latest',
		},
		npmDevDependencies: [
			'@woocommerce/dependency-extraction-webpack-plugin',
			'@woocommerce/eslint-plugin',
			'@wordpress/prettier-config@2.18.2',
			'@wordpress/scripts',
		],
	},
};
