/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import simpleGit from 'simple-git';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { sparseCheckoutRepoShallow } from '../../../core/git';
import { createPullRequest } from '../../../core/github/repo';
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
		const token = getEnvVar( 'GITHUB_TOKEN', true );
		const remote = `https://${ owner }:${ token }@${ source }`;
		const tmpRepoPath = await sparseCheckoutRepoShallow(
			remote,
			'woocommerce',
			[
				'plugins/woocommerce/includes/class-woocommerce.php',
				// All that's needed is the line above, but including these here for completeness.
				'plugins/woocommerce/composer.json',
				'plugins/woocommerce/package.json',
				'plugins/woocommerce/readme.txt',
				'plugins/woocommerce/woocommerce.php',
			]
		);
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
		const exists = await git.raw( 'ls-remote', 'origin', branch );

		if ( exists.trim().length > 0 ) {
			Logger.error(
				`Branch ${ branch } already exists. Run \`git push <remote> --delete ${ branch }\` and rerun this command.`
			);
		}

		await git.checkoutBranch( branch, base ).catch( genericErrorFunction );

		Logger.notice( `Bumping versions in ${ owner }/${ name }` );
		bumpFiles( tmpRepoPath, version );

		Logger.notice( 'Adding and committing changes' );
		await git.add( '.' ).catch( genericErrorFunction );
		await git
			.commit( `Prep trunk for ${ majorMinor } cycle` )
			.catch( genericErrorFunction );

		Logger.notice( 'Pushing to Github' );
		await git.push( 'origin', branch ).catch( ( e ) => {
			Logger.error( e );
		} );

		try {
			Logger.startTask( 'Creating a pull request' );

			const pullRequest = await createPullRequest( {
				owner,
				name,
				title: `Prep trunk for ${ majorMinor } cycle`,
				body: `This PR updates the versions in trunk to ${ version } for next development cycle.`,
				head: branch,
				base,
			} );
			Logger.notice( `Pull request created: ${ pullRequest.html_url }` );
			Logger.endTask();
		} catch ( e ) {
			Logger.error( e );
		}
	} );
