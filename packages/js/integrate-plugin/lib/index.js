/**
 * External dependencies
 */
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
const { getDefaultValues, getPluginTemplate } = require( './templates' );

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
	.option(
		'-t, --template <name>',
		'project template type name; allowed values: "standard", "es5", the name of an external npm package, or the path to a local directory',
		'standard'
	)
	.option( '--variant <variant>', 'the variant of the template to use' )
	.option( '--wp-env', 'enable integration with `@wordpress/env` package' )
	.option( '--includes-dir <dir>', 'the path to the includes directory with backend logic' )
	.option( '--src-dir <dir>', 'the path to the src directory with client-side logic' )
	.action(
		async (
			{
				wpScripts,
				wpEnv,
				template,
				variant,
				includesDir,
				srcDir,
			}
		) => {
			await checkSystemRequirements( engines );

			try {
				const pluginTemplate = await getPluginTemplate( template );
				const availableVariants = Object.keys(
					pluginTemplate.variants
				);
				if ( variant && ! availableVariants.includes( variant ) ) {
					if ( ! availableVariants.length ) {
						throw new CLIError(
							`"${ variant }" variant was selected. This template does not have any variants!`
						);
					}
					throw new CLIError(
						`"${ variant }" is not a valid variant for this template. Available variants are: ${ availableVariants.join(
							', '
						) }.`
					);
				}

				const optionsValues = Object.fromEntries(
					Object.entries( {
						includesDir,
						srcDir,
						template,
						wpEnv,
						wpScripts,
					} ).filter( ( [ , value ] ) => value !== undefined )
				);

				const pluginData = getPluginData();

				const defaultValues = getDefaultValues(
					pluginTemplate,
					variant
				);
                const answers = {
                    ...defaultValues,
					...pluginData,
                    ...optionsValues,
                };

                await scaffold( pluginTemplate, answers );

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
