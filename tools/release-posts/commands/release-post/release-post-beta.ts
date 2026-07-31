/**
 * External dependencies
 */
import semver from 'semver';
import { writeFile } from 'fs/promises';
import { tmpdir } from 'os';
import { join } from 'path';
import { Logger } from '@woocommerce/monorepo-utils/src/core/logger';
import { getEnvVar } from '@woocommerce/monorepo-utils/src/core/environment';
import { Command } from '@commander-js/extra-typings';
import dotenv from 'dotenv';

/**
 * Internal dependencies
 */
import { renderTemplate } from '../../lib/render-template';
import { getWordpressComAuthToken } from '../../lib/oauth-helper';
import { getSecondTuesdayOfTheMonth } from '../../lib/dates';
import { createWpComDraftPost } from '../../lib/draft-post';

const DEVELOPER_WOOCOMMERCE_SITE_ID = '96396764';

dotenv.config();

// Define the release post command
const program = new Command()
	.command( 'beta' )
	.description( 'CLI to automate generation of a draft beta release post.' )
	.argument(
		'<releaseVersion>',
		'The version for this post in x.y.z-beta.n format. Ex: 7.1.0-beta.1'
	)
	.option(
		'--releaseDate <date>',
		'The date for the final release as mm-dd-yyyy, year inferred as current year, defaults to second tuesday of next month.',
		getSecondTuesdayOfTheMonth(
			new Date().getMonth() + 1
		).toLocaleDateString( 'en-US', {
			month: '2-digit',
			day: '2-digit',
			year: 'numeric',
		} )
	)
	.option(
		'--outputOnly',
		'Only output the post as HTML, do not publish a draft.'
	)
	.option(
		'--tags <tags>',
		'Comma separated list of tags to add to the post.',
		'Releases,WooCommerce Core'
	)
	.option(
		'--siteId <siteId>',
		'For posting to a non-default site (for testing)'
	)
	.action( async ( releaseVersion, options ) => {
		const {
			outputOnly,
			siteId = DEVELOPER_WOOCOMMERCE_SITE_ID,
			tags,
			releaseDate,
		} = options;

		const postTags = ( tags &&
			tags.split( ',' ).map( ( tag ) => tag.trim() ) ) || [
			'WooCommerce Core',
			'Releases',
		];

		const finalReleaseDate = new Date( releaseDate );
		const isOutputOnly = !! outputOnly;
		const semverVersion = semver.parse( releaseVersion );

		if (
			! semverVersion ||
			semverVersion.prerelease[ 0 ] !== 'beta' ||
			typeof semverVersion.prerelease[ 1 ] !== 'number'
		) {
			throw new Error(
				`Invalid current version: ${ releaseVersion }. Provide current version in x.y.z-beta.n format.`
			);
		}

		if ( Number.isNaN( finalReleaseDate.valueOf() ) ) {
			throw new Error(
				`Invalid release date: ${ releaseDate }. Provide release date as mm-dd-yyyy.`
			);
		}

		const prereleaseVersion = semverVersion.prerelease[ 1 ];
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

		const html = await renderTemplate( 'beta-release.ejs', {
			version: semverVersion,
			finalReleaseDate,
		} );

		if ( isOutputOnly ) {
			const tmpFile = join(
				tmpdir(),
				`beta-release-${ releaseVersion }.html`
			);

			await writeFile( tmpFile, html );

			Logger.notice( `Output written to ${ tmpFile }` );
		} else {
			Logger.startTask( 'Publishing draft release post' );
			const { ID } = await createWpComDraftPost(
				siteId,
				`WooCommerce ${ semverVersion.major }.${ semverVersion.minor } Beta ${ prereleaseVersion } Released`,
				html,
				postTags,
				authToken
			);
			Logger.notice(
				`Release post created, edit it here: \nhttps://wordpress.com/post/developer.woocommerce.com/${ ID }`
			);
			Logger.endTask();
		}
	} );

program.parse( process.argv );
