/**
 * External dependencies
 */
import { existsSync } from 'fs';
import path from 'path';
import { pascalCase } from 'change-case';

/**
 * Internal dependencies
 */
import { code, info, success } from './log';
import {
	ComposerJSONData,
	PluginConfig,
	PluginTemplate,
	PluginTemplateDefaults,
} from './types';
import { initPackageJSON } from './init-package-json';
import { getUniqueItems, updateConfig } from './get-plugin-config';
import { isGitDirectory, updateOrCreateGitIgnore } from './git-tools';
import { initTemplates } from './init-templates';
import { initComposer } from './init-composer';

async function scaffold(
	{
		pluginOutputTemplates,
		includesOutputTemplates,
		srcOutputTemplates,
		modules: templateModules,
		onComplete,
		composerDependencies,
		composerDevDependencies,
	}: PluginTemplate & ComposerJSONData & PluginConfig,
	pluginDefaultValues: PluginTemplateDefaults
) {
	info( '' );
	info(
		`Integrating ${ pluginDefaultValues.name } with WooCommerce build scripts.`
	);

	const usesGit = isGitDirectory();

	if ( pluginDefaultValues.template ) {
		updateConfig( {
			modules: getUniqueItems( [
				...( pluginDefaultValues.modules || [] ),
				...templateModules,
			] ),
		} );
		initTemplates( {
			includesOutputTemplates,
			pluginOutputTemplates,
			srcOutputTemplates,
			defaultValues: pluginDefaultValues,
			variants: {},
		} );
	}

	if ( ! existsSync( path.join( process.cwd(), 'package.json' ) ) ) {
		await initPackageJSON( pluginDefaultValues );
		if ( usesGit ) {
			updateOrCreateGitIgnore( 'Node Package Dependencies', [
				'node_modules/',
			] );
		}
	}

	await initComposer( {
		...pluginDefaultValues,
		namespacePascalCase: pascalCase(
			pluginDefaultValues.namespace || 'create-block'
		),
		composerDependencies,
		composerDevDependencies,
	} );
	if ( usesGit ) {
		updateOrCreateGitIgnore( 'Composer Dependencies', [ 'vendor/' ] );
		updateOrCreateGitIgnore( 'Build files', [ 'build/' ] );
	}

	info( '' );
	success(
		`Done: WordPress plugin ${ pluginDefaultValues.name } integrated with WooCommerce build scripts.`
	);

	// @todo We should check that these were successfully added before showing each of these commands.
	if ( pluginDefaultValues.wpScripts ) {
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
	if ( pluginDefaultValues.wpEnv ) {
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
}

export { scaffold };
