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
// @ts-expect-error - The enquirer types are incorrect.
// eslint-disable-next-line @woocommerce/dependency-group
import { Select } from 'enquirer';

/**
 * Internal dependencies
 */
import { renderTemplate } from '../../lib/render-template';
import { getWordpressComAuthToken } from '../../lib/oauth-helper';
import { getMostRecentFinal } from '../../lib/github-api';
import {
	getFirstTuesdayOfTheMonth,
	getSecondTuesdayOfTheMonth,
} from '../../lib/dates';
import {
	createWpComDraftPost,
	searchForPostsByCategory,
} from '../../lib/draft-post';

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

		// This is supposed to be a beta post so throw if the version provided is not a beta version.
		// Things we don't accept:
		//    * missing beta.x
		//    * any other kind of prerelease, e.g. rc
		//    * .x must be a number, so not: beta.1b or beta.1.1 but beta.1 is ok.
		if (
			! semverVersion ||
			! semverVersion.prerelease.length ||
			typeof semverVersion.prerelease[ 1 ] === 'string' ||
			semverVersion.prerelease[ 0 ] !== 'beta'
		) {
			throw new Error(
				`Invalid current version: ${ releaseVersion }. Provide current version in x.y.z-beta.n format.`
			);
		} else {
			const [ , prereleaseVersion ] = semverVersion.prerelease;

			// Now infer the previous version, if the one you provide is beta.1 we'll need to find the last major release from
			// Github releases. If what you provided is beta.2 we'll assume previous was beta.1
			const previousVersion =
				prereleaseVersion === 1
					? ( await getMostRecentFinal() ).tag_name
					: `${ semverVersion.major }.${ semverVersion.minor }.${
							semverVersion.patch
					  }-beta.${ prereleaseVersion - 1 }`;

			const semverPreviousVersion = semver.parse( previousVersion );

			if ( ! semverPreviousVersion ) {
				throw new Error(
					`Could not parse previous version from: ${ previousVersion }`
				);
			}

			const clientId = getEnvVar( 'WPCOM_OAUTH_CLIENT_ID', true );
			const clientSecret = getEnvVar( 'WPCOM_OAUTH_CLIENT_SECRET', true );
			const redirectUri =
				getEnvVar( 'WPCOM_OAUTH_REDIRECT_URI' ) ||
				'http://localhost:3000/oauth';

			Logger.startTask(
				'Getting auth token for WordPress.com (needed to find last beta post).'
			);
			const authToken = await getWordpressComAuthToken(
				clientId,
				clientSecret,
				siteId,
				redirectUri,
				'posts'
			);
			Logger.endTask();

			const versionSearch =
				prereleaseVersion === 1
					? `WooCommerce ${ semverPreviousVersion.major }.${ semverPreviousVersion.minor }.${ semverPreviousVersion.patch }`
					: `WooCommerce ${ semverPreviousVersion.major }.${ semverPreviousVersion.minor } Beta ${ semverPreviousVersion.prerelease[ 1 ] }`;

			Logger.startTask(
				`Finding recent release posts with title: ${ versionSearch }`
			);

			const posts =
				( await searchForPostsByCategory(
					siteId,
					versionSearch,
					'WooCommerce Core',
					authToken
				) ) || [];

			Logger.endTask();

			const prompt = new Select( {
				name: 'Previous post',
				message: 'Choose the previous post to link to:',
				choices: posts.length
					? posts.map( ( p ) => p.title )
					: [ 'No posts found - generate default link' ],
			} );

			const lastReleasePostTitle: string = await prompt.run();
			const lastReleasePost = posts.find(
				( p ) => p.title === lastReleasePostTitle
			);

			if ( ! lastReleasePost ) {
				Logger.warn(
					'Could not find previous release post, make sure to update the link in the post before publishing.'
				);
			}

			if ( ! authToken && ! isOutputOnly ) {
				throw new Error(
					'Error getting auth token, check your env settings are correct.'
				);
			} else {
				const html = await renderTemplate( 'beta-release.ejs', {
					releaseDate,
					betaNumber: prereleaseVersion,
					version: semverVersion,
					previousVersion: semverPreviousVersion,
					prettyVersion: `${ semverVersion.major }.${ semverVersion.minor }.${ semverVersion.patch } Beta ${ prereleaseVersion }`,
					prettyPreviousVersion: `${ semverPreviousVersion.major }.${
						semverPreviousVersion.minor
					}.${ semverPreviousVersion.patch }${
						semverPreviousVersion.prerelease.length
							? ' ' +
							  semverPreviousVersion.prerelease[ 0 ] +
							  ' ' +
							  semverPreviousVersion.prerelease[ 1 ]
							: ''
					}`,
					rcReleaseDate: getFirstTuesdayOfTheMonth(
						finalReleaseDate.getMonth()
					),
					finalReleaseDate,
					lastReleasePostUrl:
						lastReleasePost?.URL ||
						'https://developer.woo.com/category/woocommerce-core-release-notes/',
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
			}
		}
	} );

program.parse( process.argv );
