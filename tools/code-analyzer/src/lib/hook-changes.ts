/**
 * External dependencies
 */
import {
	getFilename,
	getPatches,
} from '@woocommerce/monorepo-utils/src/core/git';
import fs, { existsSync } from 'node:fs';

/**
 * Internal dependencies
 */
import {
	getHookChangeType,
	getHookDescription,
	getHookName,
	getVersionRegex,
} from '../utils';

export type HookChangeDescription = {
	filePath: string;
	name: string;
	description: string;
	hookType: string;
	changeType: 'new' | 'updated';
	version: string;
	ghLink: string;
};

const getUniqueKey = (
	changes: Map< string, HookChangeDescription >,
	filePath: string
) => {
	let index = 1;
	let uniqueKey = filePath;

	// If the key already exists, append a number (#1, #2, etc.) until we find a unique key.
	while ( changes.has( uniqueKey ) ) {
		index++;
		uniqueKey = `${ filePath }#${ index }`;
	}

	return uniqueKey;
};

export const scanForHookChanges = async (
	content: string,
	version: string,
	tmpRepoPath: string
) => {
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

		// Separate patch into pieces beginning with a comment. If a piece does not have actions/filters discard it.
		const patchesWithHooks = patch.split( '/**' ).filter( ( s ) => {
			return s.includes( 'apply_filters' ) || s.includes( 'do_action' );
		} );

		if ( ! patchesWithHooks.length ) {
			continue;
		}

		const lines = patch.split( '\n' );
		const filePath = getFilename( lines[ 0 ] );

		for ( const hookPatch of patchesWithHooks ) {
			const results = hookPatch.match( newRegEx );

			if ( ! results ) {
				continue;
			}

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

				const changeType = getHookChangeType( raw );

				if ( ! hookName[ 2 ].startsWith( '-' ) ) {
					let ghLink = '';

					try {
						// If path doesn't exist for some reason don't try create a link.
						if ( existsSync( tmpRepoPath + filePath ) ) {
							const data = await fs.promises.readFile(
								tmpRepoPath + filePath,
								'utf-8'
							);

							const reg = new RegExp( name );

							data.split( '\n' ).forEach( ( line, index ) => {
								if ( line.match( reg ) ) {
									const lineNum = index + 1;

									ghLink = `https://github.com/woocommerce/woocommerce/blob/${ version }/${ filePath.replace(
										/(^\/)/,
										''
									) }#L${ lineNum }`;
								}
							} );
						}

						const uniqueKey = getUniqueKey( changes, filePath );

						changes.set( uniqueKey, {
							filePath,
							name,
							hookType,
							description,
							changeType,
							version,
							ghLink,
						} );
					} catch ( error ) {
						if ( error ) {
							// eslint-disable-next-line no-console
							console.error( error );
						}
					}
				}
			}
		}
	}

	return changes;
};
