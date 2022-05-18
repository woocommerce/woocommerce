/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';
import { join } from 'path';
import { readFileSync } from 'fs';

/**
 * Internal dependencies
 */
import { MONOREPO_ROOT } from '../../const';
import { printTemplateResults, printHookResults } from '../../print';
import {
	getVersionRegex,
	getFilename,
	getPatches,
	getHookName,
} from '../../utils';
import { generatePatch } from '../../git';

/**
 * Analyzer class
 */
export default class Analyzer extends Command {
	/**
	 * CLI description
	 */
	static description = 'Analyze code changes in WooCommerce Monorepo.';

	/**
	 * CLI arguments
	 */
	static args = [
		{
			name: 'compare',
			description:
				'GitHub branch or commit hash to compare against the base branch/commit.',
			required: true,
		},
	];

	/**
	 * CLI flags.
	 */
	static flags = {
		base: Flags.string( {
			char: 'b',
			description: 'GitHub base branch or commit hash.',
			default: 'trunk',
		} ),
		output: Flags.string( {
			char: 'o',
			description: 'Output styling.',
			options: [ 'console', 'github' ],
			default: 'console',
		} ),
		source: Flags.string( {
			char: 's',
			description: 'GitHub organization/repository.',
			default: 'woocommerce/woocommerce',
		} ),
		plugin: Flags.string( {
			char: 'p',
			description: 'Plugin to check for',
			options: [ 'core', 'admin', 'beta' ],
			default: 'core',
		} ),
	};

	/**
	 * This method is called to execute the command
	 */
	async run(): Promise< void > {
		const { args, flags } = await this.parse( Analyzer );

		await this.validateArgs( flags.source );

		const patchContent = await generatePatch(
			flags.source,
			args.compare,
			flags.base,
			( e: string ): void => this.error( e )
		);

		const pluginData = await this.getPluginData( flags.plugin );
		this.log( `${ pluginData[ 1 ] } Version: ${ pluginData[ 0 ] }` );

		await this.scanChanges( patchContent, pluginData[ 0 ], flags.output );
	}

	/**
	 * Validates all of the arguments to make sure
	 *
	 * @param {string} source The GitHub repository we are merging.
	 */
	private async validateArgs( source: string ): Promise< void > {
		// We only support pulling from GitHub so the format needs to match that.
		if ( ! source.match( /^[a-z0-9\-]+\/[a-z0-9\-]+$/ ) ) {
			this.error(
				'The "source" argument must be in "organization/repository" format'
			);
		}
	}

	/**
	 * Get plugin data
	 *
	 * @param {string} plugin Plugin slug.
	 * @return {Promise<string[]>} Promise.
	 */
	private async getPluginData( plugin: string ): Promise< string[] > {
		/**
		 * List of plugins from our monorepo.
		 */
		const plugins = <any>{
			core: {
				name: 'WooCommerce',
				mainFile: join(
					MONOREPO_ROOT,
					'plugins',
					'woocommerce',
					'woocommerce.php'
				),
			},
			admin: {
				name: 'WooCommerce Admin',
				mainFile: join(
					MONOREPO_ROOT,
					'plugins',
					'woocommerce-admin',
					'woocommerce-admin.php'
				),
			},
			beta: {
				name: 'WooCommerce Beta Tester',
				mainFile: join(
					MONOREPO_ROOT,
					'plugins',
					'woocommerce-beta-tester',
					'woocommerce-beta-tester.php'
				),
			},
		};

		const pluginData = plugins[ plugin ];

		CliUx.ux.action.start( `Getting ${ pluginData.name } version` );

		const content = readFileSync( pluginData.mainFile ).toString();
		const rawVer = content.match( /^\s+\*\s+Version:\s+(.*)/m );

		if ( ! rawVer ) {
			this.error( 'Failed to find plugin version!' );
		}
		const version = rawVer[ 1 ].replace( /\-.*/, '' );

		CliUx.ux.action.stop();

		return [ version, pluginData.name, pluginData.mainFile ];
	}

	/**
	 * Scan patches for changes in templates, hooks and database schema
	 *
	 * @param {string} content Patch content.
	 * @param {string} version Current product version.
	 * @param {string} output  Output style.
	 */
	private async scanChanges(
		content: string,
		version: string,
		output: string
	): Promise< void > {
		const templates = await this.scanTemplates( content, version );
		const hooks = await this.scanHooks( content, version, output );

		if ( templates.size ) {
			await printTemplateResults(
				templates,
				output,
				'TEMPLATE CHANGES',
				( s: string ): void => this.log( s )
			);
		} else {
			this.log( 'No template changes found' );
		}

		if ( hooks.size ) {
			await printHookResults(
				hooks,
				output,
				'HOOKS',
				( s: string ): void => this.log( s )
			);
		} else {
			this.log( 'No new hooks found' );
		}
	}

