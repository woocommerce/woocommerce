/**
 * External dependencies
 */
import Analyzer from 'code-analyzer/src/commands/analyzer';
import semver from 'semver';
import { promises } from 'fs';

/**
 * Internal dependencies
 */
import { program } from '../program';
import { renderTemplate } from '../lib/render-template';
import { writeFile } from 'fs/promises';
import { processChanges } from '../lib/process-changes';

const VERSION_VALIDATION_REGEX =
	/^([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$/;

// Define the release post command
program
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
	.action( async ( currentVersion, options ) => {
		const previousVersion = options.previousVersion
			? semver.parse( options.previousVersion )
			: semver.parse( currentVersion );

		if ( ! options.previousVersion && previousVersion ) {
			previousVersion.minor -= 1;
			previousVersion.format();
		}

		if ( previousVersion && previousVersion.major ) {
			// e.g 6.8.0 -> 6.7.0
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

			// generates a `changes.json` file in the current directory.
			await Analyzer.run( [
				currentVersion,
				currentVersion,
				'-s',
				'https://github.com/woocommerce/woocommerce.git',
				'-b',
				'trunk',
			] );

			const changes = JSON.parse(
				await promises.readFile(
					process.cwd() + '/changes.json',
					'utf8'
				)
			);

			const changeset = processChanges( changes );

			const htmlTemplate = await renderTemplate( 'release.ejs.html', {
				changes: changeset,
				displayVersion: currentVersion,
			} );

			await writeFile( 'changes.html', htmlTemplate );
		} else {
			throw new Error(
				`Could not find previous version for ${ currentVersion }`
			);
		}
	} );
