/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { join } from 'path';
import simpleGit from 'simple-git';
import { readFile, writeFile } from 'fs/promises';

/**
 * Internal dependencies
 */
import { Logger } from '../../../core/logger';
import { cloneRepo } from '../../../core/git';
import { octokitWithAuth } from '../../../core/github/api';

const errFn = ( err ) => {
	if ( err.git ) {
		return err.git;
	} // the unsuccessful mergeSummary
	throw err; // some other error, so throw
};

export const versionBumpCommand = new Command( 'version-bump' )
	.description( 'Bump versions ahead of new development cycle' )
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
	.action( async ( { owner, name } ) => {
		const source = `github.com/${ owner }/${ name }`;
		const remote = `https://${ owner }:${ process.env.GITHUB_TOKEN }@${ source }`;

		Logger.startTask(
			`Making a temporary clone of '${ owner }/${ name }'`
		);
		const tmpRepoPath = await cloneRepo( remote );
		Logger.endTask();

		Logger.notice(
			`Temporary clone of '${ owner }/${ name }' created at ${ tmpRepoPath }`
		);

		const git = simpleGit( {
			baseDir: tmpRepoPath,
			config: [ 'core.hooksPath=/dev/null' ],
		} );
		const branch = 'prep/trunk-for-next-dev-cycle-XX.XX';
		const base = 'trunk';
		await git.checkoutBranch( branch, base );

		const filePath = join(
			tmpRepoPath,
			`plugins/woocommerce/woocommerce.php`
		);

		try {
			const pluginFileContents = await readFile( filePath, 'utf8' );

			const updatedPluginFileContents = pluginFileContents.replace(
				/Version: \d+\.\d+\.\d+.*\n/m,
				`Version: XX.XX\n`
			);
			await writeFile( filePath, updatedPluginFileContents );
		} catch ( e ) {
			Logger.error( 'Unable to update plugin file.' );
		}

		console.log( 'Adding and committing changes' );
		await git.add( filePath ).catch( errFn );
		console.log( 'Committing changes' );
		await git.commit( 'Prep trunk for XX.XX cycle' ).catch( errFn );
		console.log( 'authenticating' );
		await git.env( 'GITHUB_TOKEN', process.env.GITHUB_TOKEN );
		console.log( 'Pushing changes' );
		await git
			.push( 'origin', 'prep/trunk-for-next-dev-cycle-XX.XX' )
			.catch( errFn );

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
		console.log( pr );
	} );
