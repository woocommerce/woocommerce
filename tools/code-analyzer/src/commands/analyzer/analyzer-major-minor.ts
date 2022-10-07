/**
 * External dependencies
 */
import { join } from 'path';
import { readFile } from 'fs/promises';
import simpleGit from 'simple-git';
import { cloneRepo } from 'cli-core/src/git';
import { Logger } from 'cli-core/src/logger';
import { getPluginData } from 'cli-core/src/util';
import { Command } from '@commander-js/extra-typings';

const program = new Command()
	.command( 'major-minor' )
	.argument( '<branch>', 'GitHub branch to use to determine version.' )
	.argument( '<pathToMainFile>', "Path to plugin's main PHP file." )
	.option(
		'-s, --source <source>',
		'Git repo url or local path to a git repo.',
		join( process.cwd(), '../../' )
	)
	.action( async ( branch, pathToMainFile, options ) => {
		const { source } = options;
		try {
			Logger.startTask( `Making a temporary clone of '${ branch }'` );
			const tmpRepoPath = await cloneRepo( source );
			Logger.endTask();

			Logger.startTask( `Getting version from ${ pathToMainFile }` );
			const pluginData = await getPluginData(
				tmpRepoPath,
				pathToMainFile,
				branch
			);
			Logger.endTask();

			if ( pluginData && pluginData.version ) {
				const [ major, minor ] = pluginData.version.split( '.' );
				Logger.notice( `${ major }.${ minor }.0` );
			} else {
				Logger.error( 'Failed to get version' );
			}
		} catch ( e ) {
			if ( e instanceof Error ) {
				Logger.error( e.message );
			}
		}
	} );

program.parse( process.argv );
