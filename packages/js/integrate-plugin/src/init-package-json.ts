/**
 * External dependencies
 */
import { command } from 'execa';
import npmPackageArg from 'npm-package-arg';
import { join } from 'path';
import writePkg from 'write-pkg';

/**
 * Internal dependencies
 */
import { error, info } from './log';
import { PluginTemplateHeaderFields, PluginTemplateProject } from './types';

async function initPackageJSON( {
	author = '',
	textdomain = '',
	description = '',
	license = '',
	version = '1.0.0',
	wpEnv = false,
	wpScripts = true,
	npmDependencies,
	npmDevDependencies,
	customScripts,
}: PluginTemplateProject & PluginTemplateHeaderFields ) {
	const cwd = join( process.cwd() );

	info( '' );
	info( 'Creating a "package.json" file.' );

	await writePkg(
		cwd,
		Object.fromEntries(
			Object.entries( {
				name: textdomain,
				version,
				description,
				author,
				license,
				main: wpScripts && 'build/index.js',
				scripts: {
					...( wpScripts && {
						build: 'wp-scripts build',
						format: 'wp-scripts format',
						'lint:css': 'wp-scripts lint-style',
						'lint:js': 'wp-scripts lint-js',
						'packages-update': 'wp-scripts packages-update',
						'plugin-zip': 'wp-scripts plugin-zip',
						start: 'wp-scripts start',
					} ),
					...( wpEnv && { env: 'wp-env' } ),
					...customScripts,
				},
			} ).filter( ( [ , value ] ) => !! value )
		)
	);

	/**
	 * Helper to determine if we can install this package.
	 *
	 * @param {string} packageArg The package to install.
	 */
	function checkDependency( packageArg: string ) {
		const { type } = npmPackageArg( packageArg );
		if (
			! [ 'git', 'tag', 'version', 'range', 'remote' ].includes( type )
		) {
			throw new Error(
				`Provided package type "${ type }" is not supported.`
			);
		}
	}

	if ( wpScripts ) {
		if ( npmDependencies && npmDependencies.length ) {
			info( '' );
			info(
				'Installing npm dependencies. It might take a couple of minutes...'
			);
			for ( const packageArg of npmDependencies ) {
				try {
					checkDependency( packageArg );
					info( '' );
					info( `Installing "${ packageArg }".` );
					await command( `npm install ${ packageArg }`, {
						cwd,
					} );
				} catch ( err ) {
					if (
						err instanceof Error &&
						typeof err.message === 'string'
					) {
						info( '' );
						info(
							`Skipping "${ packageArg }" npm dependency. Reason:`
						);
						error( err.message );
					}
				}
			}
		}

		if ( npmDevDependencies && npmDevDependencies.length ) {
			info( '' );
			info(
				'Installing npm devDependencies. It might take a couple of minutes...'
			);
			for ( const packageArg of npmDevDependencies ) {
				try {
					checkDependency( packageArg );
					info( '' );
					info( `Installing "${ packageArg }".` );
					await command( `npm install ${ packageArg } --save-dev`, {
						cwd,
					} );
				} catch ( err ) {
					if ( err instanceof Error && err.message === 'string' ) {
						info( '' );
						info(
							`Skipping "${ packageArg }" npm dev dependency. Reason:`
						);
						error( err.message );
					}
				}
			}
		}
	}
}
export { initPackageJSON };
