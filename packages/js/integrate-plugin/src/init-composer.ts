/**
 * External dependencies
 */
import { existsSync, readFileSync } from 'fs';
import { writeFile } from 'fs/promises';
import { join } from 'path';
import { command } from 'execa';

/**
 * Internal dependencies
 */
import { ComposerJSONData, PluginTemplateDefaults } from './types';
import { info } from './log';

export async function initComposer( {
	textdomain,
	composerDependencies,
	composerDevDependencies,
	includesDir,
	namespacePascalCase,
}: ComposerJSONData & PluginTemplateDefaults ) {
	const cwd = join( process.cwd() );

	if ( ! existsSync( join( process.cwd(), 'composer.json' ) ) ) {
		info( '' );
		info( 'Creating a "composer.json" file.' );

		const config = {
			name: `${ textdomain }/${ textdomain }`,
		};

		await writeFile(
			join( cwd, 'composer.json' ),
			JSON.stringify( config, null, 4 )
		);
	}

	// @todo This should probably be offloaded into templates to allow more configuration.
	// Custom composer config with deeply nested merging might be a good option.
	await command(
		`composer config --no-plugins allow-plugins.automattic/jetpack-autoloader true`,
		{
			cwd,
		}
	);

	const composerJson = JSON.parse(
		readFileSync( join( cwd, 'composer.json' ), 'utf8' )
	);

	composerJson.autoload = composerJson.autoload || {};
	composerJson.autoload[ 'psr-4' ] = {
		...( composerJson.autoload.ps4 || {} ),
		[ namespacePascalCase + '\\' ]: includesDir,
	};
	await writeFile(
		join( cwd, 'composer.json' ),
		JSON.stringify( composerJson, null, 4 )
	);

	if ( composerDependencies && composerDependencies.length ) {
		info( '' );
		info( 'Installing composer dependencies...' );
		for ( const packageArg of composerDependencies ) {
			info( '' );
			info( `Installing "${ packageArg }".` );
			await command( `composer require ${ packageArg }`, {
				cwd,
			} );
		}
	}

	if ( composerDevDependencies && composerDevDependencies.length ) {
		info( '' );
		info( 'Installing composer dev dependencies...' );
		for ( const packageArg of composerDevDependencies ) {
			info( '' );
			info( `Installing "${ packageArg }".` );
			await command( `composer require --dev ${ packageArg }`, {
				cwd,
			} );
		}
	}
}
