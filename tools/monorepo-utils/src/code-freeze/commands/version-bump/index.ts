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
import { getMajorMinor } from '../../../core/version';
import { bumpFiles } from './bump';
import { validateArgs, getIsAccelRelease } from './lib/validate';
import { Options } from './types';

export const versionBumpCommand = new Command( 'version-bump' )
	.description( 'Bump versions ahead of new development cycle' )
	.argument( '<version>', 'Version to bump to' )
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
	.option(
		'-f --force',
		'Force a version bump, even when the new version is less than the existing version',
		false
	)
	.option(
		'-a --allow-accel',
		'Allow accelerated versioning. When this option is not present, versions must be semantically correct',
		false
	)
	.action( async ( version, options: Options ) => {
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

		const majorMinor = getIsAccelRelease( version )
			? version
			: getMajorMinor( version );
		const branch = `prep/${ base }-for-next-dev-cycle-${ majorMinor }`;

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
				if ( base !== 'trunk' ) {
					// if the base is not trunk, we need to checkout the base branch first before creating a new branch.
					Logger.notice( `Checking out ${ base }` );
					await checkoutRemoteBranch( tmpRepoPath, base );
				}
				Logger.notice( `Creating new branch ${ branch }` );
				await git.checkoutBranch( branch, base );
			}

			Logger.notice( 'Validating arguments' );
			await validateArgs( tmpRepoPath, version, options );

			const workingBranch = commitDirectToBase ? base : branch;

			Logger.notice(
				`Bumping versions in ${ owner }/${ name } on ${ workingBranch } branch`
			);
			await bumpFiles( tmpRepoPath, version );

			if ( dryRun ) {
				const diff = await git.diffSummary();
				Logger.notice(
					`The version has been bumped to ${ version } in the following files:`
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
				`Prep ${ base } for ${ majorMinor } cycle with version bump to ${ version }`
			);

			Logger.notice( `Pushing ${ workingBranch } branch to Github` );
			// if commit is direct to base, we push normally else we push with force
			if ( commitDirectToBase ) {
				await git.push( 'origin', workingBranch );
			} else {
				await git.push( 'origin', workingBranch, [ '--force' ] );
			}

			if ( ! commitDirectToBase ) {
				Logger.startTask( 'Creating a pull request' );

				const pullRequest = await createPullRequest( {
					owner,
					name,
					title: `Prep ${ base } for ${ majorMinor } cycle`,
					body: `This PR updates the versions in ${ base } to ${ version }.`,
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
