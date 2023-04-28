/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import simpleGit from 'simple-git';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { cloneRepo } from '../../../core/git';
import { octokitWithAuth } from '../../../core/github/api';
import { getEnvVar } from '../../../core/environment';
import { bumpFiles } from './bump';
import { validateArgs } from './lib/validate';

const errFn = ( err ) => {
	if ( err.git ) {
		return err.git;
	}
	throw err;
};

export const versionBumpCommand = new Command( 'version-bump' )
	.description( 'Bump versions ahead of new development cycle' )
	.requiredOption( '-v, --version <version>', 'Version to bump to' )
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
	.action( async ( { owner, name, version } ) => {
		Logger.startTask(
			`Making a temporary clone of '${ owner }/${ name }'`
		);
		const source = `github.com/${ owner }/${ name }`;
		const token = getEnvVar( 'GITHUB_TOKEN' );
		const remote = `https://${ owner }:${ token }@${ source }`;
		const tmpRepoPath = await cloneRepo( remote );
		Logger.endTask();

		Logger.notice(
			`Temporary clone of '${ owner }/${ name }' created at ${ tmpRepoPath }`
		);

		await validateArgs( tmpRepoPath, version );

		const git = simpleGit( {
			baseDir: tmpRepoPath,
			config: [ 'core.hooksPath=/dev/null' ],
		} );
		const branch = 'prep/trunk-for-next-dev-cycle-XX.XX';
		const base = 'trunk';
		await git.checkoutBranch( branch, base ).catch( errFn );

		Logger.startTask( `Bumping versions in ${ owner }/${ name }` );
		bumpFiles( tmpRepoPath, version );
		Logger.endTask();

		Logger.startTask( 'Adding and committing changes' );
		await git.add( '.' ).catch( errFn );
		await git.commit( 'Prep trunk for XX.XX cycle' ).catch( errFn );
		Logger.endTask();

		Logger.startTask( 'Pushing to Github' );
		await git
			.push( 'origin', 'prep/trunk-for-next-dev-cycle-XX.XX' )
			.catch( errFn );
		Logger.endTask();

		try {
			Logger.startTask( 'Creating a pull request' );
			const pr = await octokitWithAuth.request(
				'POST /repos/{owner}/{repo}/pulls',
				{
					owner,
					repo: name,
					title: 'Amazing new feature',
					body: 'Please pull these awesome changes in!',
					head: branch,
					base,
				}
			);
			Logger.notice( `Pull request created: ${ pr.data.html_url }` );
			Logger.endTask();
		} catch ( e ) {
			throw e;
		}
	} );
