/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import simpleGit from 'simple-git';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import {
	sparseCheckoutRepoShallow,
	checkoutRemoteBranch,
} from '../../../core/git';
import { createPullRequest } from '../../../core/github/repo';
import { getEnvVar } from '../../../core/environment';
import { Options } from './types';
import { addHeader, createChangelog } from './lib/prep';

export const acceleratedPrepCommand = new Command( 'accelerated-prep' )
	.description( 'Prep for an accelerated release' )
	.argument( '<version>', 'Version to bump to use for changelog' )
	.argument( '<date>', 'Release date to use in changelog' )
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
	.option(
		'-d --dry-run',
		'Prepare the version bump and log a diff. Do not create a PR or push to branch',
		false
	)
	.option(
		'-c --commit-direct-to-base',
		'Commit directly to the base branch. Do not create a PR just push directly to base branch',
		false
	)
	.action( async ( version, date, options: Options ) => {
		const { owner, name, base, dryRun, commitDirectToBase } = options;

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

		const git = simpleGit( {
			baseDir: tmpRepoPath,
			config: [ 'core.hooksPath=/dev/null' ],
		} );

		const branch = `prep/${ base }-accelerated`;

		try {
			if ( commitDirectToBase ) {
				if ( base === 'trunk' ) {
					Logger.error(
						`The --commit-direct-to-base option cannot be used with the trunk branch as a base. A pull request must be created instead.`
					);
				}
				Logger.notice( `Checking out ${ base }` );
				await checkoutRemoteBranch( tmpRepoPath, base );
			} else {
				const exists = await git.raw( 'ls-remote', 'origin', branch );

				if ( ! dryRun && exists.trim().length > 0 ) {
					Logger.error(
						`Branch ${ branch } already exists. Run \`git push <remote> --delete ${ branch }\` and rerun this command.`
					);
				}

				if ( base !== 'trunk' ) {
					// if the base is not trunk, we need to checkout the base branch first before creating a new branch.
					Logger.notice( `Checking out ${ base }` );
					await checkoutRemoteBranch( tmpRepoPath, base );
				}
				Logger.notice( `Creating new branch ${ branch }` );
				await git.checkoutBranch( branch, base );
			}

			const workingBranch = commitDirectToBase ? base : branch;

			Logger.notice(
				`Adding Woo header to main plugin file and creating changelog.txt on ${ workingBranch } branch`
			);
			addHeader( tmpRepoPath );
			createChangelog( tmpRepoPath, version, date );

			if ( dryRun ) {
				const diff = await git.diffSummary();
				Logger.notice(
					`The prep has been completed in the following files:`
				);
				Logger.warn( diff.files.map( ( f ) => f.file ).join( '\n' ) );
				Logger.notice(
					'Dry run complete. No pull was request created nor was a commit made.'
				);
				return;
			}

			Logger.notice( 'Adding and committing changes' );
			await git.add( '.' );
			await git.commit(
				`Add Woo header to main plugin file and create changelog in ${ base }`
			);

			Logger.notice( `Pushing ${ workingBranch } branch to Github` );
			await git.push( 'origin', workingBranch );

			if ( ! commitDirectToBase ) {
				Logger.startTask( 'Creating a pull request' );

				const pullRequest = await createPullRequest( {
					owner,
					name,
					title: `Add Woo header to main plugin file and create changelog in ${ base }`,
					body: `This PR adds the Woo header to the main plugin file and creates a changelog.txt file in ${ base }.`,
					head: branch,
					base,
				} );
				Logger.notice(
					`Pull request created: ${ pullRequest.html_url }`
				);
				Logger.endTask();
			}
		} catch ( error ) {
			Logger.error( error );
		}
	} );
