/**
 * External dependencies
 */
import Analyzer from 'code-analyzer/src/commands/analyzer';

/**
 * Internal dependencies
 */
import { program } from '../program';

const VERSION_VALIDATION_REGEX =
	/^([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$/;

// Define the release post command
program
	.command( 'release' )
	.description( 'CLI to automate generation of a release post.' )
	.argument(
		'<previousVersion>',
		'The previous version of the plugin, please use the tag version from Github.'
	)
	.argument(
		'<currentVersion>',
		'The current version of the plugin, please use the tag version from Github.'
	)
	.option( '--outputOnly', 'Only output the post, do not publish it' )
	.action( async ( previousVersion, currentVersion, options ) => {
		console.log( 'LETTTTSSS A GOOOOO' );
		const isOutputOnly = !! options.outputOnly;

		if ( ! VERSION_VALIDATION_REGEX.test( previousVersion ) ) {
			throw new Error( `Invalid previous version: ${ previousVersion }` );
		}

		if ( ! VERSION_VALIDATION_REGEX.test( currentVersion ) ) {
			throw new Error( `Invalid current version: ${ currentVersion }` );
		}

		const output = await Analyzer.run( [
			previousVersion,
			currentVersion,
			'-s',
			'https://github.com/woocommerce/woocommerce.git',
		] );

		console.log( output );
	} );
