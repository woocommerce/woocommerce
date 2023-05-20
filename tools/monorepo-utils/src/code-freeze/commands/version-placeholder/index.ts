/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import simpleGit from 'simple-git';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { cloneRepo, generateDiff, getPatches, getFilename, getPatchAdditions } from '../../../core/git';
import { getEnvVar } from '../../../core/environment';
import { validateArgs } from './lib/validate';
import { updateFileLine } from './lib/update';
import { Options } from './types';

const genericErrorFunction = ( err ) => {
	if ( err.git ) {
		return err.git;
	}
	throw err;
};

export const versionPlaceholderCommand = new Command( 'version-placeholder' )
	.description( 'Replace version placeholders in DocBlocks' )
	.requiredOption( '-v, --version <version>', 'Version to replace placeholder with' )
	.requiredOption(
		'-p --previous <previous>',
		'Branch to compare against base for replacement of placeholders'
	)
	.option(
		'-o --owner <owner>',
		'Repository owner. Default: woocommerce',
		'woocommerce'
	)
	.option(
		'-n --name <name>',
		'Repository name. Default: woocommerce',
		'woocommerce'
	)
	.option(
		'-b --base <base>',
		'Base branch to create the PR against. Default: trunk',
		'trunk'
	)
	.action( async ( options: Options ) => {
		const { owner, name, version, base, previous } = options;
		Logger.startTask(
			`Making a temporary clone of '${ owner }/${ name }'`
		);
		const source = `github.com/${ owner }/${ name }`;
		const token = getEnvVar( 'GITHUB_TOKEN', true );
		const remote = `https://${ owner }:${ token }@${ source }`;
		const tmpRepoPath = await cloneRepo( remote );

		Logger.endTask();

		Logger.notice(
			`Temporary clone of '${ owner }/${ name }' created at ${ tmpRepoPath }`
		);

		await validateArgs( version );

		Logger.startTask( `Comparing ${ previous } with ${ base }...` );
		const diff = await generateDiff( tmpRepoPath, previous, base, () => {
			throw new Error( `Unable to generate diff betweeen ${ previous } and ${ base }` );
		}, [] );
		Logger.endTask();

		const git = simpleGit( {
			baseDir: tmpRepoPath,
			config: [ 'core.hooksPath=/dev/null' ],
		} );
		const branch = `prep/replace-version-placeholders-for-${ version }`;
		const exists = await git.raw( 'ls-remote', 'origin', branch );

		if ( exists.trim().length > 0 ) {
			Logger.error(
				`Branch ${ branch } already exists. Run \`git push <remote> --delete ${ branch }\` and rerun this command.`
			);
		}

		await git.checkoutBranch( branch, base ).catch( genericErrorFunction );

		const matchPatches = /^a\/(.+).php/g;
		const placeholderRegex = /^(\s+\*\s+@(?:version|since)\s+)[a-z]+\.[a-z]+\.[a-z]+/im;
		const placeholderChanges = getPatchAdditions( diff, matchPatches ).filter( ( line ) => {
			return line.content.match( placeholderRegex );
		} ).map( ( line ) => {
			return {
				...line,
				content: line.content.replace( placeholderRegex, "$1" + version ),
			};
		} );

		if ( placeholderChanges.length > 0 ) {
			await placeholderChanges.forEach( async ( line ) => {
				Logger.startTask( `Updating line ${ line.lineNumber } of ${ line.filename }` );
				await updateFileLine( tmpRepoPath, line.filename, line.lineNumber, line.content );
				Logger.endTask();
			} );
		} else {
			Logger.notice( 'No placeholders needed replacement.' );
			process.exit( 0 );
		}

		Logger.notice( 'Adding and committing changes' );
		await git.add( '.' ).catch( genericErrorFunction );
		await git
			.commit( `Replace placeholders for ${ version } release` )
			.catch( genericErrorFunction );

		Logger.notice( 'Pushing to Github' );
		await git.push( 'origin', branch ).catch( ( e ) => {
			Logger.error( e );
		} );

	} );
