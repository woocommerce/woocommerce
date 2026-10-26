/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import type { Field } from '@wordpress/dataviews';
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';

/**
 * Internal dependencies
 */
import type { SettingsField } from '../../entity-fields/types';

// The editor's entity field registry is private until the server-side Entity Fields API lands.
const { unlock } = __dangerousOptInToUnstableAPIsOnlyForCoreModules(
	'I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.',
	'@wordpress/routes'
);

interface EntityFieldActions {
	registerEntityField: < Item >(
		kind: string,
		name: string,
		field: Field< Item >
	) => void;
}

interface EntityFieldSelectors {
	getEntityFields: ( kind: string, name: string ) => SettingsField[];
}

/**
 * Add fields to an entity in the editor's field registry.
 */
export function registerEntityFields< Item >(
	kind: string,
	name: string,
	fields: Field< Item >[]
): void {
	const { registerEntityField } = unlock< EntityFieldActions >(
		dispatch( editorStore )
	);
	fields.forEach( ( field ) => {
		registerEntityField( kind, name, field );
	} );
}

/**
 * Read an entity's fields from the editor store's selectors.
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
