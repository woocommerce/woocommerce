/**
 * External dependencies
 */
import { join } from 'path';
import { readFile } from 'fs/promises';
import simpleGit from 'simple-git';
import { cloneRepo } from 'cli-core/src/git';
import { Logger } from 'cli-core/src/logger';
import { Command } from '@commander-js/extra-typings';

/**
 * Get plugin data
 *
 * @param {string} tmpRepoPath    - Path to repo.
 * @param {string} pathToMainFile - Path to plugin's main PHP file.
 * @param {string} hashOrBranch   - Hash or branch to checkout.
 * @return {Promise<string>} - Promise containing version as string.
 */
const getPluginData = async (
	tmpRepoPath: string,
	pathToMainFile: string,
	hashOrBranch: string
): Promise< string | void > => {
	const git = simpleGit( { baseDir: tmpRepoPath } );
	await git.checkout( [ hashOrBranch ] );

	const mainFile = join( tmpRepoPath, pathToMainFile );

	Logger.startTask( `Getting version from ${ pathToMainFile }` );

	const content = await readFile( mainFile, 'utf-8' );
	const rawVer = content.match( /^\s+\*\s+Version:\s+(.*)/m );

	if ( rawVer && rawVer.length > 1 ) {
		const version = rawVer[ 1 ].replace( /\-.*/, '' );

		Logger.endTask();

		const [ major, minor ] = version.split( '.' );

		return `${ major }.${ minor }.0`;
	}

	Logger.error(
		'Failed to find plugin version! Make sure the file contains a version in the format `Version: ...`'
	);
};

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

		Logger.startTask( `Making a temporary clone of '${ branch }'` );
		const tmpRepoPath = await cloneRepo( source );
		Logger.endTask();

		const version = await getPluginData(
			tmpRepoPath,
			pathToMainFile,
			branch
		);

		if ( version ) {
			Logger.notice( version );
		} else {
			Logger.error( 'Failed to get version' );
		}
	} );

program.parse( process.argv );
