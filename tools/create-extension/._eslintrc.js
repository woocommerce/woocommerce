module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	rules: {
		// temporary conversion to warnings until the below are all handled.
		'jsdoc/check-tag-names': [
			'error',
			{ definedTags: [ 'jest-environment' ] },
		],
	},
};
