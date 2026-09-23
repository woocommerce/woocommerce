const path = require( 'path' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

const isDevelopment = process.env.NODE_ENV !== 'production';

module.exports = [
	{
		entry: {
			'woocommerce-analytics-client': './src/client/index.ts',
		},
		mode: isDevelopment ? 'development' : 'production',
		devtool: isDevelopment ? 'source-map' : false,
		output: {
			path: path.resolve( './build' ),
			filename: '[name].js',
		},
		resolve: {
			extensions: [ '.ts', '.tsx', '.js', '.jsx', '.json' ],
		},
		node: false,
		plugins: [
			new DependencyExtractionWebpackPlugin( {
				requestToExternal( request ) {
					if ( request === 'jetpackConfig' ) {
						return null;
					}
				},
			} ),
		],
		module: {
			strictExportPresence: true,
			rules: [
				{
					test: /\.[jt]sx?$/,
					exclude: /node_modules/,
					use: {
						loader: require.resolve( 'babel-loader' ),
						options: {
							presets: [
								[
									require.resolve( '@babel/preset-env' ),
									{
										targets: {
											browsers: [
												'extends @wordpress/browserslist-config',
											],
										},
									},
								],
								require.resolve( '@babel/preset-typescript' ),
							],
						},
					},
				},
				{
					test: /\.css$/,
					use: [ 'style-loader', 'css-loader' ],
				},
				{
					test: /\.(png|jpe?g|gif|svg|woff2?|eot|ttf|otf)$/,
					type: 'asset/resource',
				},
			],
		},
		externals: {
			jetpackConfig: JSON.stringify( {
				consumer_slug: 'woocommerce-analytics',
			} ),
		},
	},
];
