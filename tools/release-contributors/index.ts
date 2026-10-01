/**
 * External dependencies
 */
import { Command } from '@commander-js/extra-typings';
import { Logger } from '@woocommerce/monorepo-utils/src/core/logger';
import dotenv from 'dotenv';
import { writeFile } from 'fs/promises';
import { tmpdir } from 'os';
import { join } from 'path';

/**
 * Internal dependencies
 */
import { generateContributors } from './lib/contributors';
import { renderTemplate } from './lib/render-template';

dotenv.config();

const program = new Command()
	.name( 'release-contributors' )
	.description(
		'Generate an HTML contributors list for a WooCommerce release.'
	)
	.argument( '<currentRef>', 'The Git ref for the current release.' )
	.argument( '<previousRef>', 'The Git ref for the previous release.' )
	.action( async ( currentRef, previousRef ) => {
		Logger.startTask( 'Generating contributors list...' );

		const contributors = await generateContributors(
			currentRef,
			previousRef
		);

		Logger.endTask();

		const html = await renderTemplate( 'contributors.ejs', {
			contributors,
		} );

		const tmpFile = join(
			tmpdir(),
			`contributors-${ currentRef.replace( '/', '-' ) }.html`
		);

		await writeFile( tmpFile, html );

		Logger.notice( `Contributors HTML generated at ${ tmpFile }` );
	} );

program.parse( process.argv );
