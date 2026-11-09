/**
 * External dependencies
 */
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';

/**
 * Internal dependencies
 */
import type { SettingsField } from '../../packages/entity-fields/src/types';

// The editor's field registry is private. This logic will be replaced once the Field Collection API is available.
const { unlock } = __dangerousOptInToUnstableAPIsOnlyForCoreModules(
	'I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.',
	'@wordpress/routes'
);

interface EntityFieldSelectors {
	getEntityFields: ( kind: string, name: string ) => SettingsField[];
	hasFinishedResolution: ( selector: string, args: unknown[] ) => boolean;
	getResolutionError: ( selector: string, args: unknown[] ) => unknown;
}

/**
 * Read an entity's fields from the editor store's selectors. The editor loads them from the
 * fields registered on the server, with the JavaScript parts of their script modules.
 */
export function getEntityFields(
	editorSelectors: unknown,
	kind: string,
	name: string
): SettingsField[] {
	return unlock< EntityFieldSelectors >( editorSelectors ).getEntityFields(
		kind,
		name
	);
}

/**
 * Whether the editor has finished loading an entity's fields, and the error if loading failed.
 */
export function getEntityFieldsStatus(
	editorSelectors: unknown,
	kind: string,
	name: string
): { hasLoaded: boolean; error: unknown } {
	const selectors = editorSelectors as EntityFieldSelectors;
	return {
		hasLoaded: selectors.hasFinishedResolution( 'getEntityFields', [
			kind,
			name,
		] ),
		error: selectors.getResolutionError( 'getEntityFields', [
			kind,
			name,
		] ),
	};
}