	/**
	 * Scan patches for changes in templates
	 *
	 * @param {string} content Patch content.
	 * @param {string} version Current product version.
	 * @return {Promise<Map<string, string[]>>} Promise.
	 */
	private async scanTemplates(
		content: string,
		version: string
	): Promise< Map< string, string[] > > {
		CliUx.ux.action.start( 'Scanning template changes' );

		const report: Map< string, string[] > = new Map< string, string[] >();

		if ( ! content.match( /diff --git a\/(.+)\/templates\/(.+)/g ) ) {
			CliUx.ux.action.stop();
			return report;
		}

		const matchPatches = /^a\/(.+)\/templates\/(.+)/g;
		const title = 'Template change detected';
		const patches = getPatches( content, matchPatches );
		const matchVersion = `^(\\+.+\\*.+)(@version)\\s+(${ version.replace(
			/\./g,
			'\\.'
		) }).*`;
		const versionRegex = new RegExp( matchVersion, 'g' );

		for ( const p in patches ) {
			const patch = patches[ p ];
			const lines = patch.split( '\n' );
			const filepath = getFilename( lines[ 0 ] );
			let code = 'warning';
			let message = 'This template may require a version bump!';

			for ( const l in lines ) {
				const line = lines[ l ];

				if ( line.match( versionRegex ) ) {
					code = 'notice';
					message = 'Version bump found';
				}
			}

			if ( code === 'notice' && report.get( filepath ) ) {
				report.set( filepath, [ code, title, message ] );
			} else if ( ! report.get( filepath ) ) {
				report.set( filepath, [ code, title, message ] );
			}
		}

		CliUx.ux.action.stop();
		return report;
	}

	/**
	 * Scan patches for hooks
	 *
	 * @param {string} content Patch content.
	 * @param {string} version Current product version.
	 * @param {string} output  Output style.
	 * @return {Promise<Map<string, Map<string, string[]>>>} Promise.
	 */
	private async scanHooks(
		content: string,
		version: string,
		output: string
	): Promise< Map< string, Map< string, string[] > > > {
		CliUx.ux.action.start( 'Scanning for new hooks' );

		const report: Map< string, Map< string, string[] > > = new Map<
			string,
			Map< string, string[] >
		>();

		if ( ! content.match( /diff --git a\/(.+).php/g ) ) {
			CliUx.ux.action.stop();
			return report;
		}

		const matchPatches = /^a\/(.+).php/g;
		const patches = getPatches( content, matchPatches );
		const verRegEx = getVersionRegex( version );
		const matchHooks = `@since\\s+(${ verRegEx })(.*?)(apply_filters|do_action)\\((\\s+)?(\\'|\\")(.*?)(\\'|\\")`;
		const newRegEx = new RegExp( matchHooks, 'gs' );

		for ( const p in patches ) {
			const patch = patches[ p ];
			const results = patch.match( newRegEx );
			const hooksList: Map< string, string[] > = new Map<
				string,
				string[]
			>();

			if ( ! results ) {
				continue;
			}

			const lines = patch.split( '\n' );
			const filepath = getFilename( lines[ 0 ] );

			for ( const raw of results ) {
				// Extract hook name and type.
				const hookName = raw.match(
					/(.*)(do_action|apply_filters)\(\s+'(.*)'/
				);

				if ( ! hookName ) {
					continue;
				}

				const name = getHookName( hookName[ 3 ] );
				const kind =
					hookName[ 2 ] === 'do_action' ? 'action' : 'filter';
				const CLIMessage = `\'${ name }\' introduced in ${ version }`;
				const GithubMessage = `\\'${ name }\\' introduced in ${ version }`;
				const message =
					output === 'github' ? GithubMessage : CLIMessage;
				const title = `New ${ kind } found`;

				if ( ! hookName[ 2 ].startsWith( '-' ) ) {
					hooksList.set( name, [ 'NOTICE', title, message ] );
				}
			}

			report.set( filepath, hooksList );
		}

		CliUx.ux.action.stop();
		return report;
	}
}
