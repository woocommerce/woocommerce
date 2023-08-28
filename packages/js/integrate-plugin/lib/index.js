/**
 * External dependencies
 */
const inquirer = require( 'inquirer' );
const program = require( 'commander' );

/**
 * Internal dependencies
 */
const checkSystemRequirements = require( '../node_modules/@wordpress/create-block/lib/check-system-requirements' );
const CLIError = require( '../node_modules/@wordpress/create-block/lib/cli-error' );
const log = require( '../node_modules/@wordpress/create-block/lib/log' );
const { engines, version } = require( '../package.json' );
const scaffold = require( './scaffold' );
const getPluginData = require( './get-plugin-data' );

const DEFAULT_VALUES = {};

const commandName = `woo-integrate-plugin`;
program
	.name( commandName )
	.description(
		'Integrates a plugin with WooCommerce build scripts and dependencies.\n\n' +
			'The provided build scripts provide an easy way to build in a modern ' +
			'JS environment and automatically assist in building block assets. '
	)
	.version( version )
	.option(
		'--wp-scripts',
		'enable integration with `@wordpress/scripts` package'
	)
	.option(
		'--no-wp-scripts',
		'disable integration with `@wordpress/scripts` package'
	)
	.option( '--wp-env', 'enable integration with `@wordpress/env` package' )
	.action(
		async (
			{
				wpScripts,
				wpEnv,
			}
		) => {
			await checkSystemRequirements( engines );
			try {
				const optionsValues = Object.fromEntries(
					Object.entries( {
						wpScripts,
						wpEnv,
					} ).filter( ( [ , value ] ) => value !== undefined )
				);

				const pluginData = getPluginData();

				// @todo Get defaults from create-block.
                const answers = {
                    ...DEFAULT_VALUES,
					...pluginData,
                    ...optionsValues,
                };

                await scaffold( answers );

			} catch ( error ) {
				if ( error instanceof CLIError ) {
					log.error( error.message );
					process.exit( 1 );
				} else {
					throw error;
				}
			}
		}
	)
	.on( '--help', () => {
		log.info( '' );
		log.info( 'Examples:' );
		log.info( `  $ ${ commandName }` );
		log.info( `  $ ${ commandName } --no-wp-scripts` );
	} )
	.parse( process.argv );
