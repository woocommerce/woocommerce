/**
 * External dependencies
 */
import { scanForChanges } from 'code-analyzer/src/lib/scan-changes';
import semver from 'semver';
import { writeFile } from 'fs/promises';
import { tmpdir } from 'os';
import { join } from 'path';
import { Logger } from 'cli-core/src/logger';
import { Command } from '@commander-js/extra-typings';
import dotenv from 'dotenv';

/**
 * Internal dependencies
 */
import { renderTemplate } from '../../lib/render-template';
import { createWpComDraftPost } from '../../lib/draft-post';
import { generateContributors } from '../../lib/contributors';

const DEVELOPER_WOOCOMMERCE_SITE_ID = '96396764';

const VERSION_VALIDATION_REGEX =
	/^([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$/;

dotenv.config();

// Define the release post command
const program = new Command()
	.command( 'release' )
	.description( 'CLI to automate generation of a release post.' )
	.argument(
		'<currentVersion>',
		'The version of the plugin to generate a post for, please use the tag version from Github.'
	)
	.option( '--outputOnly', 'Only output the post, do not publish it' )
	.option(
		'--previousVersion <previousVersion>',
		'If you would like to compare against a version other than last minor you can provide a tag version from Github.'
	)
	.option(
		'--tags <tags>',
		'Comma separated list of tags to add to the post.',
		'Releases,WooCommerce Core'
	)
	.action( async ( currentVersion, options ) => {
		const tags = options.tags.split( ',' ).map( ( tag ) => tag.trim() );

		const previousVersion = options.previousVersion
			? semver.parse( options.previousVersion )
			: semver.parse( currentVersion );

		if ( ! options.previousVersion && previousVersion ) {
			// e.g 6.8.0 -> 6.7.0
			previousVersion.major =
				previousVersion.minor === 0
					? previousVersion.major - 1
					: previousVersion.major;

			previousVersion.minor =
				previousVersion.minor === 0 ? 9 : previousVersion.minor - 1;

			previousVersion.format();
		}

		if ( previousVersion && previousVersion.major ) {
			const isOutputOnly = !! options.outputOnly;

			if ( ! VERSION_VALIDATION_REGEX.test( previousVersion.raw ) ) {
				throw new Error(
					`Invalid previous version: ${ previousVersion.raw }`
				);
			}
			if ( ! VERSION_VALIDATION_REGEX.test( currentVersion ) ) {
				throw new Error(
					`Invalid current version: ${ currentVersion }`
				);
			}

			const changes = await scanForChanges(
				currentVersion,
				currentVersion,
				false,
				'https://github.com/woocommerce/woocommerce.git',
				previousVersion.toString(),
				'cli'
			);

			const schemaChanges = changes.schema.filter(
				( s ) => ! s.areEqual
			);

			Logger.startTask( 'Finding contributors' );
			const title = `WooCommerce ${ currentVersion } Released`;

			const contributors = await generateContributors(
				currentVersion,
				previousVersion.toString()
			);

			const html = await renderTemplate( 'release.ejs', {
				contributors,
				title,
				changes: {
					...changes,
					schema: schemaChanges,
				},
				displayVersion: currentVersion,
			} );

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
					const { URL } = await createWpComDraftPost(
						DEVELOPER_WOOCOMMERCE_SITE_ID,
						title,
						html,
						tags
					);

					Logger.notice( `Published draft release post at ${ URL }` );
					Logger.endTask();
				} catch ( error: unknown ) {
					if ( error instanceof Error ) {
						Logger.error( error.message );
					}
				}
			}
		} else {
			throw new Error(
				`Could not find previous version for ${ currentVersion }`
			);
		}
	} );

program.parse( process.argv );
