/**
 * External dependencies
 */
import { CliUx, Command, Flags } from '@oclif/core';
import { tmpdir } from 'os';
import { join } from 'path';
import { readFileSync } from 'fs';
import { execSync } from 'child_process';

/**
 * Internal dependencies
 */
import { MONOREPO_ROOT } from '../../const';

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

		const patchContent = await this.getChanges(
			flags.source,
			args.compare,
			flags.base
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
	 * Fetch branches from origin.
	 *
	 * @param {string} branch branch/commit hash.
	 * @return {Promise<boolean>} Promise.
	 */
	private async fetchBranch( branch: string ): Promise< boolean > {
		CliUx.ux.action.start( `Fetching ${ branch }` );
		const branches = execSync( 'git branch', {
			encoding: 'utf-8',
		} );

		const branchExistsLocally = branches.includes( branch );

		if ( branchExistsLocally ) {
			CliUx.ux.action.stop();
			return true;
		}

		try {
			// Fetch branch.
			execSync( `git fetch origin ${ branch }` );
			// Create branch.
			execSync( `git branch ${ branch } origin/${ branch }` );
		} catch ( e ) {
			this.error( `Unable to fetch ${ branch }` );
		}

		CliUx.ux.action.stop();
		return true;
	}

	/**
	 * Generate a patch file into the temp directory and return its contents
	 *
	 * @param {string} source  The GitHub repository.
	 * @param {string} compare Branch/commit hash to compare against the base.
	 * @param {string} base    Base branch/commit hash.
	 * @return {Promise<string>} Promise.
	 */
	private async getChanges(
		source: string,
		compare: string,
		base: string
	): Promise< string > {
		const filename = `${ source }-${ base }-${ compare }.patch`.replace(
			/\//g,
			'-'
		);
		const filepath = join( tmpdir(), filename );

		await this.fetchBranch( base );
		await this.fetchBranch( compare );

		CliUx.ux.action.start( 'Generating patch for ' + compare );

		try {
			const diffCommand = `git diff ${ base }...${ compare } > ${ filepath }`;
			execSync( diffCommand );
		} catch ( e ) {
			this.error(
				'Unable to create diff. Check that git origin, base branch, and compare branch all exist.'
			);
		}

		const content = readFileSync( filepath ).toString();

		CliUx.ux.action.stop();
		return content;
	}

	/**
	 * Get patches
	 *
	 * @param {string} content Patch content.
	 * @param {RegExp} regex   Regex to find specific patches.
	 * @return {Promise<string[]>} Promise.
	 */
	private async getPatches(
		content: string,
		regex: RegExp
	): Promise< string[] > {
		const patches = content.split( 'diff --git ' );
		const changes: string[] = [];

		for ( const p in patches ) {
			const patch = patches[ p ];
			const id = patch.match( regex );

			if ( id ) {
				changes.push( patch );
			}
		}

		return changes;
	}

	/**
	 * Get filename from patch
	 *
	 * @param {string} str String to extract filename from.
	 * @return {Promise<string>} Promise.
	 */
	private async getFilename( str: string ): Promise< string > {
		return str.replace( /^a(.*)\s.*/, '$1' );
	}

	/**
	 * Format version string for regex.
	 *
	 * @param {string} rawVersion Raw version number.
	 * @return {Promise<string>} Promise.
	 */
	private async getVersionRegex( rawVersion: string ): Promise< string > {
		const version = rawVersion.replace( /\./g, '\\.' );

		if ( rawVersion.endsWith( '.0' ) ) {
			return version + '|' + version.slice( 0, -3 ) + '\\n';
		}

		return version;
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
		// @todo: Scan for changes to database schema.

		if ( templates.size ) {
			await this.printTemplateResults(
				templates,
				output,
				'TEMPLATE CHANGES'
			);
		} else {
			this.log( 'No template changes found' );
		}

		if ( hooks.size ) {
			await this.printHookResults( hooks, output, 'HOOKS' );
		} else {
			this.log( 'No new hooks found' );
		}
	}

	/**
	 * Print template results
	 *
	 * @param {Map<string, string[]>} data   Raw data.
	 * @param {string}                output Output style.
	 * @param {string}                title  Section title.
	 */
	private async printTemplateResults(
		data: Map< string, string[] >,
		output: string,
		title: string
	): Promise< void > {
		if ( output === 'github' ) {
			let opt = '\\n\\n### Template changes:';
			for ( const [ key, value ] of data ) {
				opt += `\\n* **file:** ${ key }`;
				opt += `\\n  * ${ value[ 0 ].toUpperCase() }: ${ value[ 2 ] }`;
				this.log(
					`::${ value[ 0 ] } file=${ key },line=1,title=${ value[ 1 ] }::${ value[ 2 ] }`
				);
			}

			this.log( `::set-output name=templates::${ opt }` );
		} else {
			this.log( `\n## ${ title }:` );
			for ( const [ key, value ] of data ) {
				this.log( 'FILE: ' + key );
				this.log(
					'---------------------------------------------------'
				);
				this.log(
					` ${ value[ 0 ].toUpperCase() } | ${ value[ 1 ] } | ${
						value[ 2 ]
					}`
				);
				this.log(
					'---------------------------------------------------'
				);
			}
		}
	}

	/**
	 * Print hook results
	 *
	 * @param {Map}    data   Raw data.
	 * @param {string} output Output style.
	 * @param {string} title  Section title.
	 */
	private async printHookResults(
		data: Map< string, Map< string, string[] > >,
		output: string,
		title: string
	): Promise< void > {
		if ( output === 'github' ) {
			let opt = '\\n\\n### New hooks:';
			for ( const [ key, value ] of data ) {
				if ( value.size ) {
					opt += `\\n* **file:** ${ key }`;
					for ( const [ k, v ] of value ) {
						opt += `\\n  * ${ v[ 0 ].toUpperCase() }: ${ v[ 2 ] }`;
						this.log(
							`::${ v[ 0 ] } file=${ key },line=1,title=${ v[ 1 ] } - ${ k }::${ v[ 2 ] }`
						);
					}
				}
			}

			this.log( `::set-output name=wphooks::${ opt }` );
		} else {
			this.log( `\n## ${ title }:` );
			for ( const [ key, value ] of data ) {
				if ( value.size ) {
					this.log( 'FILE: ' + key );
					this.log(
						'---------------------------------------------------'
					);
					for ( const [ k, v ] of value ) {
						this.log( 'HOOK: ' + k );
						this.log(
							'---------------------------------------------------'
						);
						this.log(
							` ${ v[ 0 ].toUpperCase() } | ${ v[ 1 ] } | ${
								v[ 2 ]
							}`
						);
						this.log(
							'---------------------------------------------------'
						);
					}
				}
			}
		}
	}

	/**
	 * Get hook name.
	 *
	 * @param {string} name Raw hook name.
	 * @return {Promise<string>} Promise.
	 */
	private async getHookName( name: string ): Promise< string > {
		if ( name.indexOf( ',' ) > -1 ) {
			name = name.substring( 0, name.indexOf( ',' ) );
		}

		return name.replace( /(\'|\")/g, '' ).trim();
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
		const patches = await this.getPatches( content, matchPatches );
		const matchVersion = `^(\\+.+\\*.+)(@version)\\s+(${ version.replace(
			/\./g,
			'\\.'
		) }).*`;
		const versionRegex = new RegExp( matchVersion, 'g' );

		for ( const p in patches ) {
			const patch = patches[ p ];
			const lines = patch.split( '\n' );
			const filepath = await this.getFilename( lines[ 0 ] );
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
		const patches = await this.getPatches( content, matchPatches );
		const verRegEx = await this.getVersionRegex( version );
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
			const filepath = await this.getFilename( lines[ 0 ] );

			for ( const raw of results ) {
				// Extract hook name and type.
				const hookName = raw.match(
					/(.*)(do_action|apply_filters)\(\s+'(.*)'/
				);

				if ( ! hookName ) {
					continue;
				}

				const name = await this.getHookName( hookName[ 3 ] );
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
