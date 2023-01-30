/**
 * External dependencies
 */
import { exec } from 'child_process';
import { promisify } from 'util';

/**
 * Internal dependencies
 */
import { SchemaDiff } from './git';

export const execAsync = promisify( exec );

/**
 * Format version string for regex.
 *
 * @param {string} rawVersion Raw version number.
 * @return {string} version regex.
 */
export const getVersionRegex = ( rawVersion: string ): string => {
	const version = rawVersion.replace( /\./g, '\\.' );

	if ( rawVersion.endsWith( '.0' ) ) {
		return version + '|' + version.slice( 0, -3 ) + '\\n';
	}

	return version;
};

/**
 * Get hook name.
 *
 * @param {string} name Raw hook name.
 * @return {string} Formatted hook name.
 */
export const getHookName = ( name: string ): string => {
	if ( name.indexOf( ',' ) > -1 ) {
		name = name.substring( 0, name.indexOf( ',' ) );
	}

	return name.replace( /(\'|\")/g, '' ).trim();
};

/**
 * Determine if schema diff object contains schemas that are equal.
 *
 * @param {Array<SchemaDiff>} schemaDiffs
 * @return {boolean|void} If the schema diff describes schemas that are equal.
 */
export const areSchemasEqual = ( schemaDiffs: SchemaDiff[] ): boolean => {
	return ! schemaDiffs.some( ( s ) => ! s.areEqual );
};

/**
 * Extract hook description from a raw diff.
 *
 * @param {string} diff raw diff.
 * @param {string} name hook name.
 * @return {string|false} hook description or false if none exists.
 */
export const getHookDescription = (
	diff: string,
	name: string
): string | false => {
	const diffWithoutDeletions = diff.replace( /-.*\n/g, '' );

	const diffWithHook = diffWithoutDeletions
		.split( '/**' ) // Separate by the beginning of a comment.
		.find( ( d ) => d.includes( name ) ); // Use just the one associated with our hook.

	if ( ! diffWithHook ) {
		return false;
	}

	// Extract hook description.
	const description = diffWithHook.match( /([\s\S]*?) @since/ );

	if ( ! description ) {
		return false;
	}

	return description[ 1 ]
		.replace( / \* /g, '' )
		.replace( /\*/g, '' )
		.replace( /\+/g, '' )
		.replace( /-/g, '' )
		.replace( /\t/g, '' )
		.replace( /\n/g, '' )
		.trim();
};

/**
 * Determine hook change type: New or Updated.
 *
 * @param {string} diff raw diff.
 * @return {'Updated' | 'New'} change type.
 */
export const getHookChangeType = ( diff: string ) => {
	const sincesRegex = /@since/g;
	const sinces = diff.match( sincesRegex ) || [];
	// If there is more than one 'since' in the diff, it means that line was updated meaning the hook already exists.
	return sinces.length > 1 ? 'updated' : 'new';
};
