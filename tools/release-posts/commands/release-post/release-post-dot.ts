/**
 * External dependencies
 */
import { scanChangesForDB } from 'code-analyzer/src/lib/scan-changes';
import semver from 'semver';
import { writeFile } from 'fs/promises';
import { tmpdir } from 'os';
import { join } from 'path';
import {
	cloneRepo,
	getCommitHash,
} from '@woocommerce/monorepo-utils/src/core/git';
import { Logger } from '@woocommerce/monorepo-utils/src/core/logger';
import { getEnvVar } from '@woocommerce/monorepo-utils/src/core/environment';
import { Command } from '@commander-js/extra-typings';
import dotenv from 'dotenv';

/**
 * Internal dependencies
 */
import { renderTemplate } from '../../lib/render-template';
import { createWpComDraftPost } from '../../lib/draft-post';
import { getWordpressComAuthToken } from '../../lib/oauth-helper';

const DEVELOPER_WOOCOMMERCE_SITE_ID = '96396764';

const SOURCE_REPO = 'https://github.com/woocommerce/woocommerce.git';

const VERSION_VALIDATION_REGEX = /^([0-9]+)\.([0-9]+)\.([0-9]+)$/;

const getDefaultReleaseDate = () =>
	new Date().toLocaleDateString( 'en-US', {
		month: '2-digit',
		day: '2-digit',
		year: 'numeric',
	} );

dotenv.config();

// Define the dot release post command
const program = new Command()
	.command( 'dot' )
	.description( 'CLI to automate generation of a dot release post.' )
	.argument(
		'<currentVersion>',
		'The current dot release version in x.y.z format. Ex: 7.1.1'
	)
	.argument(
		'<previousVersion>',
		'The previous version in x.y.z format. Ex: 7.1.0'
	)
	.option( '--outputOnly', 'Only output the post, do not publish it' )
	.option(
		'--releaseDate <date>',
		'The release date as mm-dd-yyyy, defaults to today.',
		getDefaultReleaseDate()
	)
	.option( '--securityUpdate', 'Marks the release as a security update' )
	.option(
		'--tags <tags>',
		'Comma separated list of tags to add to the post.',
		'Releases,WooCommerce Core'
	)
	.option(
		'--siteId <siteId>',
		'For posting to a non-default site (for testing)'
	)
	.action( async ( currentVersion, previousVersion, options ) => {
		const {
			outputOnly,
			releaseDate,
			securityUpdate,
			siteId = DEVELOPER_WOOCOMMERCE_SITE_ID,
			tags,
		} = options;
		const postTags = ( tags &&
			tags.split( ',' ).map( ( tag ) => tag.trim() ) ) || [
			'WooCommerce Core',
			'Releases',
		];
		const isOutputOnly = !! outputOnly;
		const releaseDateValue = new Date( releaseDate );

		if ( ! VERSION_VALIDATION_REGEX.test( currentVersion ) ) {
			throw new Error(
				`Invalid current version: ${ currentVersion }. Provide current version in x.y.z format.`
			);
		}

		if ( ! VERSION_VALIDATION_REGEX.test( previousVersion ) ) {
			throw new Error(
				`Invalid previous version: ${ previousVersion }. Provide previous version in x.y.z format.`
			);
		}

		if ( Number.isNaN( releaseDateValue.valueOf() ) ) {
			throw new Error(
				`Invalid release date: ${ releaseDate }. Provide release date as mm-dd-yyyy.`
			);
		}

		const currentParsed = semver.parse( currentVersion );
		const previousParsed = semver.parse( previousVersion );

		if ( ! currentParsed ) {
			throw new Error( 'Unable to parse current version' );
		}

		if ( ! previousParsed ) {
			throw new Error( 'Unable to parse previous version' );
		}

		if ( currentParsed.patch < 1 ) {
			throw new Error(
				`Invalid current version: ${ currentVersion }. Dot releases must have a patch version greater than 0.`
			);
		}

		let authToken = '';

		if ( ! isOutputOnly ) {
			const clientId = getEnvVar( 'WPCOM_OAUTH_CLIENT_ID', true );
			const clientSecret = getEnvVar( 'WPCOM_OAUTH_CLIENT_SECRET', true );
			const redirectUri =
				getEnvVar( 'WPCOM_OAUTH_REDIRECT_URI' ) ||
				'http://localhost:3000/oauth';

			authToken = await getWordpressComAuthToken(
				clientId,
				clientSecret,
				siteId,
				redirectUri,
				'posts'
			);

			if ( ! authToken ) {
				throw new Error(
					'Error getting auth token, check your env settings are correct.'
				);
			}
		}

		Logger.startTask( `Making temporary clone of ${ SOURCE_REPO }...` );
		const tmpRepoPath = await cloneRepo( SOURCE_REPO );
		Logger.endTask();

		const currentBranch = `release/${ currentParsed.major }.${ currentParsed.minor }`;
		const currentVersionRef = await getCommitHash(
			tmpRepoPath,
			`remotes/origin/${ currentBranch }`
		);

		Logger.notice(
			`Using ${ currentBranch }(${ currentVersionRef }) for current and ${ previousVersion } for previous.`
		);

		const changes = {
			db: await scanChangesForDB(
				currentVersionRef,
				previousVersion,
				SOURCE_REPO,
				tmpRepoPath
			),
		};

		const title = `WooCommerce ${ currentVersion } released`;
		const releaseBranch = `${ currentParsed.major }.${ currentParsed.minor }`;
		const html = await renderTemplate( 'dot-release.ejs', {
			title,
			changes,
			displayVersion: currentVersion,
			releaseBranch,
			releaseDate: releaseDateValue,
			securityUpdate: !! securityUpdate,
		} );

		if ( isOutputOnly ) {
			const tmpFile = join(
				tmpdir(),
				`dot-release-${ currentVersion }.html`
			);

			await writeFile( tmpFile, html );

			Logger.notice( `Output written to ${ tmpFile }` );
		} else {
			Logger.startTask( 'Publishing draft dot release post' );

			try {
				const { URL } = await createWpComDraftPost(
					siteId,
					title,
					html,
					postTags,
					authToken
				);

				Logger.notice( `Published draft dot release post at ${ URL }` );
				Logger.endTask();
			} catch ( error: unknown ) {
				if ( error instanceof Error ) {
					Logger.error( error.message );
				}
			}
		}
	} );

program.parse( process.argv );
