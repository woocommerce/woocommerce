/**
 * Internal dependencies
 */
import {
	getFilename,
	getHookChangeType,
	getHookDescription,
	getHookName,
	getPatches,
	getVersionRegex,
} from '../utils';

export type HookChangeDescription = {
	filePath: string;
	name: string;
	description: string;
	hookType: string;
	changeType: 'new' | 'updated';
	version: string;
};

export const scanForHookChanges = ( content: string, version: string ) => {
	const changes: Map< string, HookChangeDescription > = new Map();

	if ( ! content.match( /diff --git a\/(.+).php/g ) ) {
		return changes;
	}

	const matchPatches = /^a\/(.+).php/g;
	const patches = getPatches( content, matchPatches );
	const verRegEx = getVersionRegex( version );
	const matchHooks = `\(.*?)@since\\s+(${ verRegEx })(.*?)(apply_filters|do_action)\\((\\s+)?(\\'|\\")(.*?)(\\'|\\")`;
	const newRegEx = new RegExp( matchHooks, 'gs' );

	for ( const p in patches ) {
		const patch = patches[ p ];

		// Separate patches into bits beginning with a comment. If a bit does not have an action, disregard.
		const patchWithHook = patch.split( '/**' ).find( ( s ) => {
			return s.includes( 'apply_filters' ) || s.includes( 'do_action' );
		} );
		if ( ! patchWithHook ) {
			continue;
		}
		const results = patchWithHook.match( newRegEx );

		if ( ! results ) {
			continue;
		}

		const lines = patch.split( '\n' );
		const filePath = getFilename( lines[ 0 ] );

		for ( const raw of results ) {
			// Extract hook name and type.
			const hookName = raw.match(
				/(.*)(do_action|apply_filters)\(\s+'(.*)'/
			);

			if ( ! hookName ) {
				continue;
			}

			const name = getHookName( hookName[ 3 ] );
			const description = getHookDescription( raw, name ) || '';

			const hookType =
				hookName[ 2 ] === 'do_action' ? 'action' : 'filter';

			//  TODO - this kind of output formatting belongs in a separate reporter
			// const CLIMessage = `**${ name }** introduced in ${ version }`;
			// const GithubMessage = `\\'${ name }\\' introduced in ${ version }`;
			// const message = output === 'github' ? GithubMessage : CLIMessage;

			const changeType = getHookChangeType( raw );

			if ( ! hookName[ 2 ].startsWith( '-' ) ) {
				changes.set( filePath, {
					filePath,
					name,
					hookType,
					description,
					changeType,
					version,
				} );
			}
		}
	}

	return changes;
};
