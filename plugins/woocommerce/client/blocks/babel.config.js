module.exports = {
	env: {
		development: {
			plugins: [
				[
					'react-docgen',
					{ DOC_GEN_COLLECTION_NAME: 'STORYBOOK_REACT_CLASSES' },
				],
				[ '@babel/plugin-syntax-jsx' ],
			],
		},
	},
	presets: [
		[ '@wordpress/babel-preset-default' ],
		[ '@babel/preset-typescript' ],
	],
};
