/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { Logger } from '@woocommerce/monorepo-utils/src/core/logger';
import { writeFile } from 'fs/promises';
import { tmpdir } from 'os';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { generateContributors } from '../../lib/contributors';
import { renderTemplate } from '../../lib/render-template';

// Define the contributors command
const program = new Command()
	.command( 'contributors' )
	.description( 'CLI to automate generation of a release post.' )
	.argument(
		'<currentVersion>',
		'The version of the plugin to generate a post for, please use the tag version from Github.'
	)
	.argument(
		'<previousVersion>',
		'The previous plugin tag or release branch to compare against.'
	)
	.action( async ( currentVersion, previousVersion ) => {
		Logger.startTask( 'Generating contributors list...' );

		const contributors = await generateContributors(
			currentVersion,
			previousVersion.toString()
		);

		Logger.endTask();

		const html = await renderTemplate( 'contributors.ejs', {
			contributors,
			displayVersion: currentVersion.replace( /^release\//, '' ),
		} );

		const tmpFile = join(
			tmpdir(),
			`contributors-${ currentVersion.replace( '/', '-' ) }.html`
		);

		await writeFile( tmpFile, html );

		Logger.notice( `Contributors HTML generated at ${ tmpFile }` );
	} );

program.parse( process.argv );
