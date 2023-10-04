/**
 * External dependencies
 */
import { existsSync } from 'fs';
import path from 'path';
import { pascalCase, snakeCase } from 'change-case';

/**
 * Internal dependencies
 */
import { code, info, success } from './log';
import {
	BlockJSONData,
	OptionValues,
	PackageJSONData,
	PluginConfig,
	PluginData,
	ScriptConfig,
} from './types';
import { initPackageJSON } from './init-package-json';
import { getUniqueItems } from './get-plugin-config';
import { isGitDirectory, updateOrCreateGitIgnore } from './git-tools';
// const initTemplate = require( './init-template' );
// const initComposer = require( './init-composer' );

async function scaffold( {
	$schema,
	name,
	apiVersion,
	textdomain,
	wpScripts,
	wpEnv,
	license,
	author,
	npmDependencies,
	npmDevDependencies,
	customScripts,
	version,
	description,
	includesDir,
	srcDir,
	namespace,
	modules,
}: PluginData &
	Partial< PluginConfig > &
	Omit< OptionValues, 'namespace' > &
	BlockJSONData &
	PackageJSONData ) {
	info( '' );
	info( `Integrating ${ name } with WooCommerce build scripts.` );

	const view: ScriptConfig = {
		$schema,
		name,
		apiVersion,
		textdomain,
		wpScripts,
		wpEnv,
		license,
		author,
		npmDependencies,
		npmDevDependencies,
		customScripts,
		version,
		description,
		includesDir,
		srcDir,
		namespace,
		namespaceSnakeCase: snakeCase( namespace || '' ),
		namespacePascalCase: pascalCase( namespace || '' ),
		modules: getUniqueItems( [ ...( modules || [] ) ] ),
		composerDependencies: [],
		composerDevDependencies: [],
	};

	const usesGit = isGitDirectory();

	if ( ! existsSync( path.join( process.cwd(), 'package.json' ) ) ) {
		await initPackageJSON( view );
		if ( usesGit ) {
			updateOrCreateGitIgnore( 'Node Package Dependencies', [
				'node_modules/',
			] );
		}
	}

	// await initComposer( view );

	info( '' );
	success(
		`Done: WordPress plugin ${ name } integrated with WooCommerce build scripts.`
	);

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

	// if ( typeof onComplete === 'function' ) {
	// 	onComplete();
	// }
}

export { scaffold };
