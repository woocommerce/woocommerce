#!/usr/bin/env node

/**
 * External dependencies
 */
import { Command } from 'commander';
import CLIError from '@wordpress/create-block/lib/cli-error';

/**
 * Internal dependencies
 */
import * as log from './log';
import { getPluginData } from './get-plugin-data';
import { getPluginConfig } from './get-plugin-config';
import { OptionValues } from './types';
import { scaffold } from './scaffold';
import { getPluginTemplate, getDefaultValues } from './templates';

//add the following line
const program = new Command();

const commandName = `woo-integrate-plugin`;
program
	.name( commandName )
	.description(
		'Integrates a plugin with WooCommerce build scripts and dependencies.\n\n' +
			'The provided build scripts provide an easy way to build in a modern ' +
			'JS environment and automatically assist in building block assets. '
	)
	.version( '0.1.0' )
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
	.option(
		'--includes-dir <dir>',
		'the path to the includes directory with backend logic'
	)
	.option(
		'--src-dir <dir>',
		'the path to the src directory with client-side logic'
	)
	.option( '--namespace <value>', 'internal namespace for the plugin' )
	.action(
		async ( {
			wpScripts,
			wpEnv,
			template,
			variant,
			includesDir,
			srcDir,
			namespace,
		}: OptionValues ) => {
			try {
				const pluginTemplate = await getPluginTemplate( template );
				if ( ! pluginTemplate ) {
					return;
				}
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
				const optionsValues: Pick<
					OptionValues,
					'template' | 'wpScripts' | 'wpEnv'
				> &
					Partial< OptionValues > = {
					template,
					wpScripts,
					wpEnv,
					...Object.fromEntries(
						Object.entries( {
							includesDir,
							namespace,
							srcDir,
						} ).filter( ( [ , value ] ) => value !== undefined )
					),
				};

				const pluginData = getPluginData();
				const pluginConfig = getPluginConfig();
				const defaultValues = getDefaultValues(
					pluginTemplate,
					variant
				);

				const answers = {
					...defaultValues,
					...pluginData,
					...pluginConfig,
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
		log.info( `  $ ${ commandName } todo-list` );
		log.info(
			`  $ ${ commandName } todo-list --template es5 --title "TODO List"`
		);
	} )
	.parse( process.argv );
