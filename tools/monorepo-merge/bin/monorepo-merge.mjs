#!/usr/bin/env node
/**
 * External dependencies
 */
import { parseArgs } from 'node:util';

/**
 * Internal dependencies
 */
import { closePrompts } from '../src/confirm.mjs';
import { merge } from '../src/merge.mjs';
import { transferIssues } from '../src/transfer-issues.mjs';

const USAGE = `Usage:
  monorepo-merge merge <org/repo> <destination> [--branch <branch>]
    Merges a GitHub repository and its history into <destination> in this monorepo.
    Requires git and git-filter-repo. --branch defaults to "main".

  monorepo-merge transfer-issues <org/repo> [--destination <org/repo>] [--searchFilter <filter>] [--labels <a,b>] [--dry-run]
    Transfers issues into --destination (default "woocommerce/woocommerce").
    --searchFilter defaults to "is:open". --labels are added after the transfer.
    --dry-run runs every lookup and lists what would be transferred, without prompting or changing anything.
    The token comes from GH_TOKEN, GITHUB_TOKEN, or "gh auth token" and needs the repo and project scopes.`;

const COMMANDS = {
	merge: {
		arguments: 2,
		options: { branch: { type: 'string', default: 'main' } },
		run: ( [ source, destination ], options ) =>
			merge( source, destination, options ),
	},
	'transfer-issues': {
		arguments: 1,
		options: {
			destination: { type: 'string', default: 'woocommerce/woocommerce' },
			searchFilter: { type: 'string', default: 'is:open' },
			labels: { type: 'string' },
			'dry-run': { type: 'boolean', default: false },
		},
		run: ( [ source ], options ) =>
			transferIssues( source, {
				...options,
				dryRun: options[ 'dry-run' ],
			} ),
	},
};

const [ name, ...args ] = process.argv.slice( 2 );
const command = Object.hasOwn( COMMANDS, name ) ? COMMANDS[ name ] : undefined;

try {
	if ( ! command ) {
		if ( name && ! [ '--help', '-h', 'help' ].includes( name ) ) {
			throw new Error( `Unknown command "${ name }"\n\n${ USAGE }` );
		}
		console.log( USAGE );
	} else {
		const { values, positionals } = parseArgs( {
			args,
			options: {
				...command.options,
				help: { type: 'boolean', short: 'h' },
			},
			allowPositionals: true,
		} );

		if ( values.help ) {
			console.log( USAGE );
		} else if ( positionals.length !== command.arguments ) {
			throw new Error(
				`"${ name }" expects ${ command.arguments } argument(s)\n\n${ USAGE }`
			);
		} else {
			await command.run( positionals, values );
		}
	}
} catch ( error ) {
	console.error( `Error: ${ error.message }` );
	process.exitCode = error.exitCode ?? 1;
} finally {
	closePrompts();
}
