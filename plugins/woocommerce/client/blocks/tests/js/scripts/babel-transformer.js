const babelOptions = {
	presets: [ '@babel/preset-typescript', '@wordpress/babel-preset-default' ],
	plugins: [
		'explicit-exports-references',
		'@babel/plugin-transform-class-properties',
	],
};

module.exports =
	require( 'babel-jest' ).default.createTransformer( babelOptions );
