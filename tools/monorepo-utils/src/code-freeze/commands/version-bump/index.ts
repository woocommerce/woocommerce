/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import simpleGit from 'simple-git';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { sparseCheckoutRepo } from '../../../core/git';
import { octokitWithAuth } from '../../../core/github/api';
import { getEnvVar } from '../../../core/environment';
import { getMajorMinor } from '../../../core/version';
import { bumpFiles } from './bump';
import { validateArgs } from './lib/validate';
import { Options } from './types';

const genericErrorFunction = ( err ) => {
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
	.option(
		'-b --base <base>',
		'Base branch to create the PR against. Default: trunk',
		'trunk'
	)
	.action( async ( options: Options ) => {
		const { owner, name, version, base } = options;
		Logger.startTask(
			`Making a temporary clone of '${ owner }/${ name }'`
		);
		const source = `github.com/${ owner }/${ name }`;
		const token = getEnvVar( 'GITHUB_TOKEN' );
		const remote = `https://${ owner }:${ token }@${ source }`;
		const tmpRepoPath = await sparseCheckoutRepo( remote, 'woocommerce', [
			'plugins/woocommerce',
		] );
		Logger.endTask();

		Logger.notice(
			`Temporary clone of '${ owner }/${ name }' created at ${ tmpRepoPath }`
		);

		await validateArgs( tmpRepoPath, version );

		const git = simpleGit( {
			baseDir: tmpRepoPath,
			config: [ 'core.hooksPath=/dev/null' ],
		} );
		const majorMinor = getMajorMinor( version );
		const branch = `prep/trunk-for-next-dev-cycle-${ majorMinor }`;
		await git.checkoutBranch( branch, base ).catch( genericErrorFunction );

		Logger.startTask( `Bumping versions in ${ owner }/${ name }` );
		bumpFiles( tmpRepoPath, version );
		Logger.endTask();

		Logger.startTask( 'Adding and committing changes' );
		await git.add( '.' ).catch( genericErrorFunction );
		await git
			.commit( `Prep trunk for ${ majorMinor } cycle` )
			.catch( genericErrorFunction );
		Logger.endTask();

		Logger.startTask( 'Pushing to Github' );
		await git.push( 'origin', branch ).catch( ( e ) => {
			Logger.warn( e );
			Logger.error(
				`\nBranch ${ branch } already exists. Run \`git push origin --delete ${ branch }\` and rerun this command.`
			);
		} );
		Logger.endTask();

		try {
			Logger.startTask( 'Creating a pull request' );
			const pr = await octokitWithAuth.request(
				'POST /repos/{owner}/{repo}/pulls',
				{
					owner,
					repo: name,
					title: `Prep trunk for ${ majorMinor } cycle`,
					body: `This PR updates the versions in trunk to ${ version } for next development cycle.`,
					head: branch,
					base,
				}
			);
			Logger.notice( `Pull request created: ${ pr.data.html_url }` );
			Logger.endTask();
		} catch ( e ) {
			Logger.error( e );
		}
	} );
