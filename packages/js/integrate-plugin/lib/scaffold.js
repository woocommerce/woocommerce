/**
 * External dependencies
 */
const { existsSync } = require( 'fs' );
const { code, info, success } = require( '../node_modules/@wordpress/create-block/lib/log' );
const path = require( 'path' );
const { pascalCase, snakeCase } = require( 'change-case' );

/**
 * Internal dependencies
 */
const initPackageJSON = require( './init-package-json' );
const initWPScripts = require( './init-wp-scripts' );
const initWebpackConfig = require( './init-webpack-config' );
const initWPEnv = require( './init-wp-env' );
const initTemplate = require( './init-template' );
const initComposer = require( './init-composer' );
const { getUniqueItems, updateConfig } = require( './config' );

module.exports = async (
	{
		pluginOutputTemplates,
		includesOutputTemplates,
		srcOutputTemplates,
		modules: templateModules,
		onComplete,
		composerDependencies,
		composerDevDependencies
	},
	{
		$schema,
		apiVersion,
		name,
		textdomain,
		wpScripts,
		wpEnv,
		npmDependencies,
		npmDevDependencies,
		customScripts,
		customPackageJSON,
		customBlockJSON,
		version,
		description,
		template,
		includesDir,
		srcDir,
		namespace,
		modules,
	}
) => {
	info( '' );
	info( `Integrating ${ name } with WooCommerce build scripts.` );

	const view = {
		$schema,
		apiVersion,
		textdomain,
		wpScripts,
		wpEnv,
		npmDependencies,
		npmDevDependencies,
		customScripts,
		customPackageJSON,
		customBlockJSON,
		version,
		description,
		includesDir,
		srcDir,
		includesOutputTemplates,
		srcOutputTemplates,
		pluginOutputTemplates,
		namespace,
		namespaceSnakeCase: snakeCase( namespace ),
		namespacePascalCase: pascalCase( namespace ),
		modules: getUniqueItems( [
			...modules,
			...templateModules,
		] ),
		composerDependencies,
		composerDevDependencies,
	};

	if ( template ) {
		updateConfig( view );
		initTemplate( view );
	}

	if ( ! existsSync( path.join( process.cwd(), 'package.json' ) ) ) {
		await initPackageJSON( view );
	}

	if ( wpScripts ) {
		await initWPScripts( view );
		await initWebpackConfig( view );
	}

	if ( wpEnv ) {
		await initWPEnv( view );
	}

	await initComposer( view );

	info( '' );
	success( `Done: WordPress plugin ${ name } integrated with WooCommerce build scripts.` );

	// @todo We should check that these were successfully added before showing each of these commands.
	if ( wpScripts ) {
		info( '' );
		info( 'You can run several commands inside:' );
		info( '' );
		code( '  $ npm start' );
		info( '    Starts the build for development.' );
		info( '' );
		code( '  $ npm run build' );
		info( '    Builds the code for production.' );
		info( '' );
		code( '  $ npm run format' );
		info( '    Formats files.' );
		info( '' );
		code( '  $ npm run lint:css' );
		info( '    Lints CSS files.' );
		info( '' );
		code( '  $ npm run lint:js' );
		info( '    Lints JavaScript files.' );
		info( '' );
		code( '  $ npm run plugin-zip' );
		info( '    Creates a zip file for a WordPress plugin.' );
		info( '' );
		code( '  $ npm run packages-update' );
		info( '    Updates WordPress packages to the latest version.' );
	}
	if ( wpEnv ) {
		info( '' );
		info( 'You can start WordPress with:' );
		info( '' );
		code( '  $ npx wp-env start' );
	}
	info( '' );
	info( 'Code is Poetry' );

	if ( typeof onComplete === 'function' ) {
		onComplete();
	}
};
