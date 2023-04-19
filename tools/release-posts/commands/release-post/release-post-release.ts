/**
 * External dependencies
 */
import { scanForChanges } from 'code-analyzer/src/lib/scan-changes';
import semver from 'semver';
import { writeFile } from 'fs/promises';
import { tmpdir } from 'os';
import { join } from 'path';
import { cloneRepo, getCommitHash } from 'cli-core/src/git';
import { Logger } from 'cli-core/src/logger';
import { Command } from '@commander-js/extra-typings';
import dotenv from 'dotenv';

/**
 * Internal dependencies
 */
import { renderTemplate } from '../../lib/render-template';
import {
	createWpComDraftPost,
	fetchWpComPost,
	editWpComPostContent,
} from '../../lib/draft-post';
import { getWordpressComAuthToken } from '../../lib/oauth-helper';
import { getEnvVar } from '../../lib/environment';
import { generateContributors } from '../../lib/contributors';
import { editPostHTML } from '../../lib/edit-post';

const DEVELOPER_WOOCOMMERCE_SITE_ID = '96396764';

const SOURCE_REPO = 'https://github.com/woocommerce/woocommerce.git';

const VERSION_VALIDATION_REGEX =
	/^([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$/;

dotenv.config();

// Define the release post command
const program = new Command()
	.command( 'release' )
	.description( 'CLI to automate generation of a release post.' )
	.argument(
		'<currentVersion>',
		'The current version in x.y.z or x.y.z-stage.n format. Ex: 7.1.0, 7.1.0-rc.1'
	)
	.argument(
		'<previousVersion>',
		'The previous version in x.y.z format. Ex: 7.0.0'
	)
	.option( '--outputOnly', 'Only output the post, do not publish it' )
	.option( '--editPostId <postId>', 'Updates an existing post' )
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
		const siteId = options.siteId || DEVELOPER_WOOCOMMERCE_SITE_ID;
		const tags = ( options.tags &&
			options.tags.split( ',' ).map( ( tag ) => tag.trim() ) ) || [
			'WooCommerce Core',
			'Releases',
		];
		const isOutputOnly = !! options.outputOnly;

		if ( ! VERSION_VALIDATION_REGEX.test( currentVersion ) ) {
			throw new Error(
				`Invalid current version: ${ currentVersion }. Provide current version in x.y.z or x.y.z-stage.n format.`
			);
		}

		if ( ! VERSION_VALIDATION_REGEX.test( previousVersion ) ) {
			throw new Error(
				`Invalid previous version: ${ previousVersion }. Provide previous version in x.y.z format.`
			);
		}

		const clientId = getEnvVar( 'WPCOM_OAUTH_CLIENT_ID', true );
		const clientSecret = getEnvVar( 'WPCOM_OAUTH_CLIENT_SECRET', true );
		const redirectUri =
			getEnvVar( 'WPCOM_OAUTH_REDIRECT_URI' ) ||
			'http://localhost:3000/oauth';
		const authToken =
			isOutputOnly ||
			( await getWordpressComAuthToken(
				clientId,
				clientSecret,
				siteId,
				redirectUri,
				'posts'
			) );

		if ( ! authToken ) {
			throw new Error(
				'Error getting auth token, check your env settings are correct.'
			);
		}

		Logger.startTask( `Making temporary clone of ${ SOURCE_REPO }...` );
		const currentParsed = semver.parse( currentVersion );
		const previousParsed = semver.parse( previousVersion );
		const tmpRepoPath = await cloneRepo( SOURCE_REPO );
		Logger.endTask();
		let currentBranch;
		let previousBranch;
		let currentVersionRef;
		let previousVersionRef;

		try {
			if ( ! currentParsed ) {
				throw new Error( 'Unable to parse current version' );
			}
			currentBranch = `release/${ currentParsed.major }.${ currentParsed.minor }`;
			currentVersionRef = await getCommitHash(
				tmpRepoPath,
				`remotes/origin/${ currentBranch }`
			);
		} catch ( error: unknown ) {
			Logger.notice(
				`Unable to find '${ currentBranch }', using 'trunk'.`
			);
			currentBranch = 'trunk';
			currentVersionRef = await getCommitHash(
				tmpRepoPath,
				'remotes/origin/trunk'
			);
		}

		try {
			if ( ! previousParsed ) {
				throw new Error( 'Unable to parse previous version' );
			}
			previousBranch = `release/${ previousParsed.major }.${ previousParsed.minor }`;
			previousVersionRef = await getCommitHash(
				tmpRepoPath,
				`remotes/origin/${ previousBranch }`
			);
		} catch ( error: unknown ) {
			throw new Error(
				`Unable to find '${ previousBranch }'. Branch for previous version must exist.`
			);
		}

		Logger.notice(
			`Using ${ currentBranch }(${ currentVersionRef }) for current and ${ previousBranch }(${ previousVersionRef }) for previous.`
		);

		let postContent;

		if ( typeof options.editPostId !== 'undefined' ) {
			try {
				const prevPost = await fetchWpComPost(
					siteId,
					options.editPostId,
					authToken
				);
				postContent = prevPost.content;
			} catch ( error: unknown ) {
				throw new Error(
					`Unable to fetch existing post with ID: ${ options.editPostId }`
				);
			}
		}

		const changes = await scanForChanges(
			currentVersionRef,
			`${ previousParsed.major }.${ previousParsed.minor }.${ previousParsed.patch }`,
			false,
			SOURCE_REPO,
			previousVersionRef,
			'cli',
			tmpRepoPath
		);

		const schemaChanges = changes.schema.filter( ( s ) => ! s.areEqual );

		Logger.startTask( 'Finding contributors' );
		const title = `WooCommerce ${ currentVersion } Released`;

		const contributors = await generateContributors(
			currentVersion,
			previousVersion.toString()
		);

		const postVariables = {
			contributors,
			title,
			changes: {
				...changes,
				schema: schemaChanges,
			},
			displayVersion: currentVersion,
		};

		const html =
			typeof options.editPostId !== 'undefined'
				? editPostHTML( postContent, {
						hooks: await renderTemplate(
							'hooks.ejs',
							postVariables
						),
						database: await renderTemplate(
							'database.ejs',
							postVariables
						),
						templates: await renderTemplate(
							'templates.ejs',
							postVariables
						),
						contributors: await renderTemplate(
							'contributors.ejs',
							postVariables
						),
				  } )
				: await renderTemplate( 'release.ejs', postVariables );

		Logger.endTask();

		if ( isOutputOnly ) {
			const tmpFile = join(
				tmpdir(),
				`release-${ currentVersion }.html`
			);

			await writeFile( tmpFile, html );

			Logger.notice( `Output written to ${ tmpFile }` );
		} else {
			Logger.startTask( 'Publishing draft release post' );

			try {
				const { URL } =
					typeof options.editPostId !== 'undefined'
						? await editWpComPostContent(
								siteId,
								options.editPostId,
								html,
								authToken
						  )
						: await createWpComDraftPost(
								siteId,
								title,
								html,
								tags,
								authToken
						  );

				Logger.notice( `Published draft release post at ${ URL }` );
				Logger.endTask();
			} catch ( error: unknown ) {
				if ( error instanceof Error ) {
					Logger.error( error.message );
				}
			}
		}
	} );

program.parse( process.argv );
