const babelOptions = {
	presets: [ '@babel/preset-typescript', '@wordpress/babel-preset-default' ],
	plugins: [],
};

module.exports =
	require( 'babel-jest' ).default.createTransformer( babelOptions );
