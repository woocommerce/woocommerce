/**
 * External dependencies
 */
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const path = require( 'path' );

/**
 * Internal dependencies
 */
const { getAlias } = require( '../bin/webpack-helpers.js' );
const {
	getMainConfig,
	getStylingConfig,
} = require( '../bin/webpack-configs.js' );
const tsConfig = require( '../tsconfig.base.json' );

const aliases = Object.keys( tsConfig.compilerOptions.paths ).reduce(
	( acc, key ) => {
		// Filter out @wordpress/* paths to allow resolution from node_modules instead of build-types directory specified in tsconfig.
		// Filter out paths targeting declaration files (e.g. dinero.js/currencies); they only exist for type resolution and webpack needs the runtime module instead.
		const currentPath = tsConfig.compilerOptions.paths[ key ][ 0 ];
		if (
			! key.startsWith( '@wordpress' ) &&
			! currentPath.endsWith( '.d.ts' )
		) {
			acc[ key.replace( '/*', '' ) ] = path.resolve(
				__dirname,
				'../' + currentPath.replace( '/*', '/' )
			);
		}
		return acc;
	},
	{}
);

module.exports = ( { config: storybookConfig } ) => {
	const wooBlocksConfig = getMainConfig( { alias: getAlias() } );
	const wooStylingConfig = getStylingConfig();
	storybookConfig.resolve.alias = {
		...storybookConfig.resolve.alias,
		...aliases,
		'@woocommerce/block-settings': require.resolve(
			'./__mocks__/woocommerce-block-settings.js'
		),
		'@woocommerce/base-hooks': require.resolve(
			'./__mocks__/woocommerce-base-hooks.js'
		),
		'wordpress-components': require.resolve(
			'../node_modules/wordpress-components'
		),
	};
	storybookConfig.module.rules.push(
		{
			test: /\/stories\/.+\.js$/,
			use: [
				{
					loader: require.resolve( '@storybook/source-loader' ),
					options: { parser: 'typescript' },
				},
			],
			enforce: 'pre',
		},
		...wooBlocksConfig.module.rules,
		...wooStylingConfig.module.rules
	);

	storybookConfig.plugins.push(
		new MiniCssExtractPlugin( {
			filename: `[name].css`,
		} )
	);

	storybookConfig.module.rules = storybookConfig.module.rules.filter(
		( rule ) =>
			! (
				rule.use &&
				typeof rule.use.loader === 'string' &&
				rule.use.loader.indexOf( 'babel-loader' ) >= 0
			)
	);

	return storybookConfig;
};
